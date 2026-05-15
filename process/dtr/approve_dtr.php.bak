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
require_once dirname(__DIR__, 2) . '/functions/dtr_functions.php';

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

$role = $_SESSION['user_role'];

if (!in_array($role, ['supervisor', 'coordinator', 'admin'])) {
    http_response_code(403);
    response(['status' => 'error', 'message' => 'Unauthorized.']);
}

$action      = trim($_POST['action']       ?? '');  // approve, bulk_approve, reject
$dtrUuid     = trim($_POST['dtr_uuid']     ?? '');
$studentUuid = trim($_POST['student_uuid'] ?? '');
$reason      = trim($_POST['reason']       ?? '');
$actorUserUuid    = $_SESSION['user_uuid'];
$actorProfileUuid = $_SESSION['profile_uuid'];

if ($action === 'approve') {
    if (empty($dtrUuid)) {
        response(['status' => 'error', 'message' => 'DTR UUID is required.']);
    }

    // supervisor scope check
    if ($role === 'supervisor') {
        $stmt = $conn->prepare("
            SELECT d.uuid FROM dtr_entries d
            JOIN student_profiles sp ON d.student_uuid = sp.uuid
            WHERE d.uuid = ? AND sp.supervisor_uuid = ?
            LIMIT 1
        ");
        $stmt->bind_param('ss', $dtrUuid, $actorProfileUuid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            response(['status' => 'error', 'message' => 'Unauthorized.']);
        }
        $stmt->close();
    }

    $result = approveDtrEntry($conn, $dtrUuid, $actorUserUuid, $role, $actorProfileUuid);

    if (!$result['success']) {
        response(['status' => 'error', 'message' => $result['error']]);
    }

    response(['status' => 'success', 'message' => 'DTR entry approved.']);
}

if ($action === 'bulk_approve') {
    if (empty($studentUuid)) {
        response(['status' => 'error', 'message' => 'Student UUID is required.']);
    }

    // supervisors can only bulk approve assigned students
    if ($role === 'supervisor') {
        $stmt = $conn->prepare("
            SELECT id FROM student_profiles
            WHERE uuid = ? AND supervisor_uuid = ?
            LIMIT 1
        ");
        $stmt->bind_param('ss', $studentUuid, $actorProfileUuid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            response(['status' => 'error', 'message' => 'Unauthorized.']);
        }
        $stmt->close();
    }

    // optional specific UUIDs from POST
    $dtrUuids = !empty($_POST['dtr_uuids'])
        ? (is_array($_POST['dtr_uuids']) ? $_POST['dtr_uuids'] : json_decode($_POST['dtr_uuids'], true))
        : [];

    $result = bulkApproveDtrEntries($conn, $studentUuid, $actorUserUuid, $role, $actorProfileUuid, $dtrUuids);

    response([
        'status'   => 'success',
        'message'  => "{$result['approved']} entries approved." .
                      ($result['skipped'] > 0 ? " {$result['skipped']} skipped (backdated or ineligible)." : ''),
        'approved' => $result['approved'],
        'skipped'  => $result['skipped'],
    ]);
}

if ($action === 'reject') {
    if (empty($dtrUuid)) {
        response(['status' => 'error', 'message' => 'DTR UUID is required.']);
    }

    if (empty($reason)) {
        response(['status' => 'error', 'message' => 'Rejection reason is required.']);
    }

    $result = rejectDtrEntry($conn, $dtrUuid, $actorUserUuid, $role, $actorProfileUuid, $reason);

    if (!$result['success']) {
        response(['status' => 'error', 'message' => $result['error']]);
    }

    response(['status' => 'success', 'message' => 'DTR entry rejected.']);
}

response(['status' => 'error', 'message' => 'Invalid action.']);