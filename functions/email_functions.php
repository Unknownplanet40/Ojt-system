<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/composer/vendor/autoload.php';

function sendSystemEmail(array $smtpConfig, string $to, string $subject, string $body, bool $isHtml = true): array
{
    $mail = new PHPMailer(true);

    try {
        
        $mail->isSMTP();
        $mail->Host       = $smtpConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpConfig['user'];
        $mail->Password   = $smtpConfig['pass'];
        
        $crypto = strtolower($smtpConfig['crypto'] ?? 'tls');
        if ($crypto === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($crypto === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }

        $mail->Port = (int)$smtpConfig['port'];

        
        $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
        $mail->addAddress($to);

        
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}

/**
 * Generates a professional HTML email template
 */
function getEmailTemplate(string $title, string $content, string $schoolName = 'OJT Management System'): string
{
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f9; color: #334155;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #f4f7f9; padding: 40px 20px;'>
            <tr>
                <td align='center'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);'>
                        <!-- Header -->
                        <tr>
                            <td align='center' style='padding: 40px 40px 20px 40px; background-color: #1e40af;'>
                                <h1 style='margin: 0; color: #ffffff; font-size: 24px; font-weight: 800;'>{$schoolName}</h1>
                                <p style='margin: 8px 0 0 0; color: rgba(255, 255, 255, 0.8); font-size: 14px;'>Official Notification</p>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style='padding: 40px;'>
                                <h2 style='margin: 0 0 20px 0; color: #1e293b; font-size: 20px; font-weight: 700;'>{$title}</h2>
                                <div style='font-size: 16px; line-height: 1.6; color: #475569;'>
                                    {$content}
                                </div>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style='padding: 24px 40px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;'>
                                <p style='margin: 0; font-size: 12px; color: #94a3b8;'>
                                    This is an automated message from the {$schoolName} portal. Please do not reply directly to this email.
                                </p>
                            </td>
                        </tr>
                    </table>
                    <p style='margin-top: 24px; font-size: 12px; color: #94a3b8; text-align: center;'>
                        &copy; " . date('Y') . " {$schoolName}. All rights reserved.
                    </p>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";
}
