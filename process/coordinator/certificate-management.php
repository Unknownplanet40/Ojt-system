<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../functions/certificate_functions.php';
require_once __DIR__ . '/../../helpers/helpers.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['user_uuid']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!in_array($_SESSION['user_role'], ['coordinator', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Coordinator access required']);
    exit;
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance();
    $manager = getCertificateManager();
    $logger = new Logger();
    
    switch($action) {
        case 'list':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            listCertificates();
            break;
            
        case 'revoke':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            revokeCertificate();
            break;
            
        case 'statistics':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            getStatistics();
            break;
            
        case 'bulk-generate':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            bulkGenerateCertificates();
            break;
            
        case 'eligible-students':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
            }
            listEligibleStudents();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Coordinator API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function listCertificates() {
    global $db;
    
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all'; // all, valid, revoked
    $sortBy = $_GET['sortBy'] ?? 'generated_at';
    $sortOrder = ($_GET['sortOrder'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['perPage'] ?? 20);
    
    $where = "WHERE 1=1";
    $params = [];
    
    if ($search) {
        $where .= " AND (c.certificate_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR cp.name LIKE ?)";
        $searchTerm = "%{$search}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($status === 'revoked') {
        $where .= " AND c.is_revoked = 1";
    } elseif ($status === 'valid') {
        $where .= " AND c.is_revoked = 0";
    }
    
    $countQuery = "SELECT COUNT(*) as total FROM certificates c 
                   LEFT JOIN student_profiles s ON c.student_uuid = s.uuid
                   LEFT JOIN companies cp ON c.company_uuid = cp.uuid
                   {$where}";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $offset = ($page - 1) * $perPage;
    $query = "SELECT c.uuid, c.certificate_number, c.hours_completed, c.generated_at,
                     c.is_revoked, c.revocation_reason,
                     s.first_name, s.last_name, s.student_number as student_id,
                     cp.name as company_name
              FROM certificates c
              LEFT JOIN student_profiles s ON c.student_uuid = s.uuid
              LEFT JOIN companies cp ON c.company_uuid = cp.uuid
              {$where}
              ORDER BY c.{$sortBy} {$sortOrder}
              LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($query);
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $certificates,
        'pagination' => [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => ceil($total / $perPage)
        ]
    ]);
}

function revokeCertificate() {
    global $db, $manager, $logger;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['certificate_uuid']) || !isset($data['reason'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $certificateUuid = $data['certificate_uuid'];
    $reason = $data['reason'];
    $revokedByUuid = $_SESSION['user_uuid'];
    
    try {
        $requireApproval = $_SESSION['user_role'] !== 'admin';
        $manager->revokeCertificate($certificateUuid, $reason, $revokedByUuid, $requireApproval);
        
        // Log activity
        $logger->log('certificate_revoked', "Certificate revoked by " . $_SESSION['user_role'], 'certificates', 
                    json_encode(['certificate_uuid' => $certificateUuid, 'reason' => $reason]));
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Certificate revoked successfully',
            'requiresApproval' => $requireApproval
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getStatistics() {
    global $db;
    
    $startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['endDate'] ?? date('Y-m-d');
    
    try {
        $query = "SELECT COUNT(*) as total, 
                        SUM(CASE WHEN is_revoked = 1 THEN 1 ELSE 0 END) as revoked,
                        AVG(hours_completed) as avg_hours
                 FROM certificates
                 WHERE generated_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
        $stmt = $db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT DATE(accessed_at) as date, COUNT(*) as count
                 FROM verification_logs
                 WHERE accessed_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
                 GROUP BY DATE(accessed_at)
                 ORDER BY date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $query = "SELECT c.certificate_number, s.first_name, s.last_name, COUNT(*) as count
                 FROM verification_logs vl
                 JOIN certificates c ON vl.certificate_uuid = c.uuid
                 JOIN student_profiles s ON c.student_uuid = s.uuid
                 WHERE vl.accessed_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
                 GROUP BY vl.certificate_uuid
                 ORDER BY count DESC
                 LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        $topVerified = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'statistics' => [
                'totalCertificates' => (int)$stats['total'],
                'revokedCertificates' => (int)($stats['revoked'] ?? 0),
                'averageHours' => (float)($stats['avg_hours'] ?? 0),
                'validCertificates' => (int)($stats['total'] - ($stats['revoked'] ?? 0))
            ],
            'verificationTrend' => $verifications,
            'topVerified' => $topVerified
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function listEligibleStudents() {
    global $db;
    
    try {
        $query = "SELECT DISTINCT sp.uuid as student_uuid, sp.first_name, sp.last_name, sp.student_number as student_id,
                          og.uuid as ojt_grades_uuid, og.batch_uuid, oa.company_uuid,
                          com.name as company_name, b.uuid as batch_name,
                          (SELECT COALESCE(SUM(hours_rendered), 0) FROM dtr_entries WHERE student_uuid = sp.uuid AND status = 'approved') as hours_completed
                  FROM student_profiles sp
                  JOIN ojt_grades og ON sp.uuid = og.student_uuid
                  LEFT JOIN ojt_applications oa ON og.application_uuid = oa.uuid
                  LEFT JOIN companies com ON oa.company_uuid = com.uuid
                  LEFT JOIN batches b ON og.batch_uuid = b.uuid
                  LEFT JOIN certificates cert ON sp.uuid = cert.student_uuid AND og.batch_uuid = cert.batch_uuid
                  WHERE og.is_finalized = 1 AND cert.uuid IS NULL";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'students' => $students
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function bulkGenerateCertificates() {
    global $db, $manager, $logger;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['studentUuids']) || !is_array($data['studentUuids'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid studentUuids']);
        return;
    }
    
    $studentUuids = $data['studentUuids'];
    $generated = 0;
    $failed = 0;
    $errors = [];
    
    try {
        $query = "SELECT * FROM system_config LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $schoolConfig = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
        
        $db->beginTransaction();
        
        foreach ($studentUuids as $studentUuid) {
            try {
                $query = "SELECT og.uuid as ojt_grades_uuid, og.batch_uuid, oa.company_uuid,
                                 (SELECT COALESCE(SUM(hours_rendered), 0) FROM dtr_entries WHERE student_uuid = sp.uuid AND status = 'approved') as hours_completed
                          FROM ojt_grades og
                          JOIN student_profiles sp ON og.student_uuid = sp.uuid
                          LEFT JOIN ojt_applications oa ON og.application_uuid = oa.uuid
                          WHERE sp.uuid = ? AND og.is_finalized = 1
                          LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute([$studentUuid]);
                $grades = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$grades) {
                    $failed++;
                    $errors[] = "Student {$studentUuid}: No finalized OJT grades found";
                    continue;
                }
                
                $query = "SELECT uuid FROM certificates WHERE student_uuid = ? AND batch_uuid = ? LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute([$studentUuid, $grades['batch_uuid']]);
                if ($stmt->fetch()) {
                    $failed++;
                    $errors[] = "Student {$studentUuid}: Certificate already generated for this batch";
                    continue;
                }
                
                $certificateNumber = $manager->generateCertificateNumber($grades['company_uuid']);
                $verificationToken = $manager->generateVerificationToken();
                
                $completionDate = date('Y-m-d');
                $hoursCompleted = (int)$grades['hours_completed'];
                
                $filePath = $manager->generateCertificatePDF(
                    $studentUuid,
                    $grades['ojt_grades_uuid'],
                    $certificateNumber,
                    $verificationToken,
                    $schoolConfig
                );
                
                if (!$filePath) {
                    throw new Exception("PDF generation failed");
                }
                
                $certificateData = [
                    'student_uuid' => $studentUuid,
                    'ojt_grades_uuid' => $grades['ojt_grades_uuid'],
                    'batch_uuid' => $grades['batch_uuid'],
                    'company_uuid' => $grades['company_uuid'],
                    'certificate_number' => $certificateNumber,
                    'verification_token' => $verificationToken,
                    'file_path' => $filePath,
                    'hours_completed' => $hoursCompleted,
                    'completion_date' => $completionDate,
                    'generated_by' => $_SESSION['user_uuid'],
                ];
                
                $certificateUuid = $manager->createCertificate($certificateData);
                
                if (!$certificateUuid) {
                    throw new Exception("Database record creation failed");
                }
                
                $generated++;
                
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Student {$studentUuid}: " . $e->getMessage();
            }
        }
        
        $db->commit();
        
        http_response_code($generated > 0 ? 200 : 400);
        echo json_encode([
            'success' => $generated > 0,
            'generated' => $generated,
            'failed' => $failed,
            'error' => $generated === 0 && $failed > 0 ? "Failed to generate certificates. Check logs for details. First error: " . ($errors[0] ?? 'Unknown error') : null,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
