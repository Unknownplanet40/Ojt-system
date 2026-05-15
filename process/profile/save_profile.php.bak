<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        http_response_code(403);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    } else {
        error_log(
            "Unauthorized direct access attempt to " .
            basename(__FILE__) . " from " .
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
        );
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/profile_functions.php';
require_once dirname(__DIR__, 2) . '/functions/auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthenticated.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'       => 'critical',
        'message'      => 'Database connection failed.',
        'Details'      => $conn->connect_error ?? 'Unknown error',
        'Suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'critical', 'message' => 'Invalid CSRF token.', 'details' => 'The CSRF token provided is missing or does not match the expected value.', 'suggestion' => 'Please refresh the page and try again. If the issue persists, contact support.']);
}

$userUuid    = $_SESSION['user_uuid'];
$role        = $_SESSION['user_role'];
$base64Image = null;

if (!empty($_POST['profilePhoto'])) {
    $base64Image = $_POST['profilePhoto'];
} elseif (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
    $tmpFile = $_FILES['profilePhoto']['tmp_name'] ?? null;
    if ($tmpFile && is_uploaded_file($tmpFile)) {
        $binaryData = file_get_contents($tmpFile);
        if ($binaryData !== false && strlen($binaryData) > 0) {
            $base64Image = base64_encode($binaryData);
        }
    }
}

$SetupProfile = isset($_POST['setupProfile']) && $_POST['setupProfile'] === 'true' ? true : false;

$data = [
    'first_name'   => trim($_POST['firstName']  ?? ''),
    'last_name'    => trim($_POST['lastName']   ?? ''),
    'middle_name'  => trim($_POST['middleName'] ?? ''),
    'mobile'         => trim($_POST['contactNumber'] ?? ''),
    // for Coordinator
    'department'     => trim($_POST['department'] ?? ''),
    'employee_id'   => trim($_POST['employeeId'] ?? ''),
    // for Student
    'home_address' => trim($_POST['homeAddress'] ?? ''),
    'emergency_contact' => trim($_POST['emergencyContact'] ?? ''),
    'emergency_phone' => trim($_POST['emergencyPhone'] ?? ''),
    'section' => trim($_POST['section'] ?? ''),
    // for Supervisor
    'position' => trim($_POST['position'] ?? ''),
];

$result = saveProfileByRole($conn, $userUuid, $role, $data, $base64Image);

if (!$result['success']) {
    response([
        'status'  => 'error',
        'errors'  => $result['errors'],
        'message' => reset($result['errors']),
    ]);
}

if (!$SetupProfile) {
    $user = [
        'uuid'                 => $_SESSION['user_uuid'],
        'email'                => $_SESSION['user_email'],
        'role'                 => $_SESSION['user_role'],
        'must_change_password' => (int) $_SESSION['must_change_password'],
    ];
    $redirectUrl = getRedirectUrl($conn, $user);
} else {
    $redirectUrl = null;
}

response([
    'status'       => 'success',
    'message'      => $SetupProfile ? 'Profile created successfully.' : 'Profile updated successfully.',
    'redirect_url' => $redirectUrl,
]);
