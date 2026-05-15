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
require_once __DIR__ . '/../../../Assets/SystemInfo.php';

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

    $mail->setFrom($GmailUsername, $ShortTitle ?? 'OJT System');
    $mail->addAddress($email);
    $mail->addReplyTo($GmailUsername, $ShortTitle ?? 'OJT System');

    $mail->isHTML(true);
    $mail->Subject = 'Reset Your Password';

    $schoolName = htmlspecialchars($SchoolName ?? 'OJT Management System', ENT_QUOTES, 'UTF-8');
    $schoolMotto = htmlspecialchars($SchoolMotto ?? '', ENT_QUOTES, 'UTF-8');
    $longTitle = htmlspecialchars($LongTitle ?? 'On-The-Job Training Management System', ENT_QUOTES, 'UTF-8');
    $schoolAddress = htmlspecialchars($SchoolAddress ?? '', ENT_QUOTES, 'UTF-8');
    $schoolWebsite = htmlspecialchars($SchoolWebsite ?? '', ENT_QUOTES, 'UTF-8');
    $schoolEmail = htmlspecialchars($SchoolEmail ?? '', ENT_QUOTES, 'UTF-8');
    $footerNote = htmlspecialchars($DocumentFooterNote ?? 'Officially issued by the OJT Coordinator Management System', ENT_QUOTES, 'UTF-8');
    $verificationNote = htmlspecialchars($DocumentVerificationNote ?? 'Please verify document authenticity with the coordinator\'s office.', ENT_QUOTES, 'UTF-8');
    $logoLeft = htmlspecialchars($SchoolLogoLeft ?? 'https://placehold.co/128x128/0F6E56/FFFFFF?text=LOGO', ENT_QUOTES, 'UTF-8');
    $logoRight = htmlspecialchars($SchoolLogoRight ?? 'https://placehold.co/128x128/0F6E56/FFFFFF?text=LOGO', ENT_QUOTES, 'UTF-8');
    $expiresAtFormatted = date('F j, Y g:i A', strtotime($expiresAt));

    $mail->Body = <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Your Password</title>
    </head>
    <body style="margin:0;padding:0;background-color:#eef4f1;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef4f1;padding:36px 0;">
            <tr>
                <td align="center">
                    <table width="640" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 12px 28px rgba(15,110,86,0.12);border:1px solid rgba(15,110,86,0.08);">
                        <tr>
                            <td style="background:linear-gradient(135deg,#0F6E56 0%,#146b56 55%,#0d5a48 100%);padding:28px 40px;text-align:center;">
                                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="width:72px;text-align:left;vertical-align:middle;">
                                            <img src="{$logoLeft}" alt="School logo" style="width:56px;height:56px;object-fit:contain;border-radius:14px;background:rgba(255,255,255,0.16);padding:8px;" />
                                        </td>
                                        <td style="text-align:center;vertical-align:middle;padding:0 10px;">
                                            <div style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.3px;line-height:1.25;">{$schoolName}</div>
                                            <div style="margin-top:4px;color:#D5F3E8;font-size:12px;font-weight:500;">{$schoolMotto}</div>
                                            <div style="margin-top:8px;color:#BDE9DA;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">{$longTitle}</div>
                                        </td>
                                        <td style="width:72px;text-align:right;vertical-align:middle;">
                                            <img src="{$logoRight}" alt="School logo" style="width:56px;height:56px;object-fit:contain;border-radius:14px;background:rgba(255,255,255,0.16);padding:8px;" />
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:36px 40px 30px;">
                                <div style="background:#F5FBF8;border:1px solid #D9EFE4;border-radius:14px;padding:16px 18px;margin-bottom:24px;">
                                    <div style="font-size:11px;font-weight:700;color:#0F6E56;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:6px;">Password reset request</div>
                                    <div style="font-size:13px;line-height:1.6;color:#475569;">We received a request to reset your password for the OJT system. Use the secure button below to create a new password.</div>
                                </div>

                                <div style="text-align:center;margin-bottom:22px;">
                                    <div style="display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#E1F5EE,#F1FAF5);border:1px solid #CFE9DB;border-radius:18px;width:72px;height:72px;box-shadow:0 10px 20px rgba(15,110,86,0.08);">
                                        <span style="font-size:30px;">&#128274;</span>
                                    </div>
                                </div>

                                <h2 style="margin:0 0 8px;color:#0f172a;font-size:22px;font-weight:700;text-align:center;">Reset your password</h2>
                                <p style="margin:0 0 26px;color:#64748b;font-size:14px;line-height:1.7;text-align:center;">Click the button below to open the password reset page and finish the process securely.</p>

                                <div style="text-align:center;margin-bottom:24px;">
                                    <a href="{$resetLink}" style="display:inline-block;background:linear-gradient(135deg,#0F6E56,#136f57);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 34px;border-radius:10px;letter-spacing:0.2px;box-shadow:0 8px 18px rgba(15,110,86,0.22);">Reset Password</a>
                                </div>

                                <p style="margin:0 0 8px;color:#64748b;font-size:12px;text-align:center;">Or copy this link into your browser:</p>
                                <div style="background-color:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px 14px;margin-bottom:22px;word-break:break-all;text-align:center;">
                                    <a href="{$resetLink}" style="color:#0F6E56;font-size:12px;text-decoration:none;font-weight:600;line-height:1.5;">{$resetLink}</a>
                                </div>

                                <div style="background:linear-gradient(180deg,#FEFCEF,#FFF9E6);border:1px solid #F5D97B;border-radius:12px;padding:14px 16px;margin-bottom:22px;">
                                    <p style="margin:0;color:#92400E;font-size:13px;line-height:1.6;text-align:center;">
                                        This link expires on <strong>{$expiresAtFormatted} (Philippine Time)</strong>.
                                        If it expires, you can request a new one from the login page.
                                    </p>
                                </div>

                                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:14px 16px;margin-bottom:20px;">
                                    <p style="margin:0;color:#475569;font-size:12px;line-height:1.6;text-align:center;">
                                        If you did not request a password reset, you can safely ignore this email.
                                        Your password will remain unchanged.
                                    </p>
                                </div>

                                <div style="border-top:1px solid #E2E8F0;padding-top:16px;color:#64748b;font-size:12px;line-height:1.7;text-align:center;">
                                    <div><strong style="color:#0f172a;">{$schoolName}</strong> · {$schoolAddress}</div>
                                    <div style="margin-top:3px;">{$schoolWebsite} · {$schoolEmail}</div>
                                    <div style="margin-top:4px;">{$footerNote}</div>
                                    <div style="margin-top:2px;">{$verificationNote}</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 40px;text-align:center;">
                                <p style="margin:0;color:#64748b;font-size:11px;line-height:1.6;">
                                    This is an automated message from <strong style="color:#0f172a;">{$longTitle}</strong>.
                                    Please do not reply to this email.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
HTML;

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