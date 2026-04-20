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

// get valid rows from session (set by bulk_validate.php)
$validRows = $_SESSION['bulk_valid_rows'] ?? [];

if (empty($validRows)) {
    response([
        'status'  => 'error',
        'message' => 'No validated rows found. Please upload and validate the file first.',
    ]);
}

// clear session after use
unset($_SESSION['bulk_valid_rows']);

$result = createBulkStudents($conn, $validRows, $_SESSION['user_uuid']);

// store created accounts in session for export
$_SESSION['bulk_created'] = $result['created'];

response([
    'status'        => 'success',
    'message'       => $result['created_count'] . ' accounts created successfully.',
    'created'       => $result['created'],
    'failed'        => $result['failed'],
    'created_count' => $result['created_count'],
    'failed_count'  => $result['failed_count'],
]);  