<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Direct access is allowed for this file to support browser downloads

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/bulk_student_functions.php';

// Removed POST check to allow direct download via GET

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? ''))) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

if (!isset($_SESSION['user_uuid']) || !in_array($_SESSION['user_role'], ['admin', 'coordinator'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'       => 'critical',
        'message'      => 'Database connection failed.',
        'details'      => $conn->connect_error ?? 'Unknown error',
        'suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

if (!in_array($_SESSION['user_role'], ['admin', 'coordinator'])) {
    http_response_code(403);
    exit;
}

$coordinatorUuid = ($_SESSION['user_role'] === 'coordinator') ? ($_SESSION['profile_uuid'] ?? null) : null;
$csv = generateBulkTemplate($conn, $coordinatorUuid);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_bulk_import_template.csv"');
header('Cache-Control: private, no-cache');
header('Pragma: no-cache');
echo $csv;
exit;