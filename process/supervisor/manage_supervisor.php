<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once "../../config/db.php";
require_once "../../functions/company_hr_functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$userUuid = $_SESSION['user_uuid'];
$stmt = $conn->prepare("SELECT company_uuid, is_hr_admin FROM supervisor_profiles WHERE user_uuid = ? LIMIT 1");
$stmt->bind_param("s", $userUuid);
$stmt->execute();
$profRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profRow || (int)$profRow['is_hr_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

$companyUuid = $profRow['company_uuid'];
$targetUuid = trim($_POST['target_uuid'] ?? '');
$action = trim($_POST['action'] ?? ''); 

if (empty($targetUuid) || empty($action)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

// Verify target supervisor belongs to the same company
$stmt = $conn->prepare("SELECT user_uuid, is_active, is_hr_admin FROM supervisor_profiles WHERE uuid = ? AND company_uuid = ? LIMIT 1");
$stmt->bind_param("ss", $targetUuid, $companyUuid);
$stmt->execute();
$targetSup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$targetSup) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Supervisor not found in your company']);
    exit;
}

if ($targetSup['user_uuid'] === $userUuid) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot modify your own account permissions here.']);
    exit;
}

if ($action === 'toggle_active') {
    $newStatus = (int)$targetSup['is_active'] === 1 ? 0 : 1;
    
    $stmt = $conn->prepare("UPDATE supervisor_profiles SET is_active = ? WHERE uuid = ?");
    $stmt->bind_param("is", $newStatus, $targetUuid);
    $stmt->execute();
    
    // Also mirror this change to the main users authentication table
    $stmt2 = $conn->prepare("UPDATE users SET is_active = ? WHERE uuid = ?");
    $stmt2->bind_param("is", $newStatus, $targetSup['user_uuid']);
    $stmt2->execute();
    
    $statusText = $newStatus === 1 ? 'activated' : 'deactivated';
    logActivity(
        conn: $conn,
        eventType: 'supervisor_status_toggled',
        description: "Supervisor account was {$statusText}",
        module: 'companies',
        actorUuid: $userUuid,
        targetUuid: $targetUuid
    );
    
    echo json_encode(['status' => 'success', 'message' => "Supervisor successfully {$statusText}."]);
    exit;
    
} elseif ($action === 'toggle_hr') {
    if ((int)$targetSup['is_hr_admin'] === 1) {
        $res = revokeHRAdmin($conn, $targetUuid, $userUuid);
        if ($res['success']) $res['message'] = "HR privileges revoked successfully.";
    } else {
        $res = promoteToHRAdmin($conn, $targetUuid, $userUuid);
        if ($res['success']) $res['message'] = "Supervisor promoted to HR Admin successfully.";
    }
    echo json_encode($res);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
