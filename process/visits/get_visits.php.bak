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
require_once dirname(__DIR__, 2) . '/functions/visit_functions.php';

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

$role      = $_SESSION['user_role'];
$batchUuid = trim($_POST['batch_uuid'] ?? '');
$filters   = [];

if (!empty($_POST['status']))       $filters['status']       = $_POST['status'];
if (!empty($_POST['company_uuid'])) $filters['company_uuid'] = $_POST['company_uuid'];

if (empty($batchUuid)) {
    $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $batchUuid = $result->fetch_assoc()['uuid'] ?? null;
}

if (empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'No active batch found.']);
}

if ($role === 'coordinator') {
    $visits = getCoordinatorVisits(
        $conn,
        $_SESSION['profile_uuid'],
        $batchUuid,
        $filters
    );
} else {
    // admin — can filter by coordinator
    if (!empty($_POST['coordinator_uuid'])) {
        $filters['coordinator_uuid'] = $_POST['coordinator_uuid'];
    }
    $visits = getAllVisits($conn, $batchUuid, $filters);
}

// summary counts
$scheduled = count(array_filter($visits, fn($v) => $v['status'] === 'scheduled'));
$completed = count(array_filter($visits, fn($v) => $v['status'] === 'completed'));
$cancelled = count(array_filter($visits, fn($v) => $v['status'] === 'cancelled'));
$overdue   = count(array_filter($visits, fn($v) => $v['is_overdue']));

response([
    'status'  => 'success',
    'visits'  => $visits,
    'total'   => count($visits),
    'summary' => [
        'scheduled' => $scheduled,
        'completed' => $completed,
        'cancelled' => $cancelled,
        'overdue'   => $overdue,
    ],
]);