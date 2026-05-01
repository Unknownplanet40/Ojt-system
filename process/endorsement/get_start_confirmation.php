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
require_once dirname(__DIR__, 2) . '/functions/endorsement_functions.php';
require_once dirname(__DIR__, 2) . '/functions/application_functions.php';

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

if (!in_array($_SESSION['user_role'], ['admin', 'coordinator', 'student'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

$appUuid = trim($_POST['application_uuid'] ?? '');

if (empty($appUuid)) {
    response(['status' => 'error', 'message' => 'Application UUID is required.']);
}

$stmt = $conn->prepare(" 
    SELECT a.student_uuid, sp.coordinator_uuid
    FROM ojt_applications a
    JOIN student_profiles sp ON a.student_uuid = sp.uuid
    WHERE a.uuid = ?
    LIMIT 1
");
$stmt->bind_param('s', $appUuid);
$stmt->execute();
$appRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appRow) {
    response(['status' => 'error', 'message' => 'Application not found.']);
}

if (
    ($_SESSION['user_role'] === 'student' && $appRow['student_uuid'] !== $_SESSION['profile_uuid']) ||
    ($_SESSION['user_role'] === 'coordinator' && $appRow['coordinator_uuid'] !== $_SESSION['profile_uuid'])
) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

$confirmation = getOjtStartConfirmation($conn, $appUuid);

response([
    'status'       => 'success',
    'confirmation' => $confirmation,
    'confirmed'    => $confirmation !== null,
]);