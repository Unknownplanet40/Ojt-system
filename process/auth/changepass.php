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

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(498);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

$userUuid = $_SESSION['user_uuid'] ?? null;
$must_change_password = (bool)($_SESSION['must_change_password'] ?? false);

if ($must_change_password) {
    $result = forcedChangePassword($conn, $userUuid, $_POST['tempPassword'] ?? '', $_POST['newPassword'] ?? '', $_POST['newPassword'] ?? '');
} else {
    $result = voluntaryChangePassword($conn, $userUuid, $_POST['currentPassword'] ?? '', $_POST['newPassword'] ?? '', $_POST['newPassword'] ?? '');
}

if ($result['success']) {
    if ($must_change_password) {
        $redirectUrl = getPostPasswordChangeRedirect($conn, $userUuid, $_SESSION['user_role'] ?? '');
    } else {
        $redirectUrl = match($_SESSION['user_role'] ?? '') {
            'admin'       => '../../Src/Pages/Admin/Admin_Profile',
            'coordinator' => '../../Src/Pages/Coordinator/viewProfile',
            'student'     => '../../Src/Pages/Students/Students_Profile',
            'supervisor'  => '../../Src/Pages/Supervisor/Supervisor_Profile',
            default       => '../../Src/Pages/login',
        };
    }
    response(['status' => 'success', 'message' => 'Password changed successfully.', 'redirect' => $redirectUrl]);
} else {
    response(['status' => 'error', 'message' => $result['errors'] ?? 'Failed to change password.']);
}
