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

$studentData = $_POST['student_data'] ?? [];

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
$fileCreatedBy = $_SESSION['user_name'] ?? 'Admin User';
$roleofCreator = $_SESSION['user_role'] === 'admin' ? 'Administrator' : 'User';

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; padding: 0; }
    .page { padding: 40px; }

    .header { text-align: center; border-bottom: 2px solid #0F6E56; padding-bottom: 16px; margin-bottom: 24px; }
    .school-name { font-size: 15px; font-weight: bold; color: #0F6E56; margin-bottom: 4px; }
    .doc-title { font-size: 20px; font-weight: bold; color: #111; margin-bottom: 4px; }
    .doc-subtitle { font-size: 11px; color: #666; }

    .notice-box { background: #FEF9EE; border: 1px solid #FDE68A; border-radius: 6px; padding: 12px 14px; margin-bottom: 24px; }
    .notice-title { font-size: 11px; font-weight: bold; color: #92400E; margin-bottom: 4px; }
    .notice-text { font-size: 11px; color: #92400E; line-height: 1.5; }

    .credentials-box { background: #E1F5EE; border: 1.5px solid #1D9E75; border-radius: 8px; padding: 20px 24px; margin-bottom: 24px; text-align: center; }
    .credentials-label { font-size: 11px; font-weight: bold; color: #0F6E56; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px; }
    .cred-row { display: flex; justify-content: space-between; margin-bottom: 8px; align-items: center; }
    .cred-key { font-size: 12px; color: #065F46; font-weight: 500; text-align: left; }
    .cred-val { font-size: 13px; font-weight: bold; color: #0F6E56; font-family: 'Courier New', monospace; text-align: center; }
    .pw-val { font-size: 18px; font-weight: bold; color: #0F6E56; font-family: 'Courier New', monospace; letter-spacing: 0.1em; margin-top: 6px; }

    .section-title { font-size: 12px; font-weight: bold; color: #374151; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 10px; padding-bottom: 4px; border-bottom: 1px solid #E5E7EB; }
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .info-table td { padding: 8px 10px; border-bottom: 1px solid #F3F4F6; font-size: 12px; }
    .info-table td:first-child { color: #6B7280; width: 40%; font-weight: 500; }
    .info-table td:last-child { color: #111827; font-weight: 600; }
    .info-table tr:last-child td { border-bottom: none; }

    .steps-box { background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 6px; padding: 14px 16px; margin-bottom: 24px; }
    .steps-title { font-size: 11px; font-weight: bold; color: #0369A1; margin-bottom: 8px; }
    .step { font-size: 11px; color: #0369A1; margin-bottom: 5px; line-height: 1.4; }

    .footer { border-top: 1px solid #E5E7EB; padding-top: 12px; text-align: center; }
    .footer-text { font-size: 10px; color: #616264; line-height: 1.6; }
    .generated-info { font-size: 9px; color: #3e3f41; margin-top: 4px; text-align: right; }
    .confidential { font-size: 10px; font-weight: bold; color: #EF4444; margin-bottom: 4px; }
    .divider { border: none; border-top: 1px solid #E5E7EB; margin: 16px 0; }
  </style>
</head>
<body>
<div class="page">

  <!-- header -->
  <div class="header">
    <div class="school-name">{$schoolName}</div>
    <div class="doc-title">Student Account Credentials</div>
    <div class="doc-subtitle">Official OJT System Access Document · Generated {$generatedAt}</div>
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
            This document was generated by the {$LongTitle}
      Generated on {$generatedAt} · Do not reproduce or distribute.
    </div>
    <div class="footer-text generated-info">
        Document created by {$fileCreatedBy} ({$roleofCreator})
    </div>
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
        // fall through to Option 2
    }
}


// Option 2: Generate a very basic PDF using raw PDF syntax (without external libraries)
generateSimplePdf($fullName, $email, $tempPassword, $studentNumber, $program, $yearLevel, $section, $generatedAt, $schoolName);

function generateSimplePdf(
    string $fullName,
    string $email,
    string $tempPassword,
    string $studentNumber,
    string $program,
    string $yearLevel,
    string $section,
    string $generatedAt,
    string $schoolName
): void {
    $fileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $fullName) . '_Account_Details.pdf';

    $pdf  = "%PDF-1.4\n";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: private, no-cache, no-store');
    header('Pragma: no-cache');

    $content = buildRawPdf($fullName, $email, $tempPassword, $studentNumber, $program, $yearLevel, $section, $generatedAt, $schoolName);
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
    string $schoolName
): string {
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

    // catalog
    $catalogId = $addObj(''); // placeholder
    $pagesId   = $addObj(''); // placeholder
    $pageId    = $addObj(''); // placeholder
    $fontId    = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
    $fontBoldId = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');
    $fontMonoId = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >>');

    // build page content stream
    $stream = '';

    // helper functions for stream
    $setFont = fn ($id, $size) => "BT\n/F{$id} {$size} Tf\n";
    $text    = fn ($x, $y, $txt) => "{$x} {$y} Td ({$txt}) Tj\n";
    $line    = fn ($x1, $y1, $x2, $y2) => "{$x1} {$y1} m {$x2} {$y2} l S\n";
    $rect    = fn ($x, $y, $w, $h) => "{$x} {$y} {$w} {$h} re f\n";

    // set color helper
    $color    = fn ($r, $g, $b) => ($r / 255) . ' ' . ($g / 255) . ' ' . ($b / 255) . " rg\n";
    $colorStr = fn ($r, $g, $b) => ($r / 255) . ' ' . ($g / 255) . ' ' . ($b / 255) . " RG\n";

    // ---- build content ----
    $s = '';

    // green header bar
    $s .= $color(15, 110, 86); // #0F6E56
    $s .= "0 {$pageHeight} {$pageWidth} -80 re f\n";

    // header text — white
    $s .= "1 1 1 rg\n";
    $s .= "BT /F2 14 Tf " . ($margin) . " " . ($pageHeight - 35) . " Td ({$schoolName}) Tj ET\n";
    $s .= "BT /F2 18 Tf " . ($margin) . " " . ($pageHeight - 56) . " Td (Student Account Credentials) Tj ET\n";
    $s .= "BT /F1 9 Tf " . ($margin) . " " . ($pageHeight - 72) . " Td (Generated: {$generatedAt}) Tj ET\n";

    // reset to black
    $s .= "0 0 0 rg\n";
    $cy = $pageHeight - 110;

    // notice box
    $s .= "1 0.97 0.93 rg\n"; // light yellow
    $s .= "{$margin} " . ($cy - 50) . " " . ($pageWidth - ($margin * 2)) . " 60 re f\n";
    $s .= "0 0 0 rg\n";
    $s .= "BT /F2 9 Tf " . ($margin + 8) . " " . ($cy + 2) . " Td (IMPORTANT: This document contains sensitive login credentials. Keep confidential.) Tj ET\n";
    $s .= "BT /F1 9 Tf " . ($margin + 8) . " " . ($cy - 12) . " Td (Change your password immediately upon first login.) Tj ET\n";
    $cy -= 70;

    // credentials box
    $s .= "0.88 0.96 0.93 rg\n"; // light green
    $s .= "{$margin} " . ($cy - 85) . " " . ($pageWidth - ($margin * 2)) . " 95 re f\n";

    // green border
    $s .= $colorStr(15, 110, 86);
    $s .= "2 w\n";
    $s .= "{$margin} " . ($cy - 85) . " " . ($pageWidth - ($margin * 2)) . " 95 re S\n";
    $s .= "0 w\n";

    $s .= $color(15, 110, 86);
    $s .= "BT /F2 10 Tf " . ($margin + 8) . " " . ($cy + 2) . " Td (LOGIN CREDENTIALS) Tj ET\n";
    $s .= "0 0 0 rg\n";

    // email
    $s .= "BT /F1 10 Tf " . ($margin + 8) . " " . ($cy - 16) . " Td (Email Address:) Tj ET\n";
    $s .= "BT /F2 10 Tf 200 " . ($cy - 16) . " Td ({$email}) Tj ET\n";

    // password
    $s .= "BT /F1 10 Tf " . ($margin + 8) . " " . ($cy - 34) . " Td (Temporary Password:) Tj ET\n";
    $s .= $color(15, 110, 86);
    $s .= "BT /F3 20 Tf 200 " . ($cy - 40) . " Td ({$tempPassword}) Tj ET\n";
    $s .= "0 0 0 rg\n";

    $cy -= 110;

    // student info section
    $s .= $color(15, 110, 86);
    $s .= "BT /F2 11 Tf {$margin} {$cy} Td (STUDENT INFORMATION) Tj ET\n";
    $s .= "0 0 0 rg\n";

    // underline
    $cy -= 6;
    $s .= $colorStr(15, 110, 86);
    $s .= "1 w {$margin} {$cy} " . ($pageWidth - ($margin * 2)) . " 0 l S\n";
    $s .= "0 w 0 0 0 RG\n";
    $cy -= 18;

    $rows = [
        ['Full Name',      $fullName],
        ['Student Number', $studentNumber],
        ['Program',        $program],
        ['Year Level',     $yearLevel],
        ['Section',        $section],
    ];

    foreach ($rows as $row) {
        // alternate row background
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

    // login steps box
    $s .= "0.94 0.97 1 rg\n"; // light blue
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

    // footer line
    $s .= "0.9 0.9 0.9 RG 1 w\n";
    $s .= "{$margin} " . ($margin + 30) . " " . ($pageWidth - ($margin * 2)) . " 0 l S\n";
    $s .= "0 0 0 RG 0 w\n";

    $s .= "1 0 0 rg\n";
    $s .= "BT /F2 9 Tf {$margin} " . ($margin + 18) . " Td (CONFIDENTIAL - FOR STUDENT USE ONLY) Tj ET\n";
    $s .= "0 0 0 rg\n";
    $s .= "BT /F1 8 Tf {$margin} " . ($margin + 6) . " Td (Generated by OJT Coordinator Management System. Do not reproduce or distribute.) Tj ET\n";

    // content stream object
    $streamLen = strlen($s);
    $contentId = $addObj("<< /Length {$streamLen} >>\nstream\n{$s}\nendstream");

    // update page object
    $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R "
        . "/MediaBox [0 0 {$pageWidth} {$pageHeight}] "
        . "/Contents {$contentId} 0 R "
        . "/Resources << /Font << /F1 {$fontId} 0 R /F2 {$fontBoldId} 0 R /F3 {$fontMonoId} 0 R >> >> >>";

    $objects[$pagesId] = "<< /Type /Pages /Kids [{$pageId} 0 R] /Count 1 >>";
    $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

    // build PDF file
    $out     = "%PDF-1.4\n";
    $offsets = [];

    foreach ($objects as $num => $content) {
        $offsets[$num] = strlen($out);
        $out .= "{$num} 0 obj\n{$content}\nendobj\n";
    }

    // cross-reference table
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
