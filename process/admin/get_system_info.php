<?php
/**
 * Get System Environment Information - AJAX Handler
 * 
 * Returns comprehensive system information including:
 * - Server environment (PHP, extensions, modules)
 * - Database connection status
 * - Storage and directory information
 * 
 * Authorization: Admin only
 */

session_start();
header('Content-Type: application/json');

// Validate session and authorization
if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db.php';
require_once '../../functions/system_check_functions.php';

try {
    // Get all system information
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
