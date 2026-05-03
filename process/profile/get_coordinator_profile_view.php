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
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/auth_functions.php';
require_once dirname(__DIR__, 2) . '/functions/batch_functions.php';
require_once dirname(__DIR__, 2) . '/functions/student_functions.php';
require_once dirname(__DIR__, 2) . '/functions/coordinator_functions.php';
require_once dirname(__DIR__, 2) . '/functions/profile_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'coordinator') {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'       => 'critical',
        'message'      => 'Database connection failed.',
        'Details'      => $conn->connect_error ?? 'Unknown error',
    ]);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'critical', 'message' => 'Invalid CSRF token.']);
}

$userUuid = $_SESSION['user_uuid'];
$profileUuid = $_SESSION['profile_uuid'];

$coordinator = getCoordinator($conn, $profileUuid);
$activeBatch = getActiveBatch($conn);

$students = getAllStudents($conn, null, ['coordinator_uuid' => $profileUuid]);

if (!$coordinator) {
    response([
        'status'  => 'error',
        'message' => 'Profile not found.',
    ]);
}

$profileImage = "https://placehold.co/128x128/C1C1C1/000000/png?text=" . $coordinator['initials'] . "&font=poppins";
if (!empty($coordinator['profile_name'])) {
    $profileImage = "../../../Assets/Images/profiles/" . $coordinator['profile_name'];
}

response([
    'status'       => 'success',
    'profile'      => $coordinator,
    'profileImage' => $profileImage,
    'activeBatch'  => $activeBatch,
    'students'     => $students
]);
