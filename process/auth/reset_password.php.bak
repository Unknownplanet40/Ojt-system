<?php

date_default_timezone_set('Asia/Manila');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'       => 'critical',
        'message'      => 'Database connection failed.',
        'Details'      => $conn->connect_error ?? 'Unknown error',
        'Suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

$token           = trim($_POST['token']            ?? '');
$newPassword     = trim($_POST['new_password']     ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

$result = resetPassword($conn, $token, $newPassword, $confirmPassword);

if (!$result['success']) {
    response([
        'status'  => $result['status'] ?? 'error',
        'message' => $result['message'],
        'error'   => $result['error'] ?? null, // Include error details if available
    ]);
}

response([
    'status'       => 'success',
    'message'      => $result['message'],
    'redirect_url' => '../../Src/Pages/Login',
]);
