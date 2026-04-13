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

$type = isset($_GET['type']) ? $_GET['type'] : null;

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

const VALID_TRANSITIONS = [
    'pending'        => ['approved', 'needs_revision', 'rejected'],
    'needs_revision' => ['pending', 'withdrawn'],
    'approved'       => ['endorsed', 'withdrawn'],
    'endorsed'       => ['active', 'withdrawn'],
    'active'         => [],
    'rejected'       => [],
    'withdrawn'      => [],
];


// -----------------------------------------------
// LOG status change
// -----------------------------------------------
function logApplicationStatus(
    $conn,
    string  $applicationUuid,
    ?string $fromStatus,
    string  $toStatus,
    string  $changedByUuid,
    string  $note = ''
): void {
    $uuid = generateUuid();
    $stmt = $conn->prepare("
        INSERT INTO application_status_logs
          (uuid, application_uuid, from_status, to_status, changed_by, note)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'ssssss',
        $uuid,
        $applicationUuid,
        $fromStatus,
        $toStatus,
        $changedByUuid,
        $note
    );
    $stmt->execute();
    $stmt->close();
}


// -----------------------------------------------
// SUBMIT application  (student action)
// -----------------------------------------------
function submitApplication($conn, array $data, string $studentProfileUuid, string $actorUuid): array
{
    $companyUuid   = trim($data['company_uuid']    ?? '');
    $batchUuid     = trim($data['batch_uuid']       ?? '');
    $preferredDept = trim($data['preferred_dept']   ?? '');
    $coverLetter   = trim($data['cover_letter']     ?? '');

    // --- validate ---
    if (empty($companyUuid)) {
        return ['success' => false, 'errors' => ['company_uuid' => 'Select a company.']];
    }

    if (empty($batchUuid)) {
        return ['success' => false, 'errors' => ['general' => 'No active batch found.']];
    }

    // --- check requirements complete ---
    if (!canStudentApply($conn, $studentProfileUuid, $batchUuid)) {
        return ['success' => false, 'errors' => ['general' => 'Complete all pre-OJT requirements first.']];
    }

    // --- check no existing active application ---
    $stmt = $conn->prepare("
        SELECT uuid, status FROM ojt_applications
        WHERE student_uuid = ? AND batch_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentProfileUuid, $batchUuid);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        if (in_array($existing['status'], ['pending', 'approved', 'endorsed', 'active', 'needs_revision'])) {
            return ['success' => false, 'errors' => ['general' => 'You already have an active application for this batch.']];
        }
    }

    // --- check company is valid for this student ---
    $stmt = $conn->prepare("
        SELECT sp.program_uuid FROM student_profiles sp WHERE sp.uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $studentProfileUuid);
    $stmt->execute();
    $sp = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $programUuid = $sp['program_uuid'] ?? null;

    // check company accepts student's program
    $stmt = $conn->prepare("
        SELECT id FROM company_accepted_programs
        WHERE company_uuid = ? AND program_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $companyUuid, $programUuid);
    $stmt->execute();
    $programOk = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$programOk) {
        return ['success' => false, 'errors' => ['company_uuid' => 'This company does not accept your program.']];
    }

    // check company has remaining slots
    $stmt = $conn->prepare("
        SELECT cs.total_slots,
               COUNT(DISTINCT sp2.id) AS filled_slots
        FROM company_slots cs
        LEFT JOIN student_profiles sp2
          ON cs.company_uuid = sp2.company_uuid
          AND cs.batch_uuid  = sp2.batch_uuid
        WHERE cs.company_uuid = ? AND cs.batch_uuid = ?
        GROUP BY cs.id
        LIMIT 1
    ");
    $stmt->bind_param('ss', $companyUuid, $batchUuid);
    $stmt->execute();
    $slots = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$slots || (int)$slots['filled_slots'] >= (int)$slots['total_slots']) {
        return ['success' => false, 'errors' => ['company_uuid' => 'This company has no remaining slots.']];
    }

    // --- insert application ---
    $appUuid = generateUuid();

    $stmt = $conn->prepare("
        INSERT INTO ojt_applications
          (uuid, student_uuid, company_uuid, batch_uuid,
           status, preferred_dept, cover_letter)
        VALUES (?, ?, ?, ?, 'pending', ?, ?)
    ");
    $stmt->bind_param(
        'ssssss',
        $appUuid,
        $studentProfileUuid,
        $companyUuid,
        $batchUuid,
        $preferredDept,
        $coverLetter
    );
    $stmt->execute();
    $stmt->close();

    // log status
    logApplicationStatus(
        $conn,
        $appUuid,
        null,
        'pending',
        $actorUuid,
        'Student submitted application'
    );

    logActivity(
        conn: $conn,
        eventType: 'application_submitted',
        description: "Student submitted OJT application",
        module: 'applications',
        actorUuid: $actorUuid,
        targetUuid: $appUuid
    );

    return ['success' => true, 'uuid' => $appUuid];
}

// -----------------------------------------------
// RESUBMIT application after revision  (student action)
// -----------------------------------------------
function updateApplication($conn, string $appUuid, array $data, string $studentProfileUuid, string $actorUuid): array
{
    $companyUuid   = trim($data['company_uuid']   ?? '');
    $preferredDept = trim($data['preferred_dept'] ?? '');
    $coverLetter   = trim($data['cover_letter']   ?? '');

    if (empty($companyUuid)) {
        return ['success' => false, 'errors' => ['company_uuid' => 'Select a company.']];
    }

    // fetch current application — must belong to this student and be needs_revision
    $stmt = $conn->prepare("
        SELECT uuid, student_uuid, company_uuid, batch_uuid, status
        FROM ojt_applications
        WHERE uuid = ? AND student_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $appUuid, $studentProfileUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'errors' => ['general' => 'Application not found.']];
    }

    if ($app['status'] !== 'needs_revision') {
        return ['success' => false, 'errors' => ['general' => 'Only returned applications can be updated.']];
    }

    // validate new company — same checks as submitApplication
    $stmt = $conn->prepare("SELECT program_uuid FROM student_profiles WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $studentProfileUuid);
    $stmt->execute();
    $sp          = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $programUuid = $sp['program_uuid'] ?? null;

    // check company accepts program
    $stmt = $conn->prepare("
        SELECT id FROM company_accepted_programs
        WHERE company_uuid = ? AND program_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $companyUuid, $programUuid);
    $stmt->execute();
    $programOk = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$programOk) {
        return ['success' => false, 'errors' => ['company_uuid' => 'This company does not accept your program.']];
    }

    // check slots
    $stmt = $conn->prepare("
        SELECT cs.total_slots, COUNT(DISTINCT sp2.id) AS filled_slots
        FROM company_slots cs
        LEFT JOIN student_profiles sp2
          ON cs.company_uuid = sp2.company_uuid
         AND cs.batch_uuid   = sp2.batch_uuid
        WHERE cs.company_uuid = ? AND cs.batch_uuid = ?
        GROUP BY cs.id LIMIT 1
    ");
    $stmt->bind_param('ss', $companyUuid, $app['batch_uuid']);
    $stmt->execute();
    $slots = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$slots || (int)$slots['filled_slots'] >= (int)$slots['total_slots']) {
        return ['success' => false, 'errors' => ['company_uuid' => 'This company has no remaining slots.']];
    }

    // update the application — reset to pending
    $stmt = $conn->prepare("
        UPDATE ojt_applications
        SET company_uuid      = ?,
            preferred_dept    = ?,
            cover_letter      = ?,
            status            = 'pending',
            coordinator_note  = NULL,
            reviewed_by       = NULL,
            reviewed_at       = NULL
        WHERE uuid = ?
    ");
    $stmt->bind_param('ssss', $companyUuid, $preferredDept, $coverLetter, $appUuid);
    $stmt->execute();
    $stmt->close();

    // log status change
    logApplicationStatus(
        $conn,
        $appUuid,
        'needs_revision',
        'pending',
        $actorUuid,
        'Student updated and re-submitted application'
        . ($companyUuid !== $app['company_uuid'] ? ' — changed company' : '')
    );

    logActivity(
        conn: $conn,
        eventType: 'application_submitted',
        description: "Student re-submitted OJT application after revision",
        module: 'applications',
        actorUuid: $actorUuid,
        targetUuid: $appUuid
    );

    return ['success' => true];
}


// -----------------------------------------------
// APPROVE application  (coordinator action)
// -----------------------------------------------
function approveApplication($conn, string $appUuid, string $note = '', string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT uuid, student_uuid, company_uuid, batch_uuid, status
        FROM ojt_applications WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('approved', VALID_TRANSITIONS[$app['status']] ?? [])) {
        return ['success' => false, 'error' => "Cannot approve from status: {$app['status']}."];
    }

    $stmt = $conn->prepare("
        UPDATE ojt_applications
        SET status           = 'approved',
            coordinator_note = ?,
            reviewed_by      = ?,
            reviewed_at      = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('sss', $note, $actorUuid, $appUuid);
    $stmt->execute();
    $stmt->close();

    // assign company to student profile
    $stmt = $conn->prepare("
        UPDATE student_profiles SET company_uuid = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $app['company_uuid'], $app['student_uuid']);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, $app['status'], 'approved', $actorUuid, $note);

    logActivity(
        conn: $conn,
        eventType: 'application_approved',
        description: "Coordinator approved OJT application",
        module: 'applications',
        actorUuid: $actorUuid,
        targetUuid: $appUuid
    );

    return ['success' => true];
}


// -----------------------------------------------
// RETURN for revision  (coordinator action)
// -----------------------------------------------
function returnApplication($conn, string $appUuid, string $note, string $actorUuid): array
{
    if (empty(trim($note))) {
        return ['success' => false, 'error' => 'A note is required when returning an application.'];
    }

    $stmt = $conn->prepare("
        SELECT uuid, student_uuid, status FROM ojt_applications WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('needs_revision', VALID_TRANSITIONS[$app['status']] ?? [])) {
        return ['success' => false, 'error' => "Cannot return from status: {$app['status']}."];
    }

    $stmt = $conn->prepare("
        UPDATE ojt_applications
        SET status           = 'needs_revision',
            coordinator_note = ?,
            reviewed_by      = ?,
            reviewed_at      = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('sss', $note, $actorUuid, $appUuid);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, $app['status'], 'needs_revision', $actorUuid, $note);

    logActivity(
        conn: $conn,
        eventType: 'application_returned',
        description: "Coordinator returned application for revision",
        module: 'applications',
        actorUuid: $actorUuid,
        targetUuid: $appUuid
    );

    return ['success' => true];
}


// -----------------------------------------------
// REJECT application  (coordinator action)
// -----------------------------------------------
function rejectApplication($conn, string $appUuid, string $note, string $actorUuid): array
{
    if (empty(trim($note))) {
        return ['success' => false, 'error' => 'A reason is required when rejecting an application.'];
    }

    $stmt = $conn->prepare("
        SELECT uuid, student_uuid, company_uuid, status
        FROM ojt_applications WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('rejected', VALID_TRANSITIONS[$app['status']] ?? [])) {
        return ['success' => false, 'error' => "Cannot reject from status: {$app['status']}."];
    }

    $stmt = $conn->prepare("
        UPDATE ojt_applications
        SET status           = 'rejected',
            coordinator_note = ?,
            reviewed_by      = ?,
            reviewed_at      = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('sss', $note, $actorUuid, $appUuid);
    $stmt->execute();
    $stmt->close();

    // clear company from student profile
    $stmt = $conn->prepare("
        UPDATE student_profiles SET company_uuid = NULL WHERE uuid = ?
    ");
    $stmt->bind_param('s', $app['student_uuid']);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, $app['status'], 'rejected', $actorUuid, $note);

    logActivity(
        conn: $conn,
        eventType: 'application_rejected',
        description: "Coordinator rejected OJT application",
        module: 'applications',
        actorUuid: $actorUuid,
        targetUuid: $appUuid
    );

    return ['success' => true];
}


// -----------------------------------------------
// WITHDRAW application  (student action)
// -----------------------------------------------
function withdrawApplication($conn, string $appUuid, string $studentProfileUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT uuid, student_uuid, status
        FROM ojt_applications WHERE uuid = ? AND student_uuid = ? LIMIT 1
    ");
    $stmt->bind_param('ss', $appUuid, $studentProfileUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!in_array('withdrawn', VALID_TRANSITIONS[$app['status']] ?? [])) {
        return ['success' => false, 'error' => "You cannot withdraw an application with status: {$app['status']}."];
    }

    $stmt = $conn->prepare("
        UPDATE ojt_applications SET status = 'withdrawn' WHERE uuid = ?
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $stmt->close();

    // release company from student profile
    $stmt = $conn->prepare("
        UPDATE student_profiles SET company_uuid = NULL WHERE uuid = ?
    ");
    $stmt->bind_param('s', $studentProfileUuid);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, $app['status'], 'withdrawn', $actorUuid, 'Student withdrew application');

    logActivity(
        conn: $conn,
        eventType: 'application_withdrawn',
        description: "Student withdrew OJT application",
        module: 'applications',
        actorUuid: $actorUuid,
        targetUuid: $appUuid
    );

    return ['success' => true];
}


// -----------------------------------------------
// GET student's own application
// -----------------------------------------------
function getStudentApplication($conn, string $studentProfileUuid, string $batchUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          a.uuid,
          a.status,
          a.preferred_dept,
          a.cover_letter,
          a.coordinator_note,
          a.reviewed_at,
          a.created_at,
          c.name          AS company_name,
          c.industry,
          c.city,
          c.work_setup,
          c.address
        FROM ojt_applications a
        JOIN companies c ON a.company_uuid = c.uuid
        WHERE a.student_uuid = ? AND a.batch_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentProfileUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'uuid'             => $row['uuid'],
        'status'           => $row['status'],
        'status_label'     => match($row['status']) {
            'pending'        => 'Pending review',
            'approved'       => 'Approved',
            'endorsed'       => 'Endorsed',
            'active'         => 'OJT Active',
            'needs_revision' => 'Needs revision',
            'rejected'       => 'Rejected',
            'withdrawn'      => 'Withdrawn',
            default          => 'Unknown',
        },
        'company_name'     => $row['company_name'],
        'industry'         => $row['industry']      ?? '—',
        'city'             => $row['city']           ?? '—',
        'work_setup'       => $row['work_setup'],
        'address'          => $row['address']        ?? '—',
        'preferred_dept'   => $row['preferred_dept'] ?? '—',
        'cover_letter'     => $row['cover_letter']   ?? '',
        'coordinator_note' => $row['coordinator_note'] ?? null,
        'reviewed_at'      => $row['reviewed_at']
                               ? date('M j, Y', strtotime($row['reviewed_at']))
                               : null,
        'submitted_at'     => date('M j, Y g:i A', strtotime($row['created_at'])),
        'can_withdraw'     => in_array('withdrawn', VALID_TRANSITIONS[$row['status']] ?? []),
    ];
}


// -----------------------------------------------
// GET all applications  (coordinator view)
// -----------------------------------------------
function getApplications($conn, string $coordinatorUuid, string $batchUuid, array $filters = []): array
{
    $safeCoord = $conn->real_escape_string($coordinatorUuid);
    $safeBatch = $conn->real_escape_string($batchUuid);

    $conditions = ["a.batch_uuid = '{$safeBatch}'", "sp.coordinator_uuid = '{$safeCoord}'"];

    if (!empty($filters['status'])) {
        $s = $conn->real_escape_string($filters['status']);
        $conditions[] = "a.status = '{$s}'";
    }

    $where = implode(' AND ', $conditions);

    $result = $conn->query("
        SELECT
          a.uuid,
          a.status,
          a.preferred_dept,
          a.coordinator_note,
          a.cover_letter,
          a.created_at,
          a.reviewed_at,
          sp.uuid           AS student_uuid,
          sp.first_name,
          sp.last_name,
          sp.student_number,
          sp.year_level,
          sp.section,
          sp.mobile,
          u.email,
          p.code            AS program_code,
          c.uuid            AS company_uuid,
          c.name            AS company_name,
          c.industry,
          c.work_setup,
          cs.total_slots,
          c.city
        FROM ojt_applications a
        JOIN student_profiles sp ON a.student_uuid = sp.uuid
        LEFT JOIN programs p     ON sp.program_uuid = p.uuid
        JOIN companies c         ON a.company_uuid  = c.uuid
        JOIN users u             ON sp.user_uuid = u.uuid
        LEFT JOIN company_slots cs ON c.uuid = cs.company_uuid AND cs.batch_uuid = '{$safeBatch}'
        WHERE {$where}
        ORDER BY
          FIELD(a.status,'pending','needs_revision','approved','endorsed','active','rejected','withdrawn'),
          a.created_at ASC
    ");

    $applications = [];

    while ($row = $result->fetch_assoc()) {
        $companyUuid = $row['company_uuid'];
        $stmt = $conn->prepare("
            SELECT p.code FROM company_accepted_programs cap
            JOIN programs p ON cap.program_uuid = p.uuid
            WHERE cap.company_uuid = ? AND p.is_active = 1
        ");

        $stmt->bind_param('s', $companyUuid);
        $stmt->execute();
        $programsResult = $stmt->get_result();

        $acceptedPrograms = [];
        while ($programRow = $programsResult->fetch_assoc()) {
            $acceptedPrograms[] = $programRow['code'];
        }

        $stmt = $conn->prepare("
            SELECT req_type, status FROM student_requirements
            WHERE student_uuid = ? AND batch_uuid = ?
        ");

        $stmt->bind_param('ss', $row['student_uuid'], $safeBatch);
        $stmt->execute();
        $requirementsResult = $stmt->get_result();

        $requirementsStatus = [];
        while ($reqRow = $requirementsResult->fetch_assoc()) {
            $requirementsStatus[$reqRow['req_type']] = $reqRow['status'];
        }

        // get all student from the same company and batch to calculate remaining slots
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS count FROM student_profiles
            WHERE company_uuid = ? AND batch_uuid = ?
        ");

        $stmt->bind_param('ss', $row['company_uuid'], $safeBatch);
        $stmt->execute();
        $studentCountResult = $stmt->get_result();
        $studentCount = $studentCountResult->fetch_assoc()['count'];

        $remainingSlots = max(0, (int)$row['total_slots'] - $studentCount);

        $applications[] = [
            'uuid'             => $row['uuid'],
            'status'           => $row['status'],
            'status_label'     => match($row['status']) {
                'pending'        => 'Pending review',
                'approved'       => 'Approved',
                'endorsed'       => 'Endorsed',
                'active'         => 'OJT Active',
                'needs_revision' => 'Needs revision',
                'rejected'       => 'Rejected',
                'withdrawn'      => 'Withdrawn',
                default          => 'Unknown',
            },
            'student_uuid'     => $row['student_uuid'],
            'full_name'        => $row['first_name'] . ' ' . $row['last_name'],
            'initials'         => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)),
            'student_number'   => $row['student_number'],
            'program_code'     => $row['program_code'] ?? '—',
            'year_label'       => ordinal((int)$row['year_level']) . ' Year',
            'year_level'      => $row['year_level'],
            'section'          => $row['section']      ?? '—',
            'company_name'     => $row['company_name'],
            'work_setup'       => $row['work_setup'],
            'city'             => $row['city']          ?? '—',
            'preferred_dept'   => $row['preferred_dept'] ?? '—',
            'coordinator_note' => $row['coordinator_note'] ?? null,
            'submitted_at'     => date('M j, Y', strtotime($row['created_at'])),
            'reviewed_at'      => $row['reviewed_at']
                                    ? date('M j, Y', strtotime($row['reviewed_at']))
                                    : null,
            'can_approve'      => in_array('approved', VALID_TRANSITIONS[$row['status']] ?? []),
            'can_return'       => in_array('needs_revision', VALID_TRANSITIONS[$row['status']] ?? []),
            'can_reject'       => in_array('rejected', VALID_TRANSITIONS[$row['status']] ?? []),
            'card_one'        => [
                'student_name'  => $row['first_name'] . ' ' . $row['last_name'],
                'student_No'    => $row['student_number'],
                'program'       => $row['program_code'] ?? '—',
                'course_Section' => $row['year_level'] && $row['section'] ? $row['year_level'] . '' . $row['section'] : '—',
                'mobile'        => $row['mobile'] ?? '—',
                'email'         => $row['email']  ?? '—',
            ],
            'card_two'        => [
                'company_name' => $row['company_name'],
                'work_setup'   => $row['work_setup'],
                'city'         => $row['city'] ?? '—',
                'industry'     => $row['industry'] ?? '—',
                'slots_info'   => isset($row['total_slots']) ? "$remainingSlots remaining" : 'Slots: N/A',
                'accepted_programs' => $acceptedPrograms,
            ],
            'card_three'      => [
                'submitted_at' => date('M j, Y', strtotime($row['created_at'])),
                'preferred_dept' => $row['preferred_dept'] ?? '—',
                'cover_letter' => $row['cover_letter'] ?? '—',
            ],
            'card_four'       => [
                'requirements' => $requirementsStatus
            ]
        ];
    }

    return $applications;
}


// -----------------------------------------------
// GET status log for an application
// -----------------------------------------------
function getApplicationStatusLog($conn, string $appUuid): array
{
    $stmt = $conn->prepare("
        SELECT
          asl.from_status,
          asl.to_status,
          asl.note,
          asl.created_at,
          CASE u.role
            WHEN 'student'     THEN CONCAT(sp.first_name, ' ', sp.last_name)
            WHEN 'coordinator' THEN CONCAT(cp.first_name, ' ', cp.last_name)
            WHEN 'admin'       THEN CONCAT(ap.first_name, ' ', ap.last_name)
            ELSE 'System'
          END AS changed_by_name,
          u.role AS changed_by_role
        FROM application_status_logs asl
        LEFT JOIN users u               ON asl.changed_by     = u.uuid
        LEFT JOIN student_profiles sp   ON u.uuid = sp.user_uuid AND u.role = 'student'
        LEFT JOIN coordinator_profiles cp ON u.uuid = cp.user_uuid AND u.role = 'coordinator'
        LEFT JOIN admin_profiles ap     ON u.uuid = ap.user_uuid AND u.role = 'admin'
        WHERE asl.application_uuid = ?
        ORDER BY asl.created_at ASC
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(fn ($row) => [
        'from_status'     => $row['from_status'],
        'to_status'       => $row['to_status'],
        'note'            => $row['note'] ?? '',
        'changed_by'      => $row['changed_by_name'] ?? 'System',
        'changed_by_role' => $row['changed_by_role'] ?? '',
        'date'            => date('M j, Y g:i A', strtotime($row['created_at'])),
    ], $rows);
}

function getAllCompanies($conn, string $batchUuid = null): array
{
    if (empty($batchUuid)) {
        $batchResult = $conn->query("
            SELECT uuid FROM batches WHERE status = 'active' LIMIT 1
        ");
        $batchRow  = $batchResult->fetch_assoc();
        $batchUuid = $batchRow['uuid'] ?? null;
    }

    $safeBatchUuid = $conn->real_escape_string($batchUuid ?? '');

    $result = $conn->query("
        SELECT
          c.uuid,
          c.name,
          c.industry,
          c.city,
          c.work_setup,
          c.accreditation_status,
          c.created_at,

          cc.name  AS contact_name,
          cc.email AS contact_email,
          cc.phone AS contact_phone,

          cs.total_slots,
          COUNT(DISTINCT sp.id) AS filled_slots,

          GROUP_CONCAT(DISTINCT p.code ORDER BY p.code SEPARATOR ', ') AS accepted_programs,
          GROUP_CONCAT(DISTINCT p.uuid ORDER BY p.code SEPARATOR ',') AS accepted_program_uuids,

          MAX(cd.valid_until) AS moa_expiry

        FROM companies c
        LEFT JOIN company_contacts cc
          ON c.uuid = cc.company_uuid AND cc.is_primary = 1
        LEFT JOIN company_slots cs
          ON c.uuid = cs.company_uuid
          AND cs.batch_uuid = '{$safeBatchUuid}'
        LEFT JOIN student_profiles sp
          ON c.uuid = sp.company_uuid
          AND sp.batch_uuid = '{$safeBatchUuid}'
        LEFT JOIN company_accepted_programs cap
          ON c.uuid = cap.company_uuid
        LEFT JOIN programs p
          ON cap.program_uuid = p.uuid AND p.is_active = 1
        LEFT JOIN company_documents cd
          ON c.uuid = cd.company_uuid AND cd.doc_type = 'moa'

        GROUP BY c.id
        ORDER BY c.accreditation_status ASC, c.name ASC
    ");

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $moaExpiry    = $row['moa_expiry'];
        $daysToExpiry = $moaExpiry
            ? (int) ceil((strtotime($moaExpiry) - time()) / 86400)
            : null;

        $companies[] = [
            'uuid'                 => $row['uuid'],
            'name'                 => $row['name'],
            'industry'             => $row['industry'] ?? '—',
            'city'                 => $row['city']     ?? '—',
            'work_setup'           => $row['work_setup'],
            'accreditation_status' => $row['accreditation_status'],
            'status_label'         => ucfirst($row['accreditation_status']),
            'contact_name'         => $row['contact_name']  ?? '—',
            'contact_email'        => $row['contact_email'] ?? '—',
            'contact_phone'        => $row['contact_phone'] ?? '—',
            'total_slots'          => (int) ($row['total_slots']  ?? 0),
            'filled_slots'         => (int) ($row['filled_slots'] ?? 0),
            'remaining_slots'      => max(0, (int)($row['total_slots'] ?? 0) - (int)($row['filled_slots'] ?? 0)),
            'accepted_programs'    => $row['accepted_programs'] ?? '—',
            'accepted_program_uuids' => $row['accepted_program_uuids'] ?? '',
            'moa_expiry'           => $moaExpiry
                                        ? date('M j, Y', strtotime($moaExpiry))
                                        : null,
            'moa_days_left'        => $daysToExpiry,
            'moa_status'           => match(true) {
                $daysToExpiry === null => 'none',
                $daysToExpiry < 0     => 'expired',
                $daysToExpiry <= 30   => 'expiring',
                default               => 'valid',
            },
            'created_at'           => date('M j, Y', strtotime($row['created_at'])),
        ];
    }

    return $companies;
}

function activebatch($conn)
{
    $result = $conn->query("SELECT uuid, school_year, semester FROM batches WHERE status = 'active' LIMIT 1");
    return $result->fetch_assoc();
}

function UUID_convert($conn, $uuid, $table): ?string
{
    $table = isset($table) && in_array($table, ['student_profiles', 'coordinator_profiles', 'admin_profiles']) ? $table : 'coordinator_profiles';
    $stmt = $conn->prepare("SELECT uuid FROM {$table} WHERE user_uuid = ? LIMIT 1");
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    $result = $stmt->get_result();
    $coordinatorProfileUuid = null;
    if ($result->num_rows > 0) {
        $coordinatorProfileUuid = $result->fetch_assoc()['uuid'];
    }

    return $coordinatorProfileUuid;
}

function getApplicationStatusCount($conn, string $coordinatorUuid, string $batchUuid): array
{
    $safeCoord = $conn->real_escape_string($coordinatorUuid);
    $safeBatch = $conn->real_escape_string($batchUuid);

    $result = $conn->query("
        SELECT a.status, COUNT(*) AS count
        FROM ojt_applications a
        JOIN student_profiles sp ON a.student_uuid = sp.uuid
        WHERE a.batch_uuid = '{$safeBatch}' AND sp.coordinator_uuid = '{$safeCoord}'
        GROUP BY a.status
    ");

    $counts = [
        'pending' => 0,
        'approved' => 0,
        'endorsed' => 0,
        'active' => 0,
        'needs_revision' => 0,
        'rejected' => 0,
        'withdrawn' => 0,
    ];

    while ($row = $result->fetch_assoc()) {
        $status = $row['status'];
        if (isset($counts[$status])) {
            $counts[$status] = (int) $row['count'];
        }
    }

    return $counts;
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

$action = isset($_POST['action']) ? $_POST['action'] : null;

if (!$action) {
    response([
        'status' => 'error',
        'message' => 'No action specified.'
    ]);
}

if ($action === 'fetch_company_list') {
    $batch = activebatch($conn);
    if (!$batch) {
        response([
            'status' => 'error',
            'message' => 'No active batch found.'
        ]);
    }

    $companies = getAllCompanies($conn, $batch['uuid']);

    response([
        'status' => 'success',
        'data' => $companies,
    ]);

} elseif ($action === 'get_applications') {
    $batch = activebatch($conn);
    if (!$batch) {
        response([
            'status' => 'error',
            'message' => 'No active batch found.'
        ]);
    }

    $coordinatorProfileUuid = UUID_convert($conn, $_SESSION['user']['uuid'], 'coordinator_profiles');
    if (!$coordinatorProfileUuid) {
        response([
            'status' => 'error',
            'message' => 'Coordinator profile not found.'
        ]);
    }
    $filters = [
        'status' => isset($_POST['status']) && in_array($_POST['status'], ['pending', 'approved', 'endorsed', 'active', 'needs_revision', 'rejected', 'withdrawn']) ? $_POST['status'] : null,
    ];

    $statusCounts = getApplicationStatusCount($conn, $coordinatorProfileUuid, $batch['uuid']);

    $applications = getApplications($conn, $coordinatorProfileUuid, $batch['uuid'], $filters);
    response([
        'status' => 'success',
        'data' => $applications,
        'status_counts' => $statusCounts,
    ]);
} elseif ($action === 'submit_application') {
    $batch = activebatch($conn);
    if (!$batch) {
        response([
            'status' => 'error',
            'message' => 'No active batch found.'
        ]);
    }

    $studentProfileUuid = UUID_convert($conn, $_SESSION['user']['uuid'], 'student_profiles');
    if (!$studentProfileUuid) {
        response([
            'status' => 'error',
            'message' => 'Student profile not found.'
        ]);
    }

    $companyUuid   = $_POST['company_id']    ?? '';
    $preferredDept = $_POST['preferred_department']  ?? '';
    $coverLetter   = $_POST['cover_letter']    ?? '';

    $result = submitApplication(
        conn: $conn,
        data: [
            'company_uuid'   => $companyUuid,
            'batch_uuid'     => $batch['uuid'],
            'preferred_dept' => $preferredDept,
            'cover_letter'   => $coverLetter,
        ],
        studentProfileUuid: $studentProfileUuid,
        actorUuid: $_SESSION['user']['uuid']
    );

    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
            'application_uuid' => $result['uuid'],
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to submit application.',
            'errors' => $result['errors'] ?? ['general' => 'An unknown error occurred.']
        ]);
    }
} elseif ($action === 'get_application_status_details') {
    $studentProfileUuid = UUID_convert($conn, $_SESSION['user']['uuid'], 'student_profiles');
    if (!$studentProfileUuid) {
        response([
            'status' => 'error',
            'message' => 'Student profile not found.'
        ]);
    }

    $batch = activebatch($conn);
    if (!$batch) {
        response([
            'status' => 'error',
            'message' => 'No active batch found.'
        ]);
    }

    $application = getStudentApplication($conn, $studentProfileUuid, $batch['uuid']);

    if ($application) {
        $statusLog = getApplicationStatusLog($conn, $application['uuid']);
        response([
            'status' => 'success',
            'data' => [
                'application' => $application,
                'status_log' => $statusLog,
            ],
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'No application found for the active batch.'
        ]);
    }

} elseif ($action === 'approve_application') {
    $appUuid = $_POST['application_uuid'] ?? '';
    $note    = $_POST['note'] ?? '';

    $result = approveApplication($conn, $appUuid, $note, $_SESSION['user']['uuid']);

    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Application approved successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['error'] ?? 'Failed to approve application.'
        ]);
    }
} elseif ($action === 'resubmit_application') {
    $appUuid = $_POST['application_uuid'] ?? '';
    $note    = $_POST['note'] ?? '';

    $companyUuid   = $_POST['company_id']    ?? '';
    $preferredDept = $_POST['preferred_department']  ?? '';

    $result = resubmitApplication($conn, $appUuid, $companyUuid, $preferredDept, $note, $_SESSION['user']['uuid']);

    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Application resubmitted successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['errors'] ? implode(' ', $result['errors']) : 'Failed to resubmit application.'
        ]);
    }
} elseif ($action === 'return_application') {
    $appUuid = $_POST['application_uuid'] ?? '';
    $note    = $_POST['note'] ?? '';

    $result = returnApplication($conn, $appUuid, $note, $_SESSION['user']['uuid']);

    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Application returned for revision successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['error'] ?? 'Failed to return application.'
        ]);
    }
} elseif ($action === 'reject_application') {
    $appUuid = $_POST['application_uuid'] ?? '';
    $note    = $_POST['note'] ?? '';

    $result = rejectApplication($conn, $appUuid, $note, $_SESSION['user']['uuid']);

    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Application rejected successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['error'] ?? 'Failed to reject application.'
        ]);
    }
} elseif ($action === 'withdraw_application') {
    $appUuid = $_POST['application_uuid'] ?? '';

    $studentProfileUuid = UUID_convert($conn, $_SESSION['user']['uuid'], 'student_profiles');
    if (!$studentProfileUuid) {
        response([
            'status' => 'error',
            'message' => 'Student profile not found.'
        ]);
    }

    $result = withdrawApplication($conn, $appUuid, $studentProfileUuid, $_SESSION['user']['uuid']);

    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Application withdrawn successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => $result['error'] ?? 'Failed to withdraw application.'
        ]);
    }
} else {
    response([
        'status' => 'error',
        'message' => 'Invalid action specified.'
    ]);
}
