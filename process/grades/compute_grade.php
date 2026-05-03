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

$studentUuid = trim($_POST['student_uuid'] ?? '');
$batchUuid   = trim($_POST['batch_uuid']   ?? '');

if (empty($studentUuid) || empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'Student UUID and batch UUID are required.']);
}

// parse weights from POST
$weights = [];
if (!empty($_POST['hours_weight']))   $weights['hours']   = (float) $_POST['hours_weight'];
if (!empty($_POST['midterm_weight'])) $weights['midterm'] = (float) $_POST['midterm_weight'];
if (!empty($_POST['final_weight']))   $weights['final']   = (float) $_POST['final_weight'];
if (!empty($_POST['journal_weight'])) $weights['journal'] = (float) $_POST['journal_weight'];
if (!empty($_POST['self_weight']))    $weights['self']    = (float) $_POST['self_weight'];

$readiness = isReadyForGrading($conn, $studentUuid, $batchUuid);
$computed  = computeGradeComponents($conn, $studentUuid, $batchUuid, $weights);

if (!$computed['success']) {
    response(['status' => 'error', 'message' => $computed['error']]);
}

response([
    'status'    => 'success',
    'readiness' => $readiness,
    'computed'  => $computed,
]);