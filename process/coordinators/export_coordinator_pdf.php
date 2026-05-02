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

$coordinatorData = $_POST['coordinator_data'] ?? [];
if (is_string($coordinatorData)) {
    $coordinatorData = json_decode($coordinatorData, true) ?? [];
}

$fullName = htmlspecialchars($coordinatorData['full_name'] ?? '—');
$tempPassword = htmlspecialchars($coordinatorData['temp_password'] ?? '—');
$employeeId = htmlspecialchars($coordinatorData['employee_id'] ?? '—');
$email = htmlspecialchars($coordinatorData['email'] ?? '—');
$department = htmlspecialchars($coordinatorData['department'] ?? '—');
$mobile = htmlspecialchars($coordinatorData['mobile'] ?? '—');

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
$roleOfCreator = $_SESSION['user_role'] === 'admin' ? 'Administrator' : 'User';
$LogoPath1 = $SchoolLogoLeft ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';
$LogoPath2 = $SchoolLogoRight ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';

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
          <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$longTitle} - Coordinator Account Details</div>
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
      This document contains sensitive login credentials. Keep this confidential and do not share it with anyone.
      The temporary password below must be changed on first login. This document is for one-time use only.
    </div>
  </div>

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

  <div class="section-title">Coordinator Information</div>
  <table class="info-table">
    <tr><td>Full Name</td><td>{$fullName}</td></tr>
    <tr><td>Employee ID</td><td>{$employeeId}</td></tr>
    <tr><td>Department</td><td>{$department}</td></tr>
    <tr><td>Mobile</td><td>{$mobile}</td></tr>
  </table>

  <div class="steps-box">
    <div class="steps-title">First Login Instructions</div>
    <div class="step">1. Go to the OJT System login page at <strong>{$PageLink}</strong></div>
    <div class="step">2. Enter your email address and temporary password above.</div>
    <div class="step">3. You will be prompted to set a new password immediately after logging in.</div>
    <div class="step">4. Keep your credentials confidential and secure.</div>
  </div>

  <div class="footer">
    <div class="confidential">CONFIDENTIAL — FOR COORDINATOR USE ONLY</div>
    <div class="footer-text">
      This document was generated by the {$longTitle}
      Generated on {$generatedAt} · Do not reproduce or distribute.
    </div>
    <div class="footer-text generated-info">
      Document created by {$fileCreatedBy} ({$roleOfCreator})
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

        $fileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $fullName) . '_Coordinator_Account_Details.pdf';
        $mpdf->Output($fileName, 'D');
        exit;
    } catch (Exception $e) {
        response([
            'status' => 'error',
            'message' => 'Failed to generate PDF.'
        ]);
    }
}

response([
    'status' => 'error',
    'message' => 'PDF generator is not available on this server.'
]);
