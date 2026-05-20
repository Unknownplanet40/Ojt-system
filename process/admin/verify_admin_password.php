<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db.php';

$password = $_POST['password'] ?? '';

if (empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password is required.']);
    exit;
}

try {
    $user_uuid = $_SESSION['user_uuid'];
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE uuid = ?");
    $stmt->bind_param("s", $user_uuid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password_hash'])) {
        echo json_encode(['status' => 'success', 'message' => 'Password verified successfully.']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Incorrect administrator password.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Verification failed: ' . $e->getMessage()]);
}
?>
