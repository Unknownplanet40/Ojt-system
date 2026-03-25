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

if (!isset($_SESSION['user']['uuid'])) {
    response([
        'status' => 'error',
        'message' => 'Unauthorized access.',
        'long_message' => 'User is not authenticated. Please log in to change your password.'
    ]);
}

require_once 'logs.php';

$userUuid = isset($_SESSION['user']['uuid']) ? $_SESSION['user']['uuid'] : null;
$type = isset($_POST['type']) ? trim($_POST['type']) : null;
$newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : null;
$tempPassword = isset($_POST['tempPassword']) ? $_POST['tempPassword'] : null;
$currentPassword = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : null;

if ($type === null) {
    response([
        'status' => 'error',
        'message' => 'Missing required fields.',
        'long_message' => 'The "type" field is required to determine the password change mode.'
    ]);
}

if ($newPassword === null) {
    response([
        'status' => 'error',
        'message' => 'Missing required fields.',
        'long_message' => 'The "newPassword" field is required.'
    ]);
}

$validTypes = ['forced', 'voluntary'];
if (!in_array($type, $validTypes)) {
    response([
        'status' => 'error',
        'message' => 'Invalid type value.',
        'long_message' => 'The "type" field must be either "forced" or "voluntary".'
    ]);
}

if ($type === 'forced') {
    if (empty($tempPassword)) {
        response([
            'status' => 'error',
            'message' => 'Missing required fields.',
            'long_message' => 'The "tempPassword" field is required for forced password changes.'
        ]);
    }

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE uuid = ?");
    $stmt->bind_param("s", $userUuid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        response([
            'status' => 'error',
            'message' => 'User not found.',
            'long_message' => 'No user found with the provided UUID.'
        ]);
    }

    $user = $result->fetch_assoc();
    if (!password_verify($tempPassword, $user['password_hash'])) {
        response([
            'status' => 'error',
            'message' => 'Incorrect temporary password.',
            'long_message' => 'The temporary password you entered is incorrect. Please check and try again.'
        ]);
    }

    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $meta = [
        'change_type' => $type,
        'timestamp' => date('Y-m-d H:i:s'),
        'OldPasswordHash' => $user['password_hash'],
        'NewPasswordHash' => $newPasswordHash
    ];

    $updateStmt = $conn->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE uuid = ?");
    $updateStmt->bind_param("ss", $newPasswordHash, $userUuid);
    if ($updateStmt->execute()) {
        auditLog("password_change", "User changed their password", "authentication", $userUuid, $userUuid, $meta);
        session_destroy();
        response([
            'status' => 'success',
            'message' => 'Password changed successfully.',
            'long_message' => 'Your password has been changed successfully. You can now log in with your new password.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to change password.',
            'long_message' => 'An error occurred while changing your password. Please try again later.'
        ]);
    }
} elseif ($type === 'voluntary') {
    if (empty($currentPassword)) {
        response([
            'status' => 'error',
            'message' => 'Missing required fields.',
            'long_message' => 'The "currentPassword" field is required for voluntary password changes.'
        ]);
    }

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE uuid = ?");
    $stmt->bind_param("s", $userUuid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        response([
            'status' => 'error',
            'message' => 'User not found.',
            'long_message' => 'No user found with the provided UUID.'
        ]);
    }

    $user = $result->fetch_assoc();
    if (!password_verify($currentPassword, $user['password_hash'])) {
        response([
            'status' => 'error',
            'message' => 'Incorrect current password.',
            'long_message' => 'The current password you entered is incorrect. Please check and try again.'
        ]);
    }

    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $meta = [
        'change_type' => $type,
        'timestamp' => date('Y-m-d H:i:s'),
        'OldPasswordHash' => $user['password_hash'],
        'NewPasswordHash' => $newPasswordHash
    ];

    $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE uuid = ?");
    $updateStmt->bind_param("ss", $newPasswordHash, $userUuid);
    if ($updateStmt->execute()) {
        auditLog("password_change", "User changed their password", "authentication", $userUuid, $userUuid, $meta);
        session_destroy();
        response([
            'status' => 'success',
            'message' => 'Password changed successfully.',
            'long_message' => 'Your password has been changed successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to change password.',
            'long_message' => 'An error occurred while changing your password. Please try again later.'
        ]);
    }
}
