<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

require_once __DIR__ . '/../../helpers/helpers.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../functions/certificate_functions.php';

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function isValidToken($token) {
    return preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
}

try {
    $token = $_GET['token'] ?? null;
    
    if (!$token) {
        sendResponse([
            'success' => false,
            'error' => 'Verification token is required',
            'code' => 'MISSING_TOKEN'
        ], 400);
    }
    
    if (!isValidToken($token)) {
        sendResponse([
            'success' => false,
            'error' => 'Invalid token format. Token must be 64 hexadecimal characters.',
            'code' => 'INVALID_TOKEN_FORMAT'
        ], 400);
    }
    
    $manager = getCertificateManager();
    
    $statusInfo = $manager->getVerificationStatus($token);
    
    if (!$statusInfo['valid']) {
        sendResponse([
            'success' => false,
            'error' => $statusInfo['message'],
            'code' => strtoupper($statusInfo['status']),
            'status' => $statusInfo['status']
        ], $statusInfo['status'] === 'not_found' ? 404 : 200);
    }
    
    $certificate = $manager->verifyCertificateByToken($token);
    
    if (!$certificate) {
        sendResponse([
            'success' => false,
            'error' => 'Certificate verification failed',
            'code' => 'VERIFICATION_FAILED'
        ], 404);
    }
    
    $studentName = trim(($certificate['first_name'] ?? '') . ' ' . 
                        ($certificate['middle_name'] ? $certificate['middle_name'] . ' ' : '') . 
                        ($certificate['last_name'] ?? ''));
    
    $response = [
        'success' => true,
        'data' => [
            'certificate' => [
                'uuid' => $certificate['certificate_uuid'],
                'number' => $certificate['certificate_number'],
                'issuedDate' => $certificate['generated_at'],
                'completionDate' => $certificate['completion_date'],
                'expiresDate' => $certificate['expires_at'],
                'status' => 'VALID & AUTHENTIC',
                'isRevoked' => false
            ],
            'student' => [
                'id' => $certificate['student_id'],
                'name' => $studentName,
                'program' => $certificate['program_name'],
                'yearLevel' => $certificate['year_level'],
                'section' => $certificate['section']
            ],
            'program' => [
                'name' => $certificate['program_name'],
                'company' => $certificate['company_name'],
                'hoursCompleted' => (int)$certificate['hours_completed'],
                'schoolYear' => $certificate['school_year'],
                'semester' => $certificate['semester']
            ],
            'academic' => [
                'grade' => $certificate['grade'],
                'gpa' => (float)$certificate['gpa']
            ],
            'verification' => [
                'token' => substr($token, 0, 16) . '...' . substr($token, -16),
                'timestamp' => date('Y-m-d\TH:i:s\Z'),
                'status' => 'valid',
                'message' => 'Certificate is authentic and has not been revoked'
            ]
        ]
    ];
    
    sendResponse($response, 200);
    
} catch (Exception $e) {
    error_log("Certificate verification error: " . $e->getMessage());
    
    sendResponse([
        'success' => false,
        'error' => 'An error occurred during verification',
        'code' => 'INTERNAL_ERROR'
    ], 500);
}
?>
