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
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/Assets/SystemInfo.php';
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

$isAuthorized = false;
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        $isAuthorized = true;
    } elseif ($_SESSION['user_role'] === 'supervisor') {
        $stmt = $conn->prepare("SELECT is_hr_admin FROM supervisor_profiles WHERE user_uuid = ? LIMIT 1");
        $stmt->bind_param("s", $_SESSION['user_uuid']);
        $stmt->execute();
        $profRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($profRow && (int)$profRow['is_hr_admin'] === 1) {
            $isAuthorized = true;
        }
    }
}

if (!$isAuthorized) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'     => 'critical',
        'message'    => 'Database connection failed.',
        'details'    => $conn->connect_error ?? 'Unknown error',
        'suggestion' => 'Please try again later or contact support if the issue persists.'
    ]);
}

$supervisorData = $_POST['supervisor_data'] ?? [];
if (is_string($supervisorData)) {
    $supervisorData = json_decode($supervisorData, true) ?? [];
}

$fullName     = htmlspecialchars($supervisorData['full_name']    ?? '—');
$tempPassword = htmlspecialchars($supervisorData['temp_password'] ?? '—');
$email        = htmlspecialchars($supervisorData['email']         ?? '—');
$companyName  = htmlspecialchars($supervisorData['company_name']  ?? '—');
$position     = htmlspecialchars($supervisorData['position']      ?? '—');
$department   = htmlspecialchars($supervisorData['department']    ?? '—');
$mobile       = htmlspecialchars($supervisorData['mobile']        ?? '—');

$generatedAt              = date('F j, Y g:i A');
$schoolName               = $SchoolName  ?? 'Your School Name Here';
$longTitle                = $LongTitle   ?? 'Your System Long Title Here';
$schoolMotto              = $SchoolMotto ?? '';
$schoolAddress            = $SchoolAddress ?? '';
$schoolWebsite            = $SchoolWebsite ?? '';
$schoolEmail              = $SchoolEmail  ?? '';
$schoolPhone              = $SchoolPhone  ?? '';
$documentFooterNote       = $DocumentFooterNote ?? 'Officially issued by the OJT Coordinator Management System';
$documentVerificationNote = $DocumentVerificationNote ?? "Please verify document authenticity with the coordinator's office.";
$fileCreatedBy            = $_SESSION['user_name'] ?? 'Admin User';
$roleOfCreator            = $_SESSION['user_role'] === 'admin' ? 'Administrator' : 'HR Administrator';
$LogoPath1                = $SchoolLogoLeft  ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';
$LogoPath2                = $SchoolLogoRight ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';

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
  <table class="header-table" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td class="header-left">
        <img src="{$LogoPath1}" alt="Logo Left" class="header-logo" />
      </td>
      <td class="header-center" style="line-height:1.35;">
        <div style="font-size: 15px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em;">{$schoolName}</div>
        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$schoolMotto}</div>
        <div style="font-size: 11px; color: #475569; margin-top: 3px;">Official Digital Credential Document</div>
        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$longTitle} - Supervisor Account Details</div>
        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Generated on {$generatedAt}</div>
      </td>
      <td class="header-right">
        <img src="{$LogoPath2}" alt="Logo Right" class="header-logo" />
      </td>
    </tr>
  </table>
HTML;

if ($tempPassword !== '—' && !empty($tempPassword)) {
    $html .= <<<HTML
  <div class="notice-box">
    <div class="notice-title">&#9888; Important Notice</div>
    <div class="notice-text">
      This document contains sensitive login credentials. Keep this confidential and do not share it with anyone.
      The temporary password below must be changed on first login. This document is for one-time use only.
    </div>
  </div>

  <div class="credentials-box">
    <div class="credentials-label">Login Credentials</div>
    <div class="cred-row"><div class="cred-key">Login Email</div><div class="cred-val">{$email}</div></div>
    <div class="cred-row"><div class="cred-key">Temporary Password</div></div>
    <div class="pw-val">{$tempPassword}</div>
  </div>
HTML;
}

$html .= <<<HTML

  <div class="section-title">Supervisor Information</div>
  <table class="info-table">
    <tr><td>Full Name</td><td>{$fullName}</td></tr>
    <tr><td>Company</td><td>{$companyName}</td></tr>
    <tr><td>Position</td><td>{$position}</td></tr>
    <tr><td>Department</td><td>{$department}</td></tr>
    <tr><td>Mobile</td><td>{$mobile}</td></tr>
  </table>

  <div class="steps-box">
    <div class="steps-title">First Login Instructions</div>
    <div class="step">1. Go to the OJT System login page at <strong>{$PageLink}</strong></div>
    <div class="step">2. Enter your email address and the temporary password above.</div>
    <div class="step">3. You will be prompted to set a new password immediately after logging in.</div>
    <div class="step">4. Keep your credentials confidential and secure.</div>
  </div>

  <div class="footer">
    <div class="confidential">CONFIDENTIAL &mdash; FOR SUPERVISOR USE ONLY</div>
    <div class="footer-text">This document was generated by the {$longTitle} &middot; Generated on {$generatedAt} &middot; Do not reproduce or distribute.</div>
    <div class="footer-text generated-info">Document created by {$fileCreatedBy} ({$roleOfCreator})</div>
    <div class="footer-contact">{$documentFooterNote}<br>{$documentVerificationNote}<br>{$schoolName} &middot; {$schoolAddress} &middot; {$schoolWebsite} &middot; {$schoolEmail} &middot; {$schoolPhone}</div>
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

        $fileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $fullName) . '_Supervisor_Account_Details.pdf';
        $mpdf->Output($fileName, 'D');
        exit;
    } catch (Exception $e) {
        response([
            'status'  => 'error',
            'message' => 'Failed to generate PDF.'
        ]);
    }
}

response([
    'status'  => 'error',
    'message' => 'PDF generator is not available on this server.'
]);