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
require_once dirname(__DIR__, 2) . '/functions/auth_functions.php';
require_once dirname(__DIR__, 2) . '/functions/batch_functions.php';

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

$userUuid = $_SESSION['user_uuid'];
$role     = $_SESSION['user_role'];

$profile = getProfileByRole($conn, $userUuid, $role);
$active_Batch = getActiveBatch($conn);

if (!$profile) {
    response([
        'status'  => 'error',
        'message' => 'Profile not found.',
        'details' => 'No profile data could be retrieved for the current user.',
        'suggestion' => 'Please ensure your account is properly set up or contact support for assistance.'
    ]);
}

response([
    'status'  => 'success',
    'profile' => $profile,
    'activeBatch' => $active_Batch
]);