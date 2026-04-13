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

$email = isset($_POST['email']) ? trim($_POST['email']) : null;

if (empty($email)) {
    response([
        'status' => 'info',
        'message' => 'Email is required.',
        'long_message' => 'Please provide your email address to receive the password reset link.'
    ]);
}

$stmt = $conn->prepare("SELECT uuid FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    response([
        'status' => 'info',
        'message' => 'Email address is not registered.',
        'long_message' => 'The email address you entered is not registered. Please check and try again.'
    ]);
}

$user = $result->fetch_assoc();
$userId = $user['uuid'];

$tokenhash = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

$stmt = $conn->prepare("INSERT INTO password_resets (user_uuid, token_hash, expires_at, used, created_at) VALUES (?, ?, ?, 0, NOW())");
$stmt->bind_param("sss", $userId, $tokenhash, $expiresAt);

if ($stmt->execute()) {
    $resetLink = 'http://localhost/ojt-system/Src/Pages/ForgotPassword?token=' . $tokenhash;
} else {
    response([
        'status' => 'critical',
        'message' => 'Failed to create password reset token.',
        'long_message' => 'An error occurred while creating the password reset token. Please try again later.'
    ]);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require dirname(__DIR__, 2) . '/Libs/composer/vendor/autoload.php';

$mail = new PHPMailer(true);

//temporary
$GmailUsername = 'Your Gmail Username';
$GmailPassword = 'Your Gmail App Password';

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $GmailUsername;
    $mail->Password = $GmailPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($GmailUsername, 'OJT System');
    $mail->addAddress($email);
    $mail->addReplyTo($GmailUsername, 'OJT System');

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body = "
        <p>Dear User,</p>
        <p>You requested a password reset. Click the link below to reset your password:</p>
        <a href='$resetLink' style='display: inline-block; padding: 10px 20px; font-size: 16px; color: #fff; background-color: #007bff; text-decoration: none; border-radius: 5px;'>Reset Password</a>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request this, please ignore this email.</p>
        <p>Best regards,<br>OJT System Team</p>
    ";

    $mail->send();

    response([
        'status' => 'success',
        'message' => 'Password reset link sent.',
        'long_message' => 'A password reset link has been sent to your email address. Please check your inbox and follow the instructions to reset your password.'
    ]);
} catch (Exception $e) {
    response([
        'status' => 'critical',
        'message' => 'Failed to send email.',
        'long_message' => "Mailer Error: {$mail->ErrorInfo}"
    ]);
}