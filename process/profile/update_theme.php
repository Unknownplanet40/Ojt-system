<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/settings_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthenticated']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if ($csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$theme = $_POST['theme'] ?? 'dark';
$user_uuid = $_SESSION['user_uuid'];

$result = saveUserTheme($conn, $theme, $user_uuid);

if ($result['success']) {
    $_SESSION['theme_preference'] = $result['theme'];
    echo json_encode(['status' => 'success', 'message' => 'Theme preference saved.', 'theme' => $result['theme']]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $result['message']]);
}

$conn->close();
