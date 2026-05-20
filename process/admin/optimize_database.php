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
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $optimizedCount = 0;
    $reclaimedSpace = 0;

    foreach ($tables as $table) {
        $conn->query("OPTIMIZE TABLE `$table` ");
        $optimizedCount++;
    }

    $sizeQuery = $conn->prepare("
        SELECT 
            SUM(data_length + index_length) / 1024 / 1024 AS size_mb
        FROM information_schema.tables 
        WHERE table_schema = ?
    ");
    $sizeQuery->bind_param('s', $dbname);
    $sizeQuery->execute();
    $newSize = $sizeQuery->get_result()->fetch_assoc()['size_mb'] ?? 0;
    $sizeQuery->close();

    echo json_encode([
        'status' => 'success',
        'message' => "Database optimized successfully. $optimizedCount tables processed.",
        'data' => [
            'newSize' => number_format($newSize, 2) . ' MB'
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database optimization failed: ' . $e->getMessage()
    ]);
}
?>
