<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        http_response_code(403);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/auth_functions.php';
require_once dirname(__DIR__, 2) . '/functions/batch_functions.php';
require_once dirname(__DIR__, 2) . '/functions/student_functions.php';
require_once dirname(__DIR__, 2) . '/functions/supervisor_functions.php';
require_once dirname(__DIR__, 2) . '/functions/profile_functions.php';
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'supervisor') {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'critical', 'message' => 'Invalid CSRF token.']);
}

$userUuid = $_SESSION['user_uuid'];
$profileUuid = $_SESSION['profile_uuid'];


$stmt = $conn->prepare("
    SELECT 
        u.email, CASE WHEN u.is_active = 1 THEN 'active' ELSE 'inactive' END as account_status, u.last_login_at as last_login,
        sp.*,
        c.name as company_name
    FROM users u
    JOIN supervisor_profiles sp ON u.uuid = sp.user_uuid
    LEFT JOIN companies c ON sp.company_uuid = c.uuid
    WHERE sp.uuid = ?
");
$stmt->bind_param('s', $profileUuid);
$stmt->execute();
$supervisor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$supervisor) {
    response(['status' => 'error', 'message' => 'Profile not found.']);
}


$supervisor['full_name'] = $supervisor['first_name'] . ' ' . $supervisor['last_name'];
$supervisor['initials'] = strtoupper(substr($supervisor['first_name'], 0, 1) . substr($supervisor['last_name'], 0, 1));
$supervisor['status_label'] = ucfirst($supervisor['account_status']);
$supervisor['created_at_label'] = date('M j, Y', strtotime($supervisor['created_at']));


$students = [];
$stmt = $conn->prepare("
    SELECT 
        sp.first_name, sp.last_name, sp.student_number, sp.program, sp.profile_name,
        oa.status as app_status
    FROM student_profiles sp
    JOIN ojt_applications oa ON sp.uuid = oa.student_uuid
    WHERE sp.supervisor_uuid = ? AND oa.status = 'active'
");
$stmt->bind_param('s', $profileUuid);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
    $row['initials'] = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
    $students[] = $row;
}
$stmt->close();

$profileImage = "https://placehold.co/128x128/C1C1C1/000000/png?text=" . $supervisor['initials'] . "&font=poppins";
if (!empty($supervisor['profile_name'])) {
    $profileImage = "../../../Assets/Images/profiles/" . $supervisor['profile_name'];
}

response([
    'status'       => 'success',
    'profile'      => $supervisor,
    'profileImage' => $profileImage,
    'students'     => $students,
    'studentCount' => count($students)
]);
