<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        http_response_code(403);
        header("Location: $base/Src/Pages/ErrorPage?error=403");
        exit;
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/company_functions.php';
require_once dirname(__DIR__, 2) . '/functions/student_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'coordinator') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$companyUuid = trim($_POST['uuid'] ?? '');

if (empty($companyUuid)) {
    echo json_encode(['status' => 'error', 'message' => 'Company UUID is required.']);
    exit;
}

$result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
$activeBatch = $result->fetch_assoc();
$batchUuid = $activeBatch['uuid'] ?? null;

$company = getCompany($conn, $companyUuid, $batchUuid);

if (!$company) {
    echo json_encode(['status' => 'error', 'message' => 'Company not found.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        sp.uuid AS profile_uuid,
        sp.first_name,
        sp.last_name,
        sp.profile_name,
        p.code AS program_code
    FROM student_profiles sp
    LEFT JOIN programs p ON sp.program_uuid = p.uuid
    WHERE sp.company_uuid = ? AND sp.batch_uuid = ?
    ORDER BY sp.last_name ASC
");
$stmt->bind_param('ss', $companyUuid, $batchUuid);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'status'   => 'success',
    'company'  => $company,
    'students' => $students
]);
