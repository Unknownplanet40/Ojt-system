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
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

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
$dbSettings = $_POST['database_settings'] ?? null;
$clearActivityLog = filter_var($_POST['clear_activity_log'] ?? false, FILTER_VALIDATE_BOOLEAN);
$clearLoginLog = filter_var($_POST['clear_login_log'] ?? false, FILTER_VALIDATE_BOOLEAN);
$adminUuid = $_SESSION['user_uuid'];
$securitySettings = $_POST['security_settings'] ?? null;
$maintenanceSettings = $_POST['maintenance_settings'] ?? null;

if ($maintenanceSettings) {
    if (is_string($maintenanceSettings)) {
        $maintenanceSettings = json_decode($maintenanceSettings, true);
    }
    if ($maintenanceSettings) {
        $features = ['dtr', 'journal', 'evaluation'];
        foreach ($features as $feat) {
            $disableKey = "disable_{$feat}_submission";
            $reasonKey = "{$feat}_disable_reason";
            $startKey = "{$feat}_maintenance_start";
            $endKey = "{$feat}_maintenance_end";

            $oldStatus = getAdminSetting($conn, $disableKey, '0');
            $oldReason = getAdminSetting($conn, $reasonKey, '');
            $oldStart = getAdminSetting($conn, $startKey, '');
            $oldEnd = getAdminSetting($conn, $endKey, '');

            $newStatus = isset($maintenanceSettings[$disableKey]) ? (string)$maintenanceSettings[$disableKey] : $oldStatus;
            $newReason = isset($maintenanceSettings[$reasonKey]) ? (string)$maintenanceSettings[$reasonKey] : $oldReason;
            $newStart = isset($maintenanceSettings[$startKey]) ? (string)$maintenanceSettings[$startKey] : $oldStart;
            $newEnd = isset($maintenanceSettings[$endKey]) ? (string)$maintenanceSettings[$endKey] : $oldEnd;

            $scheduleChanged = ($newStart !== $oldStart) || ($newEnd !== $oldEnd);

            if ($scheduleChanged && !empty($newStart) && !empty($newEnd)) {
                $newStatus = '1';
            }

            if ($newStatus === '0') {
                $newStart = '';
                $newEnd = '';
            }

            // Save new settings
            saveAdminSetting($conn, $disableKey, $newStatus, $adminUuid);
            saveAdminSetting($conn, $reasonKey, $newReason, $adminUuid);
            saveAdminSetting($conn, $startKey, $newStart, $adminUuid);
            saveAdminSetting($conn, $endKey, $newEnd, $adminUuid);

            // Log activity if changed
            if ($oldStatus !== $newStatus || $oldReason !== $newReason || $oldStart !== $newStart || $oldEnd !== $newEnd) {
                $changeDetails = "Admin updated " . strtoupper($feat) . " maintenance. " .
                                 "Status: {$oldStatus} -> {$newStatus}. " .
                                 "Reason: '{$newReason}'. " .
                                 "Schedule: [{$newStart} to {$newEnd}].";
                logActivity($conn, 'maintenance_updated', $changeDetails, 'settings', $adminUuid);

                if ($oldStatus !== $newStatus) {
                    notifyUsersOfMaintenance($conn, $feat, $newStatus, $newReason, $adminUuid);
                }
            }
        }
    }
}

if ($securitySettings) {
    if (is_string($securitySettings)) {
        $securitySettings = json_decode($securitySettings, true);
    }
    if ($securitySettings) {
        saveSecuritySettings($conn, $securitySettings, $adminUuid);
    }
}

if ($dbSettings) {
    if (is_string($dbSettings)) {
        $dbSettings = json_decode($dbSettings, true);
    }
    
    if ($dbSettings) {
        if (isset($dbSettings['log_retention'])) {
            saveAdminSetting($conn, 'db_log_retention', $dbSettings['log_retention'], $adminUuid);
        }
        if (isset($dbSettings['auto_optimize'])) {
            saveAdminSetting($conn, 'db_auto_optimize', $dbSettings['auto_optimize'] ? '1' : '0', $adminUuid);
        }
    }
}


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
