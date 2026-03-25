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

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed',
        'long_message' => $e->getMessage()
    ]);
}

$token = isset($_POST['token']) ? trim($_POST['token']) : null;

if (empty($token)) {
    response([
        'status' => 'info',
        'message' => 'Token is required.',
        'long_message' => 'The token parameter is missing or empty. Please provide a valid token to proceed.'
    ]);
}

$stmt = $conn->prepare("SELECT user_uuid, expires_at FROM password_reset_tokens WHERE token_hash = ?");
$stmt->bind_param("s", $token);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    response([
        'status' => 'info',
        'message' => 'Invalid token.',
        'long_message' => 'The provided token is invalid. Please check the token and try again.'
    ]);
}

$tokenData = $result->fetch_assoc();
$userId = $tokenData['user_uuid'];
$expiresAt = strtotime($tokenData['expires_at']);
$currentTime = time();

if ($currentTime > $expiresAt) {

    $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token_hash = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    response([
        'status' => 'info',
        'message' => 'Token expired.',
        'long_message' => 'The provided token has expired. Please request a new password reset link.',
    ]);
}

response([
    'status' => 'success',
    'message' => 'Token is valid.',
    'long_message' => 'The provided token is valid and has not expired. You can proceed to reset your password.',
    'user_uuid' => $userId,
    'expires_at' => $expiresAt
]);
