<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';
require_once '../../functions/auth_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_uuid']) || !in_array($_SESSION['user_role'], ['admin', 'coordinator'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$targetUuid = $_POST['user_uuid'] ?? '';

if (empty($targetUuid)) {
    echo json_encode(['status' => 'error', 'message' => 'User UUID is required.']);
    exit;
}

if ($targetUuid === $_SESSION['user_uuid']) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot lock or unlock your own account.']);
    exit;
}

$roleStmt = $conn->prepare("SELECT role FROM users WHERE uuid = ? LIMIT 1");
$roleStmt->bind_param('s', $targetUuid);
$roleStmt->execute();
$targetRoleRow = $roleStmt->get_result()->fetch_assoc();
$roleStmt->close();

if (!$targetRoleRow) {
    echo json_encode(['status' => 'error', 'message' => 'Target user not found.']);
    exit;
}

if ($targetRoleRow['role'] === 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Administrator accounts cannot be locked or unlocked through this interface.']);
    exit;
}

if ($action === 'unlock') {
    $stmt = $conn->prepare("UPDATE users SET lockout_until = NULL, login_attempts = 0, manual_lockout = 0 WHERE uuid = ?");
    $stmt->bind_param('s', $targetUuid);
    
    if ($stmt->execute()) {
        logActivity($conn, 'account_unlocked', "User $targetUuid unlocked by " . $_SESSION['user_role'], 'security', $_SESSION['user_uuid'], $targetUuid);
        $stmt->close();

        $stmt = $conn->prepare("SELECT email FROM users WHERE uuid = ?");
        $stmt->bind_param('s', $targetUuid);
        $stmt->execute();
        $userData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($userData) {
            $smtpConfig = getEmailSettings($conn);
            $sysConfig = getSystemConfig($conn);
            $schoolName = $sysConfig['school_name'] ?: 'OJT Management System';
            
            $title = "Account Status Update: Unlocked";
            $content = "
                <p>Good news! Your account (<b>{$userData['email']}</b>) has been manually unlocked by an administrator.</p>
                <p>You can now log in to the system using your credentials.</p>
                <div style='margin-top: 30px; text-align: center;'>
                    <a href='" . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}' style='background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600;'>Login to Portal</a>
                </div>
            ";
            
            $emailBody = getEmailTemplate($title, $content, $schoolName);
            sendSystemEmail($smtpConfig, $userData['email'], $title, $emailBody);
        }

        echo json_encode(['status' => 'success', 'message' => 'User account unlocked successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to unlock account.']);
        $stmt->close();
    }
} elseif ($action === 'lock') {

    if ($_SESSION['user_role'] === 'coordinator') {
        
        $checkStmt = $conn->prepare("SELECT role FROM users WHERE uuid = ?");
        $checkStmt->bind_param('s', $targetUuid);
        $checkStmt->execute();
        $targetRole = $checkStmt->get_result()->fetch_assoc()['role'] ?? '';
        $checkStmt->close();

        if ($targetRole === 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Coordinators cannot lock administrator accounts.']);
            exit;
        }
    }
    
    $hours = (int)($_POST['hours'] ?? 1);
    $lockoutUntil = date('Y-m-d H:i:s', strtotime("+$hours hours"));
    
    if ($hours >= 168) {
        $lockoutUntil = '2099-12-31 23:59:59';
    }

    $stmt = $conn->prepare("UPDATE users SET lockout_until = ?, manual_lockout = 1 WHERE uuid = ?");
    $stmt->bind_param('ss', $lockoutUntil, $targetUuid);
    
    if ($stmt->execute()) {
        logActivity($conn, 'account_locked_manual', "User $targetUuid manually locked for $hours hours by Admin", 'security', $_SESSION['user_uuid'], $targetUuid);
        $stmt->close();

        $stmt = $conn->prepare("SELECT email FROM users WHERE uuid = ?");
        $stmt->bind_param('s', $targetUuid);
        $stmt->execute();
        $userData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($userData) {
            $smtpConfig = getEmailSettings($conn);
            $sysConfig = getSystemConfig($conn);
            $schoolName = $sysConfig['school_name'] ?: 'OJT Management System';
            
            $title = "Account Status Update: Restricted";
            $content = "
                <p>We are writing to inform you that your account (<b>{$userData['email']}</b>) has been manually restricted by an administrator.</p>
                <div style='background-color: #fff7ed; border-left: 4px solid #f97316; padding: 16px; margin: 20px 0;'>
                    <p style='margin: 0; color: #9a3412; font-weight: 600;'>Restriction Details:</p>
                    <ul style='margin: 8px 0 0 0; padding-left: 20px; color: #9a3412;'>
                        <li><b>Duration:</b> {$hours} Hours</li>
                        <li><b>Expires:</b> " . date('M d, Y - h:i A', strtotime($lockoutUntil)) . "</li>
                    </ul>
                </div>
                <p>If you believe this is an error, please contact your OJT Coordinator or the system administrator for clarification.</p>
            ";
            
            $emailBody = getEmailTemplate($title, $content, $schoolName);
            sendSystemEmail($smtpConfig, $userData['email'], $title, $emailBody);
        }

        echo json_encode(['status' => 'success', 'message' => 'Account locked successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to lock account.']);
        $stmt->close();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>
