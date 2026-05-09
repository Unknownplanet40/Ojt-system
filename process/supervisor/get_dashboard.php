<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../config/db.php";
require_once "../../functions/supervisor_functions.php";
require_once "../../helpers/helpers.php";

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'supervisor') {
    response(['status' => 'error', 'message' => "Unauthorized access."]);
}

$supervisorUuid = $_SESSION['user_uuid'];

try {
    $stats = getSupervisorDashboardStats($conn, $supervisorUuid);
    
    // Get supervised students list (brief)
    $stmt = $conn->prepare("
        SELECT 
            sp.uuid,
            sp.first_name,
            sp.last_name,
            sp.student_number,
            sp.program,
            oa.status as application_status,
            COALESCE(SUM(de.hours_rendered), 0) as total_hours
        FROM student_profiles sp
        JOIN ojt_applications oa ON sp.uuid = oa.student_uuid
        LEFT JOIN dtr_entries de ON sp.uuid = de.student_uuid AND de.status = 'approved'
        WHERE sp.supervisor_uuid = (SELECT uuid FROM supervisor_profiles WHERE user_uuid = ? LIMIT 1)
        AND oa.status = 'active'
        GROUP BY sp.uuid
        ORDER BY total_hours DESC
        LIMIT 5
    ");
    $stmt->bind_param('s', $supervisorUuid);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
        $students[] = $row;
    }
    $stmt->close();

    response([
        'status' => 'success',
        'message' => "Dashboard data fetched.",
        'data' => [
            'stats' => $stats,
            'recent_students' => $students,
            'user' => [
                'full_name' => $_SESSION['user_name'] ?? 'Supervisor',
                'email' => $_SESSION['user_email'] ?? ''
            ]
        ]
    ]);
} catch (Exception $e) {
    response(['status' => 'error', 'message' => "Error fetching dashboard data: " . $e->getMessage()]);
}

