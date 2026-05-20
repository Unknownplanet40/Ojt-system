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
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';
require_once dirname(__DIR__, 2) . '/functions/email_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    response(['status' => 'error', 'message' => 'Invalid request.'], 403);
}

if (!isset($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    response(['status' => 'error', 'message' => 'Unauthorized access.'], 403);
}

$smtpData = [
    'host' => (string)($_POST['host'] ?? ''),
    'port' => (string)($_POST['port'] ?? '587'),
    'user' => (string)($_POST['user'] ?? ''),
    'pass' => (string)($_POST['pass'] ?? ''),
    'crypto' => (string)($_POST['crypto'] ?? 'tls'),
    'from_email' => (string)($_POST['from_email'] ?? ''),
    'from_name' => (string)($_POST['from_name'] ?? 'OJT System'),
];

if (empty($smtpData['host']) || empty($smtpData['user']) || empty($smtpData['pass'])) {
    response(['status' => 'error', 'message' => 'Missing required SMTP configuration (Host, User, or Password).'], 400);
}

$subject = "OJT System - SMTP Connection Test";
$body = "<h1>Success!</h1><p>If you are reading this, your SMTP configuration for the OJT Management System is working correctly.</p><p>Sent at: " . date('Y-m-d H:i:s') . "</p>";

$result = sendSystemEmail($smtpData, $smtpData['from_email'], $subject, $body);

if ($result['success']) {
    response([
        'status' => 'success',
        'message' => 'Test email sent successfully to ' . $smtpData['from_email']
    ]);
} else {
    response([
        'status' => 'error',
        'message' => $result['message']
    ], 500);
}
