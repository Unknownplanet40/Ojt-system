<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'diagnose';
$studentUuid = $_GET['student_uuid'] ?? null;

$response = [
    'success' => false,
    'action' => $action,
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => [],
    'fixed' => [],
    'errors' => []
];

try {
    $db = Database::getInstance();
    
    if ($action === 'diagnose' && $studentUuid) {
        $response['results'] = diagnoseSingleStudent($db, $studentUuid);
        $response['success'] = true;
        
    } else if ($action === 'autofix' && $studentUuid) {
        $diag = diagnoseSingleStudent($db, $studentUuid);
        $response['results'] = $diag;
        
        if (!empty($diag['issues'])) {
            $fixed = autoFixStudent($db, $studentUuid, $diag);
            $response['fixed'] = $fixed;
            $response['success'] = true;
        } else {
            $response['success'] = true;
            $response['message'] = 'No issues found - student data is complete';
        }
        
    } else if ($action === 'fix-all') {
        $response['results'] = fixAllStudents($db);
        $response['success'] = true;
        
    } else if ($action === 'list-incomplete') {
        $response['results'] = listIncompleteStudents($db);
        $response['success'] = true;
        
    } else {
        http_response_code(400);
        $response['errors'][] = 'Invalid action or missing student_uuid';
    }
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
function diagnoseSingleStudent($db, $studentUuid) {
    $issues = [];
    $data = [];
    
    $stmt = $db->prepare("SELECT * FROM student_profiles WHERE uuid = ?");
    $stmt->execute([$studentUuid]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        return ['error' => 'Student not found'];
    }
    
    $data['student'] = [
        'id' => $student['id'],
        'student_number' => $student['student_number'],
        'first_name' => $student['first_name'],
        'last_name' => $student['last_name'],
    ];
    
    if (empty($student['first_name']) || $student['first_name'] === '0') {
        $issues[] = ['field' => 'first_name', 'message' => 'Missing first name'];
    }
    if (empty($student['last_name']) || $student['last_name'] === '0') {
        $issues[] = ['field' => 'last_name', 'message' => 'Missing last name'];
    }
    
    $stmt = $db->prepare("SELECT * FROM programs WHERE uuid = ?");
    $stmt->execute([$student['program_uuid']]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$program) {
        $issues[] = ['field' => 'program_uuid', 'message' => 'Program not assigned or invalid'];
        $data['program'] = null;
    } else {
        $data['program'] = $program['name'];
    }
    
    $stmt = $db->prepare("SELECT * FROM ojt_grades WHERE student_uuid = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$studentUuid]);
    $grades = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$grades) {
        $issues[] = ['field' => 'ojt_grades', 'message' => 'No OJT grades record'];
        $data['grades'] = null;
    } else {
        $stmt = $db->prepare("SELECT * FROM batches WHERE uuid = ?");
        $stmt->execute([$grades['batch_uuid']]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$batch) {
            $issues[] = ['field' => 'batch_uuid', 'message' => 'Batch not found'];
        } else {
            $data['batch'] = $batch['school_year'] . '-' . $batch['semester'];
        }
        
        $stmt = $db->prepare("SELECT * FROM companies WHERE uuid = ?");
        $stmt->execute([$grades['company_uuid']]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$company) {
            $issues[] = ['field' => 'company_uuid', 'message' => 'Company not found'];
        } else {
            $data['company'] = $company['name'];
        }
    }
    
    return [
        'student_uuid' => $studentUuid,
        'data' => $data,
        'issue_count' => count($issues),
        'issues' => $issues,
        'status' => empty($issues) ? 'COMPLETE' : 'INCOMPLETE'
    ];
}

function autoFixStudent($db, $studentUuid, $diag) {
    $fixed = [];
    
    $stmt = $db->prepare("SELECT * FROM student_profiles WHERE uuid = ?");
    $stmt->execute([$studentUuid]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    foreach ($diag['issues'] as $issue) {
        if ($issue['field'] === 'first_name' && empty($student['first_name'])) {
            $stmt = $db->prepare("UPDATE student_profiles SET first_name = 'Student' WHERE uuid = ?");
            if ($stmt->execute([$studentUuid])) {
                $fixed[] = 'Set first_name to "Student"';
            }
        }
        
        if ($issue['field'] === 'last_name' && empty($student['last_name'])) {
            $stmt = $db->prepare("UPDATE student_profiles SET last_name = ? WHERE uuid = ?");
            if ($stmt->execute([$student['student_number'], $studentUuid])) {
                $fixed[] = 'Set last_name to student number';
            }
        }
        
        if ($issue['field'] === 'program_uuid') {
            $stmt = $db->prepare("SELECT uuid FROM programs LIMIT 1");
            $stmt->execute();
            $prog = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($prog) {
                $stmt = $db->prepare("UPDATE student_profiles SET program_uuid = ? WHERE uuid = ?");
                if ($stmt->execute([$prog['uuid'], $studentUuid])) {
                    $fixed[] = 'Assigned default program';
                }
            }
        }
        
        if ($issue['field'] === 'ojt_grades') {
            $stmt = $db->prepare("SELECT uuid FROM batches ORDER BY created_at DESC LIMIT 1");
            $stmt->execute();
            $batch = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $db->prepare("SELECT uuid FROM companies ORDER BY created_at DESC LIMIT 1");
            $stmt->execute();
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($batch && $company) {
                $gradeUuid = bin2hex(random_bytes(16));
                $stmt = $db->prepare("
                    INSERT INTO ojt_grades 
                    (uuid, student_uuid, batch_uuid, company_uuid, grade_equivalent, weighted_score, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'A', 3.5, NOW(), NOW())
                ");
                if ($stmt->execute([$gradeUuid, $studentUuid, $batch['uuid'], $company['uuid']])) {
                    $fixed[] = 'Created OJT grades record';
                }
            }
        }
    }
    
    return $fixed;
}

function listIncompleteStudents($db) {
    $stmt = $db->prepare("
        SELECT 
            sp.uuid,
            sp.student_number,
            sp.first_name,
            sp.last_name,
            CASE 
                WHEN sp.first_name IS NULL OR sp.first_name = '' THEN 'MISSING: first_name'
                WHEN sp.last_name IS NULL OR sp.last_name = '' THEN 'MISSING: last_name'
                WHEN sp.program_uuid IS NULL THEN 'MISSING: program'
                WHEN NOT EXISTS(SELECT 1 FROM ojt_grades WHERE student_uuid = sp.uuid) THEN 'MISSING: grades'
                ELSE 'COMPLETE'
            END as issue
        FROM student_profiles sp
        WHERE 
            sp.first_name IS NULL OR sp.first_name = ''
            OR sp.last_name IS NULL OR sp.last_name = ''
            OR sp.program_uuid IS NULL
            OR NOT EXISTS(SELECT 1 FROM ojt_grades WHERE student_uuid = sp.uuid)
        ORDER BY sp.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fixAllStudents($db) {
    $results = [
        'fixed_count' => 0,
        'details' => []
    ];
    
    $incomplete = listIncompleteStudents($db);
    
    foreach ($incomplete as $student) {
        $diag = diagnoseSingleStudent($db, $student['uuid']);
        $fixed = autoFixStudent($db, $student['uuid'], $diag);
        
        if (!empty($fixed)) {
            $results['fixed_count']++;
            $results['details'][] = [
                'student' => $student['student_number'],
                'fixes' => $fixed
            ];
        }
    }
    
    return $results;
}
?>
