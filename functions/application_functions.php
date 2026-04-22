<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

const APPLICATION_VALID_TRANSITIONS = [
    'pending'        => ['approved', 'needs_revision', 'rejected'],
    'needs_revision' => ['pending', 'withdrawn'],
    'approved'       => ['endorsed'],
    'endorsed'       => ['active'],
    'active'         => [],
    'rejected'       => [],
    'withdrawn'      => [],
];

function getAllowedApplicationStatuses(): array
{
    return ['pending', 'approved', 'endorsed', 'active', 'needs_revision', 'rejected', 'withdrawn'];
}

function getApplicationStatusLabel(string $status): string
{
    return match ($status) {
        'pending'        => 'Pending review',
        'approved'       => 'Approved',
        'endorsed'       => 'Endorsed',
        'active'         => 'Active',
        'needs_revision' => 'Needs revision',
        'rejected'       => 'Rejected',
        'withdrawn'      => 'Withdrawn',
        default          => 'Unknown',
    };
}

function logApplicationStatus($conn, string $applicationUuid, ?string $fromStatus, string $toStatus, string $changedByUuid, string $note = ''): void
{
    $stmt = $conn->prepare("
        INSERT INTO application_status_logs
        (uuid, application_uuid, from_status, to_status, changed_by, note)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $uuid = generateUuid();
    $stmt->bind_param('ssssss', $uuid, $applicationUuid, $fromStatus, $toStatus, $changedByUuid, $note);
    $stmt->execute();
    $stmt->close();
}

function getRequirementMap($conn, string $studentUuid, string $batchUuid): array
{
    $stmt = $conn->prepare("
        SELECT req_type, status
        FROM student_requirements
        WHERE student_uuid = ? AND batch_uuid = ?
        ORDER BY FIELD(req_type, 'medical_certificate', 'parental_consent', 'insurance', 'nbi_clearance', 'resume', 'guardian_form')
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $requirements = [];
    foreach ($rows as $row) {
        $requirements[$row['req_type']] = $row['status'];
    }

    return $requirements;
}

function getCompanyPrograms($conn, string $companyUuid): array
{
    $stmt = $conn->prepare("
    SELECT p.code
        FROM company_accepted_programs cap
        JOIN programs p ON cap.program_uuid = p.uuid
        WHERE cap.company_uuid = ? AND p.is_active = 1
        ORDER BY p.code ASC
    ");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_values(array_map(fn ($row) => $row['code'], $rows));
}

function getCompanySlotSummary($conn, string $companyUuid, string $batchUuid): array
{
    $stmt = $conn->prepare("
        SELECT total_slots
        FROM company_slots
        WHERE company_uuid = ? AND batch_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $companyUuid, $batchUuid);
    $stmt->execute();
    $slotRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalSlots = (int) ($slotRow['total_slots'] ?? 0);

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM student_profiles
        WHERE company_uuid = ? AND batch_uuid = ?
    ");
    $stmt->bind_param('ss', $companyUuid, $batchUuid);
    $stmt->execute();
    $filledRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $filledSlots = (int) ($filledRow['total'] ?? 0);

    return [
        'total_slots'  => $totalSlots,
        'filled_slots' => $filledSlots,
        'remaining'    => max(0, $totalSlots - $filledSlots),
    ];
}

function areStudentRequirementsApproved($conn, string $studentUuid, string $batchUuid): bool
{
    $stmt = $conn->prepare("
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count
        FROM student_requirements
        WHERE student_uuid = ? AND batch_uuid = ?
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $total = (int) ($row['total'] ?? 0);
    $approvedCount = (int) ($row['approved_count'] ?? 0);

    return $total > 0 && $total === $approvedCount;
}

function getApplicationStatusCounts($conn, string $coordinatorUuid, string $batchUuid): array
{
    $counts = [
        'pending'        => 0,
        'approved'       => 0,
        'endorsed'       => 0,
        'active'         => 0,
        'needs_revision' => 0,
        'rejected'       => 0,
        'withdrawn'      => 0,
    ];

    $stmt = $conn->prepare("
        SELECT a.status, COUNT(*) AS total
        FROM ojt_applications a
        JOIN student_profiles sp ON a.student_uuid = sp.uuid
        WHERE sp.coordinator_uuid = ? AND a.batch_uuid = ?
        GROUP BY a.status
    ");
    $stmt->bind_param('ss', $coordinatorUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as $row) {
        if (array_key_exists($row['status'], $counts)) {
            $counts[$row['status']] = (int) $row['total'];
        }
    }

    return $counts;
}

function getApplicationsForCoordinator($conn, string $coordinatorUuid, string $batchUuid, array $filters = []): array
{
    $statusFilter = $filters['status'] ?? [];
    if (is_string($statusFilter)) {
        $statusFilter = [$statusFilter];
    }

    $allowedStatuses = getAllowedApplicationStatuses();
    $statusFilter = array_values(array_intersect($allowedStatuses, array_map('trim', $statusFilter)));

    $sql = "
        SELECT
            a.uuid AS application_uuid,
            a.status,
            a.preferred_dept,
            a.cover_letter,
            a.coordinator_note,
            a.reviewed_at,
            a.created_at,
            a.student_uuid,
            a.company_uuid,
            sp.student_number,
            sp.first_name,
            sp.last_name,
            sp.section,
            sp.year_level,
            sp.mobile,
            u.email,
            p.code AS program_code,
            p.name AS program_name,
            c.name AS company_name,
            c.industry,
            c.city,
            c.work_setup,
            b.school_year,
            b.semester
        FROM ojt_applications a
        JOIN student_profiles sp ON a.student_uuid = sp.uuid
        JOIN users u ON sp.user_uuid = u.uuid
        LEFT JOIN programs p ON sp.program_uuid = p.uuid
        LEFT JOIN companies c ON a.company_uuid = c.uuid
        LEFT JOIN batches b ON a.batch_uuid = b.uuid
        WHERE sp.coordinator_uuid = ? AND a.batch_uuid = ?";

    $types = 'ss';
    $params = [$coordinatorUuid, $batchUuid];

    if (!empty($statusFilter)) {
        $placeholders = implode(',', array_fill(0, count($statusFilter), '?'));
        $sql .= " AND a.status IN ({$placeholders})";
        $types .= str_repeat('s', count($statusFilter));
        $params = array_merge($params, $statusFilter);
    }

    $sql .= "\n        ORDER BY FIELD(a.status, 'pending', 'needs_revision', 'approved', 'endorsed', 'active', 'rejected', 'withdrawn'), a.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $applications = [];
    foreach ($rows as $row) {
        $requirements = getRequirementMap($conn, $row['student_uuid'], $batchUuid);
        $acceptedPrograms = getCompanyPrograms($conn, $row['company_uuid']);
        $slotSummary = getCompanySlotSummary($conn, $row['company_uuid'], $batchUuid);

        $applications[] = [
            'uuid'             => $row['application_uuid'],
            'status'           => $row['status'],
            'status_label'     => getApplicationStatusLabel($row['status']),
            'student_uuid'     => $row['student_uuid'],
            'full_name'        => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'initials'         => strtoupper(substr($row['first_name'] ?? 'S', 0, 1) . substr($row['last_name'] ?? 'T', 0, 1)),
            'student_number'   => $row['student_number'] ?? '—',
            'program_code'     => $row['program_code'] ?? '—',
            'year_label'       => ordinal((int) ($row['year_level'] ?? 1)) . ' Year',
            'section'          => $row['section'] ?? '—',
            'mobile'           => $row['mobile'] ?? '—',
            'email'            => $row['email'] ?? '—',
            'company_uuid'     => $row['company_uuid'],
            'company_name'     => $row['company_name'] ?? '—',
            'industry'         => $row['industry'] ?? '—',
            'city'             => $row['city'] ?? '—',
            'work_setup'       => $row['work_setup'] ?? '—',
            'submitted_at'     => $row['created_at'] ? date('M j, Y g:i A', strtotime($row['created_at'])) : '—',
            'reviewed_at'      => $row['reviewed_at'] ? date('M j, Y g:i A', strtotime($row['reviewed_at'])) : null,
            'preferred_dept'   => $row['preferred_dept'] ?? '—',
            'cover_letter'     => $row['cover_letter'] ?? '—',
            'coordinator_note' => $row['coordinator_note'] ?? null,
            'accepted_programs' => $acceptedPrograms,
            'slots_info'       => $slotSummary['remaining'] . ' of ' . $slotSummary['total_slots'] . ' slots remaining',
            'requirements'     => $requirements,
            'can_approve'      => in_array('approved', APPLICATION_VALID_TRANSITIONS[$row['status']] ?? [], true),
            'can_return'       => in_array('needs_revision', APPLICATION_VALID_TRANSITIONS[$row['status']] ?? [], true),
            'can_reject'       => in_array('rejected', APPLICATION_VALID_TRANSITIONS[$row['status']] ?? [], true),
            'can_endorse'      => in_array('endorsed', APPLICATION_VALID_TRANSITIONS[$row['status']] ?? [], true),
            'can_confirm_start' => in_array('active', APPLICATION_VALID_TRANSITIONS[$row['status']] ?? [], true),
            'card_one'         => [
                'student_name'   => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'student_No'     => $row['student_number'] ?? '—',
                'program'        => $row['program_code'] ?? '—',
                'course_Section' => trim(($row['year_level'] ?? '') . ' ' . ($row['section'] ?? '')),
                'mobile'         => $row['mobile'] ?? '—',
                'email'          => $row['email'] ?? '—',
            ],
            'card_two'         => [
                'company_name'      => $row['company_name'] ?? '—',
                'work_setup'        => $row['work_setup'] ?? '—',
                'city'              => $row['city'] ?? '—',
                'industry'          => $row['industry'] ?? '—',
                'slots_info'        => $slotSummary['remaining'] . ' of ' . $slotSummary['total_slots'] . ' slots remaining',
                'accepted_programs' => $acceptedPrograms,
            ],
            'card_three'       => [
                'submitted_at'     => $row['created_at'] ? date('M j, Y g:i A', strtotime($row['created_at'])) : '—',
                'preferred_dept'   => $row['preferred_dept'] ?? '—',
                'cover_letter'     => $row['cover_letter'] ?? '—',
                'coordinator_note' => $row['coordinator_note'] ?? null,
            ],
            'card_four'        => [
                'requirements' => $requirements,
            ],
        ];
    }

    return $applications;
}

function approveApplication($conn, string $appUuid, string $note, string $actorUuid): array
{
    $stmt = $conn->prepare("\n        SELECT a.uuid, a.student_uuid, a.company_uuid, a.batch_uuid, a.status, sp.program_uuid\n        FROM ojt_applications a\n        JOIN student_profiles sp ON a.student_uuid = sp.uuid\n        WHERE a.uuid = ?\n        LIMIT 1\n    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('approved', APPLICATION_VALID_TRANSITIONS[$app['status']] ?? [], true)) {
        return ['success' => false, 'error' => "Cannot approve from status: {$app['status']}."];
    }

    if (!areStudentRequirementsApproved($conn, $app['student_uuid'], $app['batch_uuid'])) {
        return ['success' => false, 'error' => 'All pre-OJT requirements must be approved before approval.'];
    }

    $stmt = $conn->prepare("\n        SELECT c.accreditation_status\n        FROM companies c\n        WHERE c.uuid = ?\n        LIMIT 1\n    ");
    $stmt->bind_param('s', $app['company_uuid']);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (($company['accreditation_status'] ?? '') !== 'active') {
        return ['success' => false, 'error' => 'The company is not actively accredited.'];
    }

    $stmt = $conn->prepare("\n        SELECT 1\n        FROM company_accepted_programs\n        WHERE company_uuid = ? AND program_uuid = ?\n        LIMIT 1\n    ");
    $stmt->bind_param('ss', $app['company_uuid'], $app['program_uuid']);
    $stmt->execute();
    $programAccepted = $stmt->get_result()->fetch_row();
    $stmt->close();

    if (!$programAccepted) {
        return ['success' => false, 'error' => 'The company no longer accepts the student program.'];
    }

    $stmt = $conn->prepare("\n        SELECT total_slots\n        FROM company_slots\n        WHERE company_uuid = ? AND batch_uuid = ?\n        LIMIT 1\n    ");
    $stmt->bind_param('ss', $app['company_uuid'], $app['batch_uuid']);
    $stmt->execute();
    $slotRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalSlots = (int) ($slotRow['total_slots'] ?? 0);
    $stmt = $conn->prepare("\n        SELECT COUNT(*) AS total\n        FROM student_profiles\n        WHERE company_uuid = ? AND batch_uuid = ?\n    ");
    $stmt->bind_param('ss', $app['company_uuid'], $app['batch_uuid']);
    $stmt->execute();
    $filledRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($totalSlots > 0 && (int) ($filledRow['total'] ?? 0) >= $totalSlots) {
        return ['success' => false, 'error' => 'This company has no remaining slots.'];
    }

    $stmt = $conn->prepare("\n        UPDATE ojt_applications\n        SET status = 'approved', coordinator_note = ?, reviewed_by = ?, reviewed_at = NOW()\n        WHERE uuid = ?\n    ");
    $stmt->bind_param('sss', $note, $actorUuid, $appUuid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("\n        UPDATE student_profiles\n        SET company_uuid = ?\n        WHERE uuid = ?\n    ");
    $stmt->bind_param('ss', $app['company_uuid'], $app['student_uuid']);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, $app['status'], 'approved', $actorUuid, $note);
    logActivity($conn, 'application_approved', 'Coordinator approved OJT application', 'applications', $actorUuid, $appUuid);

    return ['success' => true, 'message' => 'Application approved successfully.'];
}

function endorseApplication($conn, string $appUuid, string $note, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT a.uuid, a.student_uuid, a.company_uuid, a.batch_uuid, a.status
            FROM ojt_applications a
            WHERE a.uuid = ?
            LIMIT 1
        ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('endorsed', APPLICATION_VALID_TRANSITIONS[$app['status']] ?? [], true)) {
        return ['success' => false, 'error' => "Cannot endorse from status: {$app['status']}."];
    }

    $stmt = $conn->prepare("
        UPDATE ojt_applications
        SET status = 'endorsed', coordinator_note = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('sss', $note, $actorUuid, $appUuid);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, $app['status'], 'endorsed', $actorUuid, $note);
    logActivity($conn, 'application_endorsed', 'Coordinator endorsed OJT application', 'applications', $actorUuid, $appUuid);

    return ['success' => true, 'message' => 'Endorsement issued successfully.'];
}

function confirmOjtStart($conn, string $appUuid, string $startDate, string $note, string $actorUuid): array
{
    $startDate = trim($startDate);
    if ($startDate === '') {
        return ['success' => false, 'error' => 'Start date is required.'];
    }

    $date = DateTime::createFromFormat('Y-m-d', $startDate);
    if (!$date || $date->format('Y-m-d') !== $startDate) {
        return ['success' => false, 'error' => 'Start date format is invalid.'];
    }

    $stmt = $conn->prepare("
        SELECT a.uuid, a.student_uuid, a.company_uuid, a.batch_uuid, a.status
            FROM ojt_applications a
            WHERE a.uuid = ?
            LIMIT 1
        ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('active', APPLICATION_VALID_TRANSITIONS[$app['status']] ?? [], true)) {
        return ['success' => false, 'error' => "Cannot confirm start from status: {$app['status']}."];
    }

    $composedNote = trim("Start Date: {$startDate}" . (trim($note) !== '' ? "\n" . trim($note) : ''));

    $stmt = $conn->prepare("
        UPDATE ojt_applications
        SET status = 'active', coordinator_note = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('sss', $composedNote, $actorUuid, $appUuid);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, $app['status'], 'active', $actorUuid, $composedNote);
    logActivity($conn, 'application_started', 'Coordinator confirmed OJT start', 'applications', $actorUuid, $appUuid);

    return ['success' => true, 'message' => 'OJT start confirmed successfully.'];
}

function returnApplication($conn, string $appUuid, string $note, string $actorUuid): array
{
    if (trim($note) === '') {
        return ['success' => false, 'error' => 'A note is required when returning an application.'];
    }

    $stmt = $conn->prepare("
        SELECT uuid, student_uuid, status
        FROM ojt_applications
        WHERE uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('needs_revision', APPLICATION_VALID_TRANSITIONS[$app['status']] ?? [], true)) {
        return ['success' => false, 'error' => "Cannot return from status: {$app['status']}."];
    }

    $stmt = $conn->prepare("
        UPDATE ojt_applications
        SET status = 'needs_revision', coordinator_note = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('sss', $note, $actorUuid, $appUuid);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, $app['status'], 'needs_revision', $actorUuid, $note);
    logActivity($conn, 'application_returned', 'Coordinator returned OJT application for revision', 'applications', $actorUuid, $appUuid);

    return ['success' => true, 'message' => 'Application returned for revision.'];
}

function rejectApplication($conn, string $appUuid, string $note, string $actorUuid): array
{
    if (trim($note) === '') {
        return ['success' => false, 'error' => 'A note is required when rejecting an application.'];
    }

    $stmt = $conn->prepare("
        SELECT a.uuid, a.student_uuid, a.company_uuid, a.status
        FROM ojt_applications a
        WHERE a.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('rejected', APPLICATION_VALID_TRANSITIONS[$app['status']] ?? [], true)) {
        return ['success' => false, 'error' => "Cannot reject from status: {$app['status']}."];
    }

    $stmt = $conn->prepare("\n        UPDATE ojt_applications\n        SET status = 'rejected', coordinator_note = ?, reviewed_by = ?, reviewed_at = NOW()\n        WHERE uuid = ?\n    ");
    $stmt->bind_param('sss', $note, $actorUuid, $appUuid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("\n        UPDATE student_profiles\n        SET company_uuid = NULL\n        WHERE uuid = ? AND company_uuid = ?\n    ");
    $stmt->bind_param('ss', $app['student_uuid'], $app['company_uuid']);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, $app['status'], 'rejected', $actorUuid, $note);
    logActivity($conn, 'application_rejected', 'Coordinator rejected OJT application', 'applications', $actorUuid, $appUuid);

    return ['success' => true, 'message' => 'Application rejected.'];
}

function getAvailableCompaniesForStudent($conn, string $batchUuid, string $programUuid): array
{
    $stmt = $conn->prepare("
        SELECT
        c.uuid,
        c.name,
        c.industry,
        c.city,
        c.work_setup,
        c.address,\
        
        cs.total_slots,
        COUNT(DISTINCT sp.id) AS filled_slots
        FROM companies c
        JOIN company_accepted_programs cap
        ON c.uuid = cap.company_uuid
        AND cap.program_uuid = ?
        JOIN company_slots cs
        ON c.uuid = cs.company_uuid
        AND cs.batch_uuid = ?
        LEFT JOIN student_profiles sp
        ON c.uuid = sp.company_uuid
        AND sp.batch_uuid = ?
        WHERE c.accreditation_status = 'active'
        GROUP BY c.uuid, c.name, c.industry, c.city, c.work_setup, c.address, cs.total_slots
        HAVING filled_slots < cs.total_slots
        ORDER BY c.name ASC
    ");
    $stmt->bind_param('sss', $programUuid, $batchUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $companies = [];
    foreach ($rows as $row) {
        $companyPrograms = getCompanyPrograms($conn, $row['uuid']);
        $companies[] = [
            'uuid' => $row['uuid'],
            'name' => $row['name'],
            'industry' => $row['industry'] ?? '—',
            'city' => $row['city'] ?? '—',
            'work_setup' => $row['work_setup'] ?? '—',
            'address' => $row['address'] ?? '—',
            'total_slots' => (int) ($row['total_slots'] ?? 0),
            'filled_slots' => (int) ($row['filled_slots'] ?? 0),
            'remaining_slots' => max(0, (int) ($row['total_slots'] ?? 0) - (int) ($row['filled_slots'] ?? 0)),
            'accepted_programs' => $companyPrograms,
        ];
    }

    return $companies;
}

function getApplicationStatusLog($conn, string $applicationUuid): array
{
    $stmt = $conn->prepare("
        SELECT
          asl.from_status,
          asl.to_status,
          asl.note,
          asl.created_at,
          u.role AS changed_by_role,
          CASE u.role
            WHEN 'student' THEN CONCAT(sp.first_name, ' ', sp.last_name)
            WHEN 'coordinator' THEN CONCAT(cp.first_name, ' ', cp.last_name)
            WHEN 'admin' THEN CONCAT(ap.first_name, ' ', ap.last_name)
            ELSE 'System'
          END AS changed_by_name
        FROM application_status_logs asl
        LEFT JOIN users u ON asl.changed_by = u.uuid
        LEFT JOIN student_profiles sp ON u.uuid = sp.user_uuid AND u.role = 'student'
        LEFT JOIN coordinator_profiles cp ON u.uuid = cp.user_uuid AND u.role = 'coordinator'
        LEFT JOIN admin_profiles ap ON u.uuid = ap.user_uuid AND u.role = 'admin'
        WHERE asl.application_uuid = ?
        ORDER BY asl.created_at ASC
    ");
    $stmt->bind_param('s', $applicationUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(static fn ($row) => [
        'from_status' => $row['from_status'],
        'to_status' => $row['to_status'],
        'note' => $row['note'] ?? '',
        'changed_by' => $row['changed_by_name'] ?: 'System',
        'changed_by_role' => $row['changed_by_role'] ?? '',
        'date' => date('M j, Y g:i A', strtotime($row['created_at'])),
    ], $rows);
}

function getStudentApplication($conn, string $studentUuid, string $batchUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          a.uuid,
          a.status,
          a.preferred_dept,
          a.cover_letter,
          a.coordinator_note,
          a.reviewed_at,
          a.created_at,
          c.name AS company_name,
          c.industry,
          c.city,
          c.work_setup,
          c.address
        FROM ojt_applications a
        JOIN companies c ON a.company_uuid = c.uuid
        WHERE a.student_uuid = ? AND a.batch_uuid = ?
        ORDER BY a.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $status = $row['status'];

    return [
        'uuid' => $row['uuid'],
        'status' => $status,
        'status_label' => getApplicationStatusLabel($status),
        'company_name' => $row['company_name'] ?? '—',
        'industry' => $row['industry'] ?? '—',
        'city' => $row['city'] ?? '—',
        'work_setup' => $row['work_setup'] ?? '—',
        'address' => $row['address'] ?? '—',
        'preferred_dept' => $row['preferred_dept'] ?? '—',
        'cover_letter' => $row['cover_letter'] ?? '',
        'coordinator_note' => $row['coordinator_note'] ?? null,
        'reviewed_at' => $row['reviewed_at'] ? date('M j, Y g:i A', strtotime($row['reviewed_at'])) : null,
        'submitted_at' => $row['created_at'] ? date('M j, Y g:i A', strtotime($row['created_at'])) : null,
        'can_withdraw' => in_array('withdrawn', APPLICATION_VALID_TRANSITIONS[$status] ?? [], true),
        'can_download_endorsement' => in_array($status, ['endorsed', 'active'], true),
    ];
}

function submitStudentApplication($conn, string $studentUuid, string $batchUuid, string $companyUuid, string $preferredDept, string $coverLetter, string $actorUuid): array
{
    if ($companyUuid === '') {
        return ['success' => false, 'error' => 'Select a company first.'];
    }

    if (!areStudentRequirementsApproved($conn, $studentUuid, $batchUuid)) {
        return ['success' => false, 'error' => 'All pre-OJT requirements must be approved before applying.'];
    }

    $stmt = $conn->prepare("
    SELECT program_uuid
        FROM student_profiles
        WHERE uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $studentUuid);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$profile || empty($profile['program_uuid'])) {
        return ['success' => false, 'error' => 'Student profile is missing a program.'];
    }

    $stmt = $conn->prepare("
        SELECT a.uuid, a.status
        FROM ojt_applications a
        WHERE a.student_uuid = ? AND a.batch_uuid = ?
        ORDER BY a.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing && in_array($existing['status'], ['pending', 'approved', 'endorsed', 'active'], true)) {
        return ['success' => false, 'error' => 'You already have an active application for this batch.'];
    }

    $stmt = $conn->prepare("
    SELECT 1
        FROM company_accepted_programs
        WHERE company_uuid = ? AND program_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $companyUuid, $profile['program_uuid']);
    $stmt->execute();
    $acceptsProgram = $stmt->get_result()->fetch_row();
    $stmt->close();

    if (!$acceptsProgram) {
        return ['success' => false, 'error' => 'Selected company does not accept your program.'];
    }

    $slotSummary = getCompanySlotSummary($conn, $companyUuid, $batchUuid);
    if (($slotSummary['total_slots'] ?? 0) <= 0 || ($slotSummary['remaining'] ?? 0) <= 0) {
        return ['success' => false, 'error' => 'Selected company has no remaining slots.'];
    }

    if ($existing && $existing['status'] === 'needs_revision') {
        $stmt = $conn->prepare("
            UPDATE ojt_applications
            SET company_uuid = ?, preferred_dept = ?, cover_letter = ?, status = 'pending', coordinator_note = NULL, reviewed_by = NULL, reviewed_at = NULL
            WHERE uuid = ?
        ");
        $stmt->bind_param('ssss', $companyUuid, $preferredDept, $coverLetter, $existing['uuid']);
        $stmt->execute();
        $stmt->close();

        logApplicationStatus($conn, $existing['uuid'], 'needs_revision', 'pending', $actorUuid, 'Student re-submitted application after revision');
        logActivity($conn, 'application_submitted', 'Student re-submitted OJT application', 'applications', $actorUuid, $existing['uuid']);

        return ['success' => true, 'message' => 'Application re-submitted successfully.', 'application_uuid' => $existing['uuid']];
    }

    $appUuid = generateUuid();
    $stmt = $conn->prepare("
        INSERT INTO ojt_applications
          (uuid, student_uuid, company_uuid, batch_uuid, status, preferred_dept, cover_letter)
        VALUES (?, ?, ?, ?, 'pending', ?, ?)
    ");
    $stmt->bind_param('ssssss', $appUuid, $studentUuid, $companyUuid, $batchUuid, $preferredDept, $coverLetter);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, null, 'pending', $actorUuid, 'Student submitted application');
    logActivity($conn, 'application_submitted', 'Student submitted OJT application', 'applications', $actorUuid, $appUuid);

    return ['success' => true, 'message' => 'Application submitted successfully.', 'application_uuid' => $appUuid];
}

function withdrawStudentApplication($conn, string $applicationUuid, string $studentUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT uuid, status, company_uuid
        FROM ojt_applications
        WHERE uuid = ? AND student_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $applicationUuid, $studentUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('withdrawn', APPLICATION_VALID_TRANSITIONS[$app['status']] ?? [], true)) {
        return ['success' => false, 'error' => "Application cannot be withdrawn from status: {$app['status']}."];
    }

    $stmt = $conn->prepare("
        UPDATE ojt_applications
        SET status = 'withdrawn', reviewed_by = ?, reviewed_at = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $actorUuid, $applicationUuid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE student_profiles
        SET company_uuid = NULL
        WHERE uuid = ? AND company_uuid = ?
    ");
    $stmt->bind_param('ss', $studentUuid, $app['company_uuid']);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $applicationUuid, $app['status'], 'withdrawn', $actorUuid, 'Student withdrew application');
    logActivity($conn, 'application_withdrawn', 'Student withdrew OJT application', 'applications', $actorUuid, $applicationUuid);

    return ['success' => true, 'message' => 'Application withdrawn successfully.'];
}
