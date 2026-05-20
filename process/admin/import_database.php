<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required.']);
    exit;
}

$isDryRun = !empty($_POST['dry_run']) && $_POST['dry_run'] === '1';

if (!isset($_FILES['sql_file'])) {
    if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $mp = ini_get('post_max_size'); $mu = ini_get('upload_max_filesize');
        echo json_encode(['status' => 'error', 'message' => "File too large. Max Post: $mp, Max Upload: $mu"]);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'No file provided.']);
    exit;
}

$file = $_FILES['sql_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Upload error code: ' . $file['error']]);
    exit;
}
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'sql') {
    echo json_encode(['status' => 'error', 'message' => 'Only .sql files are allowed.']);
    exit;
}

$sql_content = file_get_contents($file['tmp_name']);
if (empty(trim($sql_content))) {
    echo json_encode(['status' => 'error', 'message' => 'Uploaded file is empty.']);
    exit;
}

$importLogFile = __DIR__ . '/../../config/import_log.json';

function appendImportLog(array $entry): void {
    global $importLogFile;
    $log = [];
    if (file_exists($importLogFile)) {
        $log = json_decode(file_get_contents($importLogFile), true) ?: [];
    }
    $log[] = $entry;
    if (count($log) > 50) $log = array_slice($log, -50);
    @file_put_contents($importLogFile, json_encode($log, JSON_PRETTY_PRINT));
}

$currentDbResult = $conn->query('SELECT DATABASE() AS db');
$currentDb       = $currentDbResult ? $currentDbResult->fetch_assoc()['db'] : null;

if ($currentDb !== $dbname) {
    $msg = "Environment mismatch: connected to `{$currentDb}`, expected `{$dbname}`.";
    appendImportLog([
        'date'    => date('Y-m-d'),
        'time'    => date('H:i:s'),
        'status'  => 'Error',
        'type'    => $isDryRun ? 'Dry Run' : 'System Restore',
        'file'    => $file['name'],
        'message' => $msg,
    ]);
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

$hostInfo = $conn->host_info;

$sql_content = preg_replace(
    '/CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS\s)/i',
    'CREATE TABLE IF NOT EXISTS ',
    $sql_content
);
$sql_content = preg_replace(
    '/\bINSERT\s+INTO\b/i',
    'INSERT IGNORE INTO',
    $sql_content
);

if ($isDryRun) {
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?(\w+)[`"]?/i', $sql_content, $tableMatches);
    preg_match_all('/INSERT\s+IGNORE\s+INTO\s+[`"]?(\w+)[`"]?/i', $sql_content, $insertMatches);

    $tables  = array_unique($tableMatches[1] ?? []);
    $inserts = array_count_values($insertMatches[1] ?? []);

    $summary = [
        'database'         => $currentDb,
        'host'             => $hostInfo,
        'tables_to_create' => $tables,
        'insert_counts'    => $inserts,
        'total_tables'     => count($tables),
        'total_inserts'    => array_sum($inserts),
        'file_size_kb'     => round(strlen($sql_content) / 1024, 2),
        'note'             => 'DRY RUN — no changes were made.',
    ];

    appendImportLog([
        'date'    => date('Y-m-d'),
        'time'    => date('H:i:s'),
        'status'  => 'Dry Run',
        'type'    => 'Dry Run',
        'file'    => $file['name'],
        'message' => 'Dry run completed — ' . count($tables) . ' tables, ' . array_sum($inserts) . ' inserts projected.',
    ]);

    echo json_encode([
        'status'  => 'dry_run',
        'message' => 'Dry run complete. Review the summary and confirm to proceed.',
        'summary' => $summary,
    ]);
    exit;
}

set_time_limit(300);
ini_set('memory_limit', '256M');

$importErrors = [];

try {
    $conn->autocommit(false);
    $conn->begin_transaction();

    $conn->query('SET FOREIGN_KEY_CHECKS = 0');
    $conn->query('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');

    // Execute using multi_query
    if ($conn->multi_query($sql_content)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
            if ($conn->error) {
                throw new RuntimeException('Query error: ' . $conn->error);
            }
        } while ($conn->more_results() && $conn->next_result());
    }

    if ($conn->error) {
        throw new RuntimeException('Execution error: ' . $conn->error);
    }

    $conn->query('SET FOREIGN_KEY_CHECKS = 1');

    $conn->query("
        CREATE TABLE IF NOT EXISTS `schema_version` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `version`    VARCHAR(20)  NOT NULL,
            `applied_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `source`     VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $importedVersion = 'unknown';
    if (preg_match('/--\s*Version:\s*([\d.]+)/i', $sql_content, $vm)) {
        $importedVersion = $vm[1];
    }

    $versionStmt = $conn->prepare(
        'INSERT INTO schema_version (version, source) VALUES (?, ?)'
    );
    if ($versionStmt) {
        $versionStmt->bind_param('ss', $importedVersion, $file['name']);
        $versionStmt->execute();
        $versionStmt->close();
    }

    $conn->commit();
    $conn->autocommit(true);

    $historyFile = __DIR__ . '/../../config/export_history.json';
    $history     = [];
    if (file_exists($historyFile)) {
        $history = json_decode(file_get_contents($historyFile), true) ?: [];
    }
    $history[] = ['date' => date('Y-m-d'), 'time' => date('H:i'), 'status' => 'Success', 'type' => 'System Restore'];
    if (count($history) > 10) $history = array_slice($history, -10);
    @file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));

    appendImportLog([
        'date'    => date('Y-m-d'),
        'time'    => date('H:i:s'),
        'status'  => 'Success',
        'type'    => 'System Restore',
        'file'    => $file['name'],
        'version' => $importedVersion,
        'host'    => $hostInfo,
        'db'      => $currentDb,
        'message' => 'Import completed successfully.',
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Database restored successfully. Schema version ' . $importedVersion . ' applied to `' . $currentDb . '`.',
    ]);

} catch (Throwable $e) {
    try {
        $conn->query('SET FOREIGN_KEY_CHECKS = 1');
        $conn->rollback();
    } catch (Throwable $re) { /* ignore rollback errors */ }

    $conn->autocommit(true);

    $errMsg = $e->getMessage();

    appendImportLog([
        'date'    => date('Y-m-d'),
        'time'    => date('H:i:s'),
        'status'  => 'Failed',
        'type'    => 'System Restore',
        'file'    => $file['name'],
        'host'    => $hostInfo,
        'db'      => $currentDb,
        'message' => 'ROLLED BACK — ' . $errMsg,
    ]);

    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Import failed and was automatically rolled back. No data was changed. Error: ' . $errMsg,
    ]);
}
