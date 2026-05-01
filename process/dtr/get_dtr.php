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


if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthenticated.']);
}

$role        = $_SESSION['user_role'];
$batchUuid   = trim($_POST['batch_uuid'] ?? '') ?: ($_SESSION['active_batch_uuid'] ?? '');
$filters     = [];

if (!empty($_POST['status']))       $filters['status']       = $_POST['status'];
if (!empty($_POST['month']))        $filters['month']        = $_POST['month'];
if (isset($_POST['is_backdated']))  $filters['is_backdated'] = (int) $_POST['is_backdated'];

if (empty($batchUuid)) {
    $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $row       = $result->fetch_assoc();
    $batchUuid = $row['uuid'] ?? null;
}

if (empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'No active batch found.']);
}

// -----------------------------------------------
// STUDENT — own entries + summary
// -----------------------------------------------
if ($role === 'student') {
    $studentUuid = $_SESSION['profile_uuid'];
    $entries     = getStudentDtrEntries($conn, $studentUuid, $batchUuid, $filters);
    $summary     = getDtrSummary($conn, $studentUuid, $batchUuid);

    response([
        'status'  => 'success',
        'entries' => $entries,
        'summary' => $summary,
        'total'   => count($entries),
    ]);
}

// -----------------------------------------------
// SUPERVISOR — assigned students' pending entries
// -----------------------------------------------
if ($role === 'supervisor') {
    $entries = getSupervisorPendingDtr($conn, $_SESSION['profile_uuid'], $batchUuid);

    response([
        'status'  => 'success',
        'entries' => $entries,
        'total'   => count($entries),
    ]);
}

// -----------------------------------------------
// COORDINATOR / ADMIN — all entries with filters
// -----------------------------------------------
if (in_array($role, ['coordinator', 'admin'])) {
    $coordinatorUuid = $role === 'coordinator' ? $_SESSION['profile_uuid'] : null;

    if (!empty($_POST['student_uuid'])) {
        $filters['student_uuid'] = $_POST['student_uuid'];
    }

    $entries = getAllDtrEntries($conn, $batchUuid, $coordinatorUuid, $filters);

    // also return summary if a specific student is requested
    $summary = null;
    if (!empty($filters['student_uuid'])) {
        $summary = getDtrSummary($conn, $filters['student_uuid'], $batchUuid);
    }

    response([
        'status'  => 'success',
        'entries' => $entries,
        'summary' => $summary,
        'total'   => count($entries),
    ]);
}

http_response_code(403);
response(['status' => 'error', 'message' => 'Unauthorized.']);