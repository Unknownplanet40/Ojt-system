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
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

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

$activeBatchUuid = $_SESSION['active_batch_uuid'] ?? '';
if (empty($activeBatchUuid)) {
    $res = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $activeBatchUuid = $res->fetch_assoc()['uuid'] ?? null;
}

// 1. Placement Status Distribution
$placementStats = [
    'pending' => 0,
    'active' => 0,
    'completed' => 0,
    'total' => 0
];

$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM ojt_applications 
    WHERE batch_uuid = ? 
    GROUP BY status
");
$stmt->bind_param('s', $activeBatchUuid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (in_array($row['status'], ['pending', 'active', 'completed'])) {
        $placementStats[$row['status']] = (int)$row['count'];
    }
    $placementStats['total'] += (int)$row['count'];
}
$stmt->close();

// 2. Program Distribution
$programStats = [];
$res = $conn->query("
    SELECT p.name, p.code, COUNT(sp.uuid) as count
    FROM programs p
    LEFT JOIN student_profiles sp ON sp.program_uuid = p.uuid
    WHERE sp.batch_uuid = '{$activeBatchUuid}'
    GROUP BY p.uuid
");
while ($row = $res->fetch_assoc()) {
    $programStats[] = [
        'name' => $row['name'],
        'code' => $row['code'],
        'count' => (int)$row['count']
    ];
}

// 3. Monthly Activity (Audit Logs)
$monthlyActivity = [];
$res = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month_label, COUNT(*) as count
    FROM activity_log
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_label
    ORDER BY MIN(created_at) ASC
");
while ($row = $res->fetch_assoc()) {
    $monthlyActivity[] = $row;
}

// 4. Top Companies (By Number of Students) with Real Ratings
$topCompanies = [];
$res = $conn->query("
    SELECT 
        c.name, 
        c.industry,
        COUNT(DISTINCT a.uuid) as student_count,
        COALESCE(AVG(e.total_score), 0) as avg_rating
    FROM companies c
    JOIN ojt_applications a ON a.company_uuid = c.uuid
    LEFT JOIN evaluations e ON e.application_uuid = a.uuid AND e.eval_type IN ('midterm', 'final')
    WHERE a.status = 'active' AND a.batch_uuid = '{$activeBatchUuid}'
    GROUP BY c.uuid
    ORDER BY student_count DESC
    LIMIT 5
");
while ($row = $res->fetch_assoc()) {
    $topCompanies[] = [
        'name' => $row['name'],
        'industry' => $row['industry'] ?? 'General Industry',
        'student_count' => (int)$row['student_count'],
        'rating' => (float)$row['avg_rating']
    ];
}

// 5. Overall Completion Progress
$completionData = [
    'rendered' => 0,
    'required' => 0
];
$res = $conn->query("
    SELECT SUM(hours_rendered) as total_rendered
    FROM dtr_entries
    WHERE status = 'approved' AND batch_uuid = '{$activeBatchUuid}'
");
$completionData['rendered'] = (float)($res->fetch_assoc()['total_rendered'] ?? 0);

$res = $conn->query("
    SELECT SUM(p.required_hours) as total_required
    FROM student_profiles sp
    JOIN programs p ON sp.program_uuid = p.uuid
    WHERE sp.batch_uuid = '{$activeBatchUuid}'
");
$completionData['required'] = (float)($res->fetch_assoc()['total_required'] ?? 0);

response([
    'status' => 'success',
    'data' => [
        'placement' => $placementStats,
        'programs' => $programStats,
        'activity' => $monthlyActivity,
        'companies' => $topCompanies,
        'progress' => $completionData
    ]
]);
