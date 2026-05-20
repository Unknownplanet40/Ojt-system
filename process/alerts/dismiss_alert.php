<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_uuid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthenticated.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$alertId  = (int)($_POST['alert_id'] ?? 0);
$userUuid = $_SESSION['user_uuid'];

if ($alertId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid alert ID.']);
    exit;
}

$chk = $conn->prepare("SELECT dismissible, display_type FROM system_alerts WHERE id = ? AND is_active = 1 LIMIT 1");
$chk->bind_param('i', $alertId);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Alert not found.']);
    exit;
}

if ($row['display_type'] === 'banner' && !(int)$row['dismissible']) {
    echo json_encode(['status' => 'error', 'message' => 'This alert cannot be dismissed.']);
    exit;
}

$ins = $conn->prepare("INSERT IGNORE INTO system_alert_dismissals (alert_id, user_uuid) VALUES (?, ?)");
$ins->bind_param('is', $alertId, $userUuid);
$ins->execute();
$ins->close();

echo json_encode(['status' => 'success', 'message' => 'Alert dismissed.']);
?>
