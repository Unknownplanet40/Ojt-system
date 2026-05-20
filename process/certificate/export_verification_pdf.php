<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../functions/certificate_functions.php';
require_once __DIR__ . '/../../Assets/SystemInfo.php';
require_once __DIR__ . '/../../helpers/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die("Method Not Allowed");
}

$token = $_GET['token'] ?? null;

if (!$token || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    http_response_code(400);
    die("Invalid verification token. Token must be a 64-character hexadecimal string.");
}

try {
    $manager = getCertificateManager();
    $certificate = $manager->verifyCertificateByToken($token);
    
    if (!$certificate) {
        http_response_code(404);
        die("Certificate not found, expired, or has been revoked.");
    }

    $mpdfPath = dirname(__DIR__, 2) . '/libs/composer/vendor/autoload.php';
    if (!file_exists($mpdfPath)) {
        http_response_code(500);
        die("PDF library not available. Please contact system administrator.");
    }
    require_once $mpdfPath;
    $qrCodeBase64 = $manager->generateQRCode($token, 'png');
    if (!$qrCodeBase64) {
        throw new Exception("Failed to generate QR code");
    }

    $schoolName    = $SchoolName ?? 'Educational Institution';
    $longTitle     = $LongTitle ?? 'OJT Management System';
    $schoolMotto   = $SchoolMotto ?? '';
    $schoolAddress = $SchoolAddress ?? '';
    $schoolWebsite = $SchoolWebsite ?? '';
    $schoolEmail   = $SchoolEmail ?? '';
    $schoolPhone   = $SchoolPhone ?? '';
    $documentFooterNote = $DocumentFooterNote ?? 'Officially issued by the OJT Management System';
    $documentVerificationNote = $DocumentVerificationNote ?? 'Verify this document at the registrar\'s office.';
    $logoLeft      = $SchoolLogoLeft ?? 'https://placehold.co/100x100/2c3e50/FFF?text=LOGO';
    $logoRight     = $SchoolLogoRight ?? 'https://placehold.co/100x100/2c3e50/FFF?text=LOGO';
    
    $generatedAt   = date('F j, Y \a\t g:i A');
    $issueDate     = date('F d, Y', strtotime($certificate['generated_at']));
    $completionDate = date('F d, Y', strtotime($certificate['completion_date']));
    
    $studentName = htmlspecialchars(
        trim($certificate['first_name'] . ' ' . 
        ($certificate['middle_name'] ? $certificate['middle_name'] . ' ' : '') . 
        $certificate['last_name']),
        ENT_QUOTES,
        'UTF-8'
    );
    
    $statusText = $certificate['is_revoked'] ? 'REVOKED - INVALID' : 'VALID & AUTHENTIC';
    $statusClass = $certificate['is_revoked'] ? 'revoked' : 'valid';
    
    $gpaText = number_format((float)($certificate['gpa'] ?? 0), 2);
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px; }
        .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .header-table td { vertical-align: middle; }
        .header-left { width: 20%; text-align: left; }
        .header-center { width: 60%; text-align: center; }
        .header-right { width: 20%; text-align: right; }
        .header-logo { width: 60px; height: 60px; object-fit: contain; }
        
        .school-name { font-size: 14px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
        .school-motto { font-size: 10px; color: #64748b; margin-bottom: 2px; }
        .doc-title { font-size: 11px; color: #475569; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .generated-date { font-size: 9px; color: #64748b; margin-top: 4px; }
        
        .status-box { 
            border: 2px solid #e2e8f0; 
            border-radius: 4px; 
            padding: 10px; 
            text-align: center; 
            margin-bottom: 16px; 
            font-size: 13px; 
            font-weight: bold; 
            letter-spacing: 1px;
        }
        .status-box.valid { background: #f0fdf4; border-color: #22c55e; color: #166534; }
        .status-box.revoked { background: #fef2f2; border-color: #ef4444; color: #991b1b; }
        
        .notice-box { background: #fef2f2; border: 1px solid #fecaca; padding: 10px; margin-bottom: 16px; border-radius: 4px; }
        .notice-title { font-size: 11px; font-weight: bold; color: #991b1b; margin-bottom: 4px; }
        .notice-text { font-size: 11px; color: #7f1d1d; }
        
        .section-title { font-size: 12px; font-weight: bold; color: #1e293b; margin-top: 16px; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; letter-spacing: 0.05em; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .info-table td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; font-size: 11px; }
        .info-table td.label { width: 35%; color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .info-table td.value { width: 65%; color: #0f172a; font-weight: bold; }
        
        .qr-section { width: 100%; margin-top: 20px; margin-bottom: 20px; }
        .qr-table { width: 100%; border-collapse: collapse; }
        .qr-cell { text-align: center; vertical-align: middle; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; }
        .qr-img { width: 110px; height: 110px; border: 1px solid #cbd5e1; padding: 4px; background: #ffffff; }
        .qr-text { font-size: 10px; color: #475569; margin-top: 6px; line-height: 1.4; }
        
        .security-notice { background: #f0fdfa; border: 1px solid #ccfbf1; padding: 8px 10px; margin-top: 10px; font-size: 9px; color: #0f766e; border-radius: 4px; text-align: center; }
        
        .footer { margin-top: 15px; border-top: 2px solid #e2e8f0; padding-top: 10px; text-align: center; }
        .footer-text { font-size: 10px; color: #64748b; margin-bottom: 4px; }
        .footer-contact { margin-top: 4px; font-size: 9px; color: #94a3b8; line-height: 1.3; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td class="header-left">
                    <img src="{$logoLeft}" alt="Logo" class="header-logo" />
                </td>
                <td class="header-center">
                    <div class="school-name">{$schoolName}</div>
                    <div class="school-motto">{$schoolMotto}</div>
                    <div class="doc-title">Certificate Verification Report</div>
                    <div class="generated-date">Report Generated: {$generatedAt}</div>
                </td>
                <td class="header-right">
                    <img src="{$logoRight}" alt="Logo" class="header-logo" />
                </td>
            </tr>
        </table>
    </div>

    <div class="status-box {$statusClass}">
        STATUS: {$statusText}
    </div>
HTML;

    if ($certificate['is_revoked']) {
        $revocationReason = htmlspecialchars($certificate['revocation_reason'] ?? 'No reason provided', ENT_QUOTES, 'UTF-8');
        $html .= <<<HTML
    <div class="notice-box">
        <div class="notice-title">⚠ REVOCATION NOTICE</div>
        <div class="notice-text">
            <strong>Status:</strong> This certificate has been revoked and is no longer valid.<br>
            <strong>Reason:</strong> {$revocationReason}
        </div>
    </div>
HTML;
    }

    $html .= <<<HTML
    <div class="section-title">Certificate Information</div>
    <table class="info-table" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="label">Certificate Number</td>
            <td class="value">{$certificate['certificate_number']}</td>
        </tr>
        <tr>
            <td class="label">Issue Date</td>
            <td class="value">{$issueDate}</td>
        </tr>
        <tr>
            <td class="label">Completion Date</td>
            <td class="value">{$completionDate}</td>
        </tr>
    </table>

    <div class="section-title">Student Information</div>
    <table class="info-table" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="label">Full Name</td>
            <td class="value">{$studentName}</td>
        </tr>
        <tr>
            <td class="label">Student ID</td>
            <td class="value">{$certificate['student_id']}</td>
        </tr>
        <tr>
            <td class="label">Program</td>
            <td class="value">{$certificate['program_name']}</td>
        </tr>
        <tr>
            <td class="label">Year Level & Section</td>
            <td class="value">{$certificate['year_level']} - {$certificate['section']}</td>
        </tr>
    </table>

    <div class="section-title">Program Details</div>
    <table class="info-table" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="label">Host Company</td>
            <td class="value">{$certificate['company_name']}</td>
        </tr>
        <tr>
            <td class="label">Hours Completed</td>
            <td class="value">{$certificate['hours_completed']} hours</td>
        </tr>
        <tr>
            <td class="label">Academic Term</td>
            <td class="value">{$certificate['semester']}, {$certificate['school_year']}</td>
        </tr>
        <tr>
            <td class="label">Final Grade</td>
            <td class="value">{$certificate['grade']} (GPA: {$gpaText})</td>
        </tr>
    </table>

    <div class="qr-section">
        <table class="qr-table" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td class="qr-cell">
                    <img src="data:image/png;base64,{$qrCodeBase64}" class="qr-img" />
                    <div class="qr-text">
                        <strong>REGISTRY TOKEN</strong><br>
                        <span style="font-family: monospace;">{$token}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="security-notice">
        <strong>SECURITY NOTICE:</strong> This digital credential report is generated by the {$schoolName} OJT Management System. Unauthorized reproduction or tampering is strictly prohibited and subject to disciplinary action.
    </div>

    <div class="footer">
        <div class="footer-text">{$documentFooterNote}</div>
        <div class="footer-contact">
            {$documentVerificationNote}<br>
            {$schoolName} • {$schoolAddress}<br>
            {$schoolEmail} • {$schoolPhone} • {$schoolWebsite}
        </div>
    </div>
</body>
</html>
HTML;

    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_top'    => 12,
        'margin_bottom' => 12,
        'margin_left'   => 15,
        'margin_right'  => 15,
        'margin_header' => 0,
        'margin_footer' => 0,
    ]);

    $mpdf->SetTitle('Certificate Verification - ' . $certificate['certificate_number']);
    $mpdf->SetAuthor($schoolName);
    $mpdf->SetSubject('OJT Certificate Verification Report');
    $mpdf->SetKeywords('certificate, verification, ojt, official');
    $mpdf->SetCreator('OJT Management System - Production');

    $mpdf->WriteHTML($html);
    
    $filename = 'Certificate-Verification-' . str_replace('-', '_', $certificate['certificate_number']) . '.pdf';
    $mpdf->Output($filename, 'D');
    exit;

} catch (Exception $e) {
    error_log("Certificate verification PDF export error: " . $e->getMessage());
    http_response_code(500);
    die("Error generating verification report. Please contact system administrator.");
}
?>