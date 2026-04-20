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
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

if (!isset($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed.',
        'details' => $conn->connect_error ?? 'Unknown error',
        'suggestion' => 'Please try again later or contact support if the issue persists.',
    ]);
}

$filters = [
    'source' => trim((string)($_POST['source'] ?? 'all')),
    'user_uuid' => trim((string)($_POST['user_uuid'] ?? '')),
    'event_type' => trim((string)($_POST['event_type'] ?? '')),
    'module' => trim((string)($_POST['module'] ?? '')),
    'search' => trim((string)($_POST['search'] ?? '')),
    'date_from' => trim((string)($_POST['date_from'] ?? '')),
    'date_to' => trim((string)($_POST['date_to'] ?? '')),
    'page' => (int)($_POST['page'] ?? 1),
    'page_size' => (int)($_POST['page_size'] ?? 25),
];

$normalizedFilters = normalizeAuditLogFilters($filters);
$logData = getAuditLogs($conn, $normalizedFilters);
$options = getAuditLogFilterOptions($conn);

response([
    'status' => 'success',
    'logs' => $logData['rows'],
    'pagination' => [
        'page' => $logData['page'],
        'page_size' => $logData['page_size'],
        'total' => $logData['total'],
        'total_pages' => $logData['total_pages'],
    ],
    'filter_options' => $options,
    'filters' => [
        'source' => $normalizedFilters['source'],
        'user_uuid' => $normalizedFilters['user_uuid'],
        'event_type' => $normalizedFilters['event_type'],
        'module' => $normalizedFilters['module'],
        'search' => $normalizedFilters['search'],
        'date_from' => $normalizedFilters['date_from'],
        'date_to' => $normalizedFilters['date_to'],
    ],
]);
