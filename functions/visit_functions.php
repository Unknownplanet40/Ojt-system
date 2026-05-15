<?php






if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}
require_once __DIR__ . '/../helpers/helpers.php';





function scheduleVisit(
    $conn,
    string $coordinatorUuid,
    string $batchUuid,
    array  $data
): array {
    $errors = [];

    $companyUuid  = trim($data['company_uuid'] ?? '');
    $visitDate    = trim($data['visit_date']   ?? '');
    $visitType    = trim($data['visit_type']   ?? 'scheduled');
    $purpose      = trim($data['purpose']      ?? '');

    if (empty($companyUuid)) $errors['company_uuid'] = 'Company is required.';
    if (empty($visitDate))   $errors['visit_date']   = 'Visit date is required.';
    if (empty($purpose))     $errors['purpose']       = 'Purpose of visit is required.';

    if (!in_array($visitType, ['scheduled', 'unscheduled'])) {
        $visitType = 'scheduled';
    }

    if (!empty($visitDate)) {
        $today = date('Y-m-d');
        
        if ($visitType === 'scheduled' && $visitDate < $today) {
            $errors['visit_date'] = 'Scheduled visit date must be today or in the future.';
        }
        
        if ($visitType === 'unscheduled' && $visitDate > $today) {
            $errors['visit_date'] = 'Unscheduled visit date cannot be in the future.';
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM student_profiles
        WHERE coordinator_uuid = ?
          AND company_uuid     = ?
          AND batch_uuid       = ?
    ");
    $stmt->bind_param('sss', $coordinatorUuid, $companyUuid, $batchUuid);
    $stmt->execute();
    $count = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($count === 0) {
        return [
            'success' => false,
            'errors'  => ['company_uuid' => 'No students from your batch are assigned to this company.'],
        ];
    }

    
    $status = $visitType === 'unscheduled' ? 'completed' : 'scheduled';

    $uuid = generateUuid();

    if ($visitType === 'unscheduled') {
        $findings = trim($data['findings'] ?? '');
        $recommendations = trim($data['recommendations'] ?? '');
        
        if (empty($findings)) {
            return ['success' => false, 'errors' => ['findings' => 'Findings are required for unscheduled visits.']];
        }

        $stmt = $conn->prepare("
            INSERT INTO coordinator_visits
              (uuid, coordinator_uuid, company_uuid, batch_uuid,
               visit_date, visit_type, purpose, status, findings, recommendations)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'ssssssssss',
            $uuid, $coordinatorUuid, $companyUuid, $batchUuid,
            $visitDate, $visitType, $purpose, $status, $findings, $recommendations
        );
    } else {
        $stmt = $conn->prepare("
            INSERT INTO coordinator_visits
              (uuid, coordinator_uuid, company_uuid, batch_uuid,
               visit_date, visit_type, purpose, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'ssssssss',
            $uuid, $coordinatorUuid, $companyUuid, $batchUuid,
            $visitDate, $visitType, $purpose, $status
        );
    }
    
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'visit_scheduled',
        description: $visitType === 'unscheduled'
            ? "Unscheduled visit logged for company"
            : "Company visit scheduled for {$visitDate}",
        module: 'visits',
        actorUuid: $_SESSION['user_uuid'] ?? $coordinatorUuid,
        targetUuid: $uuid
    );

    return [
        'success' => true,
        'uuid'    => $uuid,
        'status'  => $status,
    ];
}






function completeVisit(
    $conn,
    string $visitUuid,
    string $coordinatorUuid,
    array  $data
): array {
    $findings        = trim($data['findings']        ?? '');
    $recommendations = trim($data['recommendations'] ?? '');
    $studentsObserved = $data['students_observed']    ?? [];

    if (empty($findings)) {
        return ['success' => false, 'errors' => ['findings' => 'Visit findings are required.']];
    }

    
    $stmt = $conn->prepare("
        SELECT uuid, status, coordinator_uuid
        FROM coordinator_visits WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $visitUuid);
    $stmt->execute();
    $visit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$visit) {
        return ['success' => false, 'error' => 'Visit not found.'];
    }

    if ($visit['coordinator_uuid'] !== $coordinatorUuid) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    if ($visit['status'] === 'completed') {
        return ['success' => false, 'error' => 'This visit is already completed.'];
    }

    if ($visit['status'] === 'cancelled') {
        return ['success' => false, 'error' => 'Cannot complete a cancelled visit.'];
    }

    $studentsJson = !empty($studentsObserved)
        ? json_encode(array_values($studentsObserved))
        : null;

    $stmt = $conn->prepare("
        UPDATE coordinator_visits
        SET status            = 'completed',
            findings          = ?,
            recommendations   = ?,
            students_observed = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param('ssss', $findings, $recommendations, $studentsJson, $visitUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'visit_completed',
        description: 'Company visit marked as completed',
        module: 'visits',
        actorUuid: $_SESSION['user_uuid'] ?? $coordinatorUuid,
        targetUuid: $visitUuid
    );

    return ['success' => true];
}





function cancelVisit(
    $conn,
    string $visitUuid,
    string $coordinatorUuid,
    string $cancelReason
): array {
    if (empty($cancelReason)) {
        return ['success' => false, 'error' => 'Cancel reason is required.'];
    }

    $stmt = $conn->prepare("
        SELECT uuid, status, coordinator_uuid
        FROM coordinator_visits WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $visitUuid);
    $stmt->execute();
    $visit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$visit) {
        return ['success' => false, 'error' => 'Visit not found.'];
    }

    if ($visit['coordinator_uuid'] !== $coordinatorUuid) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    if ($visit['status'] !== 'scheduled') {
        return ['success' => false, 'error' => 'Only scheduled visits can be cancelled.'];
    }

    $stmt = $conn->prepare("
        UPDATE coordinator_visits
        SET status        = 'cancelled',
            cancel_reason = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $cancelReason, $visitUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'visit_cancelled',
        description: "Visit cancelled: {$cancelReason}",
        module: 'visits',
        actorUuid: $_SESSION['user_uuid'] ?? $coordinatorUuid,
        targetUuid: $visitUuid
    );

    return ['success' => true];
}






function updateVisit(
    $conn,
    string $visitUuid,
    string $coordinatorUuid,
    array  $data
): array {
    $stmt = $conn->prepare("
        SELECT uuid, status, coordinator_uuid
        FROM coordinator_visits WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $visitUuid);
    $stmt->execute();
    $visit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$visit) {
        return ['success' => false, 'error' => 'Visit not found.'];
    }

    if ($visit['coordinator_uuid'] !== $coordinatorUuid) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    if ($visit['status'] !== 'scheduled') {
        return ['success' => false, 'error' => 'Only scheduled visits can be edited.'];
    }

    $visitDate = trim($data['visit_date'] ?? '');
    $purpose   = trim($data['purpose']   ?? '');
    $errors    = [];

    if (empty($visitDate)) $errors['visit_date'] = 'Visit date is required.';
    if (empty($purpose))   $errors['purpose']    = 'Purpose is required.';

    if (!empty($visitDate) && $visitDate < date('Y-m-d')) {
        $errors['visit_date'] = 'Visit date must be today or in the future.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $conn->prepare("
        UPDATE coordinator_visits
        SET visit_date = ?, purpose = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param('sss', $visitDate, $purpose, $visitUuid);
    $stmt->execute();
    $stmt->close();

    return ['success' => true];
}





function getCoordinatorVisits(
    $conn,
    string $coordinatorUuid,
    string $batchUuid,
    array  $filters = []
): array {
    $safeCoord = $conn->real_escape_string($coordinatorUuid);
    $safeBatch = $conn->real_escape_string($batchUuid);

    $conditions = [
        "v.coordinator_uuid = '{$safeCoord}'",
        "v.batch_uuid       = '{$safeBatch}'",
    ];

    if (!empty($filters['status'])) {
        $s = $conn->real_escape_string($filters['status']);
        $conditions[] = "v.status = '{$s}'";
    }

    if (!empty($filters['company_uuid'])) {
        $c = $conn->real_escape_string($filters['company_uuid']);
        $conditions[] = "v.company_uuid = '{$c}'";
    }

    $where  = implode(' AND ', $conditions);
    $result = $conn->query("
        SELECT
          v.*,
          c.name AS company_name,
          c.city,
          c.work_setup,

          -- count students observed
          (
            SELECT COUNT(DISTINCT sp.id)
            FROM student_profiles sp
            WHERE sp.coordinator_uuid = v.coordinator_uuid
              AND sp.company_uuid     = v.company_uuid
              AND sp.batch_uuid       = v.batch_uuid
          ) AS assigned_students

        FROM coordinator_visits v
        JOIN companies c ON v.company_uuid = c.uuid
        WHERE {$where}
        ORDER BY
          FIELD(v.status, 'scheduled', 'completed', 'cancelled'),
          v.visit_date DESC
    ");

    $visits = [];
    while ($row = $result->fetch_assoc()) {
        $visits[] = formatVisit($row);
    }

    return $visits;
}





function getAllVisits(
    $conn,
    string $batchUuid,
    array  $filters = []
): array {
    $safeBatch   = $conn->real_escape_string($batchUuid);
    $conditions  = ["v.batch_uuid = '{$safeBatch}'"];

    if (!empty($filters['coordinator_uuid'])) {
        $c = $conn->real_escape_string($filters['coordinator_uuid']);
        $conditions[] = "v.coordinator_uuid = '{$c}'";
    }

    if (!empty($filters['status'])) {
        $s = $conn->real_escape_string($filters['status']);
        $conditions[] = "v.status = '{$s}'";
    }

    if (!empty($filters['company_uuid'])) {
        $c = $conn->real_escape_string($filters['company_uuid']);
        $conditions[] = "v.company_uuid = '{$c}'";
    }

    $where  = implode(' AND ', $conditions);
    $result = $conn->query("
        SELECT
          v.*,
          c.name AS company_name,
          c.city,
          CONCAT(cp.first_name, ' ', cp.last_name) AS coordinator_name,

          (
            SELECT COUNT(DISTINCT sp.id)
            FROM student_profiles sp
            WHERE sp.coordinator_uuid = v.coordinator_uuid
              AND sp.company_uuid     = v.company_uuid
              AND sp.batch_uuid       = v.batch_uuid
          ) AS assigned_students

        FROM coordinator_visits v
        JOIN companies c          ON v.company_uuid     = c.uuid
        JOIN coordinator_profiles cp ON v.coordinator_uuid = cp.uuid
        WHERE {$where}
        ORDER BY v.visit_date DESC
    ");

    $visits = [];
    while ($row = $result->fetch_assoc()) {
        $visit = formatVisit($row);
        $visit['coordinator_name'] = $row['coordinator_name'];
        $visits[] = $visit;
    }

    return $visits;
}





function getVisit($conn, string $visitUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          v.*,
          c.name AS company_name,
          c.city,
          c.address,
          c.work_setup,
          CONCAT(cp.first_name, ' ', cp.last_name) AS coordinator_name

        FROM coordinator_visits v
        JOIN companies c          ON v.company_uuid     = c.uuid
        JOIN coordinator_profiles cp ON v.coordinator_uuid = cp.uuid
        WHERE v.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $visitUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;

    $visit = formatVisit($row);
    $visit['coordinator_name'] = $row['coordinator_name'];
    $visit['company_address']  = $row['address'] ?? '—';

    
    if (!empty($visit['students_observed_raw'])) {
        $studentUuids = json_decode($visit['students_observed_raw'], true) ?? [];
        if (!empty($studentUuids)) {
            $placeholders = implode(',', array_fill(0, count($studentUuids), '?'));
            $types        = str_repeat('s', count($studentUuids));
            $stmt         = $conn->prepare("
                SELECT uuid, first_name, last_name, student_number
                FROM student_profiles
                WHERE uuid IN ({$placeholders})
            ");
            $stmt->bind_param($types, ...$studentUuids);
            $stmt->execute();
            $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $visit['students_observed'] = array_map(fn($s) => [
                'uuid'           => $s['uuid'],
                'full_name'      => $s['first_name'] . ' ' . $s['last_name'],
                'student_number' => $s['student_number'],
            ], $students);
        }
    }

    return $visit;
}






function getVisitableCompanies($conn, string $coordinatorUuid, string $batchUuid): array
{
    $stmt = $conn->prepare("
        SELECT DISTINCT
          c.uuid,
          c.name,
          c.city,
          c.work_setup,
          COUNT(DISTINCT sp.id) AS student_count

        FROM student_profiles sp
        JOIN companies c ON sp.company_uuid = c.uuid
        WHERE sp.coordinator_uuid = ?
          AND sp.batch_uuid       = ?
          AND sp.company_uuid IS NOT NULL
        GROUP BY c.uuid
        ORDER BY c.name ASC
    ");
    $stmt->bind_param('ss', $coordinatorUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(fn($row) => [
        'uuid'          => $row['uuid'],
        'name'          => $row['name'],
        'city'          => $row['city']      ?? '—',
        'work_setup'    => $row['work_setup'],
        'student_count' => (int) $row['student_count'],
        'label'         => $row['name'] . ' (' . $row['student_count'] . ' student' .
                           ($row['student_count'] > 1 ? 's' : '') . ')',
    ], $rows);
}





function formatVisit(array $row): array
{
    $status = $row['status'];

    $statusColors = [
        'scheduled' => ['bg' => '#EFF6FF', 'text' => '#185FA5'],
        'completed' => ['bg' => '#E1F5EE', 'text' => '#0F6E56'],
        'cancelled' => ['bg' => '#F3F4F6', 'text' => '#6B7280'],
    ];
    $colors = $statusColors[$status] ?? ['bg' => '#F3F4F6', 'text' => '#6B7280'];

    $isUpcoming  = $status === 'scheduled' && $row['visit_date'] >= date('Y-m-d');
    $isOverdue   = $status === 'scheduled' && $row['visit_date'] < date('Y-m-d');

    return [
        'uuid'                 => $row['uuid'],
        'coordinator_uuid'     => $row['coordinator_uuid'],
        'company_uuid'         => $row['company_uuid'],
        'company_name'         => $row['company_name'],
        'company_city'         => $row['city'] ?? '—',
        'work_setup'           => $row['work_setup'] ?? '—',
        'visit_date'           => $row['visit_date'],
        'visit_date_label'     => date('D, M j, Y', strtotime($row['visit_date'])),
        'visit_type'           => $row['visit_type'],
        'visit_type_label'     => ucfirst($row['visit_type']),
        'purpose'              => $row['purpose'],
        'status'               => $status,
        'status_label'         => ucfirst($status),
        'status_bg'            => $colors['bg'],
        'status_text'          => $colors['text'],
        'is_upcoming'          => $isUpcoming,
        'is_overdue'           => $isOverdue,
        'findings'             => $row['findings']          ?? null,
        'recommendations'      => $row['recommendations']   ?? null,
        'students_observed_raw'=> $row['students_observed'] ?? null,
        'students_observed'    => [], 
        'cancel_reason'        => $row['cancel_reason']     ?? null,
        'assigned_students'    => (int) ($row['assigned_students'] ?? 0),
        'created_at'           => date('M j, Y', strtotime($row['created_at'])),
        'time_ago'             => timeAgo($row['created_at']),
        
        'can_complete'         => $status === 'scheduled',
        'can_cancel'           => $status === 'scheduled',
        'can_edit'             => $status === 'scheduled',
    ];
}