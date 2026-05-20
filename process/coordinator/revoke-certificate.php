<?php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../functions/certificate_functions.php';

if (!isset($_SESSION['user_uuid']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Please log in.',
    ]);
    exit;
}

if ($_SESSION['user_role'] !== 'coordinator') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Permission denied. Only coordinators can revoke certificates.',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use POST.',
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid JSON payload');
    }
    
    $certificateUuid = $data['certificate_uuid'] ?? null;
    $revocationReason = $data['revocation_reason'] ?? null;
    
    if (!$certificateUuid || !$revocationReason) {
        throw new Exception('Missing required fields: certificate_uuid, revocation_reason');
    }
    
    if (strlen($revocationReason) < 10 || strlen($revocationReason) > 500) {
        throw new Exception('Revocation reason must be between 10 and 500 characters');
    }
    
    $db = Database::getInstance();
    
    $query = "SELECT uuid, is_revoked, certificate_number FROM certificates WHERE uuid = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$certificateUuid]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$certificate) {
        throw new Exception("Certificate not found: $certificateUuid");
    }
    
    if ($certificate['is_revoked']) {
        throw new Exception("Certificate is already revoked: {$certificate['certificate_number']}");
    }
    
    $certificateManager = getCertificateManager();
    $success = $certificateManager->revokeCertificate(
        $certificateUuid,
        $_SESSION['user_uuid'],
        $revocationReason
    );
    
    if (!$success) {
        throw new Exception("Failed to revoke certificate");
    }
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Certificate revoked successfully',
        'data' => [
            'certificate_uuid' => $certificateUuid,
            'certificate_number' => $certificate['certificate_number'],
            'revoked_by' => $_SESSION['user_uuid'],
            'revoked_at' => date('Y-m-d H:i:s'),
        ],
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
