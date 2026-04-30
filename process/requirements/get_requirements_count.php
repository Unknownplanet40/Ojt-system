<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    exit('Unauthenticated');
}

$role        = $_SESSION['user_role'];
$profileUuid = $_SESSION['profile_uuid'];
$batchUuid   = $_SESSION['active_batch_uuid'] ?? '';

if (empty($batchUuid)) {
    $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $row       = $result->fetch_assoc();
    $batchUuid = $row['uuid'] ?? null;
}

$count = 0;
$type  = 'info';

if ($role === 'student') {
    // Count not_submitted or returned
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM student_requirements 
        WHERE student_uuid = ? 
          AND batch_uuid = ? 
          AND status IN ('not_submitted', 'returned')
    ");
    $stmt->bind_param('ss', $profileUuid, $batchUuid);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $type = ($count > 0) ? 'danger' : 'success';

} elseif ($role === 'coordinator') {
    // Count submitted (pending review) for their students
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM student_requirements sr
        JOIN student_profiles sp ON sr.student_uuid = sp.uuid
        WHERE sp.coordinator_uuid = ? 
          AND sr.batch_uuid = ? 
          AND sr.status = 'submitted'
    ");
    $stmt->bind_param('ss', $profileUuid, $batchUuid);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $type = ($count > 0) ? 'warning' : 'success';
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'count'  => (int)$count,
    'type'   => $type
]);
