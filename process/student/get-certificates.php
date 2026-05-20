<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_uuid']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Please log in.',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use GET.',
    ]);
    exit;
}

try {
    $studentUuid = $_GET['student_uuid'] ?? null;
    
    if (!$studentUuid) {
        if ($_SESSION['user_role'] === 'student') {
            $db = Database::getInstance();
            $query = "SELECT uuid FROM student_profiles WHERE user_uuid = ? LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['user_uuid']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                throw new Exception("Student profile not found for current user");
            }
            $studentUuid = $student['uuid'];
        } else {
            throw new Exception("Missing required parameter: student_uuid");
        }
    }
    
    if ($_SESSION['user_role'] === 'student') {
        $db = Database::getInstance();
        $query = "SELECT uuid FROM student_profiles WHERE uuid = ? AND user_uuid = ? LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$studentUuid, $_SESSION['user_uuid']]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Permission denied. You can only view your own certificates.',
            ]);
            exit;
        }
    } elseif ($_SESSION['user_role'] !== 'coordinator' && $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Permission denied. Only students, coordinators, and admins can view certificates.',
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    
    $query = "SELECT c.uuid, c.certificate_number, c.completion_date, c.generated_at,
                     c.is_revoked, c.file_path, c.hours_completed, c.verification_token,
                     sp.first_name, sp.last_name,
                     og.weighted_score, og.grade_equivalent,
                     com.name as company_name,
                     b.start_date as batch_start, b.end_date as batch_end
              FROM certificates c
              JOIN student_profiles sp ON c.student_uuid = sp.uuid
              LEFT JOIN ojt_grades og ON c.ojt_grades_uuid = og.uuid
              LEFT JOIN companies com ON c.company_uuid = com.uuid
              LEFT JOIN batches b ON c.batch_uuid = b.uuid
              WHERE c.student_uuid = ?
              ORDER BY c.generated_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$studentUuid]);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => count($certificates) > 0 ? 'Certificates retrieved successfully' : 'No certificates found',
        'data' => $certificates,
        'count' => count($certificates),
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
