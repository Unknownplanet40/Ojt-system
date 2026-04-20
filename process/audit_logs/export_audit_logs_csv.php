<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        http_response_code(403);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    } else {
        error_log(
            "Unauthorized direct access attempt to " .
            basename(__FILE__) . " from " .
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
        );
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/audit_log_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

if (!isset($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'critical',
        'message' => 'Database connection failed.',
        'details' => $conn->connect_error ?? 'Unknown error',
    ]);
    exit;
}

$filters = [
    'source' => trim((string)($_POST['source'] ?? 'all')),
    'user_uuid' => trim((string)($_POST['user_uuid'] ?? '')),
    'event_type' => trim((string)($_POST['event_type'] ?? '')),
    'module' => trim((string)($_POST['module'] ?? '')),
    'search' => trim((string)($_POST['search'] ?? '')),
    'date_from' => trim((string)($_POST['date_from'] ?? '')),
    'date_to' => trim((string)($_POST['date_to'] ?? '')),
];

$rows = getAuditLogsForExport($conn, $filters, 10000);

$fileName = 'audit_logs_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Occurred At',
    'Source',
    'Event Type',
    'Module',
    'Actor Name',
    'Actor Email',
    'Actor Role',
    'Actor UUID',
    'Target UUID',
    'Description',
    'IP Address',
    'User Agent',
    'Login Result',
    'Fail Reason',
    'Meta JSON',
]);

foreach ($rows as $row) {
    $meta = $row['meta'];
    $metaJson = '';
    if (is_array($meta)) {
        $metaJson = json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } elseif (!empty($row['meta_raw'])) {
        $metaJson = (string)$row['meta_raw'];
    }

    $loginResult = '';
    if ($row['login_success'] !== null) {
        $loginResult = ((int)$row['login_success'] === 1) ? 'success' : 'failed';
    }

    fputcsv($output, [
        $row['occurred_at'] ?? '',
        $row['source'] ?? '',
        $row['event_type'] ?? '',
        $row['module'] ?? '',
        $row['actor_name'] ?? '',
        $row['actor_email'] ?? '',
        $row['actor_role'] ?? '',
        $row['actor_uuid'] ?? '',
        $row['target_uuid'] ?? '',
        $row['description'] ?? '',
        $row['ip_address'] ?? '',
        $row['user_agent'] ?? '',
        $loginResult,
        $row['fail_reason'] ?? '',
        $metaJson,
    ]);
}

fclose($output);
exit;
