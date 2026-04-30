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

if (!in_array($_SESSION['user_role'], ['admin', 'coordinator', 'student'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

$appUuid   = trim($_POST['application_uuid'] ?? '');
$newStatus = trim($_POST['new_status']       ?? '');
$reason    = trim($_POST['reason']           ?? '');

if (empty($appUuid) || empty($newStatus)) {
    response(['status' => 'error', 'message' => 'Application UUID and new status are required.']);
}

$allowed = ['approved', 'needs_revision', 'rejected', 'withdrawn', 'endorsed', 'active'];
if (!in_array($newStatus, $allowed)) {
    response(['status' => 'error', 'message' => 'Invalid status.']);
}

$result = transitionApplication(
    $conn,
    $appUuid,
    $newStatus,
    $_SESSION['user_uuid'],
    $_SESSION['profile_uuid'],
    $_SESSION['user_role'],
    $reason,
    $_POST
);

if (!$result['success']) {
    response(['status' => 'error', 'message' => $result['error']]);
}

$messages = [
    'approved'       => 'Application approved. Endorsement letter is being generated.',
    'needs_revision' => 'Application returned for revision.',
    'rejected'       => 'Application rejected.',
    'withdrawn'      => 'Application withdrawn.',
    'endorsed'       => 'Application marked as endorsed.',
    'active'         => 'OJT Start Confirmed. Application is now active.',
];

response([
    'status'     => 'success',
    'message'    => $messages[$newStatus] ?? 'Application updated.',
    'new_status' => $newStatus,
]);