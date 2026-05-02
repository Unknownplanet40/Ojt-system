<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

// functions/dtr_functions.php
// -----------------------------------------------
// Module:    DTR — Daily Time Record
// Primary:   Student (logs) · Supervisor (approves)
// Secondary: Coordinator (monitors + override)
// -----------------------------------------------
require_once __DIR__ . '/../helpers/helpers.php';

const DTR_CUTOFF_HOUR       = 23;
const DTR_BACKDATE_MAX_DAYS = 3;

function computeHoursRendered(string $timeIn, string $timeOut, int $lunchMinutes): float
{
    $in       = strtotime("1970-01-01 {$timeIn}");
    $out      = strtotime("1970-01-01 {$timeOut}");
    $diffMins = (($out - $in) / 60) - $lunchMinutes;
    return max(0, round($diffMins / 60, 2));
}

function validateEntryDate(string $entryDate): array
{
    $today    = date('Y-m-d');
    $entryTs  = strtotime($entryDate);
    $todayTs  = strtotime($today);
    $cutoffTs = strtotime($today . ' ' . DTR_CUTOFF_HOUR . ':59:59');
    $now      = time();

    if (!$entryTs) {
        return ['valid' => false, 'error' => 'Invalid entry date.'];
    }

    if ($entryTs > $todayTs) {
        return ['valid' => false, 'error' => 'Future dates are not allowed.'];
    }

    $daysDiff = (int) (($todayTs - $entryTs) / 86400);
    if ($daysDiff > DTR_BACKDATE_MAX_DAYS) {
        return ['valid' => false, 'error' => 'Entries can only be backdated up to ' . DTR_BACKDATE_MAX_DAYS . ' days.'];
    }

    if ($entryDate === $today && $now > $cutoffTs) {
        return [
            'valid'       => false,
            'error'       => "Today's DTR cutoff has passed (11:59 PM). Submit as backdated entry.",
            'past_cutoff' => true,
        ];
    }

    return [
        'valid'        => true,
        'is_backdated' => $daysDiff > 0,
        'days_diff'    => $daysDiff,
    ];
}

function canAccessStudentDtr($conn, string $studentUuid, string $actorRole, string $actorProfileUuid): bool
{
    if ($actorRole === 'admin') {
        return true;
    }

    if (empty($actorProfileUuid)) {
        return false;
    }

    if ($actorRole === 'supervisor') {
        $stmt = $conn->prepare("SELECT uuid FROM student_profiles WHERE uuid = ? AND supervisor_uuid = ? LIMIT 1");
        $stmt->bind_param('ss', $studentUuid, $actorProfileUuid);
        $stmt->execute();
        $allowed = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $allowed;
    }

    if ($actorRole === 'coordinator') {
        $stmt = $conn->prepare("SELECT uuid FROM student_profiles WHERE uuid = ? AND coordinator_uuid = ? LIMIT 1");
        $stmt->bind_param('ss', $studentUuid, $actorProfileUuid);
        $stmt->execute();
        $allowed = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $allowed;
    }

    return false;
}

function submitDtrEntry(
    $conn,
    string $studentUuid,
    string $applicationUuid,
    string $batchUuid,
    array  $data,
    string $actorUserUuid
): array {
    $entryDate      = trim($data['entry_date'] ?? '');
    $timeIn         = trim($data['time_in'] ?? '');
    $timeOut        = trim($data['time_out'] ?? '');
    $lunchMinutes   = (int) ($data['lunch_break_minutes'] ?? 60);
    $activities     = trim((string) ($data['activities'] ?? $data['activities_performed'] ?? ''));
    $backdateReason = trim($data['backdate_reason'] ?? '');

    $errors = [];
    if (empty($entryDate)) $errors['entry_date'] = 'Entry date is required.';
    if (empty($timeIn))    $errors['time_in'] = 'Time in is required.';
    if (empty($timeOut))   $errors['time_out'] = 'Time out is required.';

    if (!empty($timeIn) && !empty($timeOut)) {
        $in  = strtotime("1970-01-01 {$timeIn}");
        $out = strtotime("1970-01-01 {$timeOut}");

        if ($out <= $in) {
            $errors['time_out'] = 'Time out must be after time in.';
        }

        $totalMins = ($out - $in) / 60;
        if ($lunchMinutes >= $totalMins) {
            $errors['lunch_break_minutes'] = 'Lunch break cannot be longer than or equal to total work hours.';
        }
    }

    if ($lunchMinutes < 0 || $lunchMinutes > 120) {
        $errors['lunch_break_minutes'] = 'Lunch break must be between 0 and 120 minutes.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $dateCheck = validateEntryDate($entryDate);
    if (!$dateCheck['valid']) {
        return ['success' => false, 'errors' => ['entry_date' => $dateCheck['error']]];
    }

    $isBackdated = $dateCheck['is_backdated'];
    if ($isBackdated && empty($backdateReason)) {
        return ['success' => false, 'errors' => ['backdate_reason' => 'A reason is required for backdated entries.']];
    }

    $stmt = $conn->prepare("SELECT uuid, status FROM dtr_entries WHERE student_uuid = ? AND entry_date = ? LIMIT 1");
    $stmt->bind_param('ss', $studentUuid, $entryDate);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $statusLabel = ucfirst($existing['status']);
        return ['success' => false, 'errors' => ['entry_date' => "A DTR entry for this date already exists ({$statusLabel})."]];
    }

    $hoursRendered = computeHoursRendered($timeIn, $timeOut, $lunchMinutes);
    if ($hoursRendered <= 0) {
        return ['success' => false, 'errors' => ['time_out' => 'Computed hours must be greater than 0.']];
    }

    $uuid = generateUuid();
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("\n            INSERT INTO dtr_entries\n              (uuid, student_uuid, application_uuid, batch_uuid,\n               entry_date, time_in, time_out, lunch_break_minutes,\n               hours_rendered, activities,\n               is_backdated, backdate_reason, status)\n            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')\n        ");
        $stmt->bind_param(
            'ssssssssidss',
            $uuid, $studentUuid, $applicationUuid, $batchUuid,
            $entryDate, $timeIn, $timeOut, $lunchMinutes,
            $hoursRendered, $activities,
            $isBackdated, $backdateReason
        );
        $stmt->execute();
        $stmt->close();

        logDtrAudit($conn, $uuid, $isBackdated ? 'backdated' : 'submitted', $actorUserUuid, 'student', [
            'entry_date'     => $entryDate,
            'hours_rendered' => $hoursRendered,
            'is_backdated'   => $isBackdated,
            'reason'         => $isBackdated ? $backdateReason : null,
        ]);

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => 'Failed to submit DTR entry: ' . $e->getMessage()];
    }

    return ['success' => true, 'uuid' => $uuid, 'hours_rendered' => $hoursRendered, 'is_backdated' => $isBackdated];
}

function editDtrEntry(
    $conn,
    string $dtrUuid,
    string $studentUuid,
    array  $data,
    string $actorUserUuid
): array {
    $stmt = $conn->prepare("SELECT * FROM dtr_entries WHERE uuid = ? AND student_uuid = ? LIMIT 1");
    $stmt->bind_param('ss', $dtrUuid, $studentUuid);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$entry) {
        return ['success' => false, 'error' => 'DTR entry not found.'];
    }

    if ($entry['status'] !== 'pending') {
        return ['success' => false, 'error' => 'Only pending entries can be edited.'];
    }

    $timeIn       = trim($data['time_in'] ?? $entry['time_in']);
    $timeOut      = trim($data['time_out'] ?? $entry['time_out']);
    $lunchMinutes = (int) ($data['lunch_break_minutes'] ?? $entry['lunch_break_minutes']);
    $activities   = trim((string) ($data['activities'] ?? $data['activities_performed'] ?? $entry['activities']));

    $in  = strtotime("1970-01-01 {$timeIn}");
    $out = strtotime("1970-01-01 {$timeOut}");
    if ($out <= $in) {
        return ['success' => false, 'errors' => ['time_out' => 'Time out must be after time in.']];
    }

    $hoursRendered = computeHoursRendered($timeIn, $timeOut, $lunchMinutes);
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("\n            UPDATE dtr_entries\n            SET time_in = ?,\n                time_out = ?,\n                lunch_break_minutes = ?,\n                hours_rendered = ?,\n                activities = ?\n            WHERE uuid = ?\n        ");
        $stmt->bind_param('ssidss', $timeIn, $timeOut, $lunchMinutes, $hoursRendered, $activities, $dtrUuid);
        $stmt->execute();
        $stmt->close();

        logDtrAudit($conn, $dtrUuid, 'edited', $actorUserUuid, 'student', [
            'old_time_in'    => $entry['time_in'],
            'old_time_out'   => $entry['time_out'],
            'new_time_in'    => $timeIn,
            'new_time_out'   => $timeOut,
            'hours_rendered' => $hoursRendered,
        ]);

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => 'Failed to edit DTR entry: ' . $e->getMessage()];
    }

    return ['success' => true, 'hours_rendered' => $hoursRendered];
}

function deleteDtrEntry($conn, string $dtrUuid, string $studentUuid, string $actorUserUuid): array
{
    $stmt = $conn->prepare("SELECT status FROM dtr_entries WHERE uuid = ? AND student_uuid = ? LIMIT 1");
    $stmt->bind_param('ss', $dtrUuid, $studentUuid);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$entry) {
        return ['success' => false, 'error' => 'DTR entry not found.'];
    }

    if ($entry['status'] !== 'pending') {
        return ['success' => false, 'error' => 'Only pending entries can be deleted.'];
    }

    $stmt = $conn->prepare("DELETE FROM dtr_entries WHERE uuid = ?");
    $stmt->bind_param('s', $dtrUuid);
    $stmt->execute();
    $stmt->close();

    logDtrAudit($conn, $dtrUuid, 'deleted', $actorUserUuid, 'student', []);

    return ['success' => true];
}

function approveDtrEntry(
    $conn,
    string $dtrUuid,
    string $actorUuid,
    string $actorRole,
    string $actorProfileUuid = ''
): array {
    $stmt = $conn->prepare("SELECT uuid, status, student_uuid, is_backdated FROM dtr_entries WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $dtrUuid);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$entry) {
        return ['success' => false, 'error' => 'DTR entry not found.'];
    }

    if ($entry['status'] !== 'pending') {
        return ['success' => false, 'error' => 'Only pending entries can be approved.'];
    }

    if (!canAccessStudentDtr($conn, $entry['student_uuid'], $actorRole, $actorProfileUuid)) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    $approvedBy = $actorProfileUuid !== '' ? $actorProfileUuid : $actorUuid;

    $stmt = $conn->prepare("\n        UPDATE dtr_entries\n        SET status           = 'approved',\n            approved_by      = ?,\n            approved_at      = NOW(),\n            approved_by_role = ?\n        WHERE uuid = ?\n    ");
    $stmt->bind_param('sss', $approvedBy, $actorRole, $dtrUuid);
    $stmt->execute();
    $stmt->close();

    logDtrAudit($conn, $dtrUuid, 'approved', $actorUuid, $actorRole, [
        'is_backdated' => (int) $entry['is_backdated'],
    ]);

    return ['success' => true];
}

function bulkApproveDtrEntries(
    $conn,
    string $studentUuid,
    string $actorUuid,
    string $actorRole,
    string $actorProfileUuid = '',
    array  $dtrUuids = []
): array {
    if (!canAccessStudentDtr($conn, $studentUuid, $actorRole, $actorProfileUuid)) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    $approved = 0;
    $skipped  = 0;

    if (!empty($dtrUuids)) {
        foreach ($dtrUuids as $dtrUuid) {
            $stmt = $conn->prepare("\n                SELECT uuid FROM dtr_entries\n                WHERE uuid = ?\n                  AND student_uuid = ?\n                  AND status = 'pending'\n                  AND is_backdated = 0\n                LIMIT 1\n            ");
            $stmt->bind_param('ss', $dtrUuid, $studentUuid);
            $stmt->execute();
            $entry = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$entry) {
                $skipped++;
                continue;
            }

            $result = approveDtrEntry($conn, $dtrUuid, $actorUuid, $actorRole, $actorProfileUuid);
            $result['success'] ? $approved++ : $skipped++;
        }
    } else {
        $stmt = $conn->prepare("\n            SELECT uuid FROM dtr_entries\n            WHERE student_uuid = ?\n              AND status = 'pending'\n              AND is_backdated = 0\n        ");
        $stmt->bind_param('s', $studentUuid);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as $row) {
            $result = approveDtrEntry($conn, $row['uuid'], $actorUuid, $actorRole, $actorProfileUuid);
            $result['success'] ? $approved++ : $skipped++;
        }
    }

    return ['success' => true, 'approved' => $approved, 'skipped' => $skipped];
}

function rejectDtrEntry(
    $conn,
    string $dtrUuid,
    string $actorUuid,
    string $actorRole,
    string $actorProfileUuid,
    string $reason
): array {
    if (empty($reason)) {
        return ['success' => false, 'error' => 'Rejection reason is required.'];
    }

    $stmt = $conn->prepare("SELECT uuid, status, student_uuid FROM dtr_entries WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $dtrUuid);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$entry) {
        return ['success' => false, 'error' => 'DTR entry not found.'];
    }

    if ($entry['status'] !== 'pending') {
        return ['success' => false, 'error' => 'Only pending entries can be rejected.'];
    }

    if (!canAccessStudentDtr($conn, $entry['student_uuid'], $actorRole, $actorProfileUuid)) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    $approvedBy = $actorProfileUuid !== '' ? $actorProfileUuid : $actorUuid;

    $stmt = $conn->prepare("\n        UPDATE dtr_entries\n        SET status           = 'rejected',\n            rejection_reason = ?,\n            approved_by      = ?,\n            approved_at      = NOW(),\n            approved_by_role = ?\n        WHERE uuid = ?\n    ");
    $stmt->bind_param('ssss', $reason, $approvedBy, $actorRole, $dtrUuid);
    $stmt->execute();
    $stmt->close();

    logDtrAudit($conn, $dtrUuid, 'rejected', $actorUuid, $actorRole, ['reason' => $reason]);

    return ['success' => true];
}

function getStudentDtrEntries(
    $conn,
    string $studentUuid,
    string $batchUuid,
    array  $filters = []
): array {
    $safeBatch   = $conn->real_escape_string($batchUuid);
    $safeStudent = $conn->real_escape_string($studentUuid);

    $conditions = [
        "d.student_uuid = '{$safeStudent}'",
        "d.batch_uuid = '{$safeBatch}'",
    ];

    if (!empty($filters['status'])) {
        $s = $conn->real_escape_string($filters['status']);
        $conditions[] = "d.status = '{$s}'";
    }

    if (!empty($filters['month'])) {
        $m = $conn->real_escape_string($filters['month']);
        $conditions[] = "DATE_FORMAT(d.entry_date, '%Y-%m') = '{$m}'";
    }

    if (isset($filters['is_backdated'])) {
        $conditions[] = 'd.is_backdated = ' . (int) $filters['is_backdated'];
    }

    $where  = implode(' AND ', $conditions);
    $result = $conn->query("\n        SELECT\n          d.*,\n          CONCAT(svp.first_name, ' ', svp.last_name) AS approved_by_name,\n          CONCAT(cp.first_name, ' ', cp.last_name) AS coord_approved_by_name\n        FROM dtr_entries d\n        LEFT JOIN supervisor_profiles svp ON d.approved_by = svp.uuid AND d.approved_by_role = 'supervisor'\n        LEFT JOIN coordinator_profiles cp ON d.approved_by = cp.uuid AND d.approved_by_role = 'coordinator'\n        WHERE {$where}\n        ORDER BY d.entry_date DESC\n    ");

    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entries[] = formatDtrEntry($row);
    }

    return $entries;
}

function getDtrSummary($conn, string $studentUuid, string $batchUuid): array
{
    $stmt = $conn->prepare("\n        SELECT p.required_hours, osc.working_hours_per_day, osc.start_date\n        FROM student_profiles sp\n        JOIN programs p ON sp.program_uuid = p.uuid\n        LEFT JOIN ojt_applications a\n          ON a.student_uuid = sp.uuid AND a.batch_uuid = ? AND a.status = 'active'\n        LEFT JOIN ojt_start_confirmations osc ON osc.application_uuid = a.uuid\n        WHERE sp.uuid = ?\n        LIMIT 1\n    ");
    $stmt->bind_param('ss', $batchUuid, $studentUuid);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $requiredHours = (int) ($info['required_hours'] ?? 486);
    $hoursPerDay   = (int) ($info['working_hours_per_day'] ?? 8);
    $startDate     = $info['start_date'] ?? null;

    $stmt = $conn->prepare("\n        SELECT\n          COALESCE(SUM(hours_rendered), 0) AS total_approved,\n          COUNT(*) AS approved_count\n        FROM dtr_entries\n        WHERE student_uuid = ?\n          AND batch_uuid = ?\n          AND status = 'approved'\n    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalApproved = (float) $row['total_approved'];
    $approvedCount = (int) $row['approved_count'];
    $remaining     = max(0, $requiredHours - $totalApproved);
    $percentage    = $requiredHours > 0 ? min(100, round(($totalApproved / $requiredHours) * 100, 1)) : 0;

    $stmt = $conn->prepare("\n        SELECT\n          COALESCE(SUM(hours_rendered), 0) AS total_pending,\n          COUNT(*) AS pending_count,\n          COALESCE(SUM(CASE WHEN is_backdated = 1 THEN 1 ELSE 0 END), 0) AS backdated_pending_count\n        FROM dtr_entries\n        WHERE student_uuid = ?\n          AND batch_uuid = ?\n          AND status = 'pending'\n    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $pendingRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $pending = (float) ($pendingRow['total_pending'] ?? 0);

    $estimatedCompletion = null;
    if ($startDate && $remaining > 0 && $hoursPerDay > 0) {
        $daysLeft = ceil($remaining / $hoursPerDay);
        $estimatedCompletion = date('M j, Y', strtotime($startDate . " +{$daysLeft} weekdays"));
    } elseif ($remaining <= 0) {
        $estimatedCompletion = 'Complete';
    }

    return [
        'required_hours'          => $requiredHours,
        'total_approved'          => round($totalApproved, 2),
        'total_pending'           => round($pending, 2),
        'remaining_hours'         => round($remaining, 2),
        'percentage'              => $percentage,
        'approved_count'          => $approvedCount,
        'pending_count'           => (int) ($pendingRow['pending_count'] ?? 0),
        'backdated_pending_count' => (int) ($pendingRow['backdated_pending_count'] ?? 0),
        'hours_per_day'           => $hoursPerDay,
        'is_complete'             => $remaining <= 0,
        'estimated_completion'    => $estimatedCompletion,
        'start_date'              => $startDate ? date('M j, Y', strtotime($startDate)) : null,
    ];
}

function getAllDtrEntries(
    $conn,
    string $batchUuid,
    string $coordinatorUuid = null,
    array  $filters = []
): array {
    $safeBatch = $conn->real_escape_string($batchUuid);
    $conditions = ["d.batch_uuid = '{$safeBatch}'"];

    if ($coordinatorUuid) {
        $safeCoord = $conn->real_escape_string($coordinatorUuid);
        $conditions[] = "sp.coordinator_uuid = '{$safeCoord}'";
    }

    if (!empty($filters['student_uuid'])) {
        $s = $conn->real_escape_string($filters['student_uuid']);
        $conditions[] = "d.student_uuid = '{$s}'";
    }

    if (!empty($filters['status'])) {
        $s = $conn->real_escape_string($filters['status']);
        $conditions[] = "d.status = '{$s}'";
    }

    if (isset($filters['is_backdated'])) {
        $conditions[] = 'd.is_backdated = ' . (int) $filters['is_backdated'];
    }

    if (!empty($filters['month'])) {
        $m = $conn->real_escape_string($filters['month']);
        $conditions[] = "DATE_FORMAT(d.entry_date, '%Y-%m') = '{$m}'";
    }

    $where = implode(' AND ', $conditions);
    $result = $conn->query("\n        SELECT\n          d.*,\n          sp.first_name,\n          sp.last_name,\n          sp.student_number,\n          p.code AS program_code,\n          CONCAT(svp.first_name, ' ', svp.last_name) AS approved_by_name,\n          CONCAT(cp.first_name, ' ', cp.last_name) AS coord_approved_by_name\n        FROM dtr_entries d\n        JOIN student_profiles sp ON d.student_uuid = sp.uuid\n        LEFT JOIN programs p ON sp.program_uuid = p.uuid\n        LEFT JOIN supervisor_profiles svp ON d.approved_by = svp.uuid AND d.approved_by_role = 'supervisor'\n        LEFT JOIN coordinator_profiles cp ON d.approved_by = cp.uuid AND d.approved_by_role = 'coordinator'\n        WHERE {$where}\n        ORDER BY d.entry_date DESC, d.submitted_at DESC\n    ");

    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entry = formatDtrEntry($row);
        $entry['full_name']      = $row['first_name'] . ' ' . $row['last_name'];
        $entry['student_number'] = $row['student_number'];
        $entry['program_code']   = $row['program_code'] ?? '—';
        $entries[]               = $entry;
    }

    return $entries;
}

function getSupervisorPendingDtr($conn, string $supervisorUuid, string $batchUuid): array
{
    $safeSupervisor = $conn->real_escape_string($supervisorUuid);
    $safeBatch      = $conn->real_escape_string($batchUuid);

    $result = $conn->query("\n        SELECT\n          d.*,\n          sp.first_name,\n          sp.last_name,\n          sp.student_number,\n          p.code AS program_code\n        FROM dtr_entries d\n        JOIN student_profiles sp\n          ON d.student_uuid = sp.uuid\n          AND sp.supervisor_uuid = '{$safeSupervisor}'\n        LEFT JOIN programs p ON sp.program_uuid = p.uuid\n        WHERE d.batch_uuid = '{$safeBatch}'\n          AND d.status = 'pending'\n        ORDER BY d.is_backdated ASC, d.entry_date DESC\n    ");

    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entry = formatDtrEntry($row);
        $entry['full_name']      = $row['first_name'] . ' ' . $row['last_name'];
        $entry['student_number'] = $row['student_number'];
        $entry['program_code']   = $row['program_code'] ?? '—';
        $entries[]               = $entry;
    }

    return $entries;
}

function logDtrAudit(
    $conn,
    string $dtrUuid,
    string $action,
    string $actorUuid,
    string $actorRole,
    array  $details = []
): void {
    $uuid        = generateUuid();
    $detailsJson  = !empty($details) ? json_encode($details) : null;

    $stmt = $conn->prepare("\n        INSERT INTO dtr_audit_log\n          (uuid, dtr_uuid, action, actor_uuid, actor_role, details)\n        VALUES (?, ?, ?, ?, ?, ?)\n    ");
    $stmt->bind_param('ssssss', $uuid, $dtrUuid, $action, $actorUuid, $actorRole, $detailsJson);
    $stmt->execute();
    $stmt->close();
}

function formatDtrEntry(array $row): array
{
    $status = $row['status'];
    $statusColors = [
        'pending'  => ['bg' => '#EFF6FF', 'text' => '#185FA5'],
        'approved' => ['bg' => '#E1F5EE', 'text' => '#0F6E56'],
        'rejected' => ['bg' => '#FEF2F2', 'text' => '#DC2626'],
    ];
    $colors = $statusColors[$status] ?? ['bg' => '#F3F4F6', 'text' => '#6B7280'];

    $isBackdated = (int) ($row['is_backdated'] ?? 0) === 1;
    $approvedByName = null;
    if (!empty($row['approved_by_name'])) {
        $approvedByName = $row['approved_by_name'];
    } elseif (!empty($row['coord_approved_by_name'])) {
        $approvedByName = $row['coord_approved_by_name'] . ' (Coordinator)';
    }

    return [
        'uuid'                => $row['uuid'],
        'student_uuid'        => $row['student_uuid'] ?? null,
        'entry_date'          => $row['entry_date'],
        'entry_date_label'    => date('D, M j, Y', strtotime($row['entry_date'])),
        'time_in'             => $row['time_in'],
        'time_in_label'       => date('g:i A', strtotime($row['time_in'])),
        'time_out'            => $row['time_out'],
        'time_out_label'      => date('g:i A', strtotime($row['time_out'])),
        'lunch_break_minutes' => (int) ($row['lunch_break_minutes'] ?? 0),
        'hours_rendered'      => (float) ($row['hours_rendered'] ?? 0),
        'hours_label'         => number_format((float) ($row['hours_rendered'] ?? 0), 2) . ' hrs',
        'activities'          => $row['activities'] ?? '',
        'is_backdated'        => $isBackdated,
        'backdate_reason'     => $row['backdate_reason'] ?? null,
        'status'              => $status,
        'status_label'        => ($status === 'pending' && $isBackdated) ? 'Pending · Backdated' : ucfirst($status),
        'status_bg'           => $colors['bg'],
        'status_text'         => $colors['text'],
        'rejection_reason'    => $row['rejection_reason'] ?? null,
        'approved_by_name'    => $approvedByName,
        'approved_by_role'    => $row['approved_by_role'] ?? null,
        'approved_at'         => !empty($row['approved_at']) ? date('M j, Y g:i A', strtotime($row['approved_at'])) : null,
        'submitted_at'        => date('M j, Y g:i A', strtotime($row['submitted_at'])),
        'time_ago'            => timeAgo($row['submitted_at']),
        'can_edit'            => $status === 'pending',
        'can_delete'          => $status === 'pending',
        'can_bulk_approve'    => $status === 'pending' && !$isBackdated,
        'flagged'             => $isBackdated,
    ];
}
