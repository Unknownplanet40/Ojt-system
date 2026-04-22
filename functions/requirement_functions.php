<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';


const REQUIREMENT_TYPES = [
    'medical_certificate'  => 'Medical Certificate',
    'parental_consent'     => 'Parental / Guardian Consent Form',
    'insurance'            => 'Student Insurance',
    'nbi_clearance'        => 'NBI Clearance',
    'resume'               => 'Resume / CV',
    'guardian_form'        => 'Guardian Information Form',
];

const REQ_VALID_TRANSITIONS = [
    'not_submitted' => ['submitted'],
    'returned'      => ['submitted'],
    'submitted'     => ['approved', 'returned'],
    'approved'      => [], // locked — no transitions from approved
];


function getStudentRequirements($conn, string $studentUuid, string $batchUuid): array
{
    $stmt = $conn->prepare("
        SELECT
          sr.uuid,
          sr.req_type,
          sr.status,
          sr.file_path,
          sr.file_name,
                    sr.student_note,
                    sr.coordinator_note,
          sr.return_reason,
          sr.submitted_at,
          sr.reviewed_at,
          sr.reviewed_by,

          CONCAT(cp.first_name, ' ', cp.last_name) AS reviewer_name

        FROM student_requirements sr
        LEFT JOIN coordinator_profiles cp ON sr.reviewed_by = cp.uuid
        WHERE sr.student_uuid = ?
          AND sr.batch_uuid   = ?
        ORDER BY FIELD(
          sr.req_type,
          'medical_certificate','parental_consent','insurance',
          'nbi_clearance','resume','guardian_form'
        )
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(fn ($row) => formatRequirement($row), $rows);
}

function getAllRequirementsOverview($conn, string $batchUuid, string $coordinatorUuid = null): array
{
    $safeBatch = $conn->real_escape_string($batchUuid);

    $coordFilter = '';
    if ($coordinatorUuid) {
        $safeCoord   = $conn->real_escape_string($coordinatorUuid);
        $coordFilter = "AND sp.coordinator_uuid = '{$safeCoord}'";
    }

    $result = $conn->query("
        SELECT
          sp.uuid       AS student_uuid,
          sp.first_name,
          sp.last_name,
          sp.student_number,
          sp.year_level,

          u.email,
          p.code AS program_code,

          -- count per status
          SUM(CASE WHEN sr.status = 'approved'      THEN 1 ELSE 0 END) AS approved_count,
          SUM(CASE WHEN sr.status = 'submitted'     THEN 1 ELSE 0 END) AS submitted_count,
          SUM(CASE WHEN sr.status = 'returned'      THEN 1 ELSE 0 END) AS returned_count,
          SUM(CASE WHEN sr.status = 'not_submitted' THEN 1 ELSE 0 END) AS not_submitted_count,

          -- per requirement status for dot display
          MAX(CASE WHEN sr.req_type = 'medical_certificate' THEN sr.status END) AS medical_status,
          MAX(CASE WHEN sr.req_type = 'parental_consent'    THEN sr.status END) AS consent_status,
          MAX(CASE WHEN sr.req_type = 'insurance'           THEN sr.status END) AS insurance_status,
          MAX(CASE WHEN sr.req_type = 'nbi_clearance'       THEN sr.status END) AS nbi_status,
          MAX(CASE WHEN sr.req_type = 'resume'              THEN sr.status END) AS resume_status,
          MAX(CASE WHEN sr.req_type = 'guardian_form'       THEN sr.status END) AS guardian_status

        FROM student_profiles sp
        JOIN users u ON sp.user_uuid = u.uuid
        LEFT JOIN programs p ON sp.program_uuid = p.uuid
        LEFT JOIN student_requirements sr
          ON sr.student_uuid = sp.uuid
          AND sr.batch_uuid  = '{$safeBatch}'
        WHERE sp.batch_uuid = '{$safeBatch}'
          {$coordFilter}
          AND u.is_active = 1
        GROUP BY sp.id
        ORDER BY sp.last_name ASC, sp.first_name ASC
    ");

    $overview = [];
    while ($row = $result->fetch_assoc()) {
        $overview[] = [
            'student_uuid'      => $row['student_uuid'],
            'full_name'         => $row['first_name'] . ' ' . $row['last_name'],
            'initials'          => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)),
            'student_number'    => $row['student_number'],
            'program_code'      => $row['program_code'] ?? '—',
            'year_label'        => ordinal((int)$row['year_level']) . ' Year',
            'email'             => $row['email'],
            'approved_count'    => (int) $row['approved_count'],
            'submitted_count'   => (int) $row['submitted_count'],
            'returned_count'    => (int) $row['returned_count'],
            'not_submitted_count' => (int) $row['not_submitted_count'],
            'all_approved'      => (int) $row['approved_count'] === 6,
            'has_pending'       => (int) $row['submitted_count'] > 0,
            'has_returned'      => (int) $row['returned_count'] > 0,
            // per-doc status for dot matrix
            'doc_statuses'      => [
                'medical_certificate' => $row['medical_status']   ?? 'not_submitted',
                'parental_consent'    => $row['consent_status']   ?? 'not_submitted',
                'insurance'           => $row['insurance_status'] ?? 'not_submitted',
                'nbi_clearance'       => $row['nbi_status']       ?? 'not_submitted',
                'resume'              => $row['resume_status']    ?? 'not_submitted',
                'guardian_form'       => $row['guardian_status']  ?? 'not_submitted',
            ],
        ];
    }

    return $overview;
}


function uploadRequirement(
    $conn,
    string $studentUuid,
    string $batchUuid,
    string $reqType,
    array  $file,
    string $studentNote = ''
): array {
    if (!array_key_exists($reqType, REQUIREMENT_TYPES)) {
        return ['success' => false, 'error' => 'Invalid requirement type.'];
    }

    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error.'];
    }

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if ($mimeType !== 'application/pdf') {
        return ['success' => false, 'error' => 'Only PDF files are allowed.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File must be 5MB or less.'];
    }

    $stmt = $conn->prepare("
        SELECT uuid, status, file_path
        FROM student_requirements
        WHERE student_uuid = ? AND batch_uuid = ? AND req_type = ?
        LIMIT 1
    ");
    $stmt->bind_param('sss', $studentUuid, $batchUuid, $reqType);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
        return ['success' => false, 'error' => 'Requirement record not found.'];
    }

    if (!in_array($current['status'], ['not_submitted', 'returned'])) {
        return [
            'success' => false,
            'error'   => 'This document has already been ' . $current['status'] . ' and cannot be replaced.',
        ];
    }

    $safeFileName = generateUuid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $projectRoot  = dirname(__DIR__);
    $absoluteDir  = $projectRoot . '/uploads/requirements/' . $reqType . '/' . $studentUuid . '/';
    $relativePath = 'uploads/requirements/' . $reqType . '/' . $studentUuid . '/' . $safeFileName;

    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    if (!empty($current['file_path'])) {
        $oldPath = $projectRoot . '/' . $current['file_path'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $absoluteDir . $safeFileName)) {
        return ['success' => false, 'error' => 'Failed to save file. Check folder permissions.'];
    }

    $studentNote = trim($studentNote);

    $stmt = $conn->prepare("
        UPDATE student_requirements
        SET status        = 'submitted',
            file_path     = ?,
            file_name     = ?,
            student_note  = ?,
            coordinator_note = NULL,
            return_reason = NULL,
            submitted_at  = NOW(),
            reviewed_at   = NULL,
            reviewed_by   = NULL
        WHERE uuid = ?
    ");
    $stmt->bind_param('ssss', $relativePath, $file['name'], $studentNote, $current['uuid']);
    $stmt->execute();
    $stmt->close();

    return [
        'success'   => true,
        'req_uuid'  => $current['uuid'],
        'file_name' => $file['name'],
        'file_path' => $relativePath,
    ];
}


function approveRequirement(
    $conn,
    string $reqUuid,
    string $coordinatorUuid
): array {
    $stmt = $conn->prepare("
        SELECT uuid, status, req_type, student_uuid
        FROM student_requirements WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $reqUuid);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) {
        return ['success' => false, 'error' => 'Requirement not found.'];
    }

    if ($req['status'] !== 'submitted') {
        return [
            'success' => false,
            'error'   => 'Only submitted documents can be approved. Current status: ' . $req['status'],
        ];
    }

    $stmt = $conn->prepare("
        UPDATE student_requirements
        SET status      = 'approved',
            reviewed_at = NOW(),
            reviewed_by = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $coordinatorUuid, $reqUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'requirement_approved',
        description: REQUIREMENT_TYPES[$req['req_type']] . ' approved for student',
        module: 'requirements',
        actorUuid: $coordinatorUuid,
        targetUuid: $req['student_uuid']
    );

    return ['success' => true];
}


function approveAllRequirements(
    $conn,
    string $studentUuid,
    string $batchUuid,
    string $coordinatorUuid
): array {
    $stmt = $conn->prepare("
        SELECT uuid FROM student_requirements
        WHERE student_uuid = ?
          AND batch_uuid   = ?
          AND status       = 'submitted'
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($rows)) {
        return ['success' => false, 'error' => 'No submitted documents to approve.'];
    }

    $stmt = $conn->prepare("
        UPDATE student_requirements
        SET status      = 'approved',
            reviewed_at = NOW(),
            reviewed_by = ?
        WHERE student_uuid = ?
          AND batch_uuid   = ?
          AND status       = 'submitted'
    ");
    $stmt->bind_param('sss', $coordinatorUuid, $studentUuid, $batchUuid);
    $stmt->execute();
    $approvedCount = $stmt->affected_rows;
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'requirement_approved',
        description: "{$approvedCount} requirement(s) approved in bulk",
        module: 'requirements',
        actorUuid: $coordinatorUuid,
        targetUuid: $studentUuid
    );

    return [
        'success'       => true,
        'approved_count' => $approvedCount,
    ];
}


function returnRequirement(
    $conn,
    string $reqUuid,
    string $returnReason,
    string $coordinatorUuid
): array {
    $returnReason = trim($returnReason);

    if (empty($returnReason)) {
        return ['success' => false, 'error' => 'Return reason is required.'];
    }

    $stmt = $conn->prepare("
        SELECT uuid, status, req_type, student_uuid, file_path
        FROM student_requirements WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $reqUuid);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) {
        return ['success' => false, 'error' => 'Requirement not found.'];
    }

    if ($req['status'] !== 'submitted') {
        return [
            'success' => false,
            'error'   => 'Only submitted documents can be returned.',
        ];
    }

    $stmt = $conn->prepare("
        UPDATE student_requirements
        SET status        = 'returned',
            return_reason = ?,
            reviewed_at   = NOW(),
            reviewed_by   = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param('sss', $returnReason, $coordinatorUuid, $reqUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'requirement_returned',
        description: REQUIREMENT_TYPES[$req['req_type']] . ' returned: ' . $returnReason,
        module: 'requirements',
        actorUuid: $coordinatorUuid,
        targetUuid: $req['student_uuid']
    );

    return ['success' => true];
}


function canStudentApply($conn, string $studentUuid, string $batchUuid): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS approved
        FROM student_requirements
        WHERE student_uuid = ?
          AND batch_uuid   = ?
          AND status       = 'approved'
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) $row['approved'] >= 6;
}

function formatRequirement(array $row): array
{
    return [
        'uuid'          => $row['uuid'],
        'req_type'      => $row['req_type'],
        'req_label'     => REQUIREMENT_TYPES[$row['req_type']] ?? $row['req_type'],
        'status'        => $row['status'],
        'status_label'  => match($row['status']) {
            'approved'      => 'Approved',
            'submitted'     => 'Submitted',
            'returned'      => 'Returned',
            'not_submitted' => 'Not submitted',
            default         => ucfirst($row['status']),
        },
        'file_path'     => $row['file_path']     ?? null,
        'file_name'     => $row['file_name']     ?? null,
        'student_note'  => $row['student_note']  ?? null,
        'coordinator_note' => $row['coordinator_note'] ?? null,
        'return_reason' => $row['return_reason'] ?? null,
        'submitted_at'  => !empty($row['submitted_at'])
                             ? date('M j, Y g:i A', strtotime($row['submitted_at']))
                             : null,
        'reviewed_at'   => !empty($row['reviewed_at'])
                             ? date('M j, Y g:i A', strtotime($row['reviewed_at']))
                             : null,
        'reviewer_name' => $row['reviewer_name'] ?? null,
        'can_upload'    => in_array($row['status'], ['not_submitted', 'returned']),
        'can_view'      => in_array($row['status'], ['submitted', 'approved', 'returned']),
    ];
}
