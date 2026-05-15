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
require_once dirname(__DIR__, 2) . '/functions/evaluation_functions.php';

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

$role = $_SESSION['user_role'];

if (!in_array($role, ['supervisor', 'student'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

$batchUuid = trim($_POST['batch_uuid'] ?? '') ?: ($_SESSION['active_batch_uuid'] ?? '');

if (empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'No active batch found.']);
}

// -----------------------------------------------
// SUPERVISOR — midterm or final
// -----------------------------------------------
if ($role === 'supervisor') {
    $studentUuid = trim($_POST['student_uuid'] ?? '');
    $evalType    = trim($_POST['eval_type']    ?? '');

    if (empty($studentUuid)) {
        response(['status' => 'error', 'message' => 'Student UUID is required.']);
    }

    if (!in_array($evalType, ['midterm', 'final'])) {
        response(['status' => 'error', 'message' => 'Evaluation type must be midterm or final.']);
    }

    $result = submitSupervisorEvaluation(
        $conn,
        $_SESSION['profile_uuid'],
        $studentUuid,
        $batchUuid,
        $evalType,
        $_POST
    );

    if (!$result['success']) {
        response([
            'status'  => 'error',
            'errors'  => $result['errors'] ?? [],
            'message' => $result['error'] ?? reset($result['errors'] ?? ['Failed to submit evaluation.']),
        ]);
    }

    response([
        'status'      => 'success',
        'message'     => ucfirst($evalType) . ' evaluation submitted successfully.',
        'total_score' => $result['total_score'],
        'percentage'  => $result['percentage'],
    ]);
}

// -----------------------------------------------
// STUDENT — self evaluation
// -----------------------------------------------
if ($role === 'student') {
    $result = submitSelfEvaluation(
        $conn,
        $_SESSION['profile_uuid'],
        $batchUuid,
        $_POST
    );

    if (!$result['success']) {
        response([
            'status'  => 'error',
            'errors'  => $result['errors'] ?? [],
            'message' => $result['error'] ?? reset($result['errors'] ?? ['Failed to submit self-evaluation.']),
        ]);
    }

    response([
        'status'      => 'success',
        'message'     => 'Self-evaluation submitted successfully.',
        'total_score' => $result['total_score'],
    ]);
}
