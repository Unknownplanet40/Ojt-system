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
require_once dirname(__DIR__, 2) . '/functions/endorsement_functions.php';
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
    exit('Unauthenticated.');
}

if (!in_array($_SESSION['user_role'], ['admin', 'coordinator', 'student'])) {
    http_response_code(403);
    exit('Unauthorized.');
}

$appUuid = trim($_GET['application_uuid'] ?? '');

if (empty($appUuid)) {
    http_response_code(400);
    exit('Application UUID is required.');
}

// get endorsement letter record
$letter = getEndorsementLetter($conn, $appUuid);

if (!$letter) {
    http_response_code(404);
    exit('Endorsement letter not found. Please generate it first.');
}

// scope check for students — only own letter
if ($_SESSION['user_role'] === 'student') {
    $stmt = $conn->prepare("
        SELECT student_uuid FROM ojt_applications WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app || $app['student_uuid'] !== $_SESSION['profile_uuid']) {
        http_response_code(403);
        exit('Unauthorized.');
    }
}

// mark as endorsed on first download (only if currently approved)
$stmt = $conn->prepare("SELECT status FROM ojt_applications WHERE uuid = ? LIMIT 1");
$stmt->bind_param('s', $appUuid);
$stmt->execute();
$appRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($appRow && $appRow['status'] === 'approved') {
    transitionApplication(
        $conn,
        $appUuid,
        'endorsed',
        $_SESSION['user_uuid'],
        $_SESSION['profile_uuid'],
        'system'
    );
}

// serve the file
$absolutePath = dirname(__DIR__, 2) . '/' . $letter['file_path'];

if (!file_exists($absolutePath)) {
    http_response_code(404);
    exit('File not found on server.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $letter['file_name'] . '"');
header('Content-Length: ' . filesize($absolutePath));
header('Cache-Control: private, no-cache');
readfile($absolutePath);
exit;