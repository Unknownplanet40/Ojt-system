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
require_once dirname(__DIR__, 2) . '/functions/company_functions.php';

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
        'Details'      => $conn->connect_error ?? 'Unknown error',
        'Suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

$companyUuid = trim($_POST['company_uuid'] ?? '');

if (empty($companyUuid)) {
    response(['status' => 'error', 'message' => 'Company UUID is required.']);
}

$data = $_POST;
$data['program_uuids'] = $_POST['program_uuids'] ?? [];

if (empty($data['batch_uuid'])) {
    $result            = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $row               = $result->fetch_assoc();
    $data['batch_uuid'] = $row['uuid'] ?? null;
}

$contactUuid = trim($_POST['contact_uuid'] ?? '');
$companyUuid = trim($_POST['company_uuid'] ?? '');

if (empty($contactUuid) || empty($companyUuid)) {
    response(['status' => 'error', 'message' => 'Contact UUID and Company UUID are required.']);
}

$action = trim($_POST['action'] ?? 'update'); // 'add' or 'update'

if ($action === 'add') {
    $result = addCompanyContact($conn, $companyUuid, $_POST);
} else {
    $result = updateCompanyContact($conn, $contactUuid, $_POST, $companyUuid);
}

if (!$result['success']) {
    response([
        'status'  => 'error',
        'message' => $result['error'],
    ]);
}

response([
    'status'  => 'success',
    'message' => $action === 'add'
        ? 'Contact added successfully.'
        : 'Contact updated successfully.',
]);