<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        http_response_code(403);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    } else {
        error_log(
            "Unauthorized direct access attempt to " .
            basename(__FILE__) . " from " .
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
        );
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/student_functions.php';
require_once dirname(__DIR__, 2) . '/Assets/SystemInfo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'       => 'critical',
        'message'      => 'Database connection failed.',
        'details'      => $conn->connect_error ?? 'Unknown error',
        'suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

// Accept either:
// 1) student_data (array/json), or
// 2) direct POST fields: full_name, temp_password
$studentData = $_POST['student_data'] ?? [];

if (is_string($studentData)) {
    $studentData = json_decode($studentData, true) ?? [];
}
if (!is_array($studentData)) {
    $studentData = [];
}

$fullNameRaw     = $studentData['full_name'] ?? ($_POST['full_name'] ?? '');
$tempPasswordRaw = $studentData['temp_password'] ?? ($_POST['temp_password'] ?? '');

$fullName     = htmlspecialchars(trim((string)$fullNameRaw));
$tempPassword = htmlspecialchars(trim((string)$tempPasswordRaw));

if ($fullName === '' || $tempPassword === '') {
    http_response_code(422);
    response([
        'status'  => 'error',
        'message' => 'Missing required fields: full_name and temp_password.'
    ]);
}

$generatedAt   = date('F j, Y g:i A');
$schoolName    = $SchoolName ?? 'Your School Name Here';
$longTitle     = $LongTitle ?? 'Your System Long Title Here';
$schoolMotto   = $SchoolMotto ?? '';
$schoolAddress = $SchoolAddress ?? '';
$schoolWebsite = $SchoolWebsite ?? '';
$schoolEmail   = $SchoolEmail ?? '';
$schoolPhone   = $SchoolPhone ?? '';
$documentFooterNote = $DocumentFooterNote ?? 'Officially issued by the OJT Coordinator Management System';
$documentVerificationNote = $DocumentVerificationNote ?? 'Please verify document authenticity with the coordinator\'s office.';
$fileCreatedBy = $_SESSION['user_name'] ?? 'Admin User';
$roleofCreator = $_SESSION['user_role'] === 'admin' ? 'Administrator' : 'User';
$LogoPath1      = $SchoolLogoLeft ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';
$LogoPath2      = $SchoolLogoRight ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; }
    .page { padding: 40px; }

    .header { text-align: center; border-bottom: 2px solid #0F6E56; padding-bottom: 16px; margin-bottom: 24px; }
    .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 14px; margin-bottom: 22px; }
    .header-table td { vertical-align: middle; }
    .header-left { width: 20%; text-align: left; }
    .header-center { width: 60%; text-align: center; }
    .header-right { width: 20%; text-align: right; }
    .header-logo { width: 64px; height: 64px; object-fit: contain; }
    .school-name { font-size: 15px; font-weight: bold; color: #0F6E56; margin-bottom: 4px; }
    .school-meta { font-size: 10px; color: #64748b; margin-top: 2px; }
    .doc-title { font-size: 20px; font-weight: bold; color: #111; margin-bottom: 4px; }
    .doc-subtitle { font-size: 11px; color: #666; }

    .notice-box { background: #FEF9EE; border: 1px solid #FDE68A; border-radius: 6px; padding: 12px 14px; margin-bottom: 24px; }
    .notice-title { font-size: 11px; font-weight: bold; color: #92400E; margin-bottom: 4px; }
    .notice-text { font-size: 11px; color: #92400E; line-height: 1.5; }

    .credentials-box { background: #E1F5EE; border: 1.5px solid #1D9E75; border-radius: 8px; padding: 20px 24px; margin-bottom: 24px; text-align: center; }
    .credentials-label { font-size: 11px; font-weight: bold; color: #0F6E56; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px; }
    .cred-row { display: flex; justify-content: space-between; margin-bottom: 8px; align-items: center; }
    .cred-key { font-size: 12px; color: #065F46; font-weight: 500; text-align: left; }
    .cred-val { font-size: 13px; font-weight: bold; color: #0F6E56; font-family: 'Courier New', monospace; text-align: right; }
    .pw-val { font-size: 22px; font-weight: bold; color: #0F6E56; font-family: 'Courier New', monospace; letter-spacing: 0.1em; margin-top: 8px; }

    .steps-box { background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 6px; padding: 14px 16px; margin-bottom: 24px; }
    .steps-title { font-size: 11px; font-weight: bold; color: #0369A1; margin-bottom: 8px; }
    .step { font-size: 11px; color: #0369A1; margin-bottom: 5px; line-height: 1.4; }

    .footer { border-top: 1px solid #E5E7EB; padding-top: 12px; text-align: center; }
    .footer-text { font-size: 10px; color: #616264; line-height: 1.6; }
    .generated-info { font-size: 9px; color: #3e3f41; margin-top: 4px; text-align: right; }
    .confidential { font-size: 10px; font-weight: bold; color: #EF4444; margin-bottom: 4px; }
        .footer-contact { margin-top: 6px; font-size: 9px; color: #64748b; line-height: 1.45; }
  </style>
</head>
<body>
<div class="page">

    <div class="header">
        <table class="header-table" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td class="header-left">
                    <img src="{$LogoPath1}" alt="Logo Left" class="header-logo" />
                </td>
                <td class="header-center" style="line-height:1.35;">
                    <div style="font-size: 15px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em;">{$schoolName}</div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$schoolMotto}</div>
                    <div style="font-size: 11px; color: #475569; margin-top: 3px;">Official Digital Credential Document</div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$longTitle} - Password Reset Details</div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Generated on {$generatedAt}</div>
                </td>
                <td class="header-right">
                    <img src="{$LogoPath2}" alt="Logo Right" class="header-logo" />
                </td>
            </tr>
        </table>
    </div>

  <div class="notice-box">
    <div class="notice-title">⚠ Important Notice</div>
    <div class="notice-text">
      This document contains sensitive login credentials. Keep this confidential and do not share it.
      Use this temporary password to log in and change it immediately.
    </div>
  </div>

  <div class="credentials-box">
    <div class="credentials-label">Reset Password Details</div>
    <div class="cred-row">
      <div class="cred-key">Student Name</div>
      <div class="cred-val">{$fullName}</div>
    </div>
    <div class="cred-row">
      <div class="cred-key">Temporary Password</div>
    </div>
    <div class="pw-val">{$tempPassword}</div>
  </div>

  <div class="steps-box">
    <div class="steps-title">Next Steps</div>
    <div class="step">1. Go to the OJT System login page at <strong>{$PageLink}</strong>.</div>
    <div class="step">2. Log in using your account and this temporary password.</div>
    <div class="step">3. Change your password immediately after login.</div>
  </div>

  <div class="footer">
    <div class="confidential">CONFIDENTIAL — FOR STUDENT USE ONLY</div>
    <div class="footer-text">
            This document was generated by the {$longTitle}. Generated on {$generatedAt}.
    </div>
    <div class="footer-text generated-info">
      Document created by {$fileCreatedBy} ({$roleofCreator})
    </div>
        <div class="footer-contact">{$documentFooterNote}<br>{$documentVerificationNote}<br>{$schoolName} · {$schoolAddress} · {$schoolWebsite} · {$schoolEmail} · {$schoolPhone}</div>
  </div>
</div>
</body>
</html>
HTML;

$mpdfPath = dirname(__DIR__, 2) . '/libs/composer/vendor/autoload.php';

if (file_exists($mpdfPath)) {
    require_once $mpdfPath;

    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 0,
            'margin_bottom' => 0,
            'margin_left'   => 0,
            'margin_right'  => 0,
        ]);

        $mpdf->WriteHTML($html);

        $safeFileBase = preg_replace('/[^a-zA-Z0-9_]/', '_', html_entity_decode($fullName, ENT_QUOTES, 'UTF-8'));
        $fileName = $safeFileBase . '_Reset_Password.pdf';
        $mpdf->Output($fileName, 'D');
        exit;

    } catch (Exception $e) {
        // Fall through to basic PDF
    }
}

generateSimplePdf($fullName, $tempPassword, $generatedAt, $schoolName, $longTitle, $schoolMotto, $schoolAddress, $schoolWebsite, $schoolEmail, $schoolPhone, $documentFooterNote, $documentVerificationNote);

function generateSimplePdf(
    string $fullName,
    string $tempPassword,
    string $generatedAt,
    string $schoolName,
    string $longTitle,
    string $schoolMotto,
    string $schoolAddress,
    string $schoolWebsite,
    string $schoolEmail,
    string $schoolPhone,
    string $documentFooterNote,
    string $documentVerificationNote
): void {
    $safeFileBase = preg_replace('/[^a-zA-Z0-9_]/', '_', html_entity_decode($fullName, ENT_QUOTES, 'UTF-8'));
    $fileName = $safeFileBase . '_Reset_Password.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: private, no-cache, no-store');
    header('Pragma: no-cache');

    echo buildRawPdf($fullName, $tempPassword, $generatedAt, $schoolName, $longTitle, $schoolMotto, $schoolAddress, $schoolWebsite, $schoolEmail, $schoolPhone, $documentFooterNote, $documentVerificationNote);
    exit;
}

function pdfEscape(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\(', '\)', '', ''], $text);
}

function buildRawPdf(
    string $fullName,
    string $tempPassword,
    string $generatedAt,
    string $schoolName,
    string $longTitle,
    string $schoolMotto,
    string $schoolAddress,
    string $schoolWebsite,
    string $schoolEmail,
    string $schoolPhone,
    string $documentFooterNote,
    string $documentVerificationNote
): string {
    $fullNameEsc     = pdfEscape($fullName);
    $tempPasswordEsc = pdfEscape($tempPassword);
    $generatedAtEsc  = pdfEscape($generatedAt);
    $schoolNameEsc   = pdfEscape($schoolName);

    $pageWidth  = 595;
    $pageHeight = 842;
    $margin     = 50;

    $objects = [];
    $objNum  = 0;

    $addObj = function (string $content) use (&$objects, &$objNum): int {
        $objNum++;
        $objects[$objNum] = $content;
        return $objNum;
    };

    $catalogId  = $addObj('');
    $pagesId    = $addObj('');
    $pageId     = $addObj('');
    $fontId     = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
    $fontBoldId = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');
    $fontMonoId = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >>');

    $s = '';

    $s .= "0.06 0.43 0.34 rg\n";
    $s .= "0 {$pageHeight} {$pageWidth} -80 re f\n";
    $s .= "1 1 1 rg\n";
    $s .= "BT /F2 14 Tf {$margin} " . ($pageHeight - 35) . " Td ({$schoolNameEsc}) Tj ET\n";
    $s .= "BT /F2 18 Tf {$margin} " . ($pageHeight - 56) . " Td (Password Reset Credentials) Tj ET\n";
    $s .= "BT /F1 9 Tf {$margin} " . ($pageHeight - 72) . " Td (Generated: {$generatedAtEsc}) Tj ET\n";

    $cy = $pageHeight - 120;
    $s .= "0 0 0 rg\n";
    $s .= "BT /F1 11 Tf {$margin} {$cy} Td (Student Name:) Tj ET\n";
    $s .= "BT /F2 11 Tf 170 {$cy} Td ({$fullNameEsc}) Tj ET\n";

    $cy -= 35;
    $s .= "BT /F1 11 Tf {$margin} {$cy} Td (Temporary Password:) Tj ET\n";
    $s .= "0.06 0.43 0.34 rg\n";
    $s .= "BT /F3 22 Tf 220 {$cy} Td ({$tempPasswordEsc}) Tj ET\n";
    $s .= "0 0 0 rg\n";

    $cy -= 45;
    $s .= "BT /F1 10 Tf {$margin} {$cy} Td (Change this password immediately after login.) Tj ET\n";

    $cy -= 18;
    $footerRaw = pdfEscape($documentFooterNote . ' ' . $documentVerificationNote . ' ' . $schoolName . ' | ' . $schoolAddress . ' | ' . $schoolWebsite . ' | ' . $schoolEmail . ' | ' . $schoolPhone);
    $s .= "BT /F1 7 Tf {$margin} {$cy} Td ({$footerRaw}) Tj ET\n";

    $contentId = $addObj("<< /Length " . strlen($s) . " >>\nstream\n{$s}\nendstream");

    $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R "
        . "/MediaBox [0 0 {$pageWidth} {$pageHeight}] "
        . "/Contents {$contentId} 0 R "
        . "/Resources << /Font << /F1 {$fontId} 0 R /F2 {$fontBoldId} 0 R /F3 {$fontMonoId} 0 R >> >> >>";

    $objects[$pagesId] = "<< /Type /Pages /Kids [{$pageId} 0 R] /Count 1 >>";
    $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

    $out     = "%PDF-1.4\n";
    $offsets = [];

    foreach ($objects as $num => $content) {
        $offsets[$num] = strlen($out);
        $out .= "{$num} 0 obj\n{$content}\nendobj\n";
    }

    $xrefOffset = strlen($out);
    $out .= "xref\n0 " . ($objNum + 1) . "\n";
    $out .= "0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $out .= str_pad((string)$offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $out .= "trailer\n<< /Size " . ($objNum + 1) . " /Root {$catalogId} 0 R >>\n";
    $out .= "startxref\n{$xrefOffset}\n%%EOF";

    return $out;
}
