<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        http_response_code(403);
        header("Location: $base/Src/Pages/ErrorPage?error=403");
        exit;
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/student_functions.php';
require_once dirname(__DIR__, 2) . '/functions/requirement_functions.php';
require_once dirname(__DIR__, 2) . '/functions/dtr_functions.php';
require_once dirname(__DIR__, 2) . '/functions/journal_functions.php';
require_once dirname(__DIR__, 2) . '/functions/evaluation_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'coordinator') {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

$studentUuid = trim($_POST['student_uuid'] ?? '');

if (empty($studentUuid)) {
    response(['status' => 'error', 'message' => 'Student UUID is required.']);
}

$student = getStudent($conn, $studentUuid);
if (!$student) {
    response(['status' => 'error', 'message' => 'Student not found.']);
}

if ($student['coordinator_uuid'] !== $_SESSION['profile_uuid']) {
    response(['status' => 'error', 'message' => 'Unauthorized access to student data.']);
}

$requirements = getStudentRequirements($conn, $studentUuid, $student['batch_uuid']);
$recentDtr    = getStudentDtrEntries($conn, $studentUuid, $student['batch_uuid']); // last 10 entries
$journals     = getStudentJournals($conn, $studentUuid, $student['batch_uuid']);

$totalHours = 0;
foreach ($recentDtr as $entry) {
    if ($entry['status'] === 'approved') {
        // This is only for the last 10. We need total hours.
        $totalHours += (float) $entry['hours_rendered'];
    }
}

$stmt = $conn->prepare("SELECT SUM(hours_rendered) as total FROM dtr_entries WHERE student_uuid = ? AND status = 'approved'");
$stmt->bind_param('s', $studentUuid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$totalHours = (float) ($res['total'] ?? 0);
$stmt->close();

$reqsCompleted = 0;
foreach ($requirements as $r) {
    if ($r['status'] === 'approved') $reqsCompleted++;
}

response([
    'status'       => 'success',
    'student'      => $student,
    'requirements' => $requirements,
    'recentDtr'    => $recentDtr,
    'journals'     => $journals,
    'stats'        => [
        'totalHours'    => $totalHours,
        'reqsCompleted' => $reqsCompleted,
        'totalReqs'     => count($requirements),
        'totalJournals' => count($journals),
    ]
]);
