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
require_once dirname(__DIR__, 2) . '/functions/dtr_functions.php';

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

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'student') {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

$studentUuid     = $_SESSION['profile_uuid'];
$batchUuid       = $_SESSION['active_batch_uuid'] ?? '';

if (empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'No active batch found.']);
}

// get active application UUID
$stmt = $conn->prepare("
    SELECT uuid FROM ojt_applications
    WHERE student_uuid = ? AND batch_uuid = ? AND status = 'active'
    LIMIT 1
");
$stmt->bind_param('ss', $studentUuid, $batchUuid);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$app) {
    response([
        'status'  => 'error',
        'message' => 'No active OJT found. OJT must be confirmed by your coordinator first.',
    ]);
}

$result = submitDtrEntry(
    $conn,
    $studentUuid,
    $app['uuid'],
    $batchUuid,
    $_POST,
    $_SESSION['user_uuid']
);

if (!$result['success']) {
    response([
        'status'  => 'error',
        'errors'  => $result['errors'] ?? [],
        'message' => isset($result['errors'])
            ? reset($result['errors'])
            : ($result['error'] ?? 'Failed to submit DTR entry.'),
    ]);
}

response([
    'status'         => 'success',
    'message'        => $result['is_backdated']
        ? 'Backdated DTR entry submitted. Awaiting coordinator review.'
        : 'DTR entry submitted. Awaiting supervisor approval.',
    'uuid'           => $result['uuid'],
    'hours_rendered' => $result['hours_rendered'],
    'is_backdated'   => $result['is_backdated'],
]);