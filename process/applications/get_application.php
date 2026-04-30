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
require_once dirname(__DIR__, 2) . '/functions/application_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'       => 'critical',
        'message'      => 'Database connection failed.',
        'Details'      => $conn->connect_error ?? 'Unknown error',
        'Suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthenticated.']);
}

$role = $_SESSION['user_role'];

if ($role === 'student') {
    $studentUuid = $_SESSION['profile_uuid'];
    $batchUuid   = $_SESSION['active_batch_uuid'] ?? '';
} elseif (in_array($role, ['coordinator', 'admin'])) {
    $studentUuid = trim($_POST['student_uuid'] ?? '');
    $batchUuid   = trim($_POST['batch_uuid']   ?? '');
} else {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (empty($studentUuid) || empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'Student UUID and batch UUID are required.']);
}

$application = getStudentApplication($conn, $studentUuid, $batchUuid);
$history     = $application
    ? getApplicationHistory($conn, $application['uuid'])
    : [];

$requirementsComplete = false;
$requirementsStatus   = [];
if ($role === 'student') {
    $requirementsComplete = canStudentApply($conn, $studentUuid, $batchUuid);
    if (!$requirementsComplete) {
        $requirementsStatus = getStudentRequirements($conn, $studentUuid, $batchUuid);
    }
}

response([
    'status'      => 'success',
    'application' => $application,
    'history'     => $history,
    'has_application' => $application !== null,
    'requirements_complete' => $requirementsComplete,
    'requirements' => $requirementsStatus
]);
