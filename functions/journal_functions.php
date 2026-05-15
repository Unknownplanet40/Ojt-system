<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}
require_once __DIR__ . '/../helpers/helpers.php';













function ensureWeeklyJournalsSchema($conn): bool
{
    $columns = [
        'application_uuid'   => "CHAR(36) NULL AFTER student_uuid",
        'week_number'        => "TINYINT NULL AFTER batch_uuid",
        'accomplishments'    => "TEXT NULL AFTER week_end",
        'skills_learned'     => "TEXT NULL AFTER accomplishments",
        'challenges'         => "TEXT NULL AFTER skills_learned",
        'plans_next_week'    => "TEXT NULL AFTER challenges",
        'return_reason'      => "TEXT NULL AFTER status",
        'coordinator_remarks'=> "TEXT NULL AFTER return_reason",
        'reviewed_by'        => "CHAR(36) NULL AFTER coordinator_remarks",
        'reviewed_at'        => "DATETIME NULL AFTER reviewed_by",
        'submitted_at'       => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER reviewed_at",
        'updated_at'         => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER submitted_at",
    ];

    foreach ($columns as $column => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM weekly_journals LIKE '{$column}'");
        if ($check && $check->num_rows > 0) {
            continue;
        }

        if (!$conn->query("ALTER TABLE weekly_journals ADD COLUMN {$column} {$definition}")) {
            return false;
        }
    }

    return true;
}

function computeWeekNumber(string $startDate, string $weekStart): int
{
    $start  = strtotime($startDate);
    $week   = strtotime($weekStart);
    $diff   = max(0, $week - $start);
    return (int) floor($diff / (7 * 86400)) + 1;
}









function validateWeekRange(
    $conn,
    string $studentUuid,
    string $batchUuid,
    string $weekStart,
    string $weekEnd,
    string $excludeUuid = null
): array {
    $errors = [];

    if (empty($weekStart)) {
        $errors['week_start'] = 'Week start date is required.';
    }
    if (empty($weekEnd)) {
        $errors['week_end']   = 'Week end date is required.';
    }

    if (!empty($errors)) {
        return ['valid' => false, 'errors' => $errors];
    }

    $startTs = strtotime($weekStart);
    $endTs   = strtotime($weekEnd);
    $today   = strtotime(date('Y-m-d'));

    
    if ($startTs > $today) {
        $errors['week_start'] = 'Week start cannot be a future date.';
    }

    
    if ($endTs <= $startTs) {
        $errors['week_end'] = 'Week end must be after week start.';
    }

    
    $rangeDays = ($endTs - $startTs) / 86400;
    if ($rangeDays > 7) {
        $errors['week_end'] = 'Week range cannot exceed 7 days.';
    }

    if (!empty($errors)) {
        return ['valid' => false, 'errors' => $errors];
    }

    
    $stmt = $conn->prepare("
        SELECT osc.start_date
        FROM ojt_applications a
        JOIN ojt_start_confirmations osc ON osc.application_uuid = a.uuid
        WHERE a.student_uuid = ? AND a.batch_uuid = ? AND a.status = 'active'
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return [
            'valid'  => false,
            'errors' => ['week_start' => 'No active OJT found. OJT must be confirmed first.'],
        ];
    }

    $ojtStartTs = strtotime($row['start_date']);

    if ($startTs < $ojtStartTs) {
        $errors['week_start'] = 'Week start cannot be before your OJT start date (' .
            date('M j, Y', $ojtStartTs) . ').';
    }

    if (!empty($errors)) {
        return ['valid' => false, 'errors' => $errors];
    }

    
    $stmt = $conn->prepare("
        SELECT uuid FROM weekly_journals
        WHERE student_uuid = ?
          AND batch_uuid   = ?
          AND week_start   = ?
          " . ($excludeUuid ? "AND uuid != '{$excludeUuid}'" : "") . "
        LIMIT 1
    ");
    $stmt->bind_param('sss', $studentUuid, $batchUuid, $weekStart);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $errors['week_start'] = 'A journal entry for this week already exists.';
        return ['valid' => false, 'errors' => $errors];
    }

    $weekNumber = computeWeekNumber($row['start_date'], $weekStart);

    return [
        'valid'       => true,
        'week_number' => $weekNumber,
        'ojt_start'   => $row['start_date'],
    ];
}





function submitJournal(
    $conn,
    string $studentUuid,
    string $batchUuid,
    array  $data
): array {
    if (!ensureWeeklyJournalsSchema($conn)) {
        return ['success' => false, 'errors' => ['general' => 'Failed to initialize weekly journal table.']];
    }

    $weekStart      = trim($data['week_start']      ?? '');
    $weekEnd        = trim($data['week_end']        ?? '');
    $accomplishments = trim($data['accomplishments'] ?? '');
    $skillsLearned  = trim($data['skills_learned']  ?? '');
    $challenges     = trim($data['challenges']      ?? '');
    $plansNextWeek  = trim($data['plans_next_week'] ?? '');

    $errors = [];

    if (empty($accomplishments)) {
        $errors['accomplishments'] = 'Accomplishments field is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    
    $weekCheck = validateWeekRange($conn, $studentUuid, $batchUuid, $weekStart, $weekEnd);

    if (!$weekCheck['valid']) {
        return ['success' => false, 'errors' => $weekCheck['errors']];
    }

    
    $stmt = $conn->prepare("
        SELECT uuid FROM ojt_applications
        WHERE student_uuid = ? AND batch_uuid = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'errors' => ['general' => 'No active OJT found.']];
    }

    $uuid       = generateUuid();
    $weekNumber = $weekCheck['week_number'];

    $stmt = $conn->prepare("
        INSERT INTO weekly_journals
          (uuid, student_uuid, application_uuid, batch_uuid,
           week_number, week_start, week_end,
           accomplishments, skills_learned, challenges, plans_next_week,
           status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')
    ");
    $stmt->bind_param(
        'ssssissssss',
        $uuid,
        $studentUuid,
        $app['uuid'],
        $batchUuid,
        $weekNumber,
        $weekStart,
        $weekEnd,
        $accomplishments,
        $skillsLearned,
        $challenges,
        $plansNextWeek
    );
    $stmt->execute();
    $stmt->close();

    
    $userStmt = $conn->prepare("SELECT user_uuid FROM student_profiles WHERE uuid = ? LIMIT 1");
    $userStmt->bind_param('s', $studentUuid);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    $userUuid = $userRow['user_uuid'] ?? null;

    logActivity(
        conn: $conn,
        eventType: 'journal_submitted',
        description: "Week {$weekNumber} journal submitted",
        module: 'journal',
        actorUuid: $userUuid,
        targetUuid: $uuid
    );

    return [
        'success'     => true,
        'uuid'        => $uuid,
        'week_number' => $weekNumber,
    ];
}





function editJournal(
    $conn,
    string $journalUuid,
    string $studentUuid,
    string $batchUuid,
    array  $data
): array {
    $stmt = $conn->prepare("
        SELECT uuid, status, week_start, week_end
        FROM weekly_journals
        WHERE uuid = ? AND student_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $journalUuid, $studentUuid);
    $stmt->execute();
    $journal = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$journal) {
        return ['success' => false, 'error' => 'Journal not found.'];
    }

    if ($journal['status'] !== 'returned') {
        return [
            'success' => false,
            'error'   => 'Only returned journals can be edited and resubmitted.',
        ];
    }

    $weekStart      = trim($data['week_start']       ?? $journal['week_start']);
    $weekEnd        = trim($data['week_end']         ?? $journal['week_end']);
    $accomplishments = trim($data['accomplishments']  ?? '');
    $skillsLearned  = trim($data['skills_learned']   ?? '');
    $challenges     = trim($data['challenges']       ?? '');
    $plansNextWeek  = trim($data['plans_next_week']  ?? '');

    if (empty($accomplishments)) {
        return ['success' => false, 'errors' => ['accomplishments' => 'Accomplishments field is required.']];
    }

    
    $weekCheck = validateWeekRange(
        $conn,
        $studentUuid,
        $batchUuid,
        $weekStart,
        $weekEnd,
        $journalUuid
    );

    if (!$weekCheck['valid']) {
        return ['success' => false, 'errors' => $weekCheck['errors']];
    }

    $stmt = $conn->prepare("
        UPDATE weekly_journals
        SET week_start       = ?,
            week_end         = ?,
            week_number      = ?,
            accomplishments  = ?,
            skills_learned   = ?,
            challenges       = ?,
            plans_next_week  = ?,
            status           = 'submitted',
            return_reason    = NULL,
            reviewed_by      = NULL,
            reviewed_at      = NULL,
            submitted_at     = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param(
        'sissssss',
        $weekStart,
        $weekEnd,
        $weekCheck['week_number'],
        $accomplishments,
        $skillsLearned,
        $challenges,
        $plansNextWeek,
        $journalUuid
    );
    $stmt->execute();
    $stmt->close();

    
    $userStmt = $conn->prepare("SELECT user_uuid FROM student_profiles WHERE uuid = ? LIMIT 1");
    $userStmt->bind_param('s', $studentUuid);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    $userUuid = $userRow['user_uuid'] ?? null;

    logActivity(
        conn: $conn,
        eventType: 'journal_resubmitted',
        description: "Week {$weekCheck['week_number']} journal resubmitted after return",
        module: 'journal',
        actorUuid: $userUuid,
        targetUuid: $journalUuid
    );

    return ['success' => true];
}






function reviewJournal(
    $conn,
    string $journalUuid,
    string $coordinatorUuid,
    string $action,
    string $remarks = '',
    string $returnReason = ''
): array {
    $validActions = ['remark', 'approve', 'return'];
    if (!in_array($action, $validActions)) {
        return ['success' => false, 'error' => 'Invalid action.'];
    }

    
    $stmt = $conn->prepare("
        SELECT wj.uuid, wj.status, wj.student_uuid, wj.week_number,
               sp.coordinator_uuid
        FROM weekly_journals wj
        JOIN student_profiles sp ON wj.student_uuid = sp.uuid
        WHERE wj.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $journalUuid);
    $stmt->execute();
    $journal = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$journal) {
        return ['success' => false, 'error' => 'Journal not found.'];
    }

    if ($journal['coordinator_uuid'] !== $coordinatorUuid) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    if ($journal['status'] === 'approved' && $action !== 'remark') {
        return ['success' => false, 'error' => 'Approved journals cannot be returned or re-approved.'];
    }

    
    if ($action === 'return' && empty($returnReason)) {
        return ['success' => false, 'error' => 'Return reason is required.'];
    }

    
    $newStatus    = match($action) {
        'approve' => 'approved',
        'return'  => 'returned',
        default   => $journal['status'], 
    };

    $stmt = $conn->prepare("
        UPDATE weekly_journals
        SET coordinator_remarks = ?,
            status              = ?,
            return_reason       = ?,
            reviewed_by         = ?,
            reviewed_at         = NOW()
        WHERE uuid = ?
    ");
    $reason = $action === 'return' ? $returnReason : null;
    $stmt->bind_param(
        'sssss',
        $remarks,
        $newStatus,
        $reason,
        $coordinatorUuid,
        $journalUuid
    );
    $stmt->execute();
    $stmt->close();

    
    $userStmt = $conn->prepare("SELECT user_uuid FROM coordinator_profiles WHERE uuid = ? LIMIT 1");
    $userStmt->bind_param('s', $coordinatorUuid);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    $userUuid = $userRow['user_uuid'] ?? null;

    $eventMap = [
        'remark'  => 'journal_remarked',
        'approve' => 'journal_approved',
        'return'  => 'journal_returned',
    ];

    logActivity(
        conn: $conn,
        eventType: $eventMap[$action],
        description: "Week {$journal['week_number']} journal {$action}ed",
        module: 'journal',
        actorUuid: $userUuid,
        targetUuid: $journalUuid
    );

    return ['success' => true, 'new_status' => $newStatus];
}





function getStudentJournals(
    $conn,
    string $studentUuid,
    string $batchUuid,
    array  $filters = []
): array {
    $safeStudent = $conn->real_escape_string($studentUuid);
    $safeBatch   = $conn->real_escape_string($batchUuid);

    $conditions = [
        "wj.student_uuid = '{$safeStudent}'",
        "wj.batch_uuid   = '{$safeBatch}'",
    ];

    if (!empty($filters['status'])) {
        $s = $conn->real_escape_string($filters['status']);
        $conditions[] = "wj.status = '{$s}'";
    }

    $where  = implode(' AND ', $conditions);
    $result = $conn->query("
        SELECT
          wj.*,
          CONCAT(cp.first_name, ' ', cp.last_name) AS reviewer_name

        FROM weekly_journals wj
        LEFT JOIN coordinator_profiles cp ON wj.reviewed_by = cp.uuid
        WHERE {$where}
        ORDER BY wj.week_start DESC
    ");

    $journals = [];
    while ($row = $result->fetch_assoc()) {
        $journals[] = formatJournal($row);
    }

    return $journals;
}






function getAllJournals(
    $conn,
    string $batchUuid,
    string $coordinatorUuid = null,
    array  $filters = []
): array {
    $safeBatch = $conn->real_escape_string($batchUuid);
    $conditions = ["wj.batch_uuid = '{$safeBatch}'"];

    if ($coordinatorUuid) {
        $safeCoord    = $conn->real_escape_string($coordinatorUuid);
        $conditions[] = "sp.coordinator_uuid = '{$safeCoord}'";
    }

    if (!empty($filters['student_uuid'])) {
        $s = $conn->real_escape_string($filters['student_uuid']);
        $conditions[] = "wj.student_uuid = '{$s}'";
    }

    if (!empty($filters['status'])) {
        $s = $conn->real_escape_string($filters['status']);
        $conditions[] = "wj.status = '{$s}'";
    }

    $where  = implode(' AND ', $conditions);
    $result = $conn->query("
        SELECT
          wj.*,
          sp.first_name,
          sp.last_name,
          sp.student_number,
          p.code AS program_code,
          CONCAT(cp.first_name, ' ', cp.last_name) AS reviewer_name

        FROM weekly_journals wj
        JOIN student_profiles sp ON wj.student_uuid  = sp.uuid
        LEFT JOIN programs p     ON sp.program_uuid   = p.uuid
        LEFT JOIN coordinator_profiles cp ON wj.reviewed_by = cp.uuid
        WHERE {$where}
        ORDER BY
          FIELD(wj.status, 'submitted', 'returned', 'approved'),
          wj.week_start DESC
    ");

    $journals = [];
    while ($row = $result->fetch_assoc()) {
        $entry                   = formatJournal($row);
        $entry['full_name']      = $row['first_name'] . ' ' . $row['last_name'];
        $entry['initials']       = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
        $entry['student_number'] = $row['student_number'];
        $entry['program_code']   = $row['program_code'] ?? '—';
        $journals[]              = $entry;
    }

    return $journals;
}






function getSupervisorJournals(
    $conn,
    string $supervisorUuid,
    string $batchUuid,
    array  $filters = []
): array {
    $safeSupervisor = $conn->real_escape_string($supervisorUuid);
    $safeBatch      = $conn->real_escape_string($batchUuid);

    $conditions = [
        "sp.supervisor_uuid = '{$safeSupervisor}'",
        "wj.batch_uuid      = '{$safeBatch}'",
    ];

    if (!empty($filters['student_uuid'])) {
        $s = $conn->real_escape_string($filters['student_uuid']);
        $conditions[] = "wj.student_uuid = '{$s}'";
    }

    $where  = implode(' AND ', $conditions);
    $result = $conn->query("
        SELECT
          wj.*,
          sp.first_name,
          sp.last_name,
          sp.student_number

        FROM weekly_journals wj
        JOIN student_profiles sp ON wj.student_uuid    = sp.uuid
          AND sp.supervisor_uuid = '{$safeSupervisor}'
        WHERE {$where}
        ORDER BY wj.week_start DESC
    ");

    $journals = [];
    while ($row = $result->fetch_assoc()) {
        $entry                   = formatJournal($row);
        $entry['full_name']      = $row['first_name'] . ' ' . $row['last_name'];
        $entry['student_number'] = $row['student_number'];
        $journals[]              = $entry;
    }

    return $journals;
}





function getJournal($conn, string $journalUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          wj.*,
          sp.first_name,
          sp.last_name,
          sp.student_number,
          sp.coordinator_uuid,
          sp.supervisor_uuid,
          CONCAT(cp.first_name, ' ', cp.last_name) AS reviewer_name

        FROM weekly_journals wj
        JOIN student_profiles sp ON wj.student_uuid = sp.uuid
        LEFT JOIN coordinator_profiles cp ON wj.reviewed_by = cp.uuid
        WHERE wj.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $journalUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $entry                   = formatJournal($row);
    $entry['full_name']      = $row['first_name'] . ' ' . $row['last_name'];
    $entry['student_number'] = $row['student_number'];
    $entry['student_uuid']   = $row['student_uuid'];
    $entry['coordinator_uuid'] = $row['coordinator_uuid'];
    $entry['supervisor_uuid']  = $row['supervisor_uuid'];

    return $entry;
}





function formatJournal(array $row): array
{
    $status = $row['status'];

    $statusColors = [
        'submitted' => ['bg' => '#EFF6FF', 'text' => '#185FA5'],
        'approved'  => ['bg' => '#E1F5EE', 'text' => '#0F6E56'],
        'returned'  => ['bg' => '#FEF2F2', 'text' => '#DC2626'],
    ];
    $colors = $statusColors[$status] ?? ['bg' => '#F3F4F6', 'text' => '#6B7280'];

    return [
        'uuid'                => $row['uuid'],
        'week_number'         => (int) $row['week_number'],
        'week_label'          => 'Week ' . $row['week_number'],
        'week_start'          => $row['week_start'],
        'week_end'            => $row['week_end'],
        'week_range'          => date('M j', strtotime($row['week_start'])) .
                                 ' – ' .
                                 date('M j, Y', strtotime($row['week_end'])),
        'accomplishments'     => $row['accomplishments'],
        'skills_learned'      => $row['skills_learned']  ?? '',
        'challenges'          => $row['challenges']       ?? '',
        'plans_next_week'     => $row['plans_next_week']  ?? '',
        'status'              => $status,
        'status_label'        => ucfirst($status),
        'status_bg'           => $colors['bg'],
        'status_text'         => $colors['text'],
        'return_reason'       => $row['return_reason']       ?? null,
        'coordinator_remarks' => $row['coordinator_remarks'] ?? null,
        'reviewed_by'         => $row['reviewed_by']         ?? null,
        'reviewer_name'       => $row['reviewer_name']       ?? null,
        'reviewed_at'         => !empty($row['reviewed_at'])
                                   ? date('M j, Y g:i A', strtotime($row['reviewed_at']))
                                   : null,
        'submitted_at'        => date('M j, Y g:i A', strtotime($row['submitted_at'])),
        'time_ago'            => timeAgo($row['submitted_at']),
        
        'can_edit'            => $status === 'returned',
        'can_view'            => true,
    ];
}
