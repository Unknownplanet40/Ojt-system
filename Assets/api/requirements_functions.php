<?php

require_once 'ServerConfig.php';

// Prevent direct access to this file
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    // Only allow AJAX requests
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/dbconfig.php';

function response($data)
{
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response([
        'status' => 'info',
        'message' => 'Request method is not allowed.',
        'long_message' => 'Only POST requests are allowed for this endpoint.'
    ]);
}

if (!isModRewriteEnabled()) {
    response([
        'status' => 'critical',
        'message' => 'mod_rewrite is disabled.',
        'long_message' => 'mod_rewrite is disabled. Required for clean URLs. Enable it in httpd.conf and restart Apache.'
    ]);
}

$action = isset($_POST['action']) ? $_POST['action'] : null;

if (!$action) {
    response([
        'status' => 'info',
        'message' => 'No action specified.',
        'long_message' => 'Please specify an action parameter to indicate which operation to perform.'
    ]);
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed',
        'long_message' => $e->getMessage()
    ]);
}

require_once 'helpers.php';

const REQUIREMENT_TYPES = [
    'medical_certificate' => [
        'label'       => 'Medical Certificate',
        'description' => 'Issued by a licensed physician within the last 3 months',
        'required'    => true,
    ],
    'parental_consent' => [
        'label'       => 'Parental Consent / Waiver',
        'description' => 'Signed by parent or guardian for on-site or remote OJT',
        'required'    => true,
    ],
    'insurance' => [
        'label'       => 'Personal Accident Insurance',
        'description' => 'Valid insurance policy for the duration of OJT',
        'required'    => true,
    ],
    'nbi_clearance' => [
        'label'       => 'NBI Clearance',
        'description' => 'Required for companies handling sensitive data',
        'required'    => true,
    ],
    'resume' => [
        'label'       => 'Resume / CV',
        'description' => 'Updated resume to be submitted to the company',
        'required'    => true,
    ],
    'guardian_form' => [
        'label'       => 'Parent / Guardian Information Form',
        'description' => 'Filled out school form with guardian contact details',
        'required'    => true,
    ],
];

function initializeRequirements($conn, string $studentUuid, string $batchUuid): void
{
    $stmt = $conn->prepare("
        INSERT IGNORE INTO student_requirements
          (uuid, student_uuid, batch_uuid, req_type, status)
        VALUES (?, ?, ?, ?, 'not_submitted')
    ");

    foreach (array_keys(REQUIREMENT_TYPES) as $reqType) {
        $uuid = generateUuid();
        $stmt->bind_param('ssss', $uuid, $studentUuid, $batchUuid, $reqType);
        $stmt->execute();
    }

    $stmt->close();
}


function getStudentRequirements($conn, string $studentUuid, string $batchUuid): array
{
    $stmt = $conn->prepare("
        SELECT
          uuid, req_type, status,
          file_name, file_path,
          student_note, coordinator_note,
          submitted_at, reviewed_at
        FROM student_requirements
        WHERE student_uuid = ? AND batch_uuid = ?
        ORDER BY FIELD(req_type,
          'medical_certificate','parental_consent','insurance',
          'nbi_clearance','resume','guardian_form'
        )
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // merge with type definitions
    $requirements = [];
    foreach ($rows as $row) {
        $typeDef = REQUIREMENT_TYPES[$row['req_type']] ?? [];
        $requirements[] = [
            'uuid'             => $row['uuid'],
            'req_type'         => $row['req_type'],
            'label'            => $typeDef['label']       ?? $row['req_type'],
            'description'      => $typeDef['description'] ?? '',
            'status'           => $row['status'],
            'status_label'     => match($row['status']) {
                'not_submitted' => 'Not submitted',
                'submitted'     => 'Submitted',
                'under_review'  => 'Under review',
                'approved'      => 'Approved',
                'returned'      => 'Returned',
                default         => 'Unknown',
            },
            'file_name'        => $row['file_name'],
            'file_path'        => $row['file_path'],
            'student_note'     => $row['student_note'],
            'coordinator_note' => $row['coordinator_note'],
            'submitted_at'     => $row['submitted_at']
                                    ? date('M j, Y', strtotime($row['submitted_at']))
                                    : null,
            'reviewed_at'      => $row['reviewed_at']
                                    ? date('M j, Y', strtotime($row['reviewed_at']))
                                    : null,
        ];
    }

    return $requirements;
}

function getRequirementsSummary($conn, string $studentUuid, string $batchUuid): array
{
    $stmt = $conn->prepare("
        SELECT
          status,
          COUNT(*) AS total
        FROM student_requirements
        WHERE student_uuid = ? AND batch_uuid = ?
        GROUP BY status
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $counts = [
        'not_submitted' => 0,
        'submitted'     => 0,
        'under_review'  => 0,
        'approved'      => 0,
        'returned'      => 0,
    ];

    foreach ($rows as $row) {
        $counts[$row['status']] = (int) $row['total'];
    }

    $total     = array_sum($counts);
    $approved  = $counts['approved'];
    $pending   = $counts['submitted'] + $counts['under_review'];
    $allDone   = $approved === $total && $total > 0;

    return [
        'counts'        => $counts,
        'total'         => $total,
        'approved'      => $approved,
        'pending'       => $pending,
        'percentage'    => $total > 0 ? round(($approved / $total) * 100) : 0,
        'is_complete'   => $allDone,
        'can_apply'     => $allDone,  // all 6 approved = can submit OJT application
    ];
}

function submitRequirement($conn, string $requirementUuid, array $file, string $studentNote = ''): array
{
    // validate file
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error.'];
    }

    if ($file['type'] !== 'application/pdf') {
        return ['success' => false, 'error' => 'Only PDF files are allowed.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File must be 5MB or less.'];
    }

    // fetch requirement to get student_uuid, req_type, and old file_path
    $stmt = $conn->prepare("
        SELECT uuid, student_uuid, req_type, status, file_path
        FROM student_requirements
        WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $requirementUuid);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) {
        return ['success' => false, 'error' => 'Requirement not found.'];
    }

    // only allow submit if not_submitted or returned
    if (!in_array($req['status'], ['not_submitted', 'returned'])) {
        return ['success' => false, 'error' => 'This document has already been submitted or approved.'];
    }

    // save new file
    $safeFileName = generateUuid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $uploadDir    = dirname(__DIR__, 2) . '/uploads/requirements/' . $req['req_type'] . '/' . $req['student_uuid'] . '/';
    $relativePath = 'uploads/requirements/' . $req['req_type'] . '/' . $req['student_uuid'] . '/' . $safeFileName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeFileName)) {
        return ['success' => false, 'error' => 'Failed to save file. Check folder permissions.'];
    }

    // update DB
    $stmt = $conn->prepare("
        UPDATE student_requirements
        SET status           = 'submitted',
            file_name        = ?,
            file_path        = ?,
            student_note     = ?,
            coordinator_note = NULL,
            submitted_at     = NOW(),
            reviewed_at      = NULL,
            reviewed_by      = NULL
        WHERE uuid = ?
    ");
    $stmt->bind_param(
        'ssss',
        $file['name'],
        $relativePath,
        $studentNote,
        $requirementUuid
    );
    $stmt->execute();
    $stmt->close();

    // delete old file after successful replacement
    if (!empty($req['file_path'])) {
        $oldAbsolutePath = dirname(__DIR__, 2) . '/' . ltrim($req['file_path'], '/\\');
        $newAbsolutePath = $uploadDir . $safeFileName;

        if ($oldAbsolutePath !== $newAbsolutePath && is_file($oldAbsolutePath)) {
            @unlink($oldAbsolutePath);
        }
    }

    logActivity(
        conn: $conn,
        eventType: 'requirement_submitted',
        description: "Student submitted " . (REQUIREMENT_TYPES[$req['req_type']]['label'] ?? $req['req_type']),
        module: 'requirements',
        actorUuid: $req['student_uuid'],
        targetUuid: $requirementUuid
    );

    return ['success' => true];
}

function approveRequirement($conn, string $requirementUuid, string $coordinatorUserUuid): array
{
    $stmt = $conn->prepare("
        SELECT uuid, student_uuid, req_type, status
        FROM student_requirements
        WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $requirementUuid);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) {
        return ['success' => false, 'error' => 'Requirement not found.'];
    }

    if ($req['status'] !== 'submitted') {
        return ['success' => false, 'error' => 'Only submitted documents can be approved.'];
    }

    $stmt = $conn->prepare("
        UPDATE student_requirements
        SET status      = 'approved',
            reviewed_by = ?,
            reviewed_at = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $coordinatorUserUuid, $requirementUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'requirement_approved',
        description: "Coordinator approved " . (REQUIREMENT_TYPES[$req['req_type']]['label'] ?? $req['req_type']),
        module: 'requirements',
        actorUuid: $coordinatorUserUuid,
        targetUuid: $requirementUuid
    );

    // check if all requirements are now approved
    // if so — log that student is ready to apply
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved
        FROM student_requirements
        WHERE student_uuid = (
            SELECT student_uuid FROM student_requirements WHERE uuid = ? LIMIT 1
        )
    ");
    $stmt->bind_param('s', $requirementUuid);
    $stmt->execute();
    $counts = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)$counts['approved'] === (int)$counts['total']) {
        logActivity(
            conn: $conn,
            eventType: 'other',
            description: "All pre-OJT requirements approved — student is ready to apply",
            module: 'requirements',
            actorUuid: $coordinatorUserUuid,
            targetUuid: $req['student_uuid']
        );
    }

    return ['success' => true];
}

function returnRequirement($conn, string $requirementUuid, string $coordinatorNote, string $coordinatorUserUuid): array
{
    if (empty(trim($coordinatorNote))) {
        return ['success' => false, 'error' => 'A note is required when returning a document.'];
    }

    $stmt = $conn->prepare("
        SELECT uuid, student_uuid, req_type, status
        FROM student_requirements
        WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $requirementUuid);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) {
        return ['success' => false, 'error' => 'Requirement not found.'];
    }

    if ($req['status'] !== 'submitted') {
        return ['success' => false, 'error' => 'Only submitted documents can be returned.'];
    }

    $stmt = $conn->prepare("
        UPDATE student_requirements
        SET status           = 'returned',
            coordinator_note = ?,
            reviewed_by      = ?,
            reviewed_at      = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('sss', $coordinatorNote, $coordinatorUserUuid, $requirementUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'other',
        description: "Coordinator returned " . (REQUIREMENT_TYPES[$req['req_type']]['label'] ?? $req['req_type']) . " for revision",
        module: 'requirements',
        actorUuid: $coordinatorUserUuid,
        targetUuid: $requirementUuid
    );

    return ['success' => true];
}

function canStudentApply($conn, string $studentUuid, string $batchUuid): bool
{
    $stmt = $conn->prepare("
        SELECT
          COUNT(*)                                                    AS total,
          SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END)       AS approved
        FROM student_requirements
        WHERE student_uuid = ? AND batch_uuid = ?
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total    = (int) $row['total'];
    $approved = (int) $row['approved'];

    return $total > 0 && $approved === $total;
}

function getStudentsRequirementsOverview($conn, string $coordinatorUuid, string $batchUuid): array
{
    $safeCoord = $conn->real_escape_string($coordinatorUuid);
    $safeBatch = $conn->real_escape_string($batchUuid);

    $result = $conn->query("
        SELECT
          sp.uuid           AS student_uuid,
          sp.first_name,
          sp.last_name,
          sp.year_level,
          p.code            AS program_code,

          COUNT(sr.id)      AS total_reqs,
          SUM(CASE WHEN sr.status = 'approved'  THEN 1 ELSE 0 END) AS approved,
          SUM(CASE WHEN sr.status = 'submitted'
                    OR sr.status = 'under_review' THEN 1 ELSE 0 END) AS pending,
          SUM(CASE WHEN sr.status = 'returned'  THEN 1 ELSE 0 END) AS returned

        FROM student_profiles sp
        LEFT JOIN programs p
          ON sp.program_uuid = p.uuid
        LEFT JOIN student_requirements sr
          ON sp.uuid = sr.student_uuid
          AND sr.batch_uuid = '{$safeBatch}'
        WHERE sp.coordinator_uuid = '{$safeCoord}'
          AND sp.batch_uuid       = '{$safeBatch}'
        GROUP BY sp.id
        ORDER BY sp.last_name ASC
    ");

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $total    = (int) $row['total_reqs'];
        $approved = (int) $row['approved'];
        $pending  = (int) $row['pending'];
        $returned = (int) $row['returned'];

        $students[] = [
            'student_uuid' => $row['student_uuid'],
            'full_name'    => $row['first_name'] . ' ' . $row['last_name'],
            'initials'     => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)),
            'program_code' => $row['program_code'] ?? '—',
            'year_label'   => ordinal((int)$row['year_level']) . ' Year',
            'total'        => $total,
            'approved'     => $approved,
            'pending'      => $pending,
            'returned'     => $returned,
            'can_apply'    => $total > 0 && $approved === $total,
            'percentage'   => $total > 0 ? round(($approved / $total) * 100) : 0,
        ];
    }

    return $students;
}

function getRequirementDetails($conn, string $studentUuid, string $batchUuid): ?array
{

    $userUuid = $conn->real_escape_string($studentUuid);
    $batchUuid = $conn->real_escape_string($batchUuid);


    $stmt = $conn->prepare("
        SELECT
          sr.uuid, sr.req_type, sr.status,
          sr.file_name, sr.file_path,
          sr.student_note, sr.coordinator_note,
          sr.submitted_at, sr.reviewed_at,
          TRIM(CONCAT_WS(' ', sp.first_name, sp.last_name)) AS student_full_name
        FROM student_requirements sr
        INNER JOIN student_profiles sp
          ON sp.uuid = sr.student_uuid
        WHERE sr.student_uuid = ? 
          AND sr.batch_uuid = ? 
          AND sr.status IN ('submitted', 'under_review')
        ORDER BY sr.submitted_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('ss', $userUuid, $batchUuid);
    $stmt->execute();

    $requirement = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$requirement) {
        return null;
    }

    $typeDef = REQUIREMENT_TYPES[$requirement['req_type']] ?? [];
    return [
        'uuid'             => $requirement['uuid'],
        'student_full_name' => $requirement['student_full_name'] ?? 'Student',
        'req_type'         => $requirement['req_type'],
        'label'            => $typeDef['label']       ?? $requirement['req_type'],
        'description'      => $typeDef['description'] ?? '',
        'status'           => $requirement['status'],
        'status_label'     => match($requirement['status']) {
            'not_submitted' => 'Not submitted',
            'submitted'     => 'Submitted',
            'under_review'  => 'Under review',
            'approved'      => 'Approved',
            'returned'      => 'Returned',
            default         => 'Unknown',
        },
        'file_name'        => $requirement['file_name'],
        'file_path'        => $requirement['file_path'],
        'student_note'     => $requirement['student_note'],
        'coordinator_note' => $requirement['coordinator_note'],
        'submitted_at'     => $requirement['submitted_at']
                                    ? date('M j, Y', strtotime($requirement['submitted_at']))
                                    : null,
        'reviewed_at'      => $requirement['reviewed_at']
                                    ? date('M j, Y', strtotime($requirement['reviewed_at']))
                                    : null,
    ];
}

function activebatch($conn)
{
    $result = $conn->query("SELECT uuid, school_year, semester FROM batches WHERE status = 'active' LIMIT 1");
    return $result->fetch_assoc();
}

function UUID_convert($conn, $uuid): ?string
{
    $stmt = $conn->prepare("SELECT uuid FROM coordinator_profiles WHERE user_uuid = ?");
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    $result = $stmt->get_result();
    $coordinatorProfileUuid = null;
    if ($result->num_rows > 0) {
        $coordinatorProfileUuid = $result->fetch_assoc()['uuid'];
    }

    return $coordinatorProfileUuid;
}

function UUID_convert_Student($conn, $uuid): ?string
{
    $stmt = $conn->prepare("SELECT uuid FROM student_profiles WHERE user_uuid = ?");
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentProfileUuid = null;
    if ($result->num_rows > 0) {
        $studentProfileUuid = $result->fetch_assoc()['uuid'];
    }

    return $studentProfileUuid;
}

function studentCount($conn, $coordinatorUuid, $batchUuid = null)
{
    $safeBatch = $conn->real_escape_string($batchUuid ?? '');
    $safeCoord = $conn->real_escape_string(UUID_convert($conn, $coordinatorUuid) ?? '');

    $result        = $conn->query("
        SELECT COUNT(*) AS total
        FROM student_profiles
        WHERE coordinator_uuid = '{$safeCoord}'
          AND batch_uuid = '{$safeBatch}'
    ");
    $row = $result->fetch_assoc();
    return (int) $row['total'];
}

$action = isset($_POST['action']) ? $_POST['action'] : null;

if (empty($action)) {
    response([
        'status' => 'error',
        'message' => 'No action specified.'
    ]);
}

if (!isset($_SESSION['user'])) {
    response([
        'status' => 'error',
        'message' => 'Unauthorized. Please log in.'
    ]);
}

if ($action === 'fetch_students') {
    $activeBatch = activebatch($conn);
    if (!$activeBatch) {
        response([
            'status' => 'error',
            'message' => 'No active batch found. Please contact the administrator.'
        ]);
    }

    $studentCount = studentCount($conn, $_SESSION['user']['uuid'], $activeBatch['uuid']);

    $students = getStudentsRequirementsOverview($conn, UUID_convert($conn, $_SESSION['user']['uuid']) ?? '', $activeBatch['uuid']);

    response([
        'status' => 'success',
        'data' => $students,
        'active_batch' => $activeBatch,
        'student_count' => $studentCount
    ]);
} elseif ($action === 'get_requirements_status') {
    $studentId   = isset($_POST['studentId']) ? $_POST['studentId'] : null;
    $convertedId = UUID_convert_Student($conn, $studentId);
    $activeBatch = activebatch($conn);

    if (!$activeBatch) {
        response([
            'status' => 'error',
            'message' => 'No active batch found. Please contact the administrator.'
        ]);
    }

    if (!$studentId) {
        response([
            'status' => 'error',
            'message' => 'No student ID provided.'
        ]);
    }

    $requirements = getStudentRequirements($conn, $convertedId, $activeBatch['uuid']);
    response([
        'status' => 'success',
        'data' => $requirements
    ]);

} elseif ($action === 'upload_requirement') {

    $requirementId = isset($_POST['requirementId']) ? $_POST['requirementId'] : null;
    $note          = isset($_POST['note']) ? $_POST['note'] : '';

    if (!$requirementId) {
        response([
            'status' => 'error',
            'message' => 'No requirement ID provided.'
        ]);
    }

    if (!isset($_FILES['file'])) {
        response([
            'status' => 'error',
            'message' => 'No file uploaded.'
        ]);
    }

    $result = submitRequirement($conn, $requirementId, $_FILES['file'], $note);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Document submitted successfully.'
        ]);
    } else {
        {
            response([
                'status' => 'error',
                'message' => $result['error']
            ]);
        }
    }
} elseif ($action === 'get_requirement_details') {
    $studentId   = isset($_POST['studentUuid']) ? $_POST['studentUuid'] : null;
    $activeBatch = activebatch($conn);

    if (!$activeBatch) {
        response([
            'status' => 'error',
            'message' => 'No active batch found. Please contact the administrator.'
        ]);
    }

    if (!$studentId) {
        response([
            'status' => 'error',
            'message' => 'No student ID provided.'
        ]);
    }

    $requirementDetails = getRequirementDetails($conn, $studentId, $activeBatch['uuid']);
    if ($requirementDetails) {
        response([
            'status' => 'success',
            'data' => $requirementDetails
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'No requirement found for this student that is currently submitted or under review.'
        ]);
    }
} elseif ($action === 'approve_document') {
    $documentUuid = isset($_POST['documentUuid']) ? $_POST['documentUuid'] : null;

    if (!$documentUuid) {
        response([
            'status' => 'error',
            'message' => 'No document ID provided.'
        ]);
    }

    $result = approveRequirement($conn, $documentUuid, $_SESSION['user']['uuid']);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Document approved successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['error']
        ]);
    }

} elseif ($action === 'return_document') {
    $documentUuid = isset($_POST['documentUuid']) ? $_POST['documentUuid'] : null;
    $note         = isset($_POST['coordinatorNote']) ? $_POST['coordinatorNote'] : '';

    if (!$documentUuid) {
        response([
            'status' => 'error',
            'message' => 'No document ID provided.'
        ]);
    }

    if (empty(trim($note))) {
        response([
            'status' => 'error',
            'message' => 'A note is required when returning a document.'
        ]);
    }

    $result = returnRequirement($conn, $documentUuid, $note, $_SESSION['user']['uuid']);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Document returned successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['error']
        ]);
    }
}


else {
    response([
        'status' => 'error',
        'message' => 'Invalid action specified.'
    ]);
}
