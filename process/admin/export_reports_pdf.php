<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/libs/composer/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/Assets/SystemInfo.php';
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

use Mpdf\Mpdf;

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed.");
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    die("Invalid request token.");
}

$activeBatchUuid = $_SESSION['active_batch_uuid'] ?? '';
if (empty($activeBatchUuid)) {
    $res = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $activeBatchUuid = $res->fetch_assoc()['uuid'] ?? null;
}


$stmt = $conn->prepare("SELECT * FROM batches WHERE uuid = ?");
$stmt->bind_param('s', $activeBatchUuid);
$stmt->execute();
$batch = $stmt->get_result()->fetch_assoc();
$stmt->close();


$placementStats = ['pending' => 0, 'active' => 0, 'completed' => 0, 'total' => 0];
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM ojt_applications WHERE batch_uuid = ? GROUP BY status");
$stmt->bind_param('s', $activeBatchUuid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (isset($placementStats[$row['status']])) {
        $placementStats[$row['status']] = (int)$row['count'];
    }
    $placementStats['total'] += (int)$row['count'];
}
$stmt->close();


$programStats = [];
$res = $conn->query("
    SELECT p.name, p.code, COUNT(sp.uuid) as count
    FROM programs p
    LEFT JOIN student_profiles sp ON sp.program_uuid = p.uuid
    WHERE sp.batch_uuid = '{$activeBatchUuid}'
    GROUP BY p.uuid
");
while ($row = $res->fetch_assoc()) {
    $programStats[] = $row;
}


$res = $conn->query("SELECT SUM(hours_rendered) as total FROM dtr_entries WHERE status = 'approved' AND batch_uuid = '{$activeBatchUuid}'");
$totalRendered = (float)($res->fetch_assoc()['total'] ?? 0);

$res = $conn->query("SELECT SUM(p.required_hours) as total FROM student_profiles sp JOIN programs p ON sp.program_uuid = p.uuid WHERE sp.batch_uuid = '{$activeBatchUuid}'");
$totalRequired = (float)($res->fetch_assoc()['total'] ?? 0);
$completionPercent = $totalRequired > 0 ? round(($totalRendered / $totalRequired) * 100, 1) : 0;


$companies = [];
$res = $conn->query("
    SELECT 
        c.name, 
        COUNT(DISTINCT a.uuid) as interns,
        COALESCE(AVG(e.total_score), 0) as avg_rating
    FROM companies c
    JOIN ojt_applications a ON a.company_uuid = c.uuid
    LEFT JOIN evaluations e ON e.application_uuid = a.uuid AND e.eval_type IN ('midterm', 'final')
    WHERE a.status = 'active' AND a.batch_uuid = '{$activeBatchUuid}'
    GROUP BY c.uuid
    ORDER BY interns DESC
");
while ($row = $res->fetch_assoc()) {
    $companies[] = $row;
}


$html = '
<style>
    body { font-family: "Helvetica", "Arial", sans-serif; font-size: 10pt; color: #333; }
    .header-table { width: 100%; border-bottom: 2px solid #0F6E56; margin-bottom: 30px; padding-bottom: 10px; }
    .logo-td { width: 80px; text-align: left; }
    .logo-img { width: 70px; height: auto; }
    .school-info-td { text-align: left; padding-left: 15px; }
    .school-name { font-size: 16pt; font-weight: bold; color: #0F6E56; margin-bottom: 2px; }
    .school-motto { font-style: italic; font-size: 8pt; color: #666; }
    .school-address { font-size: 8pt; color: #777; margin-top: 4px; }
    
    .report-meta-table { width: 100%; margin-bottom: 30px; background: #f9f9f9; padding: 15px; border-radius: 8px; }
    .report-title { font-size: 15pt; font-weight: bold; color: #333; text-transform: uppercase; margin-bottom: 5px; }
    .batch-label { font-size: 11pt; color: #0F6E56; font-weight: bold; }
    
    .section-header { background: #0F6E56; color: white; padding: 8px 12px; font-weight: bold; font-size: 11pt; margin-top: 30px; margin-bottom: 10px; border-radius: 4px; }
    
    table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.data-table th { background: #f4f4f4; color: #333; padding: 10px; text-align: left; border-bottom: 2px solid #0F6E56; font-size: 9pt; text-transform: uppercase; }
    table.data-table td { border-bottom: 1px solid #eee; padding: 10px; font-size: 10pt; }
    
    .stat-container { margin-bottom: 30px; }
    .stat-card { float: left; width: 31%; border: 1px solid #e0e0e0; padding: 15px; text-align: center; background: #fff; border-radius: 8px; margin-right: 2%; }
    .stat-card.last { margin-right: 0; }
    .stat-value { font-size: 22pt; font-weight: bold; color: #0F6E56; margin-bottom: 5px; }
    .stat-desc { font-size: 8pt; text-transform: uppercase; color: #888; font-weight: bold; letter-spacing: 0.5px; }
    
    .footer { text-align: center; font-size: 8pt; color: #999; margin-top: 60px; border-top: 1px solid #eee; padding-top: 15px; }
    .timestamp { color: #555; font-weight: bold; }
    
    .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 8pt; font-weight: bold; }
    .bg-active { background: #e8f5e9; color: #2e7d32; }
    .bg-pending { background: #fff3e0; color: #ef6c00; }
    .bg-completed { background: #e3f2fd; color: #1565c0; }
</style>

<table class="header-table">
    <tr>
        <td class="logo-td">
            <img src="' . $SchoolLogoLeft . '" class="logo-img">
        </td>
        <td class="school-info-td">
            <div class="school-name">' . $SchoolName . '</div>
            <div class="school-motto">' . $SchoolMotto . '</div>
            <div class="school-address">' . $SchoolAddress . '</div>
        </td>
        <td style="text-align: right; vertical-align: bottom; font-size: 8pt; color: #666;">
            Report ID: OJT-' . date('Ymd') . '-' . substr($activeBatchUuid, 0, 4) . '
        </td>
    </tr>
</table>

<div class="report-meta-table">
    <div class="report-title">OJT Internship Analytics Report</div>
    <div class="batch-label">Academic Period: ' . $batch['school_year'] . ' | ' . $batch['semester'] . ' Semester</div>
    <div style="font-size: 9pt; color: #666; margin-top: 5px;">This report provides an executive summary of student placements, program distributions, and industry partnership metrics for the specified academic period.</div>
</div>

<div class="stat-container">
    <div class="stat-card">
        <div class="stat-value">' . $placementStats['total'] . '</div>
        <div class="stat-desc">Total Interns</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">' . $completionPercent . '%</div>
        <div class="stat-desc">Completion Progress</div>
    </div>
    <div class="stat-card last">
        <div class="stat-value">' . count($companies) . '</div>
        <div class="stat-desc">Active Partners</div>
    </div>
    <div style="clear: both;"></div>
</div>

<div class="section-header">Student Placement Distribution</div>
<table class="data-table">
    <thead>
        <tr>
            <th>Internship Status</th>
            <th class="text-center">Count</th>
            <th class="text-center">Percentage</th>
            <th class="text-right">Remarks</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Active Placements</strong></td>
            <td class="text-center">' . $placementStats['active'] . '</td>
            <td class="text-center">' . ($placementStats['total'] > 0 ? round(($placementStats['active'] / $placementStats['total']) * 100, 1) : 0) . '%</td>
            <td class="text-right"><span class="status-badge bg-active">Ongoing</span></td>
        </tr>
        <tr>
            <td><strong>Pending Deployment</strong></td>
            <td class="text-center">' . $placementStats['pending'] . '</td>
            <td class="text-center">' . ($placementStats['total'] > 0 ? round(($placementStats['pending'] / $placementStats['total']) * 100, 1) : 0) . '%</td>
            <td class="text-right"><span class="status-badge bg-pending">In-Process</span></td>
        </tr>
        <tr>
            <td><strong>Successfully Completed</strong></td>
            <td class="text-center">' . $placementStats['completed'] . '</td>
            <td class="text-center">' . ($placementStats['total'] > 0 ? round(($placementStats['completed'] / $placementStats['total']) * 100, 1) : 0) . '%</td>
            <td class="text-right"><span class="status-badge bg-completed">Finished</span></td>
        </tr>
    </tbody>
</table>

<div class="section-header">Program-wise Enrollment Breakdown</div>
<table class="data-table">
    <thead>
        <tr>
            <th>Academic Program</th>
            <th class="text-center">Code</th>
            <th class="text-center">Student Population</th>
            <th class="text-right">Required Hours</th>
        </tr>
    </thead>
    <tbody>';

foreach ($programStats as $ps) {
    $html .= '
        <tr>
            <td>' . $ps['name'] . '</td>
            <td class="text-center">' . $ps['code'] . '</td>
            <td class="text-center">' . $ps['count'] . '</td>
            <td class="text-right">' . $batch['required_hours'] . ' hrs</td>
        </tr>';
}

$html .= '
    </tbody>
</table>

<div class="section-header">Executive Summary: Top Industry Partners</div>
<table class="data-table">
    <thead>
        <tr>
            <th>Partner Company Name</th>
            <th class="text-center">Current Interns</th>
            <th class="text-right">Performance Rating</th>
        </tr>
    </thead>
    <tbody>';

$count = 0;
foreach ($companies as $c) {
    if ($count++ >= 8) break;
    $rating = (float)$c['avg_rating'];
    $ratingStr = $rating > 0 ? number_format($rating, 1) . ' / 5.0' : 'N/A';
    
    $html .= '
        <tr>
            <td><strong>' . $c['name'] . '</strong></td>
            <td class="text-center">' . $c['interns'] . '</td>
            <td class="text-right" style="color: #BA7517; font-weight: bold;">' . $ratingStr . '</td>
        </tr>';
}

if (empty($companies)) {
    $html .= '<tr><td colspan="3" class="text-center">No active partner data available for this batch.</td></tr>';
}

$html .= '
    </tbody>
</table>

<div class="footer">
    ' . $DocumentFooterNote . '<br>
    <span class="timestamp">Generated by OJT Management System on ' . date('F j, Y g:i A') . '</span><br>
    Requested by: Admin ' . $_SESSION['user_name'] . ' (ID: ' . ($_SESSION['user_uuid'] ? substr($_SESSION['user_uuid'], 0, 8) : 'N/A') . ')<br>
    <div style="margin-top: 10px; font-size: 7pt;">This is a certified official report of the ' . $SchoolName . '.</div>
</div>
';


$mpdf = new Mpdf([
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'format' => 'A4'
]);

$mpdf->SetTitle('OJT Analytics Report - ' . $batch['school_year']);
$mpdf->WriteHTML($html);


$filename = 'OJT_Analytics_Report_' . date('Ymd_His') . '.pdf';
$mpdf->Output($filename, 'D');
