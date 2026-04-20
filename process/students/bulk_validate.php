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
require_once dirname(__DIR__, 2) . '/functions/bulk_student_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
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

// check file uploaded
if (empty($_FILES['bulk_file']) || $_FILES['bulk_file']['error'] !== UPLOAD_ERR_OK) {
    response(['status' => 'error', 'message' => 'No file uploaded.']);
}

$file = $_FILES['bulk_file'];

// max 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    response(['status' => 'error', 'message' => 'File must be 5MB or less.']);
}

// max 500 rows check happens after parsing
$rows = parseBulkFile($file);

if (isset($rows['error'])) {
    response(['status' => 'error', 'message' => $rows['error']]);
}

if (empty($rows)) {
    response(['status' => 'error', 'message' => 'File is empty or has no data rows.']);
}

if (count($rows) > 500) {
    response(['status' => 'error', 'message' => 'File exceeds 500 row limit. Split into smaller files.']);
}

// get active batch
$result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
$batchRow  = $result->fetch_assoc();
$batchUuid = $batchRow['uuid'] ?? null;

if (!$batchUuid) {
    response(['status' => 'error', 'message' => 'No active batch found. Create and activate a batch first.']);
}

// coordinator UUID
$coordinatorUuid = trim($_POST['coordinator_uuid'] ?? '');

// optional fallback: resolve coordinator by name
if (empty($coordinatorUuid)) {
    $coordinatorName = trim($_POST['coordinator_name'] ?? '');
    if (!empty($coordinatorName)) {
        $resolvedCoordinatorUuid = findCoordinatorUuidByName($conn, $coordinatorName);
        if (!$resolvedCoordinatorUuid) {
            response(['status' => 'error', 'message' => "Coordinator '{$coordinatorName}' not found."]);
        }
        $coordinatorUuid = $resolvedCoordinatorUuid;
    }
}

// validate rows
$result = validateBulkRows($conn, $rows, $batchUuid, $coordinatorUuid);

// store valid rows in session for the confirm step
// so we don't re-validate on confirm
$_SESSION['bulk_valid_rows']  = $result['valid_rows'];
$_SESSION['bulk_batch_uuid']  = $batchUuid;

response([
    'status'      => 'success',
    'valid_rows'  => $result['valid_rows'],
    'error_rows'  => $result['error_rows'],
    'total'       => $result['total'],
    'valid_count' => $result['valid_count'],
    'error_count' => $result['error_count'],
]);