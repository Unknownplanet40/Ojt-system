<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';

header('Content-Type: application/json');

// Admin only
if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'list') {
    $res = $conn->query("
        SELECT
            sa.id,
            sa.title,
            sa.message,
            sa.alert_type,
            sa.display_type,
            sa.target_roles,
            sa.is_active,
            sa.dismissible,
            sa.expires_at,
            sa.created_at,
            COALESCE(
                CONCAT(ap.first_name, ' ', ap.last_name),
                u.email
            ) AS created_by_name,
            (SELECT COUNT(*) FROM system_alert_dismissals WHERE alert_id = sa.id) AS dismiss_count
        FROM system_alerts sa
        LEFT JOIN users u ON u.uuid = sa.created_by
        LEFT JOIN admin_profiles ap ON ap.user_uuid = u.uuid
        ORDER BY sa.id DESC
    ");
    $alerts = $res->fetch_all(MYSQLI_ASSOC);
    foreach ($alerts as &$a) {
        $a['id']           = (int)$a['id'];
        $a['is_active']    = (bool)(int)$a['is_active'];
        $a['dismissible']  = (bool)(int)$a['dismissible'];
        $a['dismiss_count']= (int)$a['dismiss_count'];
    }
    echo json_encode(['status' => 'success', 'alerts' => $alerts]);
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title']        ?? '');
    $message      = trim($_POST['message']      ?? '');
    $alertType    = $_POST['alert_type']   ?? 'info';
    $displayType  = $_POST['display_type'] ?? 'banner';
    $targetRoles  = trim($_POST['target_roles'] ?? 'all');
    $dismissible  = isset($_POST['dismissible']) ? 1 : 0;
    $expiresAtRaw = trim($_POST['expires_at'] ?? '');
    if ($expiresAtRaw) {
        $expiresAt = str_replace('T', ' ', $expiresAtRaw);
        if (strlen($expiresAt) === 16) $expiresAt .= ':00';
    } else {
        $expiresAt = null;
    }
    $createdBy    = $_SESSION['user_uuid'];

    if (empty($title) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Title and message are required.']);
        exit;
    }

    $validAlertTypes   = ['info', 'warning', 'danger', 'success'];
    $validDisplayTypes = ['banner', 'modal', 'toast'];

    if (!in_array($alertType, $validAlertTypes, true))   $alertType   = 'info';
    if (!in_array($displayType, $validDisplayTypes, true)) $displayType = 'banner';

    $stmt = $conn->prepare("
        INSERT INTO system_alerts
            (title, message, alert_type, display_type, target_roles, is_active, dismissible, expires_at, created_by)
        VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)
    ");
    $stmt->bind_param(
        'sssssiss',
        $title, $message, $alertType, $displayType,
        $targetRoles, $dismissible, $expiresAt, $createdBy
    );

    if ($stmt->execute()) {
        $newId = (int)$stmt->insert_id;
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Alert created.', 'id' => $newId]);
    } else {
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit;
}

if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'Invalid ID.']); exit; }

    $stmt = $conn->prepare("UPDATE system_alerts SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    // Return new state
    $row = $conn->query("SELECT is_active FROM system_alerts WHERE id = $id")->fetch_assoc();
    echo json_encode(['status' => 'success', 'is_active' => (bool)(int)$row['is_active']]);
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'Invalid ID.']); exit; }

    $stmt = $conn->prepare("DELETE FROM system_alerts WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'Alert deleted.']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
?>
