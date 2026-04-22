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
require_once dirname(__DIR__, 2) . '/functions/requirement_functions.php';

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
        'details'      => $conn->connect_error ?? 'Unknown error',
        'suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthenticated.']);
}

$role = $_SESSION['user_role'];

if ($role === 'student') {
    $studentUuid = $_SESSION['profile_uuid'];
    $batchUuid   = $_SESSION['active_batch_uuid'] ?? '';

    if (empty($studentUuid)) {
        response(['status' => 'error', 'message' => 'Student profile is missing.']);
    }
} elseif (in_array($role, ['coordinator', 'admin'])) {
    $studentUuid = trim($_POST['student_uuid'] ?? '');
    $batchUuid   = trim($_POST['batch_uuid']   ?? '');

    if (empty($studentUuid)) {
        response(['status' => 'error', 'message' => 'Student UUID is required.']);
    }

    if ($role === 'coordinator') {
        $stmt = $conn->prepare("
            SELECT id FROM student_profiles
            WHERE uuid = ? AND coordinator_uuid = ?
            LIMIT 1
        ");
        $stmt->bind_param('ss', $studentUuid, $_SESSION['profile_uuid']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            response(['status' => 'error', 'message' => 'Unauthorized.']);
        }
        $stmt->close();
    }
} else {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (empty($batchUuid)) {
    $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $row       = $result->fetch_assoc();
    $batchUuid = $row['uuid'] ?? null;
}

if (empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'No active batch found.']);
}

$requirements = getStudentRequirements($conn, $studentUuid, $batchUuid);
$canApply     = canStudentApply($conn, $studentUuid, $batchUuid);

response([
    'status'       => 'success',
    'requirements' => $requirements,
    'can_apply'    => $canApply,
    'approved_count' => count(array_filter($requirements, fn($r) => $r['status'] === 'approved')),
    'total'        => count($requirements),
]);
