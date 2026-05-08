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

if (!ensureGradeTableExists($conn)) {
    response(['status' => 'critical', 'message' => 'Failed to initialize grading table.']);
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
    $batchUuid = resolveActiveBatchUuid($conn, $batchUuid) ?? '';
}

if (empty($studentUuid) || empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'Student UUID and batch UUID are required.']);
}

if ($_SESSION['user_role'] === 'coordinator' && !studentBelongsToCoordinator($conn, $studentUuid, $_SESSION['profile_uuid'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized access to this student.']);
}

$readiness = isReadyForGrading($conn, $studentUuid, $batchUuid);
if (!$readiness['ready']) {
    response([
        'status'    => 'error',
        'message'   => 'Student is not ready for grading yet.',
        'readiness' => $readiness,
    ]);
}

$weights = [
    'hours'   => (float) ($_POST['hours_weight']   ?? DEFAULT_WEIGHTS['hours']),
    'midterm' => (float) ($_POST['midterm_weight']  ?? DEFAULT_WEIGHTS['midterm']),
    'final'   => (float) ($_POST['final_weight']    ?? DEFAULT_WEIGHTS['final']),
    'journal' => (float) ($_POST['journal_weight']  ?? DEFAULT_WEIGHTS['journal']),
    'self'    => (float) ($_POST['self_weight']     ?? DEFAULT_WEIGHTS['self']),
];

$notes  = trim($_POST['coordinator_notes'] ?? '');
$result = finalizeGrade($conn, $studentUuid, $batchUuid, $_SESSION['profile_uuid'], $weights, $notes);

if (!$result['success']) {
    response(['status' => 'error', 'message' => $result['error']]);
}

response([
    'status'           => 'success',
    'message'          => "Grade finalized: {$result['computed']['grade_equivalent']} ({$result['computed']['weighted_score']}%)",
    'grade_equivalent' => $result['computed']['grade_equivalent'],
    'weighted_score'   => $result['computed']['weighted_score'],
    'remarks'          => $result['computed']['remarks'],
]);