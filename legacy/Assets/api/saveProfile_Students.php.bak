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

require_once 'helpers.php';

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

$action = isset($_POST['action']) ? $_POST['action'] : null;
$userId = $_SESSION['user']['uuid'];
$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : null;
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : null;
$middleName = isset($_POST['middleName']) ? trim($_POST['middleName']) : null;
$studentNumber = isset($_POST['studentNumber']) ? trim($_POST['studentNumber']) : null;
$contactNumber = isset($_POST['contactNumber']) ? trim($_POST['contactNumber']) : null;
$homeAddress = isset($_POST['homeAddress']) ? trim($_POST['homeAddress']) : null;
$emergencyContactName = isset($_POST['emergencyContactName']) ? trim($_POST['emergencyContactName']) : null;
$emergencyContactNumber = isset($_POST['emergencyContactNumber']) ? trim($_POST['emergencyContactNumber']) : null;
$program = isset($_POST['program']) ? trim($_POST['program']) : null;
$yearLevel = isset($_POST['yearLevel']) ? trim($_POST['yearLevel']) : null;
$section = isset($_POST['section']) ? trim($_POST['section']) : null;
$ProfilePhoto = isset($_POST['profilePhoto']) ? trim($_POST['profilePhoto']) : null;

if ($action === 'fetch_profile_data') {
    $stmt = $conn->prepare("SELECT * FROM student_profiles WHERE user_uuid = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $profileData = $result->fetch_assoc();

        $stmtReq = $conn->prepare("SELECT student_uuid FROM student_requirements WHERE student_uuid = ?");
        $stmtReq->bind_param("s", $userId);
        $stmtReq->execute();

        // if return 0 means no requirements submitted yet
        $profileData['hasSubmittedRequirements'] = $stmtReq->get_result()->num_rows > 0 ? true : false;
        
        response([
            'status' => 'success',
            'message' => 'Profile data fetched successfully.',
            'data' => $profileData
        ]);
    } else {
        response([
            'status' => 'info',
            'message' => 'No profile data found.',
            'long_message' => 'No profile data found for the current user.'
        ]);
    }
}

if (empty($firstName) || empty($lastName) || empty($studentNumber) || empty($contactNumber) || empty($homeAddress) || empty($emergencyContactName) || empty($emergencyContactNumber) || empty($program) || empty($yearLevel) || empty($section)) {
    response([
        'status' => 'error',
        'message' => 'All fields are required.',
        'long_message' => 'Please fill in all the required fields before submitting the form.'
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

$stmt = $conn->prepare("SELECT user_uuid FROM student_profiles WHERE student_number = ? AND user_uuid != ?");
$stmt->bind_param("ss", $studentNumber, $_SESSION['user']['uuid']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    response([
        'status' => 'info',
        'message' => 'Student number already exists.',
        'long_message' => 'The student number you entered is already associated with another account. Please use a different student number.'
    ]);
}

$stmt = $conn->prepare("SELECT user_uuid FROM student_profiles WHERE user_uuid = ?");
$stmt->bind_param("s", $_SESSION['user']['uuid']);
$stmt->execute();
$result = $stmt->get_result();
$isUpdate = false;

if ($result->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE student_profiles SET first_name = ?, last_name = ?, middle_name = ?, student_number = ?, mobile = ?, home_address = ?, emergency_contact = ?, emergency_phone = ?, program = ?, year_level = ?, section = ?, profile_path = ?, profile_name = ? WHERE user_uuid = ?");
    $stmt->bind_param("ssssssssssssss", $firstName, $lastName, $middleName, $studentNumber, $contactNumber, $homeAddress, $emergencyContactName, $emergencyContactNumber, $program, $yearLevel, $section, $ProfilePhoto, $progfileName, $_SESSION['user']['uuid']);
    $isUpdate = true;
} else {
    $stmt = $conn->prepare("INSERT INTO student_profiles (user_uuid, first_name, last_name, middle_name, student_number, mobile, home_address, emergency_contact, emergency_phone, program, year_level, section, profile_path, profile_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssssss", $_SESSION['user']['uuid'], $firstName, $lastName, $middleName, $studentNumber, $contactNumber, $homeAddress, $emergencyContactName, $emergencyContactNumber, $program, $yearLevel, $section, $ProfilePhoto, $progfileName);
}

if ($stmt->execute()) {
    if ($isUpdate) {
        logActivity(
            conn: $conn,
            eventType: 'profile_updated',
            description: $firstName . " " . $lastName . " updated their profile",
            module: 'profile',
            actorUuid: $_SESSION['user']['uuid'],
            targetUuid: $_SESSION['user']['uuid'],
            meta: [
                'student_number' => $studentNumber
            ]
        );
    } else {
        logActivity(
            conn: $conn,
            eventType: 'profile_created',
            description: $firstName . " " . $lastName . " created their profile",
            module: 'profile',
            actorUuid: $_SESSION['user']['uuid'],
            targetUuid: $_SESSION['user']['uuid'],
            meta: [
                'student_number' => $studentNumber
            ]
        );
    }

    response([
    'status' => 'success',
    'message' => 'Profile saved successfully.',
    'long_message' => 'Your profile has been created successfully.'
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
