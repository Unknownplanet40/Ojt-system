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
