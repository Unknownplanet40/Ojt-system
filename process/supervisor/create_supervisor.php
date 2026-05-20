<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/supervisor_functions.php';

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
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Only HR Administrators can add supervisors.']);
    exit;
}

$companyUuid = $profRow['company_uuid'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

$data = [
    'email' => trim($_POST['email'] ?? ''),
    'company_uuid' => $companyUuid,
    'first_name' => trim($_POST['first_name'] ?? ''),
    'last_name' => trim($_POST['last_name'] ?? ''),
    'position' => trim($_POST['position'] ?? ''),
    'department' => trim($_POST['department'] ?? ''),
    'mobile' => trim($_POST['mobile'] ?? '')
];

$result = createSupervisor($conn, $data, $userUuid);

if (!$result['success']) {
    echo json_encode([
        'status' => 'error',
        'message' => reset($result['errors']) ?: 'Failed to create supervisor.'
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => 'Supervisor account created successfully.',
    'temp_password' => $result['temp_password']
]);
exit;
