<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_uuid']) || empty($_SESSION['user_role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthenticated.']);
    exit;
}

$userUuid = $_SESSION['user_uuid'];
$userRole = $_SESSION['user_role'];
$now      = date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    SELECT
        sa.id,
        sa.title,
        sa.message,
        sa.alert_type,
        sa.display_type,
        sa.dismissible,
        sa.target_roles
    FROM system_alerts sa
    LEFT JOIN system_alert_dismissals sad
        ON sad.alert_id = sa.id AND sad.user_uuid = ?
    WHERE sa.is_active = 1
      AND sad.id IS NULL
      AND (sa.expires_at IS NULL OR sa.expires_at > ?)
    ORDER BY sa.id DESC
");
$stmt->bind_param('ss', $userUuid, $now);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$alerts = [];
foreach ($rows as $row) {
    if ($row['target_roles'] !== 'all') {
        $allowed = array_map('trim', explode(',', $row['target_roles']));
        if (!in_array($userRole, $allowed, true)) {
            continue;
        }
    }
    $alerts[] = [
        'id'           => (int)$row['id'],
        'title'        => $row['title'],
        'message'      => $row['message'],
        'alert_type'   => $row['alert_type'],
        'display_type' => $row['display_type'],
        'dismissible'  => (bool)$row['dismissible'],
    ];
}

echo json_encode(['status' => 'success', 'alerts' => $alerts]);
?>
