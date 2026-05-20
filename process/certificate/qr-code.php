<?php
require_once __DIR__ . '/../../helpers/helpers.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../functions/certificate_functions.php';

$token = $_GET['token'] ?? null;
$format = $_GET['format'] ?? 'png';
$size = $_GET['size'] ?? 300;

if (!$token) {
    response(['error' => 'Missing verification token'], 400);
}

if (!in_array($format, ['png', 'svg'])) {
    response(['error' => 'Invalid format. Use "png" or "svg"'], 400);
}

$size = (int)$size;
if ($size < 100 || $size > 1000) {
    $size = 300;
}

try {
    $manager = getCertificateManager();
    $certificate = $manager->verifyCertificateByToken($token);
    if (!$certificate) {
        response(['error' => 'Certificate not found or invalid'], 404);
    }
    
    $certificateUuid = $certificate['certificate_uuid'];
    $verificationUrl = buildCertificateVerificationUrl($token);
    $qrData = $manager->getOrGenerateQRCode($certificateUuid, $verificationUrl, $format);
    
    if (!$qrData) {
        $qrData = generateQRCodeFromPublicService($verificationUrl, $format, $size);
    }
    
    if (!$qrData) {
        response(['error' => 'Failed to generate QR code'], 500);
    }
    if ($format === 'png') {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=2592000'); // 30 days
    } else {
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=2592000');
    }
    
    header('Content-Disposition: inline; filename="certificate-' . substr($certificateUuid, 0, 8) . '.qr"');
    echo $qrData;
    
} catch (Exception $e) {
    error_log("QR code generation error: " . $e->getMessage());
    response(['error' => 'Internal server error'], 500);
}
function generateQRCodeFromPublicService($url, $format = 'png', $size = 300) {
    try {
        if ($format === 'svg') {
            $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?format=svg&size={$size}x{$size}&data=" . urlencode($url);
        } else {
            $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?format=png&size={$size}x{$size}&data=" . urlencode($url);
        }
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'OJT-System/1.0'
            ],
            'https' => [
                'timeout' => 5,
                'user_agent' => 'OJT-System/1.0'
            ]
        ]);
        
        $qrData = @file_get_contents($apiUrl, false, $context);
        return $qrData ?: false;
        
    } catch (Exception $e) {
        error_log("QR server fallback failed: " . $e->getMessage());
        return false;
    }
}
?>
