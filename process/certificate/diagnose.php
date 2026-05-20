<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/helpers.php';
require_once __DIR__ . '/../../functions/certificate_functions.php';

header('Content-Type: application/json; charset=utf-8');

$studentUuid = $_GET['student_uuid'] ?? null;

if (!$studentUuid) {
    http_response_code(400);
    echo json_encode(['error' => 'student_uuid parameter is required'], JSON_PRETTY_PRINT);
    exit;
}

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'student_uuid' => $studentUuid,
    'checks' => [],
    'issues' => [],
    'recommendations' => []
];

try {
    $db = Database::getInstance();
    $diagnostics['checks']['student_profile'] = [];
    $stmt = $db->prepare("SELECT * FROM student_profiles WHERE uuid = ?");
    $stmt->execute([$studentUuid]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        $diagnostics['checks']['student_profile']['status'] = 'FAILED';
        $diagnostics['checks']['student_profile']['message'] = 'Student profile not found';
        $diagnostics['issues'][] = 'Student with UUID ' . substr($studentUuid, 0, 8) . '... does not exist in student_profiles table';
        $diagnostics['recommendations'][] = 'Verify the student UUID is correct and student has been enrolled';
    } else {
        $diagnostics['checks']['student_profile']['status'] = 'OK';
        $diagnostics['checks']['student_profile']['data'] = [
            'id' => $student['id'],
            'student_number' => $student['student_number'],
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'program_uuid' => substr($student['program_uuid'], 0, 8) . '...',
            'user_uuid' => isset($student['user_uuid']) ? substr($student['user_uuid'], 0, 8) . '...' : 'NULL'
        ];
    }
    
    if ($student) {
        $diagnostics['checks']['program'] = [];
        $stmt = $db->prepare("SELECT uuid, name FROM programs WHERE uuid = ?");
        $stmt->execute([$student['program_uuid']]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$program) {
            $diagnostics['checks']['program']['status'] = 'FAILED';
            $diagnostics['checks']['program']['message'] = 'Program not found';
            $diagnostics['issues'][] = 'Program associated with student not found in programs table';
            $diagnostics['recommendations'][] = 'Ensure student is assigned to a valid program';
        } else {
            $diagnostics['checks']['program']['status'] = 'OK';
            $diagnostics['checks']['program']['name'] = $program['name'];
        }
    }
    
    if ($student) {
        $diagnostics['checks']['ojt_grades'] = [];
        $stmt = $db->prepare("SELECT * FROM ojt_grades WHERE student_uuid = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$studentUuid]);
        $grades = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$grades) {
            $diagnostics['checks']['ojt_grades']['status'] = 'FAILED';
            $diagnostics['checks']['ojt_grades']['message'] = 'No OJT grades found for student';
            $diagnostics['issues'][] = 'Student has no OJT grades record (ojt_grades table)';
            $diagnostics['recommendations'][] = 'Ensure grades have been entered for this student';
        } else {
            $diagnostics['checks']['ojt_grades']['status'] = 'OK';
            $diagnostics['checks']['ojt_grades']['data'] = [
                'uuid' => substr($grades['uuid'], 0, 8) . '...',
                'batch_uuid' => substr($grades['batch_uuid'], 0, 8) . '...',
                'company_uuid' => substr($grades['company_uuid'], 0, 8) . '...',
                'grade_equivalent' => $grades['grade_equivalent'] ?? 'NULL',
                'weighted_score' => $grades['weighted_score'] ?? 'NULL'
            ];
        }
    }
    
    if ($student && $grades) {
        $diagnostics['checks']['batch'] = [];
        $stmt = $db->prepare("SELECT * FROM batches WHERE uuid = ?");
        $stmt->execute([$grades['batch_uuid']]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$batch) {
            $diagnostics['checks']['batch']['status'] = 'FAILED';
            $diagnostics['checks']['batch']['message'] = 'Batch not found';
            $diagnostics['issues'][] = 'Batch associated with grades not found in batches table';
            $diagnostics['recommendations'][] = 'Verify batch exists and UUID is correct in ojt_grades table';
        } else {
            $diagnostics['checks']['batch']['status'] = 'OK';
            $diagnostics['checks']['batch']['data'] = [
                'school_year' => $batch['school_year'],
                'semester' => $batch['semester'],
                'start_date' => $batch['start_date'],
                'end_date' => $batch['end_date']
            ];
        }
    }
    
    if ($student && $grades) {
        $diagnostics['checks']['company'] = [];
        $stmt = $db->prepare("SELECT * FROM companies WHERE uuid = ?");
        $stmt->execute([$grades['company_uuid']]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$company) {
            $diagnostics['checks']['company']['status'] = 'FAILED';
            $diagnostics['checks']['company']['message'] = 'Company not found';
            $diagnostics['issues'][] = 'Company associated with grades not found in companies table';
            $diagnostics['recommendations'][] = 'Verify company exists and UUID is correct in ojt_grades table';
        } else {
            $diagnostics['checks']['company']['status'] = 'OK';
            $diagnostics['checks']['company']['data'] = [
                'name' => $company['name'],
                'address' => $company['address']
            ];
        }
    }
    $diagnostics['checks']['coordinator'] = [];
    if (isset($_SESSION['user_uuid'])) {
        $stmt = $db->prepare("SELECT first_name, last_name FROM coordinator_profiles WHERE user_uuid = ?");
        $stmt->execute([$_SESSION['user_uuid']]);
        $coord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($coord) {
            $diagnostics['checks']['coordinator']['status'] = 'OK';
            $diagnostics['checks']['coordinator']['name'] = $coord['first_name'] . ' ' . $coord['last_name'];
        } else {
            $diagnostics['checks']['coordinator']['status'] = 'WARNING';
            $diagnostics['checks']['coordinator']['message'] = 'Coordinator profile not found, will use default name';
        }
    } else {
        $diagnostics['checks']['coordinator']['status'] = 'WARNING';
        $diagnostics['checks']['coordinator']['message'] = 'No user session, will use default coordinator name';
    }
    
    $diagnostics['checks']['mpdf'] = [];
    $mpdfPath = __DIR__ . '/../../libs/composer/vendor/autoload.php';
    if (file_exists($mpdfPath)) {
        $diagnostics['checks']['mpdf']['status'] = 'OK';
        $diagnostics['checks']['mpdf']['message'] = 'mPDF library found';
    } else {
        $diagnostics['checks']['mpdf']['status'] = 'FAILED';
        $diagnostics['checks']['mpdf']['message'] = 'mPDF library not found';
        $diagnostics['issues'][] = 'mPDF library not installed or not at expected location';
        $diagnostics['recommendations'][] = 'Run: composer require mpdf/mpdf';
    }
    
    $diagnostics['checks']['directories'] = [];
    $certsDir = __DIR__ . '/../../uploads/certificates';
    if (is_dir($certsDir) && is_writable($certsDir)) {
        $diagnostics['checks']['directories']['status'] = 'OK';
        $diagnostics['checks']['directories']['certificates_dir'] = 'writable';
    } else {
        $diagnostics['checks']['directories']['status'] = 'FAILED';
        if (!is_dir($certsDir)) {
            $diagnostics['checks']['directories']['message'] = 'Certificates directory does not exist';
            $diagnostics['issues'][] = 'Directory uploads/certificates/ not found';
        } else {
            $diagnostics['checks']['directories']['message'] = 'Certificates directory not writable';
            $diagnostics['issues'][] = 'Directory uploads/certificates/ is not writable';
        }
        $diagnostics['recommendations'][] = 'Create directory or fix permissions: chmod 750 uploads/certificates/';
    }
    
    $diagnostics['checks']['php'] = [];
    $memoryLimit = ini_get('memory_limit');
    $diagnostics['checks']['php']['memory_limit'] = $memoryLimit;
    if (parseBytes($memoryLimit) >= 128 * 1024 * 1024) {
        $diagnostics['checks']['php']['memory_status'] = 'OK';
    } else {
        $diagnostics['checks']['php']['memory_status'] = 'WARNING';
        $diagnostics['issues'][] = 'PHP memory limit may be too low for PDF generation';
        $diagnostics['recommendations'][] = 'Increase memory_limit in php.ini to at least 256M';
    }
    
    $diagnostics['summary'] = [
        'total_checks' => count($diagnostics['checks']),
        'passed' => count(array_filter($diagnostics['checks'], fn($c) => $c['status'] === 'OK')),
        'failed' => count(array_filter($diagnostics['checks'], fn($c) => $c['status'] === 'FAILED')),
        'warnings' => count(array_filter($diagnostics['checks'], fn($c) => $c['status'] === 'WARNING'))
    ];
    
    http_response_code(200);
    echo json_encode($diagnostics, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Diagnostic failed',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
function parseBytes($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value) - 1]);
    $value = (int)$value;
    
    switch ($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    
    return $value;
}
?>
