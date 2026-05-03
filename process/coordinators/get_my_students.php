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
require_once dirname(__DIR__, 2) . '/functions/program_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'coordinator') {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

$coordinatorUuid = $_SESSION['profile_uuid'];

// Filters
$filters = [
    'coordinator_uuid' => $coordinatorUuid,
    'program_uuid'     => trim($_POST['program_uuid'] ?? ''),
    'status'           => trim($_POST['status'] ?? ''),
    'search'           => trim($_POST['search'] ?? ''),
];

$students = getAllStudents($conn, null, $filters);
$programs = getAllPrograms($conn, true); // only active

// Stats
$stats = [
    'total'     => 0,
    'active'    => 0,
    'pending'   => 0,
    'completed' => 0,
];

$allMyStudents = getAllStudents($conn, null, ['coordinator_uuid' => $coordinatorUuid]);
$stats['total'] = count($allMyStudents);

foreach ($allMyStudents as $s) {
    if ($s['account_status'] === 'active') {
        $stats['active']++;
    }
    // For pending/completed, we might need to check ojt_applications or student_profiles.isProfileDone
    // Let's keep it simple for now or fetch from applications if needed
}

$stmt = $conn->prepare("
    SELECT COUNT(a.id) as pending_count 
    FROM ojt_applications a
    JOIN student_profiles sp ON a.student_uuid = sp.uuid
    WHERE sp.coordinator_uuid = ? AND a.status = 'pending'
");
$stmt->bind_param('s', $coordinatorUuid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stats['pending'] = (int) ($res['pending_count'] ?? 0);
$stmt->close();

response([
    'status'   => 'success',
    'students' => $students,
    'programs' => $programs,
    'stats'    => $stats,
]);
