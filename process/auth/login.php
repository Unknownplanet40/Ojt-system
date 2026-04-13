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
require_once dirname(__DIR__, 2) . '/functions/auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'       => 'critical',
        'message'      => 'Database connection failed.',
        'Details'      => $conn->connect_error ?? 'Unknown error',
        'Suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

$result = loginUser($conn, $email, $password);

if (!$result['success']) {
    response([
        'status'  => 'error',
        'message' => $result['message'],
    ]);
}

$user = $result['user'];
buildSession($conn, $user);
$hasSubmittedRequirements = false;

if ($user['role'] === 'student' && !empty($_SESSION['profile_uuid']) && !empty($_SESSION['active_batch_uuid'])) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM student_requirements
        WHERE student_uuid = ?
          AND batch_uuid   = ?
          AND status != 'not_submitted'
    ");
    $stmt->bind_param('ss', $_SESSION['profile_uuid'], $_SESSION['active_batch_uuid']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $hasSubmittedRequirements = (int) $row['total'] > 0;
}

$redirectUrl = getRedirectUrl($conn, $user);

response([
    'status'                    => 'success',
    'message'                   => 'Login successful.',
    'redirect_url'              => $redirectUrl,
    'has_submitted_requirements' => $hasSubmittedRequirements,
    'role'                      => $user['role'],
    'must_change_password'      => (int) $user['must_change_password'],
]);
