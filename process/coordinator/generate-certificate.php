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
        'message' => 'Permission denied. Only coordinators can generate certificates.',
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
    
    $studentUuid = $data['student_uuid'] ?? null;
    $gradesUuid = $data['ojt_grades_uuid'] ?? null;
    $batchUuid = $data['batch_uuid'] ?? null;
    $companyUuid = $data['company_uuid'] ?? null;
    $hoursCompleted = $data['hours_completed'] ?? null;
    $completionDate = $data['completion_date'] ?? null;
    
    if (!$studentUuid || !$gradesUuid || !$batchUuid || !$companyUuid || !$hoursCompleted || !$completionDate) {
        throw new Exception('Missing required fields: student_uuid, ojt_grades_uuid, batch_uuid, company_uuid, hours_completed, completion_date');
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $completionDate)) {
        throw new Exception('Invalid completion_date format. Use YYYY-MM-DD');
    }
    
    $db = Database::getInstance();
    
    $query = "SELECT uuid FROM student_profiles WHERE uuid = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$studentUuid]);
    if (!$stmt->fetch()) {
        throw new Exception("Student not found: $studentUuid");
    }
    
    $query = "SELECT uuid, is_finalized FROM ojt_grades WHERE uuid = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$gradesUuid]);
    $grades = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$grades) {
        throw new Exception("Grades not found: $gradesUuid");
    }
    
    if (!$grades['is_finalized']) {
        throw new Exception("Cannot generate certificate: Grades are not finalized");
    }
    
    $query = "SELECT uuid FROM certificates WHERE student_uuid = ? AND batch_uuid = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$studentUuid, $batchUuid]);
    if ($stmt->fetch()) {
        throw new Exception("Certificate already exists for this student in this batch");
    }
    
    $query = "SELECT * FROM system_config LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $schoolConfig = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
    
    $certificateManager = getCertificateManager();
    
    $certificateNumber = $certificateManager->generateCertificateNumber($companyUuid);
    
    $verificationToken = $certificateManager->generateVerificationToken();
    
    $filePath = $certificateManager->generateCertificatePDF(
        $studentUuid,
        $gradesUuid,
        $certificateNumber,
        $verificationToken,
        $schoolConfig
    );
    
    if (!$filePath) {
        throw new Exception("Failed to generate certificate PDF");
    }
    
    $certificateData = [
        'student_uuid' => $studentUuid,
        'ojt_grades_uuid' => $gradesUuid,
        'batch_uuid' => $batchUuid,
        'company_uuid' => $companyUuid,
        'certificate_number' => $certificateNumber,
        'verification_token' => $verificationToken,
        'file_path' => $filePath,
        'hours_completed' => $hoursCompleted,
        'completion_date' => $completionDate,
        'generated_by' => $_SESSION['user_uuid'],
    ];
    
    $certificateUuid = $certificateManager->createCertificate($certificateData);
    
    if (!$certificateUuid) {
        throw new Exception("Failed to store certificate in database");
    }
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Certificate generated successfully',
        'data' => [
            'certificate_uuid' => $certificateUuid,
            'certificate_number' => $certificateNumber,
            'file_path' => $filePath,
            'verification_token' => $verificationToken,
            'download_url' => '/file_serve.php?type=certificate&cert_uuid=' . urlencode($certificateUuid),
        ],
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
