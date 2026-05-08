<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Prevent direct access
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        http_response_code(403);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/audit_log_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

// Validate CSRF
if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request security token.']);
}

// Ensure user is logged in
if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Session expired. Please login again.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed.'
    ]);
}

$userUuid = $_SESSION['user_uuid'];

// Setup filters for own logs
$filters = [
    'source' => 'login',
    'user_uuid' => $userUuid, // Force current user's UUID
    'page' => (int)($_POST['page'] ?? 1),
    'page_size' => (int)($_POST['page_size'] ?? 10),
];

// Get logs using the existing audit function
$normalizedFilters = normalizeAuditLogFilters($filters);
$logData = getAuditLogs($conn, $normalizedFilters);

response([
    'status' => 'success',
    'logs' => $logData['rows'],
    'pagination' => [
        'page' => $logData['page'],
        'page_size' => $logData['page_size'],
        'total' => $logData['total'],
        'total_pages' => $logData['total_pages'],
    ]
]);
