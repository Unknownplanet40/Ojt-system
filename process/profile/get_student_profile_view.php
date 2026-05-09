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
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'student') {
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

// Get Student Details
$stmt = $conn->prepare("
    SELECT 
        u.email, CASE WHEN u.is_active = 1 THEN 'active' ELSE 'inactive' END as account_status,
        sp.*,
        p.name as program_name, p.code as program_code,
        oa.status as ojt_status,
        c.name as company_name,
        sv.first_name as sv_first, sv.last_name as sv_last
    FROM users u
    JOIN student_profiles sp ON u.uuid = sp.user_uuid
    LEFT JOIN programs p ON sp.program_uuid = p.uuid
    LEFT JOIN ojt_applications oa ON sp.uuid = oa.student_uuid
    LEFT JOIN companies c ON sp.company_uuid = c.uuid
    LEFT JOIN supervisor_profiles sv ON sp.supervisor_uuid = sv.uuid
    WHERE sp.uuid = ?
");
$stmt->bind_param('s', $profileUuid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    response(['status' => 'error', 'message' => 'Profile not found.']);
}

// Formatting
$student['full_name'] = $student['first_name'] . ' ' . $student['last_name'];
$student['initials'] = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
$student['status_label'] = ucfirst($student['account_status']);
$student['ojt_status_label'] = ucfirst($student['ojt_status'] ?? 'Not Started');
$student['created_at_label'] = date('M j, Y', strtotime($student['created_at']));
$student['supervisor_name'] = $student['sv_first'] ? $student['sv_first'] . ' ' . $student['sv_last'] : 'Not Assigned';

// Get Hours Rendered
$stmt = $conn->prepare("SELECT SUM(hours_rendered) as total FROM dtr_entries WHERE student_uuid = ? AND status = 'approved'");
$stmt->bind_param('s', $profileUuid);
$stmt->execute();
$hours = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Get Requirements Progress
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved FROM student_requirements WHERE student_uuid = ?");
$stmt->bind_param('s', $profileUuid);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profileImage = "https://placehold.co/128x128/C1C1C1/000000/png?text=" . $student['initials'] . "&font=poppins";
if (!empty($student['profile_name'])) {
    $profileImage = "../../../Assets/Images/profiles/" . $student['profile_name'];
}

response([
    'status'       => 'success',
    'profile'      => $student,
    'profileImage' => $profileImage,
    'stats' => [
        'hours' => $hours,
        'requirements_total' => $req['total'] ?? 0,
        'requirements_approved' => $req['approved'] ?? 0
    ]
]);
