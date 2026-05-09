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
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'critical', 'message' => 'Invalid CSRF token.']);
}

$userUuid = $_SESSION['user_uuid'];

// Get Admin Details with Profile
$stmt = $conn->prepare("
    SELECT 
        u.email, CASE WHEN u.is_active = 1 THEN 'active' ELSE 'inactive' END as account_status, u.created_at, u.last_login_at as last_login,
        ap.first_name, ap.last_name, ap.contact_number, ap.profile_name, ap.employee_id
    FROM users u
    LEFT JOIN admin_profiles ap ON u.uuid = ap.user_uuid
    WHERE u.uuid = ?
");
$stmt->bind_param('s', $userUuid);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    response(['status' => 'error', 'message' => 'Profile not found.']);
}

// Formatting
$admin['full_name'] = !empty($admin['first_name']) ? $admin['first_name'] . ' ' . $admin['last_name'] : $_SESSION['user_name'];
$initials = !empty($admin['first_name']) ? strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)) : $_SESSION['user_initials'];
$admin['initials'] = $initials;
$admin['status_label'] = ucfirst($admin['account_status']);
$admin['created_at_label'] = date('M j, Y', strtotime($admin['created_at']));
$admin['last_login_label'] = $admin['last_login'] ? date('M j, Y h:i A', strtotime($admin['last_login'])) : 'Never';

// System Stats
$stats = [];
$stats['total_users'] = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$stats['total_students'] = $conn->query("SELECT COUNT(*) FROM student_profiles")->fetch_row()[0];
$stats['total_companies'] = $conn->query("SELECT COUNT(*) FROM companies")->fetch_row()[0];

// Recent Logs (brief)
$logs = [];
$stmt = $conn->prepare("
    SELECT event_type, description, created_at 
    FROM activity_log 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $row['time_ago'] = timeAgo($row['created_at']);
    $logs[] = $row;
}
$stmt->close();

$profileImage = "https://placehold.co/128x128/C1C1C1/000000/png?text=" . $admin['initials'] . "&font=poppins";
if (!empty($admin['profile_name'])) {
    $profileImage = "../../../Assets/Images/profiles/" . $admin['profile_name'];
}

response([
    'status'       => 'success',
    'profile'      => $admin,
    'profileImage' => $profileImage,
    'stats'        => $stats,
    'logs'         => $logs
]);
