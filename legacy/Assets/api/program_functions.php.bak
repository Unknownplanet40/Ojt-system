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

function createProgram($conn, array $data, string $adminUuid): array
{
    $errors = [];

    $code     = strtoupper(trim($data['code']           ?? ''));
    $name     = trim($data['name']                       ?? '');
    $dept     = trim($data['department']                 ?? '');
    $hours    = (int) ($data['required_hours']           ?? 486);
    $isActive = (int) ($data['is_active']                ?? 1);

    if (empty($code)) {
        $errors['code'] = 'Program code is required.';
    } elseif (!preg_match('/^[A-Z0-9]+$/', $code)) {
        $errors['code'] = 'Code must contain only letters and numbers.';
    } elseif (strlen($code) > 20) {
        $errors['code'] = 'Code must be 20 characters or less.';
    }

    if (empty($name)) {
        $errors['name'] = 'Program name is required.';
    }

    if ($hours < 1) {
        $errors['required_hours'] = 'Required hours must be at least 1.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // check duplicate code
    $stmt = $conn->prepare("SELECT id FROM programs WHERE code = ? LIMIT 1");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        return [
            'success' => false,
            'errors'  => ['code' => "Program code {$code} already exists."]
        ];
    }

    $stmt = $conn->prepare("
        INSERT INTO programs (code, name, department, required_hours, is_active, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssiss', $code, $name, $dept, $hours, $isActive, $adminUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'program_create',
        description: "Admin created program {$code} — {$name}",
        module: 'programs',
        actorUuid: $adminUuid,
        meta: json_encode(['program_code' => $code, 'program_name' => $name])
    );

    return ['success' => true];
}

function editProgram($conn, string $programUuid, array $data, string $adminUuid): array
{
    $errors = [];

    $code     = strtoupper(trim($data['code']     ?? ''));
    $name     = trim($data['name']                 ?? '');
    $dept     = trim($data['department']           ?? '');
    $hours    = (int) ($data['required_hours']     ?? 486);
    $isActive = (int) ($data['is_active']          ?? 1);

    if (empty($code)) {
        $errors['code'] = 'Program code is required.';
    }
    if (empty($name)) {
        $errors['name'] = 'Program name is required.';
    }
    if ($hours < 1) {
        $errors['required_hours'] = 'Required hours must be at least 1.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // check duplicate code — exclude current program
    $stmt = $conn->prepare("SELECT id FROM programs WHERE code = ? AND uuid != ? LIMIT 1");
    $stmt->bind_param('ss', $code, $programUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        return [
            'success' => false,
            'errors'  => ['code' => "Program code {$code} is already used by another program."]
        ];
    }

    $stmt = $conn->prepare("
        UPDATE programs
        SET code           = ?,
            name           = ?,
            department     = ?,
            required_hours = ?,
            is_active      = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param('sssiss', $code, $name, $dept, $hours, $isActive, $programUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'program_updated',
        description: "Admin edited program {$code} — {$name}",
        module: 'programs',
        actorUuid: $adminUuid,
        meta: json_encode(['program_code' => $code, 'program_name' => $name])
    );

    return ['success' => true];
}

function toggleProgram($conn, string $programUuid, string $adminUuid): array
{
    $stmt = $conn->prepare("SELECT code, name, is_active FROM programs WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $programUuid);
    $stmt->execute();
    $prog = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prog) {
        return ['success' => false, 'error' => 'Program not found.'];
    }

    $newStatus = (int) $prog['is_active'] === 1 ? 0 : 1;
    $action    = $newStatus === 1 ? 'enabled' : 'disabled';

    $stmt = $conn->prepare("UPDATE programs SET is_active = ? WHERE uuid = ?");
    $stmt->bind_param('is', $newStatus, $programUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'program_' . strtolower($action),
        description: "Admin {$action} program {$prog['code']} — {$prog['name']}",
        module: 'programs',
        actorUuid: $adminUuid,
        meta: ['program_code' => $prog['code'], 'program_name' => $prog['name'], 'new_status' => $newStatus]
    );

    return ['success' => true, 'new_status' => $newStatus];
}

function getAllPrograms($conn, bool $activeOnly = false): array
{
    $where  = $activeOnly ? 'WHERE is_active = 1' : '';
    $result = $conn->query("
        SELECT uuid, code, name, department, required_hours, is_active, created_at
        FROM programs
        {$where}
        ORDER BY is_active DESC, created_at DESC
    ");

    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = [
            'uuid'           => $row['uuid'],
            'code'           => $row['code'],
            'name'           => $row['name'],
            'department'     => $row['department'] ?? '—',
            'required_hours' => (int) $row['required_hours'],
            'is_active'      => (int) $row['is_active'],
            'status_label'   => (int) $row['is_active'] === 1 ? 'Active' : 'Inactive',
            'created_at'     => date('M j, Y', strtotime($row['created_at'])),
        ];
    }

    return $programs;
}

function getProgramHours($conn, string $programUuid): int
{
    $stmt = $conn->prepare("SELECT required_hours FROM programs WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $programUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? (int) $row['required_hours'] : 486;
}

if ($action === 'program_create') {
    $AdminUuid = $_SESSION['user']['uuid'] ?? null;
    if (!$AdminUuid) {
        response([
            'status' => 'error',
            'message' => 'Unauthorized',
            'long_message' => 'You must be logged in to perform this action.'
        ]);
    }
    $data = [
      'code'           => isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : null,
      'name'           => isset($_POST['name']) ? trim($_POST['name']) : null,
      'department'     => isset($_POST['department']) ? trim($_POST['department']) : null,
      'required_hours' => isset($_POST['required_hours']) ? (int) $_POST['required_hours'] : 486,
      'is_active'      => isset($_POST['activate_immediately']) ? (int) $_POST['activate_immediately'] : 1,
    ];

    $result = createProgram($conn, $data, $AdminUuid);

    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Program created successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['errors']['code'] ?? $result['errors']['name'] ?? $result['errors']['required_hours'] ?? 'Failed to create program.',
            'long_message' => $result['errors'] ?? ['An unknown error occurred while trying to create the program.']
        ]);
    }
} elseif ($action === 'program_edit') {
    $AdminUuid = $_SESSION['user']['uuid'] ?? null;
    if (!$AdminUuid) {
        response([
            'status' => 'error',
            'message' => 'Unauthorized',
            'long_message' => 'You must be logged in to perform this action.'
        ]);
    }

    $ProgramUuid = $_POST['program_uuid'] ?? null;
    if (!$ProgramUuid) {
        response([
            'status' => 'error',
            'message' => 'Program UUID is required.',
            'long_message' => 'The "program_uuid" parameter is required to identify which program to edit or toggle.'
        ]);
    }

    $data = [
        'code'           => isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : null,
        'name'           => isset($_POST['name']) ? trim($_POST['name']) : null,
        'department'     => isset($_POST['department']) ? trim($_POST['department']) : null,
        'required_hours' => isset($_POST['required_hours']) ? (int) $_POST['required_hours'] : 486,
        'is_active'      => isset($_POST['activate_immediately']) ? (int) $_POST['activate_immediately'] : 1,
    ];

    $result = editProgram($conn, $ProgramUuid, $data, $AdminUuid);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Program updated successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['errors']['code'] ?? $result['errors']['name'] ?? $result['errors']['required_hours'] ?? 'Failed to update program.',
            'long_message' => $result['errors'] ?? ['An unknown error occurred while trying to update the program.']
        ]);
    }

} elseif ($action === 'program_toggle') {
    $AdminUuid = $_SESSION['user']['uuid'] ?? null;
    if (!$AdminUuid) {
        response([
            'status' => 'error',
            'message' => 'Unauthorized',
            'long_message' => 'You must be logged in to perform this action.'
        ]);
    }

    $ProgramUuid = $_POST['program_uuid'] ?? null;
    if (!$ProgramUuid) {
        response([
            'status' => 'error',
            'message' => 'Program UUID is required.',
            'long_message' => 'The "program_uuid" parameter is required to identify which program to edit or toggle.'
        ]);
    }

    $result = toggleProgram($conn, $ProgramUuid, $AdminUuid);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => "Program has been " . ($result['new_status'] === 1 ? 'enabled' : 'disabled') . ".",
            'new_status' => $result['new_status']
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['error'] ?? 'Failed to toggle program status.',
            'long_message' => $result['error'] ?? 'An unknown error occurred while trying to toggle the program status.'
        ]);
    }

} elseif ($action === 'fetch_programs') {
    $programs = getAllPrograms($conn);
    response([
        'status' => 'success',
        'programs' => $programs
    ]);
} else {
    response([
        'status' => 'error',
        'message' => 'Invalid action specified.',
        'long_message' => "The action '{$action}' is not recognized. Please specify a valid action."
    ]);
}
