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
    } else {
        error_log(
            "Unauthorized direct access attempt to " .
            basename(__FILE__) . " from " .
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
        );
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/evaluation_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    response(['status' => 'error', 'message' => 'Method not allowed.']);
}

if (empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Invalid request.']);
}

if (!$conn || $conn->connect_error) {
    response([
        'status'       => 'critical',
        'message'      => 'Database connection failed.',
        'Details'      => $conn->connect_error ?? 'Unknown error',
        'Suggestion'   => 'Please try again later or contact support if the issue persists.'
    ]);
}

if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    response(['status' => 'error', 'message' => 'Unauthenticated.']);
}

$role      = $_SESSION['user_role'];
$batchUuid = trim($_POST['batch_uuid'] ?? '') ?: ($_SESSION['active_batch_uuid'] ?? '');

if (empty($batchUuid)) {
    $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
    $batchUuid = $result->fetch_assoc()['uuid'] ?? null;
}

if (empty($batchUuid)) {
    response(['status' => 'error', 'message' => 'No active batch found.']);
}

// -----------------------------------------------
// STUDENT — own evaluations + unlock status
// -----------------------------------------------
if ($role === 'student') {
    $data = getStudentEvaluations($conn, $_SESSION['profile_uuid'], $batchUuid);

    response([
        'status' => 'success',
        'data'   => $data,
    ]);
}

// -----------------------------------------------
// SUPERVISOR — assigned students' evaluations
// -----------------------------------------------
if ($role === 'supervisor') {
    $studentUuid = trim($_POST['student_uuid'] ?? '');

    if (empty($studentUuid)) {
        // return all assigned students' eval status
        $safeBatch      = $conn->real_escape_string($batchUuid);
        $safeSupervisor = $conn->real_escape_string($_SESSION['profile_uuid']);

        $result = $conn->query("
            SELECT
              sp.uuid AS student_uuid,
              sp.first_name, sp.last_name, sp.student_number,

              MAX(CASE WHEN e.eval_type = 'midterm' THEN e.total_score END) AS midterm_score,
              MAX(CASE WHEN e.eval_type = 'final'   THEN e.total_score END) AS final_score,
              MAX(CASE WHEN e.eval_type = 'self'    THEN e.total_score END) AS self_score,

              SUM(CASE WHEN d.status = 'approved' THEN d.hours_rendered ELSE 0 END) AS approved_hours,
              p.required_hours

            FROM student_profiles sp
            LEFT JOIN evaluations e ON e.student_uuid = sp.uuid AND e.batch_uuid = '{$safeBatch}'
            LEFT JOIN dtr_entries d ON d.student_uuid = sp.uuid AND d.batch_uuid = '{$safeBatch}'
            LEFT JOIN programs p ON sp.program_uuid = p.uuid
            WHERE sp.supervisor_uuid = '{$safeSupervisor}'
              AND sp.batch_uuid = '{$safeBatch}'
            GROUP BY sp.uuid
        ");

        $students = [];
        while ($row = $result->fetch_assoc()) {
            $approvedHours = (float) $row['approved_hours'];
            $requiredHours = (int)   $row['required_hours'];
            $percentage    = $requiredHours > 0
                ? min(100, round(($approvedHours / $requiredHours) * 100, 1))
                : 0;

            $students[] = [
                'student_uuid'   => $row['student_uuid'],
                'full_name'      => $row['first_name'] . ' ' . $row['last_name'],
                'student_number' => $row['student_number'],
                'approved_hours' => round($approvedHours, 2),
                'required_hours' => $requiredHours,
                'percentage'     => $percentage,
                'midterm_done'   => !is_null($row['midterm_score']),
                'final_done'     => !is_null($row['final_score']),
                'self_done'      => !is_null($row['self_score']),
                'midterm_unlocked' => $percentage >= 50,
                'final_unlocked'   => $percentage >= 100,
            ];
        }

        response([
            'status'   => 'success',
            'students' => $students,
            'total'    => count($students),
        ]);
    }

    // specific student
    $data = getStudentEvaluations($conn, $studentUuid, $batchUuid);
    response(['status' => 'success', 'data' => $data]);
}

// -----------------------------------------------
// COORDINATOR / ADMIN — all evaluations
// -----------------------------------------------
if (in_array($role, ['coordinator', 'admin'])) {
    $coordinatorUuid = $role === 'coordinator'
        ? $_SESSION['profile_uuid']
        : null;

    $studentUuid = trim($_POST['student_uuid'] ?? '');

    if (!empty($studentUuid)) {
        // single student detail
        $data    = getStudentEvaluations($conn, $studentUuid, $batchUuid);
        $summary = getEvaluationSummary($conn, $studentUuid, $batchUuid);

        response([
            'status'  => 'success',
            'data'    => $data,
            'summary' => $summary,
        ]);
    }

    // all evaluations overview
    $evaluations = getAllEvaluations($conn, $batchUuid, $coordinatorUuid);

    response([
        'status'      => 'success',
        'evaluations' => $evaluations,
        'total'       => count($evaluations),
    ]);
}

http_response_code(403);
response(['status' => 'error', 'message' => 'Unauthorized.']);