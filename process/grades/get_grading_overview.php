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
require_once dirname(__DIR__, 2) . '/functions/grade_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'       => 'critical',
        'message'      => 'Database connection failed.',
        'Details'      => $conn->connect_error ?? 'Unknown error',
        'Suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthenticated.']);
}

if (!in_array($_SESSION['user_role'], ['coordinator', 'admin'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

$batchUuid = trim($_POST['batch_uuid'] ?? '');

if (empty($batchUuid)) {
    $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $batchUuid = $result->fetch_assoc()['uuid'] ?? null;
}

if (empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'No active batch found.']);
}

$coordinatorUuid = $_SESSION['user_role'] === 'coordinator'
    ? $_SESSION['profile_uuid']
    : null;

$overview = getGradingOverview($conn, $batchUuid, $coordinatorUuid);

$total       = count($overview);
$finalized   = count(array_filter($overview, fn($s) => $s['grade_status'] === 'finalized'));
$ready       = count(array_filter($overview, fn($s) => $s['grade_status'] === 'ready'));
$computed    = count(array_filter($overview, fn($s) => $s['grade_status'] === 'computed'));
$incomplete  = count(array_filter($overview, fn($s) => $s['grade_status'] === 'incomplete'));

response([
    'status'   => 'success',
    'overview' => $overview,
    'summary'  => [
        'total'      => $total,
        'finalized'  => $finalized,
        'ready'      => $ready,
        'computed'   => $computed,
        'incomplete' => $incomplete,
    ],
]);