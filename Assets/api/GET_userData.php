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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    response([
        'status' => 'info',
        'message' => 'Request method is not allowed.',
        'long_message' => 'Only GET requests are allowed for this endpoint.'
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

$UUID = isset($_SESSION['user']['uuid']) ? $_SESSION['user']['uuid'] : null;

if (empty($UUID)) {
    response([
        'status' => 'error',
        'message' => 'User not authenticated.',
        'long_message' => 'No user session found. Please log in to access this resource.'
    ]);
}

switch ($_SESSION['user']['role']) {
    case 'admin':
        $table = 'admin_profiles';
        break;
    case 'coordinator':
        $table = 'coordinator_profiles';
        break;
    case 'student':
        $table = 'student_profiles';
        break;
    case 'supervisor':
        $table = 'supervisor_profiles';
        break;
    default:
        response([
            'status' => 'error',
            'message' => 'Invalid user role.',
            'long_message' => 'The user role is not recognized. Please contact support.'
        ]);
        break;
}

$stmt = $conn->prepare("SELECT * FROM $table WHERE user_uuid = ?");
$stmt->bind_param("s", $UUID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    response([
        'status' => 'info',
        'message' => 'No profile data found.',
        'long_message' => 'No profile data found for the authenticated user.'
    ]);
} else {
    $profileData = $result->fetch_assoc();
    $profileData['role'] = $_SESSION['user']['role'];
    response([
        'status' => 'success',
        'message' => 'Profile data retrieved successfully.',
        'data' => $profileData
    ]);
}

$conn->close();