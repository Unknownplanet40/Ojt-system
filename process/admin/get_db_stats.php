<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db.php';

try {
    $sizeQuery = $conn->prepare("
        SELECT 
            SUM(data_length + index_length) / 1024 / 1024 AS size_mb
        FROM information_schema.tables 
        WHERE table_schema = ?
    ");
    $sizeQuery->bind_param('s', $dbname);
    $sizeQuery->execute();
    $result = $sizeQuery->get_result();
    $dbSize = $result->fetch_assoc()['size_mb'] ?? 0;
    $sizeQuery->close();

    $tablesQuery = $conn->prepare("
        SELECT COUNT(*) as table_count
        FROM information_schema.tables 
        WHERE table_schema = ?
    ");
    $tablesQuery->bind_param('s', $dbname);
    $tablesQuery->execute();
    $result = $tablesQuery->get_result();
    $totalTables = $result->fetch_assoc()['table_count'] ?? 0;
    $tablesQuery->close();

    $recordsQuery = $conn->prepare("
        SELECT SUM(table_rows) as row_count
        FROM information_schema.tables 
        WHERE table_schema = ?
    ");
    $recordsQuery->bind_param('s', $dbname);
    $recordsQuery->execute();
    $result = $recordsQuery->get_result();
    $totalRecords = $result->fetch_assoc()['row_count'] ?? 0;
    $recordsQuery->close();

    $logFile = '../../config/export_history.json';
    $exportHistory = [];
    if (file_exists($logFile)) {
        $exportHistory = json_decode(file_get_contents($logFile), true) ?: [];
    }
    
    $exportHistory = array_reverse($exportHistory);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'size' => number_format($dbSize, 2) . ' MB',
            'tables' => (int)$totalTables,
            'records' => number_format((int)$totalRecords),
            'history' => $exportHistory
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve database statistics: ' . $e->getMessage()
    ]);
}
?>
