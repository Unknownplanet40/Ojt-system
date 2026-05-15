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
require_once dirname(__DIR__, 2) . '/functions/settings_functions.php';

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

if (!$conn || $conn->connect_error) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed.',
        'details' => $conn->connect_error ?? 'Unknown error',
    ], 500);
}

$theme = (string)($_POST['theme'] ?? '');
$emailSettings = $_POST['email_settings'] ?? null;
$instSettings = $_POST['institutional_settings'] ?? null;
$clearActivityLog = filter_var($_POST['clear_activity_log'] ?? false, FILTER_VALIDATE_BOOLEAN);
$clearLoginLog = filter_var($_POST['clear_login_log'] ?? false, FILTER_VALIDATE_BOOLEAN);
$adminUuid = $_SESSION['user_uuid'];


$themeResult = saveUserTheme($conn, $theme, $adminUuid);
if (!$themeResult['success']) {
    response(['status' => 'error', 'message' => 'Failed to save theme: ' . $themeResult['message']], 500);
}


if ($instSettings) {
    if (is_string($instSettings)) {
        $instSettings = json_decode($instSettings, true);
    }
    
    if ($instSettings) {
        $instResult = saveSystemConfig($conn, $instSettings);
        if (!$instResult['success']) {
            response(['status' => 'error', 'message' => 'Failed to save institutional profile: ' . $instResult['message']], 500);
        }
    }
}


$uploadDir = dirname(__DIR__, 2) . '/Assets/Images/systemImages/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

foreach (['logo_1', 'logo_2'] as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
        $fileName = "{$field}_" . time() . ".{$ext}";
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
            updateSystemLogo($conn, $field, $fileName);
        }
    }
}


if ($emailSettings) {
    if (is_string($emailSettings)) {
        $emailSettings = json_decode($emailSettings, true);
    }
    
    if ($emailSettings) {
        $emailResult = saveEmailSettings($conn, $emailSettings, $adminUuid);
        if (!$emailResult['success']) {
            response(['status' => 'error', 'message' => 'Failed to save email settings: ' . $emailResult['message']], 500);
        }
    }
}


if ($clearActivityLog) {
    if (!$conn->query("TRUNCATE TABLE activity_log")) {
        response(['status' => 'error', 'message' => 'Failed to clear activity log.'], 500);
    }
}

if ($clearLoginLog) {
    if (!$conn->query("TRUNCATE TABLE login_audit_log")) {
        response(['status' => 'error', 'message' => 'Failed to clear login audit log.'], 500);
    }
}

response([
    'status' => 'success',
    'message' => 'All settings saved successfully.',
    'settings' => [
        'theme' => $themeResult['theme'],
    ],
]);
