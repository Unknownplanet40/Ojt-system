<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/journal_functions.php';
require_once dirname(__DIR__, 2) . '/functions/student_functions.php';
require_once dirname(__DIR__, 2) . '/Assets/SystemInfo.php';

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

// Get journal data
$journal = getJournal($conn, $journalUuid);

if (!$journal) {
    http_response_code(404);
    response(['status' => 'error', 'message' => 'Journal not found.']);
}

// Authorization check - ONLY STUDENTS CAN EXPORT
$studentProfileUuid = $journal['student_uuid'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';
$userProfileUuid = $_SESSION['profile_uuid'] ?? '';
$authorized = false;

// Only students who own the journal can export
if ($userRole === 'student' && $studentProfileUuid === $userProfileUuid) {
    $authorized = true;
}

if (!$authorized) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Only students can export their journals.']);
}

// Get student info
$student = getStudent($conn, $journal['student_uuid']);

// Format journal data
$studentName = htmlspecialchars($student['full_name'] ?? 'Unknown Student');
$studentNumber = htmlspecialchars($student['student_number'] ?? '—');
$programName = htmlspecialchars($student['program_name'] ?? '—');
$yearLevel = htmlspecialchars($student['year_level'] ?? '—');

$weekStart = htmlspecialchars($journal['week_start'] ?? '—');
$weekEnd = htmlspecialchars($journal['week_end'] ?? '—');
$accomplishments = nl2br(htmlspecialchars($journal['accomplishments'] ?? '—'));
$skillsLearned = nl2br(htmlspecialchars($journal['skills_learned'] ?? '—'));
$challenges = nl2br(htmlspecialchars($journal['challenges'] ?? '—'));
$plansNextWeek = nl2br(htmlspecialchars($journal['plans_next_week'] ?? '—'));

$status = strtoupper($journal['status'] ?? 'PENDING');
$statusColor = match ($status) {
    'APPROVED' => '#10B981',
    'RETURNED' => '#EF4444',
    'PENDING' => '#F59E0B',
    default => '#6B7280'
};

$submittedAt = !empty($journal['submitted_at'])
    ? $journal['submitted_at']
    : '—';
$reviewedAt = !empty($journal['reviewed_at']) && !empty($journal['reviewed_by'])
    ? $journal['reviewed_at']
    : '—';

$coordinatorRemarks = !empty($journal['coordinator_remarks'])
    ? nl2br(htmlspecialchars($journal['coordinator_remarks']))
    : '—';
$returnReason = !empty($journal['return_reason'])
    ? nl2br(htmlspecialchars($journal['return_reason']))
    : '—';

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
$fileCreatedBy = $_SESSION['user_name'] ?? 'User';
$roleofCreator = ucfirst($userRole);
$LogoPath1 = $SchoolLogoLeft ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';
$LogoPath2 = $SchoolLogoRight ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';

// Determine which fields to display based on status
$reviewSection = '';
if ($status === 'APPROVED' || $status === 'RETURNED') {
    $reviewSection = "
    <div class=\"review-section\" style=\"background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 6px; padding: 14px 16px; margin-top: 16px;\">
        <div style=\"font-size: 11px; font-weight: bold; color: #0369A1; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.06em;\">Coordinator Review</div>
        <table class=\"info-table\">
            <tr><td>Review Status</td><td><span style=\"background: $statusColor; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;\">$status</span></td></tr>
            <tr><td>Reviewed On</td><td>$reviewedAt</td></tr>";

    if ($status === 'RETURNED') {
        $reviewSection .= "<tr><td>Return Reason</td><td style=\"font-style: italic;\">$returnReason</td></tr>";
    }

    if ($coordinatorRemarks !== '—') {
        $reviewSection .= "<tr><td>Remarks</td><td>$coordinatorRemarks</td></tr>";
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
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
    .page { padding: 40px; }

    .header { text-align: center; border-bottom: 2px solid #0F6E56; padding-bottom: 16px; margin-bottom: 24px; }
    .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 14px; margin-bottom: 22px; }
    .header-table td { vertical-align: middle; }
    .header-left { width: 20%; text-align: left; }
    .header-center { width: 60%; text-align: center; }
    .header-right { width: 20%; text-align: right; }
    .header-logo { width: 64px; height: 64px; object-fit: contain; }
    .doc-title { font-size: 18px; font-weight: bold; color: #111; margin-bottom: 4px; }
    .doc-subtitle { font-size: 10px; color: #666; margin-bottom: 3px; }

    .student-info { background: #E1F5EE; border: 1.5px solid #1D9E75; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px; }
    .info-label { font-size: 10px; color: #0F6E56; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
    .info-key { font-size: 10px; color: #065F46; font-weight: 500; }
    .info-val { font-size: 11px; color: #0F6E56; font-weight: 600; }

    .week-info { background: #F3F4F6; border: 1px solid #D1D5DB; border-radius: 6px; padding: 12px 14px; margin-bottom: 16px; }
    .section-title { font-size: 11px; font-weight: bold; color: #374151; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #E5E7EB; margin-top: 16px; }
    .section-content { font-size: 10px; color: #374151; line-height: 1.6; margin-bottom: 12px; padding: 10px; background: #FAFAFA; border-left: 3px solid #0F6E56; border-radius: 2px; }

    .review-section { background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 6px; padding: 14px 16px; margin-top: 16px; }
    .review-title { font-size: 11px; font-weight: bold; color: #0369A1; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.06em; }
    .info-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .info-table td { padding: 6px 8px; border-bottom: 1px solid #E5E7EB; }
    .info-table td:first-child { color: #6B7280; width: 30%; font-weight: 500; }
    .info-table td:last-child { color: #111827; font-weight: 500; }
    .info-table tr:last-child td { border-bottom: none; }

    .footer { border-top: 1px solid #E5E7EB; padding-top: 10px; text-align: center; margin-top: 20px; }
    .footer-text { font-size: 9px; color: #616264; line-height: 1.5; }
    .generated-info { font-size: 8px; color: #3e3f41; margin-top: 3px; text-align: right; }
    .footer-contact { margin-top: 4px; font-size: 8px; color: #64748b; line-height: 1.4; }
  </style>
</head>
<body>
<div class="page">

  <!-- Header -->
  <div class="header">
    <table class="header-table" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td class="header-left">
          <img src="{$LogoPath1}" alt="Logo Left" class="header-logo" />
        </td>
        <td class="header-center" style="line-height:1.35;">
          <div style="font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em;">{$schoolName}</div>
          <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{$schoolMotto}</div>
          <div style="font-size: 11px; color: #475569; margin-top: 3px;\">Weekly Journal Record</div>
          <div style="font-size: 10px; color: #64748b; margin-top: 2px;\">{$longTitle}</div>
          <div style=\"font-size: 10px; color: #64748b; margin-top: 2px;\">Generated on {$generatedAt}</div>
        </td>
        <td class="header-right">
          <img src="{$LogoPath2}" alt="Logo Right" class="header-logo" />
        </td>
      </tr>
    </table>
  </div>

  <!-- Student Information -->
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
      <div class="info-val">{$programName} ● Year {$yearLevel}</div>
    </div>
    <div class="info-row">
      <div class="info-key">Submitted:</div>
      <div class="info-val">{$submittedAt}</div>
    </div>
  </div>

  <!-- Week Information -->
  <div class="week-info" style="display: none;">
    <div style=\"font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 4px;\">Week of {$weekStart} to {$weekEnd}</div>
    <div style=\"font-size: 10px; color: #6B7280;\">Current Status: <span style=\"background: $statusColor; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 9px; display: inline-block; margin-left: 4px;\">{$status}</span></div>
  </div>

  <!-- Journal Content -->
  <div class=\"section-title\">Accomplishments & Tasks Completed</div>
  <div class=\"section-content\">{$accomplishments}</div>

  <div class=\"section-title\">Skills & Competencies Learned</div>
  <div class=\"section-content\">{$skillsLearned}</div>

  <div class=\"section-title\">Challenges & Issues Encountered</div>
  <div class=\"section-content\">{$challenges}</div>

  <div class=\"section-title\">Plans for Next Week</div>
  <div class=\"section-content\">{$plansNextWeek}</div>

  <!-- Review Section (if applicable) -->
  {$reviewSection}

  <!-- Footer -->
  <div class="footer">
    <div class="footer-text">
      This document was generated by the {$longTitle}. For document authenticity, please verify with your coordinator's office.
    </div>
    <div class="footer-text generated-info">
      Generated by {$fileCreatedBy} ({$roleofCreator}) · {$generatedAt}
    </div>
    <div class="footer-contact">
      {$documentFooterNote}<br>{$documentVerificationNote}<br>
      {$schoolName} · {$schoolAddress} · {$schoolWebsite} · {$schoolEmail} · {$schoolPhone}
    </div>
  </div>

</div>
</body>
</html>
HTML;

// Try using MPDF library
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
        // Fall through to error response
        http_response_code(500);
        response(['status' => 'error', 'message' => 'PDF generation failed: ' . $e->getMessage()]);
    }
}

// MPDF not available
http_response_code(500);
response(['status' => 'error', 'message' => 'PDF library not available. Please ensure MPDF is installed.']);
