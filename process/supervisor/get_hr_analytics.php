<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once "../../config/db.php";
require_once "../../functions/company_hr_functions.php";

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$userUuid = $_SESSION['user_uuid'];
$stmt = $conn->prepare("SELECT company_uuid, is_hr_admin FROM supervisor_profiles WHERE user_uuid = ? LIMIT 1");
$stmt->bind_param("s", $userUuid);
$stmt->execute();
$profRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profRow || (int)$profRow['is_hr_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Not an HR Admin']);
    exit;
}

$companyUuid = $profRow['company_uuid'];

try {
    $analytics = getCompanyHRAnalytics($conn, $companyUuid);
    
    $companyData = getCompany($conn, $companyUuid);

    $students = getCompanyStudents($conn, $companyUuid, '');

    $stmt = $conn->prepare("
        SELECT uuid, user_uuid, first_name, last_name, position, department, is_active, is_hr_admin, profile_path 
        FROM supervisor_profiles 
        WHERE company_uuid = ? 
        ORDER BY is_active DESC, first_name ASC
    ");
    $stmt->bind_param("s", $companyUuid);
    $stmt->execute();
    $supervisors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'analytics' => $analytics,
        'supervisors' => $supervisors,
        'company' => $companyData['company'] ?? null,
        'documents' => $companyData['documents'] ?? [],
        'students' => $students
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch HR data: ' . $e->getMessage()]);
}
