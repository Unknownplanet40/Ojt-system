<?php

require_once 'ServerConfig.php';

// Prevent direct access to this file
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    // Only allow AJAX requests
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    }
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/dbconfig.php';

function response($data)
{
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response([
        'status' => 'info',
        'message' => 'Request method is not allowed.',
        'long_message' => 'Only POST requests are allowed for this endpoint.'
    ]);
}

if (!isModRewriteEnabled()) {
    response([
        'status' => 'critical',
        'message' => 'mod_rewrite is disabled.',
        'long_message' => 'mod_rewrite is disabled. Required for clean URLs. Enable it in httpd.conf and restart Apache.'
    ]);
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed',
        'long_message' => $e->getMessage()
    ]);
}

require_once 'helpers.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;
$RequirePasswordChange = false;

if (empty($email) || empty($password)) {
    response([
        'status' => 'info',
        'message' => 'Email and password are required.',
        'long_message' => 'Please provide both email and password to log in.'
    ]);
}


$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    response([
        'status' => 'info',
        'message' => 'No account found with that email.',
        'long_message' => 'No account found with the provided email. Please check your credentials and try again.'
    ]);
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password_hash'])) {
    loginAudit($user['uuid'], 0, 'wrong_password');
    response([
        'status' => 'info',
        'message' => 'Incorrect password.',
        'long_message' => 'The password you entered is incorrect. Please check your credentials and try again.'
    ]);
}

if ($user['must_change_password'] == 1) {
    $RequirePasswordChange = true;
}

if ($user['is_active'] == 0) {
    loginAudit($user['uuid'], 0, 'account_inactive');
    response([
        'status' => 'info',
        'message' => 'Account is inactive.',
        'long_message' => 'Your account is currently inactive. Please contact your administrator for assistance.'
    ]);
}

$ValidRoles = ['admin', 'coordinator', 'student', 'supervisor'];

if (!in_array($user['role'], $ValidRoles)) {
    response([
        'status' => 'error',
        'message' => 'Invalid user role.',
        'long_message' => 'Your account has an invalid role assigned. Please contact your administrator for assistance.'
    ]);
}

$profileTable = '';
$redirectFolder = '';
$redirectPage = '';
$redirectUrl = '';
$noProfile = false;
$LogMessage = '';
$hasSubmittedRequrement = false;

switch ($user['role']) {
    case 'admin':
        $profileTable = 'admin_profiles';
        $redirectFolder = 'Admin';
        $redirectPage = 'AdminDashboard';
        break;
    case 'coordinator':
        $profileTable = 'coordinator_profiles';
        $redirectFolder = 'Coordinator';
        $redirectPage = 'CoordinatorDashboard';
        break;
    case 'student':
        $profileTable = 'student_profiles';
        $redirectFolder = 'Students';
        $redirectPage = 'StudentsDashboard';
        break;
    case 'supervisor':
        $profileTable = 'supervisor_profiles';
        $redirectFolder = 'Supervisor';
        $redirectPage = 'SupervisorDashboard';
        break;
}

switch ($user['role']) {
    case 'admin':
        $redirectUrl = '../../Src/Pages/' . $redirectFolder . '/' . $redirectPage;
        break;
    case 'coordinator':
        $redirectUrl = '../../Src/Pages/' . $redirectFolder . '/' . $redirectPage;
        break;
    case 'student':
        $redirectUrl = '../../Src/Pages/' . $redirectFolder . '/' . $redirectPage;
        break;
    case 'supervisor':
        $redirectUrl = '../../Src/Pages/' . $redirectFolder . '/' . $redirectPage;
        break;
}

$stmt = $conn->prepare("SELECT * FROM $profileTable WHERE user_uuid = ?");
$stmt->bind_param("s", $user['uuid']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $redirectUrl = '../../Src/Pages/' . $redirectFolder . '/'. $redirectFolder .'_Profile';
    $noProfile = true;
}

if ($noProfile) {
    $_SESSION['user'] = [
        'uuid' => $user['uuid'],
        'email' => $user['email'],
        'role' => $user['role'],
        'require_password_change' => $RequirePasswordChange
    ];
    $LogMessage = 'logged in successfully but has no profile';

} elseif (!$noProfile && $RequirePasswordChange) {
    $_SESSION['user'] = [
        'uuid' => $user['uuid'],
        'email' => $user['email'],
        'role' => $user['role'],
        'mode' => $RequirePasswordChange ? 'forced' : 'voluntary',
        'continueUrl' => '../../Src/Pages/' . $redirectFolder . '/' . $redirectPage 
    ];
    $redirectUrl = '../../Src/Pages/ChangePassword';
    $LogMessage = 'logged in successfully but must change password';
} else {
    $_SESSION['user'] = [
        'uuid' => $user['uuid'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    $stmt = $conn->prepare("UPDATE users SET last_login_at = ? WHERE uuid = ?");
    $currentDateTime = date('Y-m-d H:i:s');
    $stmt->bind_param("ss", $currentDateTime, $user['uuid']);
    $stmt->execute();
    $LogMessage = 'logged in successfully';

    loginAudit($user['uuid'], 1);
    logActivity(
        conn: $conn,
        eventType: 'login_success',
        description: "$user[email]" . " " . $LogMessage,
        module: 'authentication',
        actorUuid: $user['uuid']
    );
}

if ($user['role'] === 'student') {
    $stmt = $conn->prepare("SELECT uuid, batch_uuid FROM student_profiles WHERE user_uuid = ?");
    $stmt->bind_param("s", $user['uuid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $batchData = $result->fetch_assoc();

    $stmt = $conn->prepare("
        SELECT
          COUNT(*)                                                    AS total,
          SUM(CASE WHEN status = 'not_submitted' THEN 1 ELSE 0 END)  AS not_submitted
        FROM student_requirements
        WHERE student_uuid = ? AND batch_uuid = ?
    ");
    $stmt->bind_param("ss", $batchData['uuid'], $batchData['batch_uuid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $hasSubmittedRequrement = ($data['total'] == $data['not_submitted']);
}

$conn->close();

response([
    'status' => 'success',
    'message' => $LogMessage,
    'redirect_url' => $redirectUrl,
    'has_submitted_requirements' => $hasSubmittedRequrement
]);
