<?php

require_once 'ServerConfig.php';

// Prevent direct access to this file
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    // Only allow AJAX requests
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/dbconfig.php';

function response($data)
{
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response([
        'status' => 'info',
        'message' => 'Request method is not allowed.',
        'long_message' => 'Only POST requests are allowed for this endpoint.'
    ]);
}

if (!isModRewriteEnabled()) {
    response([
        'status' => 'critical',
        'message' => 'mod_rewrite is disabled.',
        'long_message' => 'mod_rewrite is disabled. Required for clean URLs. Enable it in httpd.conf and restart Apache.'
    ]);
}

$action = isset($_POST['action']) ? $_POST['action'] : null;
if (!$action) {
    response([
        'status' => 'error',
        'message' => 'No action specified.',
        'long_message' => 'The "action" parameter is required to specify which operation to perform.'
    ]);
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed',
        'long_message' => $e->getMessage()
    ]);
}

require_once 'helpers.php';

function createBatch($conn, array $data, string $adminUuid): array
{
    $errors = [];

    if (empty($data['school_year'])) {
        $errors['school_year'] = 'School year is required.';
    }
    if (empty($data['semester'])) {
        $errors['semester'] = 'Semester is required.';
    }
    if (empty($data['start_date'])) {
        $errors['start_date'] = 'Start date is required.';
    }
    if (empty($data['end_date'])) {
        $errors['end_date'] = 'End date is required.';
    }
    if (!empty($data['start_date']) && !empty($data['end_date'])) {
        if ($data['start_date'] >= $data['end_date']) {
            $errors['end_date'] = 'End date must be after start date.';
        }
    }

    // check for duplicate school year + semester
    $stmt = $conn->prepare("
        SELECT id FROM batches
        WHERE school_year = ? AND semester = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $data['school_year'], $data['semester']);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $errors['semester'] = 'A batch for this school year and semester already exists.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $requiredHours = (int) ($data['required_hours'] ?? 486);

    $batchUuid = generateUuid();

    $stmt = $conn->prepare("
        INSERT INTO batches
          (uuid, school_year, semester, start_date, end_date, required_hours, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, 'upcoming', ?)
    ");
    $stmt->bind_param(
        'sssssis',
        $batchUuid,
        $data['school_year'],
        $data['semester'],
        $data['start_date'],
        $data['end_date'],
        $requiredHours,
        $adminUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'batch_created',
        description: "Admin created batch AY {$data['school_year']} {$data['semester']} Semester",
        module: 'system',
        actorUuid: $adminUuid,
        targetUuid: $batchUuid,
        meta: [
            'school_year' => $data['school_year'],
            'semester'    => $data['semester']
        ]
    );

    return ['success' => true, 'uuid' => $batchUuid];
}

function updateBatch($conn, string $batchUuid, array $data, string $adminUuid): array
{
    $stmt = $conn->prepare("
        SELECT status, school_year, semester
        FROM batches
        WHERE uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
        return ['success' => false, 'errors' => ['general' => 'Batch not found.']];
    }

    if ($current['status'] === 'closed') {
        return ['success' => false, 'errors' => ['general' => 'Closed batches cannot be edited.']];
    }

    $errors = [];

    $schoolYear = trim($data['school_year'] ?? '');
    $semester   = trim($data['semester']    ?? '');
    $startDate  = trim($data['start_date']  ?? '');
    $endDate    = trim($data['end_date']    ?? '');
    $reqHours   = (int) ($data['required_hours'] ?? 486);

    if (empty($schoolYear)) {
        $errors['school_year'] = 'School year is required.';
    } elseif (!preg_match('/^\d{4}-\d{4}$/', $schoolYear)) {
        $errors['school_year'] = 'Format must be YYYY-YYYY (e.g. 2025-2026).';
    } else {
        [$y1, $y2] = explode('-', $schoolYear);
        if ((int)$y2 !== (int)$y1 + 1) {
            $errors['school_year'] = 'Second year must be one year after the first.';
        }
    }

    if (empty($semester) || !in_array($semester, ['1st', '2nd', 'Summer'])) {
        $errors['semester'] = 'Select a valid semester.';
    }

    if (empty($startDate)) {
        $errors['start_date'] = 'Start date is required.';
    }

    if (empty($endDate)) {
        $errors['end_date'] = 'End date is required.';
    }

    if (!empty($startDate) && !empty($endDate) && $startDate >= $endDate) {
        $errors['end_date'] = 'End date must be after start date.';
    }

    if ($reqHours < 1) {
        $errors['required_hours'] = 'Required hours must be at least 1.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            SELECT id FROM batches
            WHERE school_year = ? AND semester = ? AND uuid != ?
            LIMIT 1
        ");
        $stmt->bind_param('sss', $schoolYear, $semester, $batchUuid);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $errors['semester'] = 'A batch for this school year and semester already exists.';
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $conn->prepare("
        UPDATE batches
        SET school_year    = ?,
            semester       = ?,
            start_date     = ?,
            end_date       = ?,
            required_hours = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param(
        'ssssis',
        $schoolYear,
        $semester,
        $startDate,
        $endDate,
        $reqHours,
        $batchUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'batch_updated',
        description: "Admin updated batch AY {$schoolYear} {$semester} Semester",
        module: 'system',
        actorUuid: $adminUuid,
        targetUuid: $batchUuid,
        meta: [
            'school_year' => $schoolYear,
            'semester'    => $semester
        ]
    );

    return ['success' => true, 'uuid' => $batchUuid];
}

function activateBatch($conn, string $batchUuid, string $adminUuid): array
{
    // check batch exists and is upcoming
    $stmt = $conn->prepare("
        SELECT uuid, status, school_year, semester
        FROM batches WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$batch) {
        return ['success' => false, 'error' => 'Batch not found.'];
    }
    if ($batch['status'] === 'active') {
        return ['success' => false, 'error' => 'This batch is already active.'];
    }
    if ($batch['status'] === 'closed') {
        return ['success' => false, 'error' => 'A closed batch cannot be reactivated.'];
    }

    // close any currently active batch first
    $stmt = $conn->prepare("
        UPDATE batches
        SET status    = 'closed',
            closed_by = ?,
            closed_at = NOW()
        WHERE status = 'active'
    ");
    $stmt->bind_param('s', $adminUuid);
    $stmt->execute();
    $stmt->close();

    // activate the new batch
    $stmt = $conn->prepare("
        UPDATE batches
        SET status       = 'active',
            activated_by = ?,
            activated_at = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $adminUuid, $batchUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'batch_activated',
        description: "Admin activated batch AY {$batch['school_year']} {$batch['semester']} Semester",
        module: 'system',
        actorUuid: $adminUuid,
        targetUuid: $batchUuid,
    );

    return ['success' => true];
}

function closeBatch($conn, string $batchUuid, string $adminUuid): array
{
    $stmt = $conn->prepare("
        SELECT uuid, status, school_year, semester
        FROM batches WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$batch) {
        return ['success' => false, 'error' => 'Batch not found.'];
    }
    if ($batch['status'] === 'closed') {
        return ['success' => false, 'error' => 'Batch is already closed.'];
    }
    if ($batch['status'] === 'upcoming') {
        return ['success' => false, 'error' => 'Cannot close an upcoming batch — activate it first.'];
    }

    $stmt = $conn->prepare("
        UPDATE batches
        SET status    = 'closed',
            closed_by = ?,
            closed_at = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $adminUuid, $batchUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'batch_closed',
        description: "Admin closed batch AY {$batch['school_year']} {$batch['semester']} Semester",
        module: 'system',
        actorUuid: $adminUuid,
        targetUuid: $batchUuid,
    );

    return ['success' => true];
}

function getAllBatches($conn): array
{
    $result = $conn->query("
        SELECT
          b.uuid,
          b.school_year,
          b.semester,
          b.start_date,
          b.end_date,
          b.required_hours,
          b.status,
          b.created_at,
          b.activated_at,
          b.closed_at,

          -- student count for this batch
          COUNT(sp.id) AS student_count,

          -- who created it
          CONCAT(COALESCE(ap.first_name,''), ' ', SUBSTRING(COALESCE(ap.last_name,''),1,1), '.') AS created_by_name

        FROM batches b
        LEFT JOIN student_profiles sp ON b.uuid = sp.batch_uuid
        LEFT JOIN users u             ON b.created_by = u.uuid
        LEFT JOIN admin_profiles ap   ON u.uuid = ap.user_uuid

        GROUP BY b.id
        ORDER BY b.created_at DESC
    ");

    $batches = [];
    while ($row = $result->fetch_assoc()) {
        $batches[] = [
            'uuid'           => $row['uuid'],
            'label'          => "AY {$row['school_year']} {$row['semester']} Semester",
            'school_year'    => $row['school_year'],
            'semester'       => $row['semester'],
            'start_date'     => $row['start_date']
                                  ? date('M j, Y', strtotime($row['start_date']))
                                  : '—',
            'end_date'       => $row['end_date']
                                  ? date('M j, Y', strtotime($row['end_date']))
                                  : '—',
            'required_hours' => (int) $row['required_hours'],
            'status'         => $row['status'],
            'status_label'   => ucfirst($row['status']),
            'student_count'  => (int) $row['student_count'],
            'created_by'     => trim($row['created_by_name']) ?: 'System',
            'created_at'     => date('M j, Y', strtotime($row['created_at'])),
            'activated_at'   => $row['activated_at']
                                  ? date('M j, Y', strtotime($row['activated_at']))
                                  : null,
            'closed_at'      => $row['closed_at']
                                  ? date('M j, Y', strtotime($row['closed_at']))
                                  : null,
        ];
    }

    return $batches;
}

function getActiveBatch($conn): ?array
{
    $result = $conn->query("
        SELECT uuid, school_year, semester, start_date, end_date, required_hours
        FROM batches
        WHERE status = 'active'
        LIMIT 1
    ");

    $row = $result->fetch_assoc();
    if (!$row) {
        return null;
    }

    return [
        'uuid'           => $row['uuid'],
        'label'          => "AY {$row['school_year']} {$row['semester']} Semester",
        'school_year'    => $row['school_year'],
        'semester'       => $row['semester'],
        'required_hours' => (int) $row['required_hours'],
        'start_date'     => $row['start_date'],
        'end_date'       => $row['end_date'],
    ];
}

function getBatchStudents($conn, string $batchUuid): array
{
    $stmt = $conn->prepare("
        SELECT
          sp.uuid            AS profile_uuid,
          sp.student_number,
          sp.first_name,
          sp.last_name,
          sp.year_level,
          sp.section,

          u.uuid             AS user_uuid,
          u.email,
          u.is_active,
          u.last_login_at,

          p.code             AS program_code,
          p.name             AS program_name,
          p.department       AS program_department,

          CONCAT(cp.first_name, ' ', cp.last_name) AS coordinator_name,

          CASE
            WHEN u.is_active = 0         THEN 'inactive'
            WHEN u.last_login_at IS NULL THEN 'never_logged_in'
            ELSE 'active'
          END AS account_status

        FROM student_profiles sp
        JOIN users u
          ON sp.user_uuid = u.uuid
        LEFT JOIN programs p
          ON sp.program_uuid = p.uuid
        LEFT JOIN coordinator_profiles cp
          ON sp.coordinator_uuid = cp.uuid
        WHERE sp.batch_uuid = ?
        ORDER BY sp.last_name ASC, sp.first_name ASC
    ");

    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(fn ($row) => [
        'profile_uuid'        => $row['profile_uuid'],
        'user_uuid'           => $row['user_uuid'],
        'student_number'      => $row['student_number'],
        'full_name'           => $row['first_name'] . ' ' . $row['last_name'],
        'initials'            => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)),
        'email'               => $row['email'],
        'program_code'        => $row['program_code'] ?? '—',
        'program_name'        => $row['program_name'] ?? '—',
        'program_department'  => $row['program_department'] ?? '—',
        'year_level'          => $row['year_level'],
        'year_label'          => ordinal((int)$row['year_level']) . ' Year',
        'section'             => $row['section'] ?? '—',
        'coordinator'         => $row['coordinator_name'] ?? '—',
        'is_active'           => (int) $row['is_active'],
        'account_status'      => $row['account_status'],
        'status_label'        => match($row['account_status']) {
            'active'          => 'Active',
            'inactive'        => 'Inactive',
            'never_logged_in' => 'Never logged in',
            default           => 'Unknown',
        },
        'last_login'          => $row['last_login_at']
                                   ? date('M j, Y', strtotime($row['last_login_at']))
                                   : null,
    ], $rows);
}

if ($action === 'create_batch') {
    $adminUuid = isset($_SESSION['user']['uuid']) ? $_SESSION['user']['uuid'] : null;
    if (!$adminUuid) {
        response([
            'status' => 'error',
            'message' => 'Admin UUID is required.',
            'long_message' => 'To create a batch, the admin must be logged in and their UUID must be available in the session.'
        ]);
    }

    $data = [
        'school_year'    => isset($_POST['school_year']) ? $_POST['school_year'] : null,
        'semester'       => isset($_POST['semester']) ? $_POST['semester'] : null,
        'start_date'     => isset($_POST['start_date']) ? $_POST['start_date'] : null,
        'end_date'       => isset($_POST['end_date']) ? $_POST['end_date'] : null,
        'required_hours' => isset($_POST['required_hours']) ? (int) $_POST['required_hours'] : 486,
    ];

    $result = createBatch($conn, $data, $adminUuid);
    if ($result['success']) {
        if (isset($_POST['activate_immediately']) && $_POST['activate_immediately'] == '1') {
            $activateResult = activateBatch($conn, $result['uuid'], $adminUuid);
            if (!$activateResult['success']) {
                response([
                    'status' => 'warning',
                    'message' => 'Batch created but failed to activate.',
                    'long_message' => $activateResult['error']
                ]);
            }
        }

        response([
            'status' => 'success',
            'message' => 'Batch created successfully.',
            'data' => [
                'batch_uuid' => $result['uuid']
            ]
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to create batch.',
            'errors' => $result['errors']
        ]);
    }
} elseif ($action === 'edit_batch') {
    $batchUuid = isset($_POST['batch_uuid']) ? $_POST['batch_uuid'] : null;
    $adminUuid = isset($_SESSION['user']['uuid']) ? $_SESSION['user']['uuid'] : null;

    if (!$batchUuid || !$adminUuid) {
        response([
            'status' => 'error',
            'message' => 'Batch UUID and admin UUID are required.',
            'long_message' => 'To edit a batch, both the batch UUID and the admin UUID must be provided.'
        ]);
    }

    $data = [
        'school_year'    => isset($_POST['school_year']) ? $_POST['school_year'] : null,
        'semester'       => isset($_POST['semester']) ? $_POST['semester'] : null,
        'start_date'     => isset($_POST['start_date']) ? $_POST['start_date'] : null,
        'end_date'       => isset($_POST['end_date']) ? $_POST['end_date'] : null,
        'required_hours' => isset($_POST['required_hours']) ? (int) $_POST['required_hours'] : 486,
    ];

    $result = updateBatch($conn, $batchUuid, $data, $adminUuid);
    if ($result['success']) {
        if (isset($_POST['activate_immediately']) && $_POST['activate_immediately'] == '1') {
            $activateResult = activateBatch($conn, $batchUuid, $adminUuid);
            if (!$activateResult['success']) {
                response([
                    'status' => 'warning',
                    'message' => 'Batch updated but failed to activate.',
                    'long_message' => $activateResult['error']
                ]);
            }
        }

        response([
            'status' => 'success',
            'message' => 'Batch updated successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to update batch.',
            'errors' => $result['errors']
        ]);
    }
} elseif ($action === 'activate_batch') {
    $batchUuid = isset($_POST['batch_uuid']) ? $_POST['batch_uuid'] : null;
    $adminUuid = isset($_SESSION['user']['uuid']) ? $_SESSION['user']['uuid'] : null;

    if (!$batchUuid || !$adminUuid) {
        response([
            'status' => 'error',
            'message' => 'Batch UUID and admin UUID are required.',
            'long_message' => 'To activate a batch, both the batch UUID and the admin UUID must be provided.'
        ]);
    }

    $result = activateBatch($conn, $batchUuid, $adminUuid);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Batch activated successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to activate batch.',
            'long_message' => $result['error']
        ]);
    }
} elseif ($action === 'close_batch') {
    $batchUuid = isset($_POST['batch_uuid']) ? $_POST['batch_uuid'] : null;
    $adminUuid = isset($_SESSION['user']['uuid']) ? $_SESSION['user']['uuid'] : null;

    if (!$batchUuid || !$adminUuid) {
        response([
            'status' => 'error',
            'message' => 'Batch UUID and admin UUID are required.',
            'long_message' => 'To close a batch, both the batch UUID and the admin UUID must be provided.'
        ]);
    }

    $result = closeBatch($conn, $batchUuid, $adminUuid);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Batch closed successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to close batch.',
            'long_message' => $result['error']
        ]);
    }

} elseif ($action === 'get_batches') {
    $batches = getAllBatches($conn);
    $activeBatch = getActiveBatch($conn);
    response([
        'status' => 'success',
        'data' => [
            'batches' => $batches,
            'active_batch' => $activeBatch
        ]
    ]);
} elseif ($action === 'get_batch_students') {
    $batch_uuid = isset($_POST['batch_uuid']) ? $_POST['batch_uuid'] : null;
    if (!$batch_uuid) {
        response([
            'status' => 'error',
            'message' => 'Batch UUID is required.',
            'long_message' => 'To get students of a batch, the batch UUID must be provided.'
        ]);
    }

    $students = getBatchStudents($conn, $batch_uuid);
    response([
        'status' => 'success',
        'data' => [
            'students' => $students
        ]
    ]);
} else {
    response([
        'status' => 'error',
        'message' => 'Invalid action specified.',
        'long_message' => "The action '$action' is not recognized. Please provide a valid action."
    ]);
}
