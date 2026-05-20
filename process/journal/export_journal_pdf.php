<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/journal_functions.php';
require_once dirname(__DIR__, 2) . '/functions/student_functions.php';
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

if (!isset($_SESSION['user_uuid'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    response(['status' => 'critical', 'message' => 'Database connection failed.']);
}

$journalUuid = $_POST['journal_uuid'] ?? '';

if (empty($journalUuid)) {
    http_response_code(422);
    response(['status' => 'error', 'message' => 'Missing journal UUID.']);
}

$journal = getJournal($conn, $journalUuid);

if (!$journal) {
    http_response_code(404);
    response(['status' => 'error', 'message' => 'Journal not found.']);
}

$studentProfileUuid = $journal['student_uuid'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';
$userProfileUuid = $_SESSION['profile_uuid'] ?? '';
$authorized = false;

if ($userRole === 'student' && $studentProfileUuid === $userProfileUuid) {
    $authorized = true;
}

if (!$authorized) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Only students can export their journals.']);
}

$student = getStudent($conn, $journal['student_uuid']);

$studentName    = htmlspecialchars($student['full_name'] ?? 'Unknown Student');
$studentNumber  = htmlspecialchars($student['student_number'] ?? '—');
$programName    = htmlspecialchars($student['program_name'] ?? '—');
$yearLevel      = htmlspecialchars($student['year_level'] ?? '—');

$weekStart       = htmlspecialchars($journal['week_start'] ?? '—');
$weekEnd         = htmlspecialchars($journal['week_end'] ?? '—');
$accomplishments = nl2br(htmlspecialchars($journal['accomplishments'] ?? '—'));
$skillsLearned   = nl2br(htmlspecialchars($journal['skills_learned'] ?? '—'));
$challenges      = nl2br(htmlspecialchars($journal['challenges'] ?? '—'));
$plansNextWeek   = nl2br(htmlspecialchars($journal['plans_next_week'] ?? '—'));

$status      = strtoupper($journal['status'] ?? 'PENDING');
$statusColor = match ($status) {
    'APPROVED' => '#10B981',
    'RETURNED' => '#EF4444',
    'PENDING'  => '#F59E0B',
    default    => '#6B7280'
};

$submittedAt = !empty($journal['submitted_at']) ? $journal['submitted_at'] : '—';
$reviewedAt  = (!empty($journal['reviewed_at']) && !empty($journal['reviewed_by'])) ? $journal['reviewed_at'] : '—';

$coordinatorRemarks = !empty($journal['coordinator_remarks'])
    ? nl2br(htmlspecialchars($journal['coordinator_remarks']))
    : '—';
$returnReason = !empty($journal['return_reason'])
    ? nl2br(htmlspecialchars($journal['return_reason']))
    : '—';

$generatedAt           = date('F j, Y g:i A');
$schoolName            = $SchoolName ?? 'Your School Name Here';
$longTitle             = $LongTitle ?? 'Your System Long Title Here';
$schoolMotto           = $SchoolMotto ?? '';
$schoolAddress         = $SchoolAddress ?? '';
$schoolWebsite         = $SchoolWebsite ?? '';
$schoolEmail           = $SchoolEmail ?? '';
$schoolPhone           = $SchoolPhone ?? '';
$documentFooterNote    = $DocumentFooterNote ?? 'Officially issued by the OJT Coordinator Management System';
$documentVerificationNote = $DocumentVerificationNote ?? "Please verify document authenticity with the coordinator's office.";
$fileCreatedBy         = $_SESSION['user_name'] ?? 'User';
$roleofCreator         = ucfirst($userRole);
$LogoPath1             = $SchoolLogoLeft  ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';
$LogoPath2             = $SchoolLogoRight ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';

$reviewSection = '';
if ($status === 'APPROVED' || $status === 'RETURNED') {
    $reviewSection = "
    <div style=\"background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 6px; padding: 14px 16px; margin-top: 16px;\">
        <div style=\"font-size: 11px; font-weight: bold; color: #0369A1; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.06em;\">Coordinator Review</div>
        <table style=\"width: 100%; border-collapse: collapse; font-size: 10px;\">
            <tr><td style=\"padding: 6px 8px; border-bottom: 1px solid #E5E7EB; color: #64748b; font-weight: bold; width: 140px;\">Review Status</td><td style=\"padding: 6px 8px; border-bottom: 1px solid #E5E7EB; color: #1e293b;\"><span style=\"background: $statusColor; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;\">$status</span></td></tr>
            <tr><td style=\"padding: 6px 8px; border-bottom: 1px solid #E5E7EB; color: #64748b; font-weight: bold;\">Reviewed On</td><td style=\"padding: 6px 8px; border-bottom: 1px solid #E5E7EB; color: #1e293b;\">$reviewedAt</td></tr>";

    if ($status === 'RETURNED') {
        $reviewSection .= "<tr><td style=\"padding: 6px 8px; border-bottom: 1px solid #E5E7EB; color: #64748b; font-weight: bold;\">Return Reason</td><td style=\"padding: 6px 8px; border-bottom: 1px solid #E5E7EB; color: #1e293b; font-style: italic;\">$returnReason</td></tr>";
    }

    if ($coordinatorRemarks !== '—') {
        $reviewSection .= "<tr><td style=\"padding: 6px 8px; border-bottom: none; color: #64748b; font-weight: bold;\">Remarks</td><td style=\"padding: 6px 8px; border-bottom: none; color: #1e293b;\">$coordinatorRemarks</td></tr>";
    }

    $reviewSection .= "</table></div>";
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; }
    .page { padding: 40px; }

    .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; }
    .header-table td { vertical-align: middle; }
    .header-left { width: 20%; text-align: left; }
    .header-center { width: 60%; text-align: center; }
    .header-right { width: 20%; text-align: right; }
    .header-logo { width: 64px; height: 64px; object-fit: contain; }

    .student-info { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 16px; margin-bottom: 16px; }
    .info-label { font-size: 10px; color: #64748b; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
    .info-key { font-size: 10px; color: #64748b; font-weight: bold; min-width: 130px; }
    .info-val { font-size: 11px; color: #1e293b; flex: 1; }

    .week-info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; }

    .section-title { font-size: 11px; font-weight: bold; color: #374151; margin-top: 14px; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; }
    .section-content { font-size: 10px; color: #4b5563; line-height: 1.6; padding: 8px 0; }

    .footer { border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 24px; }
    .footer-text { font-size: 9px; color: #64748b; margin-bottom: 4px; }
    .footer-contact { margin-top: 4px; font-size: 8px; color: #94a3b8; }
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
        <div style="font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em;">{$schoolName}</div>
        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$schoolMotto}</div>
        <div style="font-size: 11px; color: #475569; margin-top: 3px;">Weekly Journal Record</div>
        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$longTitle}</div>
        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Generated on {$generatedAt}</div>
      </td>
      <td class="header-right">
        <img src="{$LogoPath2}" alt="Logo Right" class="header-logo" />
      </td>
    </tr>
  </table>

  <div class="student-info">
    <div class="info-label">Student Information</div>
    <div class="info-row">
      <div class="info-key">Name:</div>
      <div class="info-val">{$studentName}</div>
    </div>
    <div class="info-row">
      <div class="info-key">Student Number:</div>
      <div class="info-val">{$studentNumber}</div>
    </div>
    <div class="info-row">
      <div class="info-key">Program:</div>
      <div class="info-val">{$programName} &bull; Year {$yearLevel}</div>
    </div>
    <div class="info-row">
      <div class="info-key">Submitted:</div>
      <div class="info-val">{$submittedAt}</div>
    </div>
  </div>

  <div class="week-info">
    <div style="font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 4px;">Week of {$weekStart} to {$weekEnd}</div>
    <div style="font-size: 10px; color: #6B7280;">Current Status: <span style="background: {$statusColor}; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 9px; display: inline-block; margin-left: 4px;">{$status}</span></div>
  </div>

  <div class="section-title">Accomplishments &amp; Tasks Completed</div>
  <div class="section-content">{$accomplishments}</div>

  <div class="section-title">Skills &amp; Competencies Learned</div>
  <div class="section-content">{$skillsLearned}</div>

  <div class="section-title">Challenges &amp; Issues Encountered</div>
  <div class="section-content">{$challenges}</div>

  <div class="section-title">Plans for Next Week</div>
  <div class="section-content">{$plansNextWeek}</div>

  {$reviewSection}

  <div class="footer">
    <div class="footer-text">
      This document was generated by the {$longTitle}. For document authenticity, please verify with your coordinator's office.
    </div>
    <div class="footer-text" style="font-size: 8px; color: #94a3b8;">
      Generated by {$fileCreatedBy} ({$roleofCreator}) &middot; {$generatedAt}
    </div>
    <div class="footer-contact">
      {$documentFooterNote}<br>{$documentVerificationNote}<br>
      {$schoolName} &middot; {$schoolAddress} &middot; {$schoolWebsite} &middot; {$schoolEmail} &middot; {$schoolPhone}
    </div>
  </div>

</div>
</body>
</html>
HTML;

$mpdfPath = dirname(__DIR__, 2) . '/libs/composer/vendor/autoload.php';

if (file_exists($mpdfPath)) {
    try {
        require_once $mpdfPath;

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 0,
            'margin_bottom' => 0,
            'margin_left'   => 0,
            'margin_right'  => 0,
        ]);

        $mpdf->WriteHTML($html);

        $fileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $studentName) . '_Journal_' . date('Y-m-d') . '.pdf';
        $mpdf->Output($fileName, 'D');
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        response(['status' => 'error', 'message' => 'PDF generation failed: ' . $e->getMessage()]);
    }
}

http_response_code(500);
response(['status' => 'error', 'message' => 'PDF library not available. Please ensure MPDF is installed.']);
