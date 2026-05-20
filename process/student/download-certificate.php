<?php

session_start();

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_uuid']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Please log in.',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use GET.',
    ]);
    exit;
}

try {
    $certificateUuid = $_GET['certificate_uuid'] ?? null;
    
    if (!$certificateUuid) {
        throw new Exception('Missing required parameter: certificate_uuid');
    }
    
    $db = Database::getInstance();
    
    $query = "SELECT c.uuid, c.file_path, c.is_revoked, c.student_uuid
              FROM certificates c
              WHERE c.uuid = ?
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$certificateUuid]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$certificate) {
        throw new Exception("Certificate not found: $certificateUuid");
    }
    
    if ($_SESSION['user_role'] === 'student') {
        $query = "SELECT uuid FROM student_profiles WHERE uuid = ? AND user_uuid = ? LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$certificate['student_uuid'], $_SESSION['user_uuid']]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Permission denied. You can only download your own certificates.',
            ]);
            exit;
        }
    } elseif ($_SESSION['user_role'] !== 'coordinator' && $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Permission denied. Only students, coordinators, and admins can download certificates.',
        ]);
        exit;
    }
    
    if ($certificate['is_revoked']) {
        http_response_code(410);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'This certificate has been revoked and cannot be downloaded.',
        ]);
        exit;
    }
    
    $filePath = __DIR__ . '/../../' . $certificate['file_path'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Certificate file not found on server.',
        ]);
        exit;
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
