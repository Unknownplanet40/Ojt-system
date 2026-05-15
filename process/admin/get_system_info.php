<?php


session_start();
header('Content-Type: application/json');


if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db.php';
require_once '../../functions/system_check_functions.php';

try {
    
    $systemInfo = getAllSystemInfo($conn);
    
    echo json_encode([
        'status' => 'success',
        'data' => $systemInfo
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve system information: ' . $e->getMessage()
    ]);
}

?>
