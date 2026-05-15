<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
require_once dirname(__DIR__, 2) . '/functions/dtr_functions.php';
require_once dirname(__DIR__, 2) . '/functions/requirement_functions.php';
require_once dirname(__DIR__, 2) . '/functions/journal_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'student') {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthorized access.']);
}

$studentUuid = $_SESSION['profile_uuid'];
$batchUuid   = $_SESSION['active_batch_uuid'] ?? '';

if (empty($batchUuid)) {
    $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $batchUuid = $result->fetch_assoc()['uuid'] ?? null;
}

if (empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'No active batch found.']);
}


$dtrSummary = getDtrSummary($conn, $studentUuid, $batchUuid);
$recentDtr  = array_slice(getStudentDtrEntries($conn, $studentUuid, $batchUuid), 0, 10);


$journals = getStudentJournals($conn, $studentUuid, $batchUuid);
$totalJournals = count($journals);
$approvedJournals = count(array_filter($journals, fn($j) => $j['status'] === 'approved'));


$requirements = getStudentRequirements($conn, $studentUuid, $batchUuid);
$totalReqs = count($requirements);
$approvedReqs = count(array_filter($requirements, fn($r) => $r['status'] === 'approved'));


$stmt = $conn->prepare("
    SELECT 
        c.name AS company_name,
        c.address AS company_address,
        c.work_setup,
        a.preferred_department AS department,
        a.status AS app_status,
        osc.start_date,
        osc.working_hours_per_day,
        CONCAT(svp.first_name, ' ', svp.last_name) AS supervisor_name,
        CONCAT(cp.first_name, ' ', cp.last_name) AS coordinator_name,
        p.name AS program_name,
        p.required_hours
    FROM student_profiles sp
    LEFT JOIN ojt_applications a ON a.student_uuid = sp.uuid AND a.batch_uuid = ? AND a.status = 'active'
    LEFT JOIN companies c ON a.company_uuid = c.uuid
    LEFT JOIN ojt_start_confirmations osc ON osc.application_uuid = a.uuid
    LEFT JOIN supervisor_profiles svp ON sp.supervisor_uuid = svp.uuid
    LEFT JOIN coordinator_profiles cp ON sp.coordinator_uuid = cp.uuid
    LEFT JOIN programs p ON sp.program_uuid = p.uuid
    WHERE sp.uuid = ?
    LIMIT 1
");
$stmt->bind_param('ss', $batchUuid, $studentUuid);
$stmt->execute();
$ojtDetails = $stmt->get_result()->fetch_assoc();
$stmt->close();


$stmt = $conn->prepare("SELECT semester, school_year FROM batches WHERE uuid = ?");
$stmt->bind_param('s', $batchUuid);
$stmt->execute();
$batchInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

response([
    'status' => 'success',
    'stats' => [
        'dtr' => $dtrSummary,
        'journals' => [
            'total' => $totalJournals,
            'approved' => $approvedJournals,
            'percent' => $totalJournals > 0 ? round(($approvedJournals / 24) * 100, 1) : 0 
        ],
        'requirements' => [
            'total' => $totalReqs,
            'approved' => $approvedReqs,
            'percent' => $totalReqs > 0 ? round(($approvedReqs / $totalReqs) * 100, 1) : 0
        ]
    ],
    'recent_dtr' => $recentDtr,
    'ojt_details' => $ojtDetails,
    'batch_info' => $batchInfo,
    'requirements_list' => array_slice($requirements, 0, 5) 
]);
