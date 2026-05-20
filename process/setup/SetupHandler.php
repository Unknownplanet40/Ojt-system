<?php


require_once __DIR__ . '/../../helpers/helpers.php';




if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['status' => 'error', 'message' => 'Invalid request method.'], 405);
}


$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'ojt_system';


$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    response(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error], 500);
}

$db_check = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
if ($db_check && $db_check->num_rows > 0) {
    $conn->select_db($dbname);
    $table_check = $conn->query("SHOW TABLES LIKE 'system_config'");
    if ($table_check && $table_check->num_rows > 0) {
        $setup_check = $conn->query("SELECT is_setup_locked FROM system_config WHERE id = 1");
        if ($setup_check && $setup_check->num_rows > 0) {
            $is_locked = (int)$setup_check->fetch_assoc()['is_setup_locked'] === 1;
            if ($is_locked) {
                $conn->close();
                response(['status' => 'error', 'message' => 'System is already configured and setup is locked.'], 403);
            }
        }
    }
}


if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    response(['status' => 'error', 'message' => 'Failed to create database: ' . $conn->error], 500);
}

$conn->select_db($dbname);
$conn->set_charset('utf8mb4');


$schemaFile = __DIR__ . '/../../config/init.sql';
if (!file_exists($schemaFile)) {
    response(['status' => 'error', 'message' => 'Schema file not found.'], 500);
}

$sql = file_get_contents($schemaFile);



if ($conn->multi_query($sql)) {
    do {
        
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
} else {
    response(['status' => 'error', 'message' => 'Schema execution failed: ' . $conn->error], 500);
}


$createSystemConfigTable = "
CREATE TABLE IF NOT EXISTS `system_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_name` varchar(255) DEFAULT NULL,
  `short_title` varchar(50) DEFAULT NULL,
  `system_description` text DEFAULT NULL,
  `school_motto` varchar(255) DEFAULT NULL,
  `school_address` text DEFAULT NULL,
  `school_email` varchar(255) DEFAULT NULL,
  `school_phone` varchar(50) DEFAULT NULL,
  `school_website` varchar(255) DEFAULT NULL,
  `logo_1` varchar(255) DEFAULT NULL,
  `logo_2` varchar(255) DEFAULT NULL,
  `footer_note` text DEFAULT NULL,
  `verification_note` text DEFAULT NULL,
  `is_setup_locked` tinyint(1) DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$createEmailTable = "
CREATE TABLE IF NOT EXISTS `email_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int(11) NOT NULL,
  `smtp_user` varchar(255) NOT NULL,
  `smtp_pass` varchar(255) NOT NULL,
  `smtp_crypto` enum('none', 'ssl', 'tls') NOT NULL DEFAULT 'tls',
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$conn->query($createSystemConfigTable) || !$conn->query($createEmailTable)) {
    response(['status' => 'error', 'message' => 'Failed to create supplemental tables: ' . $conn->error], 500);
}


$conn->query("INSERT IGNORE INTO system_config (id) VALUES (1)");


$schoolName = $_POST['school_name'] ?? 'Cavite State University - Imus Campus';
$shortTitle = $_POST['short_title'] ?? 'OJT-SMS';
$description = $_POST['description'] ?? '';
$schoolMotto = $_POST['school_motto'] ?? '';
$schoolAddress = $_POST['school_address'] ?? '';
$schoolEmail = $_POST['school_email'] ?? '';
$schoolPhone = $_POST['school_phone'] ?? '';
$schoolWebsite = $_POST['school_website'] ?? '';
$footerNote = $_POST['footer_note'] ?? '';
$verifyNote = $_POST['verify_note'] ?? '';

$adminEmail = $_POST['admin_email'] ?? '';
$adminPassword = $_POST['admin_password'] ?? '';
$adminName = $_POST['admin_name'] ?? 'Administrator';

$smtpHost = $_POST['smtp_host'] ?? '';
$smtpPort = $_POST['smtp_port'] ?? '587';
$smtpUser = $_POST['smtp_user'] ?? '';
$smtpPass = $_POST['smtp_pass'] ?? '';
$smtpCrypto = $_POST['smtp_encryption'] ?? 'tls';


$stmt = $conn->prepare("
    UPDATE system_config 
    SET school_name = ?, short_title = ?, system_description = ?, school_motto = ?, 
        school_address = ?, school_email = ?, school_phone = ?, school_website = ?,
        footer_note = ?, verification_note = ?,
        is_setup_locked = 1
    WHERE id = 1
");

if (!$stmt) {
    
    $conn->query("INSERT INTO system_config (id) VALUES (1)");
    $stmt = $conn->prepare("
        UPDATE system_config 
        SET school_name = ?, short_title = ?, system_description = ?, school_motto = ?, 
            school_address = ?, school_email = ?, school_phone = ?, school_website = ?,
            footer_note = ?, verification_note = ?,
            is_setup_locked = 1
        WHERE id = 1
    ");
}

$stmt->bind_param('ssssssssss', $schoolName, $shortTitle, $description, $schoolMotto, $schoolAddress, $schoolEmail, $schoolPhone, $schoolWebsite, $footerNote, $verifyNote);
$stmt->execute();
$stmt->close();


$conn->query("TRUNCATE TABLE email_config"); 
$stmt = $conn->prepare("
    INSERT INTO email_config (smtp_host, smtp_port, smtp_user, smtp_pass, smtp_crypto, from_email, from_name)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$fromName = $shortTitle;
$stmt->bind_param('sisssss', $smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpCrypto, $smtpUser, $fromName);
$stmt->execute();
$stmt->close();


$adminUuid = generateUuid();
$passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT);
$role = 'admin';


$conn->query("DELETE FROM users WHERE role = 'admin'");

$stmt = $conn->prepare("INSERT INTO users (uuid, email, password_hash, role, is_active, must_change_password) VALUES (?, ?, ?, ?, 1, 0)");
$stmt->bind_param('ssss', $adminUuid, $adminEmail, $passwordHash, $role);
$stmt->execute();
$stmt->close();


$profileUuid = generateUuid();
$nameParts = explode(' ', $adminName, 2);
$firstName = $nameParts[0];
$lastName = $nameParts[1] ?? 'Admin';

$stmt = $conn->prepare("INSERT INTO admin_profiles (uuid, user_uuid, first_name, last_name, isProfileDone) VALUES (?, ?, ?, ?, 1)");
$stmt->bind_param('ssss', $profileUuid, $adminUuid, $firstName, $lastName);
$stmt->execute();
$stmt->close();


$targetDir = __DIR__ . '/../../Assets/Images/systemImages/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

function uploadLogo($inputName, $dbField, $conn) {
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
        $fileName = $inputName . '_' . time() . '.' . $ext;
        $targetPath = __DIR__ . '/../../Assets/Images/systemImages/' . $fileName;
        
        if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetPath)) {
            $stmt = $conn->prepare("UPDATE system_config SET $dbField = ? WHERE id = 1");
            $stmt->bind_param('s', $fileName);
            $stmt->execute();
            $stmt->close();
        }
    }
}

uploadLogo('logo_left', 'logo_1', $conn);
uploadLogo('logo_right', 'logo_2', $conn);

response(['status' => 'success', 'message' => 'System initialized successfully.']);
