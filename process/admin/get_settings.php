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
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/settings_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
}

if (!isset($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    response(['status' => 'error', 'message' => 'Unauthorized access.'], 403);
}

if (!$conn || $conn->connect_error) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed.',
        'details' => $conn->connect_error ?? 'Unknown error',
    ], 500);
}

response([
    'status' => 'success',
    'settings' => [
        'theme' => getUserTheme($conn, $_SESSION['user_uuid']),
        'email' => getEmailSettings($conn),
        'institutional' => getSystemConfig($conn),
    ],
]);
