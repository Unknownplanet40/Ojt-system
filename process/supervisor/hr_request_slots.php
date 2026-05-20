<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once "../../config/db.php";
require_once "../../helpers/helpers.php";

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$userUuid = $_SESSION['user_uuid'];

$stmt = $conn->prepare("
    SELECT sp.company_uuid, sp.is_hr_admin, c.name as company_name 
    FROM supervisor_profiles sp
    JOIN companies c ON sp.company_uuid = c.uuid
    WHERE sp.user_uuid = ? LIMIT 1
");
$stmt->bind_param("s", $userUuid);
$stmt->execute();
$profRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profRow || (int)$profRow['is_hr_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Not an HR Admin']);
    exit;
}

$companyUuid = $profRow['company_uuid'];
$companyName = $profRow['company_name'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$requestedSlots = (int)($_POST['requested_slots'] ?? 0);
$reason = htmlspecialchars(strip_tags(trim($_POST['reason'] ?? '')));

if ($requestedSlots <= 0 || $requestedSlots > 50) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid number of requested slots. (1-50)']);
    exit;
}

try {
    $conn->begin_transaction();

    logActivity(
        conn: $conn,
        eventType: 'slot_request',
        description: "Requested {$requestedSlots} additional slots. Reason: " . (empty($reason) ? 'None provided' : $reason),
        module: 'companies',
        actorUuid: $userUuid,
        targetUuid: $companyUuid
    );

    $alertTitle = "Slot Request from {$companyName}";
    $alertMessage = "{$companyName} has requested {$requestedSlots} additional intern slots. Reason: " . (empty($reason) ? 'None provided' : $reason);
    $alertType = "info";
    $displayType = "banner";
    $targetRoles = "coordinator";
    
    $stmt = $conn->prepare("
        INSERT INTO system_alerts (title, message, alert_type, display_type, target_roles, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssssss", $alertTitle, $alertMessage, $alertType, $displayType, $targetRoles, $userUuid);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Your slot request has been sent to the university coordinator.']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("HR Slot Request Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while submitting your request.']);
}
