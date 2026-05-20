<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');



require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/student_functions.php';
require_once dirname(__DIR__, 2) . '/Assets/SystemInfo.php';
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? ''))) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

if (!isset($_SESSION['user_uuid']) || !in_array($_SESSION['user_role'], ['admin', 'coordinator'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (!$conn || $conn->connect_error) {
    die("Database connection failed.");
}

$studentData = $_POST['student_data'] ?? [];

if (empty($studentData) && isset($_GET['uuid'])) {
    $uuid = $conn->real_escape_string($_GET['uuid']);
    $student = getStudent($conn, $uuid);
    if ($student) {
        $studentData = [
            'full_name'      => $student['full_name'],
            'student_number' => $student['student_number'],
            'email'          => $student['email'],
            'program'        => $student['program_name'],
            'year_level'     => $student['year_level'],
            'section'        => $student['section'],
            'temp_password'  => isset($_GET['temp_password']) ? $_GET['temp_password'] : '********' 
        ];
    }
}

if (is_string($studentData)) {
    $studentData = json_decode($studentData, true) ?? [];
}

$fullName      = htmlspecialchars($studentData['full_name']      ?? '—');
$tempPassword  = htmlspecialchars($studentData['temp_password']  ?? '—');
$studentNumber = htmlspecialchars($studentData['student_number'] ?? '—');
$email         = htmlspecialchars($studentData['email']          ?? '—');
$program       = htmlspecialchars($studentData['program']        ?? '—');
$yearLevel     = htmlspecialchars($studentData['year_level']     ?? '—');
$section       = htmlspecialchars($studentData['section']        ?? '—');
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
    body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; }
    .page { padding: 40px; }

    .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; }
    .header-table td { vertical-align: middle; }
    .header-left { width: 20%; text-align: left; }
    .header-center { width: 60%; text-align: center; }
    .header-right { width: 20%; text-align: right; }
    .header-logo { width: 64px; height: 64px; object-fit: contain; }

    .notice-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; }
    .notice-title { font-size: 11px; font-weight: bold; color: #92400e; margin-bottom: 6px; }
    .notice-text { font-size: 11px; color: #78350f; }

    .credentials-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 14px 16px; margin-bottom: 16px; }
    .credentials-label { font-size: 11px; font-weight: bold; color: #166534; margin-bottom: 10px; }
    .cred-row { display: flex; justify-content: space-between; margin-bottom: 8px; align-items: center; }
    .cred-key { font-size: 12px; color: #64748b; font-weight: bold; }
    .cred-val { font-size: 13px; font-weight: bold; color: #1e293b; }
    .pw-val { font-size: 18px; font-weight: bold; color: #166534; font-family: monospace; margin-top: 4px; }

    .section-title { font-size: 12px; font-weight: bold; color: #374151; margin-top: 14px; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; }
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .info-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
    .info-table td:first-child { color: #64748b; font-weight: bold; width: 140px; }
    .info-table td:last-child { color: #1e293b; }
    .info-table tr:last-child td { border-bottom: none; }

    .steps-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 14px 16px; margin-bottom: 20px; }
    .steps-title { font-size: 11px; font-weight: bold; color: #1e40af; margin-bottom: 8px; }
    .step { font-size: 11px; color: #1e3a8a; margin-bottom: 5px; }

    .footer { border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 20px; }
    .footer-text { font-size: 10px; color: #64748b; margin-bottom: 4px; }
    .generated-info { font-size: 9px; color: #94a3b8; }
    .confidential { font-size: 10px; font-weight: bold; color: #dc2626; margin-bottom: 6px; }
    .footer-contact { margin-top: 6px; font-size: 9px; color: #94a3b8; }
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
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$longTitle} - Student Account Details</div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Generated on {$generatedAt}</div>
                </td>
                <td class="header-right">
                    <img src="{$LogoPath2}" alt="Logo Right" class="header-logo" />
                </td>
            </tr>
        </table>
    </div>

  <!-- notice -->
  <div class="notice-box">
    <div class="notice-title">⚠ Important Notice</div>
    <div class="notice-text">
      This document contains sensitive login credentials. Keep this confidential and do not share it with anyone.
      The temporary password below must be changed on your first login. This document is for one-time use only.
    </div>
  </div>

  <!-- credentials -->
  <div class="credentials-box">
    <div class="credentials-label">Login Credentials</div>
    <div class="cred-row">
      <div class="cred-key">Login Email</div>
      <div class="cred-val">{$email}</div>
    </div>
    <div class="cred-row">
      <div class="cred-key">Temporary Password</div>
    </div>
    <div class="pw-val">{$tempPassword}</div>
  </div>

  <!-- student info -->
  <div class="section-title">Student Information</div>
  <table class="info-table">
    <tr><td>Full Name</td><td>{$fullName}</td></tr>
    <tr><td>Student Number</td><td>{$studentNumber}</td></tr>
    <tr><td>Program</td><td>{$program}</td></tr>
    <tr><td>Year Level</td><td>{$yearLevel}</td></tr>
    <tr><td>Section</td><td>{$section}</td></tr>
  </table>

  <!-- login steps -->
  <div class="steps-box">
    <div class="steps-title">First Login Instructions</div>
    <div class="step">1. Go to the OJT System login page at <strong>{$PageLink}</strong></div>
    <div class="step">2. Enter your email address and the temporary password above.</div>
    <div class="step">3. You will be prompted to set a new password immediately after logging in.</div>
    <div class="step">4. Complete your profile setup before accessing the system features.</div>
    <div class="step">5. Contact your coordinator if you encounter any login issues.</div>
  </div>

  <!-- footer -->
  <div class="footer">
    <div class="confidential">CONFIDENTIAL — FOR STUDENT USE ONLY</div>
    <div class="footer-text">
                        This document was generated by the {$longTitle}
      Generated on {$generatedAt} · Do not reproduce or distribute.
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

        $fileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $fullName) . '_Account_Details.pdf';
        $mpdf->Output($fileName, 'D');
        exit;

    } catch (Exception $e) {
        
    }
}



function pdfEscape(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\(', '\)', '', ''], $text);
}

generateSimplePdf($fullName, $email, $tempPassword, $studentNumber, $program, $yearLevel, $section, $generatedAt, $schoolName, $longTitle, $schoolMotto, $schoolAddress, $schoolWebsite, $schoolEmail, $schoolPhone, $documentFooterNote, $documentVerificationNote);

function generateSimplePdf(
    string $fullName,
    string $email,
    string $tempPassword,
    string $studentNumber,
    string $program,
    string $yearLevel,
    string $section,
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
    $fileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $fullName) . '_Account_Details.pdf';

    $pdf  = "%PDF-1.4\n";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: private, no-cache, no-store');
    header('Pragma: no-cache');

    $content = buildRawPdf($fullName, $email, $tempPassword, $studentNumber, $program, $yearLevel, $section, $generatedAt, $schoolName, $longTitle, $schoolMotto, $schoolAddress, $schoolWebsite, $schoolEmail, $schoolPhone, $documentFooterNote, $documentVerificationNote);
    echo $content;
    exit;
}


function buildRawPdf(
    string $fullName,
    string $email,
    string $tempPassword,
    string $studentNumber,
    string $program,
    string $yearLevel,
    string $section,
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
    $fullNameEsc      = pdfEscape($fullName);
    $emailEsc         = pdfEscape($email);
    $tempPasswordEsc  = pdfEscape($tempPassword);
    $studentNumberEsc = pdfEscape($studentNumber);
    $programEsc       = pdfEscape($program);
    $yearLevelEsc     = pdfEscape($yearLevel);
    $sectionEsc       = pdfEscape($section);
    $generatedAtEsc   = pdfEscape($generatedAt);
    $schoolNameEsc    = pdfEscape($schoolName);

    $pageWidth  = 595;
    $pageHeight = 842;
    $margin     = 50;
    $y          = $pageHeight - $margin;

    $objects = [];
    $objNum  = 0;

    $addObj = function (string $content) use (&$objects, &$objNum): int {
        $objNum++;
        $objects[$objNum] = $content;
        return $objNum;
    };

    
    $catalogId = $addObj(''); 
    $pagesId   = $addObj(''); 
    $pageId    = $addObj(''); 
    $fontId    = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
    $fontBoldId = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');
    $fontMonoId = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >>');

    
    $stream = '';

    
    $setFont = fn ($id, $size) => "BT\n/F{$id} {$size} Tf\n";
    $text    = fn ($x, $y, $txt) => "{$x} {$y} Td ({$txt}) Tj\n";
    $line    = fn ($x1, $y1, $x2, $y2) => "{$x1} {$y1} m {$x2} {$y2} l S\n";
    $rect    = fn ($x, $y, $w, $h) => "{$x} {$y} {$w} {$h} re f\n";

    
    $color    = fn ($r, $g, $b) => ($r / 255) . ' ' . ($g / 255) . ' ' . ($b / 255) . " rg\n";
    $colorStr = fn ($r, $g, $b) => ($r / 255) . ' ' . ($g / 255) . ' ' . ($b / 255) . " RG\n";

    
    $s = '';

    
    $s .= $color(15, 110, 86); 
    $s .= "0 {$pageHeight} {$pageWidth} -80 re f\n";

    
    $s .= "1 1 1 rg\n";
    $s .= "BT /F2 14 Tf " . ($margin) . " " . ($pageHeight - 35) . " Td ({$schoolNameEsc}) Tj ET\n";
    $s .= "BT /F2 18 Tf " . ($margin) . " " . ($pageHeight - 56) . " Td (Student Account Credentials) Tj ET\n";
    $s .= "BT /F1 9 Tf " . ($margin) . " " . ($pageHeight - 72) . " Td (Generated: {$generatedAtEsc}) Tj ET\n";

    
    $s .= "0 0 0 rg\n";
    $cy = $pageHeight - 110;

    
    $s .= "1 0.97 0.93 rg\n"; 
    $s .= "{$margin} " . ($cy - 50) . " " . ($pageWidth - ($margin * 2)) . " 60 re f\n";
    $s .= "0 0 0 rg\n";
    $s .= "BT /F2 9 Tf " . ($margin + 8) . " " . ($cy + 2) . " Td (IMPORTANT: This document contains sensitive login credentials. Keep confidential.) Tj ET\n";
    $s .= "BT /F1 9 Tf " . ($margin + 8) . " " . ($cy - 12) . " Td (Change your password immediately upon first login.) Tj ET\n";
    $cy -= 70;

    
    $s .= "0.88 0.96 0.93 rg\n"; 
    $s .= "{$margin} " . ($cy - 85) . " " . ($pageWidth - ($margin * 2)) . " 95 re f\n";

    
    $s .= $colorStr(15, 110, 86);
    $s .= "2 w\n";
    $s .= "{$margin} " . ($cy - 85) . " " . ($pageWidth - ($margin * 2)) . " 95 re S\n";
    $s .= "0 w\n";

    $s .= $color(15, 110, 86);
    $s .= "BT /F2 10 Tf " . ($margin + 8) . " " . ($cy + 2) . " Td (LOGIN CREDENTIALS) Tj ET\n";
    $s .= "0 0 0 rg\n";

    
    $s .= "BT /F1 10 Tf " . ($margin + 8) . " " . ($cy - 16) . " Td (Email Address:) Tj ET\n";
    $s .= "BT /F2 10 Tf 200 " . ($cy - 16) . " Td ({$emailEsc}) Tj ET\n";

    
    $s .= "BT /F1 10 Tf " . ($margin + 8) . " " . ($cy - 34) . " Td (Temporary Password:) Tj ET\n";
    $s .= $color(15, 110, 86);
    $s .= "BT /F3 20 Tf 200 " . ($cy - 40) . " Td ({$tempPasswordEsc}) Tj ET\n";
    $s .= "0 0 0 rg\n";

    $cy -= 110;

    
    $s .= $color(15, 110, 86);
    $s .= "BT /F2 11 Tf {$margin} {$cy} Td (STUDENT INFORMATION) Tj ET\n";
    $s .= "0 0 0 rg\n";

    
    $cy -= 6;
    $s .= $colorStr(15, 110, 86);
    $s .= "1 w {$margin} {$cy} " . ($pageWidth - ($margin * 2)) . " 0 l S\n";
    $s .= "0 w 0 0 0 RG\n";
    $cy -= 18;

    $rows = [
        ['Full Name',      $fullNameEsc],
        ['Student Number', $studentNumberEsc],
        ['Program',        $programEsc],
        ['Year Level',     $yearLevelEsc],
        ['Section',        $sectionEsc],
    ];

    foreach ($rows as $row) {
        
        static $alt = false;
        if ($alt) {
            $s .= "0.97 0.97 0.97 rg\n";
            $s .= "{$margin} " . ($cy - 6) . " " . ($pageWidth - ($margin * 2)) . " 20 re f\n";
            $s .= "0 0 0 rg\n";
        }
        $alt = !$alt;

        $s .= "BT /F1 10 Tf " . ($margin + 6) . " {$cy} Td ({$row[0]}) Tj ET\n";
        $s .= "BT /F2 10 Tf 230 {$cy} Td ({$row[1]}) Tj ET\n";
        $cy -= 22;
    }

    $cy -= 14;

    
    $s .= "0.94 0.97 1 rg\n"; 
    $s .= "{$margin} " . ($cy - 100) . " " . ($pageWidth - ($margin * 2)) . " 110 re f\n";
    $s .= "0 0 0 rg\n";

    $s .= "BT /F2 10 Tf " . ($margin + 8) . " " . ($cy - 2) . " Td (FIRST LOGIN INSTRUCTIONS) Tj ET\n";
    $steps = [
        '1. Go to the OJT System login page.',
        '2. Enter your email address and the temporary password above.',
        '3. You will be prompted to set a new password immediately.',
        '4. Complete your profile setup before using system features.',
        '5. Contact your coordinator if you have any login issues.',
    ];
    $sy = $cy - 18;
    foreach ($steps as $step) {
        $s .= "BT /F1 9 Tf " . ($margin + 8) . " {$sy} Td ({$step}) Tj ET\n";
        $sy -= 14;
    }

    $cy -= 120;

    
    $s .= "0.9 0.9 0.9 RG 1 w\n";
    $s .= "{$margin} " . ($margin + 30) . " " . ($pageWidth - ($margin * 2)) . " 0 l S\n";
    $s .= "0 0 0 RG 0 w\n";

    $s .= "1 0 0 rg\n";
    $s .= "BT /F2 9 Tf {$margin} " . ($margin + 18) . " Td (CONFIDENTIAL - FOR STUDENT USE ONLY) Tj ET\n";
    $s .= "0 0 0 rg\n";
    $footerLine = pdfEscape($documentFooterNote . ' ' . $documentVerificationNote . ' ' . $schoolName . ' | ' . $schoolAddress . ' | ' . $schoolWebsite . ' | ' . $schoolEmail . ' | ' . $schoolPhone);
    $s .= "BT /F1 8 Tf {$margin} " . ($margin + 6) . " Td (Generated by {$longTitle}. Do not reproduce or distribute.) Tj ET\n";
    $s .= "BT /F1 7 Tf {$margin} " . ($margin - 6) . " Td ({$footerLine}) Tj ET\n";

    
    $streamLen = strlen($s);
    $contentId = $addObj("<< /Length {$streamLen} >>\nstream\n{$s}\nendstream");

    
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
        $out .= str_pad($offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $out .= "trailer\n<< /Size " . ($objNum + 1) . " /Root {$catalogId} 0 R >>\n";
    $out .= "startxref\n{$xrefOffset}\n%%EOF";

    return $out;
}
