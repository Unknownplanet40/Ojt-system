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

if (empty($_SESSION['user'])) {
    header("Location: ../../Src/Pages/Login");
    exit;
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

$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : null;
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : null;
$middleName = isset($_POST['middleName']) ? trim($_POST['middleName']) : null;
$employeeId = isset($_POST['employeeId']) ? trim($_POST['employeeId']) : null;
$contactNumber = isset($_POST['contactNumber']) ? trim($_POST['contactNumber']) : null;
$ProfilePhoto = isset($_POST['ProfilePhoto']) ? trim($_POST['ProfilePhoto']) : null;
$department = isset($_POST['department']) ? trim($_POST['department']) : null;
$progfileName = null;

if (empty($firstName) || empty($lastName) || empty($employeeId) || empty($contactNumber) || empty($department)) {
    response([
        'status' => 'info',
        'message' => 'Please fill in all required fields.',
        'long_message' => 'First name, last name, employee ID, contact number, and department are required fields.'
    ]);
}

if ($ProfilePhoto) {
    $data = explode(',', $ProfilePhoto);
    if (count($data) === 2) {
        $imageData = base64_decode($data[1]);
        $filename = __DIR__ . "/../Images/Profiles/" . $_SESSION['user']['uuid'] . "-" . time() . ".png";
        file_put_contents($filename, $imageData);
        $ProfilePhoto = "Assets/Images/Profiles/" . $_SESSION['user']['uuid'] . "-" . time() . ".png";
        $progfileName = $_SESSION['user']['uuid'] . "-" . time() . ".png";
    } else {
        response([
            'status' => 'error',
            'message' => 'Invalid image data.',
            'long_message' => 'The provided image data is not in a valid format.'
        ]);
    }
} else {
    $ProfilePhoto = null;
}

$stmt = $conn->prepare("SELECT user_uuid FROM coordinator_profiles WHERE employee_id = ? AND user_uuid != ?");
$stmt->bind_param("ss", $employeeId, $_SESSION['user']['uuid']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    response([
        'status' => 'info',
        'message' => 'Employee ID already exists.',
        'long_message' => 'The employee ID you entered is already associated with another account. Please use a different employee ID.'
    ]);
}

$stmt = $conn->prepare("SELECT user_uuid FROM coordinator_profiles WHERE user_uuid = ?");
$stmt->bind_param("s", $_SESSION['user']['uuid']);
$stmt->execute();
$result = $stmt->get_result();
$isUpdate = false;
if ($result->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE coordinator_profiles SET first_name = ?, last_name = ?, middle_name = ?, employee_id = ?, mobile = ?, profile_path = ?, profile_name = ?, department = ? WHERE user_uuid = ?");
    $stmt->bind_param("sssssssss", $firstName, $lastName, $middleName, $employeeId, $contactNumber, $ProfilePhoto, $progfileName, $department, $_SESSION['user']['uuid']);
    $isUpdate = true;
} else {
    $stmt = $conn->prepare("INSERT INTO coordinator_profiles (user_uuid, first_name, last_name, middle_name, employee_id, mobile, profile_path, profile_name, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $_SESSION['user']['uuid'], $firstName, $lastName, $middleName, $employeeId, $contactNumber, $ProfilePhoto, $progfileName, $department);
}

if ($stmt->execute()) {
    if ($isUpdate) {
        logActivity(
            conn: $conn,
            eventType: 'profile_updated',
            description: "Coordinator updated their profile",
            module: 'profile',
            actorUuid: $_SESSION['user']['uuid'],
            targetUuid: $_SESSION['user']['uuid'],
            meta: [
                'employee_id' => $employeeId
            ]
        );
    } else {
        logActivity(
            conn: $conn,
            eventType: 'profile_created',
            description: "Coordinator created their profile",
            module: 'profile',
            actorUuid: $_SESSION['user']['uuid'],
            targetUuid: $_SESSION['user']['uuid'],
            meta: [
                'employee_id' => $employeeId
            ]
        );
    }

    response([
        'status' => 'success',
        'message' => 'Profile saved successfully.',
        'long_message' => 'Your profile has been saved successfully. You can now continue to the next step.'
    ]);
} else {
    response([
        'status' => 'error',
        'message' => 'Failed to save profile.',
        'long_message' => 'An error occurred while saving your profile. Please try again later.'
    ]);
}

$stmt->close();
$conn->close();
