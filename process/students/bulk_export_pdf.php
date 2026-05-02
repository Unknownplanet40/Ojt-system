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

$created = $_SESSION['bulk_created'] ?? [];

if (empty($created)) {
    response([
        'status' => 'error',
        'message' => 'No created accounts found to export. Please create accounts first.'
    ]);
}

$generatedAt = date('F j, Y g:i A');
$schoolName = $SchoolName ?? 'Your School Name Here';
$longTitle = $LongTitle ?? 'Your System Long Title Here';
$schoolMotto = $SchoolMotto ?? '';
$schoolAddress = $SchoolAddress ?? '';
$schoolWebsite = $SchoolWebsite ?? '';
$schoolEmail = $SchoolEmail ?? '';
$schoolPhone = $SchoolPhone ?? '';
$documentFooterNote = $DocumentFooterNote ?? 'Officially issued by the OJT Coordinator Management System';
$documentVerificationNote = $DocumentVerificationNote ?? 'Please verify document authenticity with the coordinator\'s office.';
$fileCreatedBy = $_SESSION['user_name'] ?? 'Admin User';
$roleofCreator = $_SESSION['user_role'] === 'admin' ? 'Administrator' : 'User';
$LogoPath1 = $SchoolLogoLeft ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';
$LogoPath2 = $SchoolLogoRight ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';

$rowsHtml = '';
foreach ($created as $index => $student) {
    $name = htmlspecialchars($student['full_name'] ?? '—');
    $email = htmlspecialchars($student['email'] ?? '—');
    $studentNumber = htmlspecialchars($student['student_number'] ?? '—');
    $programCode = htmlspecialchars($student['program_code'] ?? '—');
    $yearLabel = htmlspecialchars($student['year_label'] ?? '');
    $program = trim($programCode . ' ' . $yearLabel);
    $tempPassword = htmlspecialchars($student['temp_password'] ?? '—');
    $coordinator = htmlspecialchars($student['coordinator_name'] ?? '—');

    $rowNo = $index + 1;
    $rowsHtml .= "
      <tr>
        <td>{$rowNo}</td>
        <td>{$name}</td>
        <td>{$email}</td>
        <td>{$studentNumber}</td>
        <td>{$program}</td>
        <td>{$coordinator}</td>
        <td class=\"password\">{$tempPassword}</td>
      </tr>
    ";
}

$totalCreated = count($created);

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #111827; margin: 24px; }
    .header { text-align: center; border-bottom: 2px solid #0F6E56; padding-bottom: 10px; margin-bottom: 16px; }
    .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 8px; margin-bottom: 12px; }
    .header-table td { vertical-align: middle; }
    .header-left { width: 20%; text-align: left; }
    .header-center { width: 60%; text-align: center; }
    .header-right { width: 20%; text-align: right; }
    .header-logo { width: 52px; height: 52px; object-fit: contain; }
    .school-name { font-size: 14px; font-weight: bold; color: #0F6E56; }
    .school-meta { font-size: 10px; color: #64748b; margin-top: 2px; }
    .title { font-size: 18px; font-weight: bold; margin-top: 4px; }
    .subtitle { font-size: 10px; color: #6B7280; margin-top: 3px; }

    .meta { margin-bottom: 14px; font-size: 10px; color: #374151; }
    .meta strong { color: #111827; }

    .notice { background: #FEF9EE; border: 1px solid #FDE68A; border-radius: 6px; padding: 10px; margin-bottom: 14px; color: #92400E; }

    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #E5E7EB; padding: 6px 7px; vertical-align: top; }
    th { background: #F3F4F6; text-transform: uppercase; font-size: 9px; letter-spacing: 0.04em; color: #374151; }
    td { font-size: 10px; }
    td.password { font-family: "Courier New", monospace; font-weight: bold; color: #065F46; }

    .footer { margin-top: 14px; border-top: 1px solid #E5E7EB; padding-top: 8px; font-size: 9px; color: #6B7280; text-align: right; }
    .footer-contact { margin-top: 5px; font-size: 8px; color: #64748b; line-height: 1.4; text-align: left; }
  </style>
</head>
<body>
  <div class="header">
    <table class="header-table" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td class="header-left">
          <img src="{$LogoPath1}" alt="Logo Left" class="header-logo" />
        </td>
        <td class="header-center" style="line-height:1.35;">
          <div style="font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em;">{$schoolName}</div>
          <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$schoolMotto}</div>
          <div style="font-size: 10px; color: #475569; margin-top: 2px;">Official Digital Credential Document</div>
          <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$longTitle} - Bulk Student Account Details</div>
          <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Generated on {$generatedAt}</div>
        </td>
        <td class="header-right">
          <img src="{$LogoPath2}" alt="Logo Right" class="header-logo" />
        </td>
      </tr>
    </table>
  </div>

  <div class="meta">
    <div><strong>Total Accounts Created:</strong> {$totalCreated}</div>
  </div>

  <div class="notice">
    <strong>Confidential:</strong> This sheet contains sensitive login credentials. Share securely and require students to change their password on first login.
  </div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Student Name</th>
        <th>Email</th>
        <th>Student No.</th>
        <th>Program</th>
        <th>Coordinator</th>
        <th>Temp Password</th>
      </tr>
    </thead>
    <tbody>
      {$rowsHtml}
    </tbody>
  </table>

  <div class="footer">
    Document created by {$fileCreatedBy} ({$roleofCreator}) · {$longTitle}
    <div class="footer-contact">{$documentFooterNote}<br>{$documentVerificationNote}<br>{$schoolName} · {$schoolAddress} · {$schoolWebsite} · {$schoolEmail} · {$schoolPhone}</div>
  </div>
</body>
</html>
HTML;

$mpdfPath = dirname(__DIR__, 2) . '/libs/composer/vendor/autoload.php';
if (!file_exists($mpdfPath)) {
    response([
        'status' => 'error',
        'message' => 'PDF library is not available on this server.'
    ]);
}

require_once $mpdfPath;

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'margin_top'    => 10,
        'margin_bottom' => 10,
        'margin_left'   => 10,
        'margin_right'  => 10,
    ]);

    $mpdf->WriteHTML($html);
    $fileName = 'bulk_created_accounts_' . date('Y-m-d_His') . '.pdf';
    $mpdf->Output($fileName, 'D');
    exit;
} catch (Exception $e) {
    response([
        'status' => 'error',
        'message' => 'Failed to generate PDF: ' . $e->getMessage()
    ]);
}
