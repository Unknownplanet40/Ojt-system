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

if (!in_array($_SESSION['user_role'], ['admin', 'coordinator'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

$reqUuid = trim($_POST['req_uuid'] ?? '');

if (empty($reqUuid)) {
    response(['status' => 'error', 'message' => 'Requirement UUID is required.']);
}

if (($_SESSION['user_role'] ?? '') === 'coordinator') {
    $stmt = $conn->prepare("
        SELECT sp.id
        FROM student_requirements sr
        JOIN student_profiles sp ON sr.student_uuid = sp.uuid
        WHERE sr.uuid = ? AND sp.coordinator_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $reqUuid, $_SESSION['profile_uuid']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        http_response_code(403);
        response(['status' => 'error', 'message' => 'Unauthorized.']);
    }
    $stmt->close();
}

$result = approveRequirement($conn, $reqUuid, $_SESSION['user_uuid'], $_SESSION['profile_uuid']);

if (!$result['success']) {
    response(['status' => 'error', 'message' => $result['error']]);
}

response([
    'status'  => 'success',
    'message' => 'Document approved.',
]);