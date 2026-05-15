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

$type = isset($_GET['type']) ? $_GET['type'] : null;

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

function getCoordinatorProfile($conn, string $profileUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          cp.uuid,
          cp.user_uuid,
          cp.employee_id,
          cp.last_name,
          cp.first_name,
          cp.middle_name,
          cp.department,
          cp.profile_path,
          cp.mobile,
          cp.created_at,

          u.email,
          u.is_active,
          u.last_login_at,
          u.created_at AS account_created_at

        FROM coordinator_profiles cp
        JOIN users u ON cp.user_uuid = u.uuid
        WHERE cp.user_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $profileUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;

    return [
        'profile_uuid' => $row['uuid'],
        'user_uuid'    => $row['user_uuid'],
        'employee_id'  => $row['employee_id'] ?? '—',
        'full_name'    => $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'],
        'first_name'   => $row['first_name'],
        'last_name'    => $row['last_name'],
        'middle_name'  => $row['middle_name'] ?? '',
        'profile_path' => $row['profile_path'] ?? null,
        'initials'     => strtoupper(substr($row['first_name'],0,1) . substr($row['last_name'],0,1)),
        'department'   => $row['department']  ?? '—',
        'mobile'       => $row['mobile']      ?? '—',
        'email'        => $row['email'],
        'is_active'    => (int) $row['is_active'],
        'last_login'   => $row['last_login_at']
                            ? date('M j, Y g:i A', strtotime($row['last_login_at']))
                            : 'Never',
        'created_at'   => date('M j, Y', strtotime($row['account_created_at'])),
    ];
}

function getCoordinatorStats($conn, string $profileUuid, string $batchUuid = null): array
{
    // auto-fetch active batch
    if (empty($batchUuid)) {
        $result    = $conn->query("SELECT uuid, school_year, semester FROM batches WHERE status = 'active' LIMIT 1");
        $batchRow  = $result->fetch_assoc();
        $batchUuid = $batchRow['uuid']        ?? null;
        $batchLabel = $batchRow
            ? "AY {$batchRow['school_year']} {$batchRow['semester']}"
            : '—';
    }

    $safeBatch    = $conn->real_escape_string($batchUuid ?? '');
    $safeProfile  = $conn->real_escape_string($profileUuid);

    // total students under this coordinator in active batch
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM student_profiles
        WHERE coordinator_uuid = '{$safeProfile}'
          AND batch_uuid = '{$safeBatch}'
    ");
    $totalStudents = (int) $result->fetch_assoc()['total'];

    return [
        'total_students' => $totalStudents,
        'batch_label'    => $batchLabel ?? '—',
    ];
}

function getCoordinatorStudents($conn, string $profileUuid, string $batchUuid = null): array
{
    if (empty($batchUuid)) {
        $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
        $batchRow  = $result->fetch_assoc();
        $batchUuid = $batchRow['uuid'] ?? null;
    }

    $safeBatch   = $conn->real_escape_string($batchUuid ?? '');
    $safeProfile = $conn->real_escape_string($profileUuid);

    $result = $conn->query("
        SELECT
          sp.uuid           AS profile_uuid,
          sp.student_number,
          sp.first_name,
          sp.last_name,
          sp.year_level,
          p.code            AS program_code,
          u.last_login_at,
          u.is_active,
          CASE
            WHEN u.is_active = 0         THEN 'inactive'
            WHEN u.last_login_at IS NULL THEN 'never_logged_in'
            ELSE 'active'
          END AS account_status
        FROM student_profiles sp
        JOIN users u         ON sp.user_uuid     = u.uuid
        LEFT JOIN programs p ON sp.program_uuid  = p.uuid
        WHERE sp.coordinator_uuid = '{$safeProfile}'
          AND sp.batch_uuid       = '{$safeBatch}'
        ORDER BY sp.last_name ASC
    ");

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'profile_uuid'   => $row['profile_uuid'],
            'student_number' => $row['student_number'],
            'full_name'      => $row['first_name'] . ' ' . $row['last_name'],
            'initials'       => strtoupper(substr($row['first_name'],0,1) . substr($row['last_name'],0,1)),
            'program_code'   => $row['program_code'] ?? '—',
            'year_level'     => $row['year_level'],
            'year_label'     => ordinal((int)$row['year_level']) . ' Year',
            'account_status' => $row['account_status'],
            'status_label'   => match($row['account_status']) {
                'active'          => 'Active',
                'inactive'        => 'Inactive',
                'never_logged_in' => 'Never logged in',
                default           => 'Unknown',
            },
        ];
    }

    return $students;
}

function updateCoordinatorProfile($conn, string $profileUuid, array $data, string $actorUuid): array
{
    $errors = [];

    $lastName   = trim($data['last_name']   ?? '');
    $firstName  = trim($data['first_name']  ?? '');
    $middleName = trim($data['middle_name'] ?? '');
    $mobile     = trim($data['mobile']      ?? '');
    $department = trim($data['department']  ?? '');

    if (empty($lastName))  $errors['last_name']  = 'Last name is required.';
    if (empty($firstName)) $errors['first_name'] = 'First name is required.';

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $conn->prepare("
        UPDATE coordinator_profiles
        SET last_name   = ?,
            first_name  = ?,
            middle_name = ?,
            mobile      = ?,
            department  = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param(
        'ssssss',
        $lastName, $firstName, $middleName,
        $mobile, $department, $profileUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'profile_updated',
        description: "{$firstName} {$lastName} updated their coordinator profile",
        module: 'users',
        actorUuid: $actorUuid,
        targetUuid: $profileUuid
    );

    return ['success' => true];
}

$action = isset($_POST['action']) ? $_POST['action'] : null;
$uuid  = isset($_POST['uuid']) ? $_POST['uuid'] : null;

function UUID_convert($conn, $uuid): ?string
{
    $stmt = $conn->prepare("SELECT uuid FROM coordinator_profiles WHERE user_uuid = ?");
    $stmt->bind_param("s",$uuid);
    $stmt->execute();
    $result = $stmt->get_result();
    $coordinatorProfileUuid = null;
    if ($result->num_rows > 0) {
        $coordinatorProfileUuid = $result->fetch_assoc()['uuid'];
    }

    return $coordinatorProfileUuid;
}


if (empty($action)) {
    response([
        'status' => 'info',
        'message' => 'No action specified.',
        'long_message' => 'Please specify an action parameter to determine which operation to perform.'
    ]);
}

if (empty($uuid)) {
    response([
        'status' => 'info',
        'message' => 'No profile UUID provided.',
        'long_message' => 'Please provide the uuid of the coordinator profile to perform this action.'
    ]);
}

if ($action === 'fetch_profile_data') {

    $profileData = getCoordinatorProfile($conn, $uuid);
    if (!$profileData) {
        response([
            'status' => 'error',
            'message' => 'Profile not found.',
            'long_message' => 'No coordinator profile found with the provided UUID.'
        ]);
    }

    $stats = getCoordinatorStats($conn, UUID_convert($conn, $uuid));
    $Students = getCoordinatorStudents($conn, UUID_convert($conn, $uuid));

    response([
        'status' => 'success',
        'data' => [
            'profile' => $profileData,
            'stats'   => $stats,
            'students' => $Students,
        ]
    ]);
}

