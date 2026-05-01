<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/requirement_functions.php';

const VALID_TRANSITIONS = [
    'pending'       => ['approved', 'needs_revision', 'rejected', 'withdrawn'],
    'approved'      => ['endorsed'],
    'endorsed'      => ['active'],
    'needs_revision' => ['pending', 'withdrawn'],
    'rejected'      => [],
    'withdrawn'     => [],
    'active'        => [],
];

const TRANSITION_ACTOR = [
    'pending'    => 'coordinator',
    'approved'   => 'coordinator',
    'endorsed'   => 'system',
    'active'     => 'coordinator',
    'needs_revision' => 'coordinator',
    'rejected'   => 'coordinator',
    'withdrawn'  => 'student',
];

function getAvailableCompaniesForStudent(
    $conn,
    string $programUuid,
    string $batchUuid
): array {
    $safeProgram = $conn->real_escape_string($programUuid);
    $safeBatch   = $conn->real_escape_string($batchUuid);

    $result = $conn->query("
        SELECT
          c.uuid,
          c.name,
          c.industry,
          c.city,
          c.address,
          c.work_setup,
          c.website,
          c.email      AS company_email,
          c.phone      AS company_phone,

          cs.total_slots,
          COUNT(DISTINCT sp.id) AS filled_slots,
          (cs.total_slots - COUNT(DISTINCT sp.id)) AS remaining_slots,

          cc.name     AS contact_name,
          cc.position AS contact_position,

          GROUP_CONCAT(
            DISTINCT p2.code ORDER BY p2.code SEPARATOR ', '
          ) AS accepted_programs

        FROM companies c
        JOIN company_accepted_programs cap
          ON cap.company_uuid  = c.uuid
          AND cap.program_uuid = '{$safeProgram}'
        JOIN company_slots cs
          ON cs.company_uuid = c.uuid
          AND cs.batch_uuid  = '{$safeBatch}'
        LEFT JOIN student_profiles sp
          ON sp.company_uuid = c.uuid
          AND sp.batch_uuid  = '{$safeBatch}'
        LEFT JOIN company_contacts cc
          ON cc.company_uuid = c.uuid
          AND cc.is_primary  = 1
        LEFT JOIN company_accepted_programs cap2
          ON cap2.company_uuid = c.uuid
        LEFT JOIN programs p2
          ON cap2.program_uuid = p2.uuid
          AND p2.is_active = 1
        WHERE c.accreditation_status = 'active'
        GROUP BY c.id
        HAVING remaining_slots > 0
        ORDER BY c.name ASC
    ");

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = [
            'uuid'              => $row['uuid'],
            'name'              => $row['name'],
            'industry'          => $row['industry']         ?? '—',
            'city'              => $row['city']             ?? '—',
            'address'           => $row['address']          ?? '—',
            'work_setup'        => $row['work_setup'],
            'work_setup_label'  => ucfirst($row['work_setup']),
            'website'           => $row['website']          ?? null,
            'company_email'     => $row['company_email']    ?? null,
            'company_phone'     => $row['company_phone']    ?? null,
            'contact_name'      => $row['contact_name']     ?? '—',
            'contact_position'  => $row['contact_position'] ?? '—',
            'total_slots'       => (int) $row['total_slots'],
            'filled_slots'      => (int) $row['filled_slots'],
            'remaining_slots'   => (int) $row['remaining_slots'],
            'accepted_programs' => $row['accepted_programs'] ?? '—',
        ];
    }

    return $companies;
}

function getStudentApplication($conn, string $studentUuid, string $batchUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          a.uuid,
          a.status,
          a.cover_letter,
          a.rejection_reason,
          a.revision_reason,
          a.preferred_department,
          a.created_at,
          a.updated_at,

          c.uuid AS company_uuid,
          c.name AS company_name,
          c.city,
          c.work_setup,
          c.industry,
          
          sp.mobile,
          u.email AS student_email,
          
          (SELECT total_slots FROM company_slots WHERE company_uuid = c.uuid AND batch_uuid = a.batch_uuid LIMIT 1) AS total_slots,
          (SELECT COUNT(*) FROM student_profiles WHERE company_uuid = c.uuid AND batch_uuid = a.batch_uuid) AS filled_slots,
          (SELECT GROUP_CONCAT(p.code SEPARATOR ', ') FROM company_accepted_programs cap JOIN programs p ON cap.program_uuid = p.uuid WHERE cap.company_uuid = c.uuid) AS accepted_programs

        FROM ojt_applications a
        JOIN companies c         ON a.company_uuid = c.uuid
        JOIN student_profiles sp ON a.student_uuid = sp.uuid
        JOIN users u             ON sp.user_uuid    = u.uuid
        WHERE a.student_uuid = ?
          AND a.batch_uuid   = ?
        ORDER BY a.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? formatApplication($row) : null;
}

function getApplicationHistory($conn, string $applicationUuid): array
{
    $stmt = $conn->prepare("
        SELECT
          asl.uuid,
          asl.from_status,
          asl.to_status,
          asl.reason,
          asl.created_at,

          CASE u.role
            WHEN 'coordinator' THEN CONCAT(cp.first_name, ' ', cp.last_name)
            WHEN 'student'     THEN CONCAT(sp.first_name, ' ', sp.last_name)
            ELSE 'System'
          END AS actor_name,
          u.role AS actor_role,
          CASE u.role
            WHEN 'coordinator' THEN cp.profile_name
            WHEN 'student'     THEN sp.profile_name
            ELSE NULL
          END AS profile_pic

        FROM application_status_logs asl
        LEFT JOIN users u ON asl.actor_uuid = u.uuid
        LEFT JOIN coordinator_profiles cp ON u.uuid = cp.user_uuid AND u.role = 'coordinator'
        LEFT JOIN student_profiles sp     ON u.uuid = sp.user_uuid AND u.role = 'student'
        WHERE asl.application_uuid = ?
        ORDER BY asl.created_at ASC
    ");
    $stmt->bind_param('s', $applicationUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(fn ($row) => [
        'from_status' => $row['from_status'] ?? null,
        'to_status'   => $row['to_status'],
        'reason'      => $row['reason']      ?? null,
        'actor_name'  => trim($row['actor_name']),
        'actor_role'  => $row['actor_role']  ?? 'system',
        'profile_pic' => $row['profile_pic'] ?? null,
        'created_at'  => date('M j, Y g:i A', strtotime($row['created_at'])),
        'time_ago'    => timeAgo($row['created_at']),
    ], $rows);
}

function getAllApplications(
    $conn,
    string $batchUuid,
    string $coordinatorUuid = null,
    array  $filters = []
): array {
    $safeBatch = $conn->real_escape_string($batchUuid);
    $conditions = ["a.batch_uuid = '{$safeBatch}'"];

    if ($coordinatorUuid) {
        $safeCoord    = $conn->real_escape_string($coordinatorUuid);
        $conditions[] = "sp.coordinator_uuid = '{$safeCoord}'";
    }

    if (!empty($filters['status'])) {
        $safeStatus   = $conn->real_escape_string($filters['status']);
        $conditions[] = "a.status = '{$safeStatus}'";
    }

    if (!empty($filters['search'])) {
        $s            = $conn->real_escape_string($filters['search']);
        $conditions[] = "(sp.first_name LIKE '%{$s}%' OR sp.last_name LIKE '%{$s}%' OR c.name LIKE '%{$s}%')";
    }

    $where  = implode(' AND ', $conditions);
    $result = $conn->query("
        SELECT
          a.uuid,
          a.status,
          a.cover_letter,
          a.rejection_reason,
          a.revision_reason,
          a.preferred_department,
          a.created_at,
          a.updated_at,

          sp.uuid       AS student_uuid,
          sp.first_name,
          sp.last_name,
          sp.student_number,
          sp.year_level,

          p.code        AS program_code,

          c.uuid        AS company_uuid,
          c.name        AS company_name,
          c.city,
          c.work_setup,
          c.industry,

          sp.mobile,
          u.email       AS student_email,

          (SELECT total_slots FROM company_slots WHERE company_uuid = c.uuid AND batch_uuid = a.batch_uuid LIMIT 1) AS total_slots,
          (SELECT COUNT(*) FROM student_profiles WHERE company_uuid = c.uuid AND batch_uuid = a.batch_uuid) AS filled_slots,
          (SELECT GROUP_CONCAT(p.code SEPARATOR ', ') FROM company_accepted_programs cap JOIN programs p ON cap.program_uuid = p.uuid WHERE cap.company_uuid = c.uuid) AS accepted_programs

        FROM ojt_applications a
        JOIN student_profiles sp ON a.student_uuid  = sp.uuid
        JOIN users u             ON sp.user_uuid     = u.uuid
        JOIN companies c         ON a.company_uuid  = c.uuid
        LEFT JOIN programs p     ON sp.program_uuid = p.uuid
        WHERE {$where}
        ORDER BY
          FIELD(a.status,'pending','needs_revision','approved','endorsed','active','rejected','withdrawn'),
          a.updated_at DESC
    ");

    $applications = [];
    while ($row = $result->fetch_assoc()) {
        $app                    = formatApplication($row);
        $app['student_uuid']    = $row['student_uuid'];
        $app['full_name']       = $row['first_name'] . ' ' . $row['last_name'];
        $app['initials']        = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
        $app['student_number']  = $row['student_number'];
        $app['year_label']      = ordinal((int)$row['year_level']) . ' Year';
        $app['program_code']    = $row['program_code'] ?? '—';
        $app['preferred_department'] = $row['preferred_department'] ?? '—';
        $applications[]         = $app;
    }

    return $applications;
}

function submitApplication(
    $conn,
    string $studentUuid,
    string $batchUuid,
    string $companyUuid,
    string $coverLetter,
    string $programUuid,
    string $actorUserUuid,
    string $preferredDepartment = ''
): array {
    if (!canStudentApply($conn, $studentUuid, $batchUuid)) {
        return [
            'success' => false,
            'error'   => 'All 6 pre-OJT requirements must be approved before applying.',
        ];
    }
    $stmt = $conn->prepare("
        SELECT uuid, status FROM ojt_applications
        WHERE student_uuid = ? AND batch_uuid = ?
          AND status NOT IN ('rejected','withdrawn')
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentUuid, $batchUuid);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        return [
            'success' => false,
            'error'   => 'You already have an active application (' . $existing['status'] . '). Withdraw it before applying elsewhere.',
        ];
    }

    $safeBatch   = $conn->real_escape_string($batchUuid);
    $safeCompany = $conn->real_escape_string($companyUuid);
    $safeProgram = $conn->real_escape_string($programUuid);

    $result = $conn->query("
        SELECT
          cs.total_slots,
          COUNT(DISTINCT sp.id) AS filled_slots
        FROM companies c
        JOIN company_slots cs
          ON cs.company_uuid = c.uuid AND cs.batch_uuid = '{$safeBatch}'
        JOIN company_accepted_programs cap
          ON cap.company_uuid = c.uuid AND cap.program_uuid = '{$safeProgram}'
        LEFT JOIN student_profiles sp
          ON sp.company_uuid = c.uuid AND sp.batch_uuid = '{$safeBatch}'
        WHERE c.uuid = '{$safeCompany}'
          AND c.accreditation_status = 'active'
        GROUP BY c.id
        HAVING filled_slots < cs.total_slots
        LIMIT 1
    ");

    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'error'   => 'This company is no longer available. It may be full or no longer accredited.',
        ];
    }

    $appUuid    = generateUuid();
    $coverLetter = trim($coverLetter);

    $stmt = $conn->prepare("
        INSERT INTO ojt_applications
          (uuid, student_uuid, batch_uuid, company_uuid, cover_letter, preferred_department, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param(
        'ssssss',
        $appUuid,
        $studentUuid,
        $batchUuid,
        $companyUuid,
        $coverLetter,
        $preferredDepartment
    );
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, null, 'pending', null, $actorUserUuid);

    logActivity(
        conn: $conn,
        eventType: 'application_submitted',
        description: 'Student submitted OJT application',
        module: 'applications',
        actorUuid: $actorUserUuid,
        targetUuid: $appUuid
    );

    return ['success' => true, 'uuid' => $appUuid];
}

function transitionApplication(
    $conn,
    string $appUuid,
    string $newStatus,
    string $actorUserUuid,
    string $actorProfileUuid,
    string $actorRole,
    string $reason = '',
    array  $meta = []
): array {
    $stmt = $conn->prepare("
        SELECT a.uuid, a.status, a.student_uuid, a.company_uuid, a.batch_uuid,
               sp.coordinator_uuid
        FROM ojt_applications a
        JOIN student_profiles sp ON a.student_uuid = sp.uuid
        WHERE a.uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    $currentStatus = $app['status'];

    // validate transition is allowed
    $allowed = VALID_TRANSITIONS[$currentStatus] ?? [];
    if (!in_array($newStatus, $allowed)) {
        return [
            'success' => false,
            'error'   => "Cannot transition from {$currentStatus} to {$newStatus}.",
        ];
    }

    // validate actor role
    $expectedActor = TRANSITION_ACTOR[$newStatus] ?? null;
    if ($expectedActor && $expectedActor !== 'system' && $expectedActor !== $actorRole) {
        return [
            'success' => false,
            'error'   => "Only a {$expectedActor} can perform this action.",
        ];
    }

    // coordinator scope — can only act on own students
    if ($actorRole === 'coordinator' && $app['coordinator_uuid'] !== $actorProfileUuid) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    // student scope — can only act on own application
    if ($actorRole === 'student' && $app['student_uuid'] !== $actorProfileUuid) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    // require reason for certain transitions
    if (in_array($newStatus, ['needs_revision', 'rejected', 'withdrawn']) && empty($reason)) {
        return ['success' => false, 'error' => 'A reason is required for this action.'];
    }

    if ($newStatus === 'approved' && !canStudentApply($conn, $app['student_uuid'], $app['batch_uuid'])) {
        return [
            'success' => false,
            'error'   => 'Cannot approve application: Student has not finished all 6 requirements.',
        ];
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(" 
            UPDATE ojt_applications
            SET status = ?,
                revision_reason = CASE WHEN ? = 'needs_revision' THEN ? ELSE NULL END,
                rejection_reason = CASE WHEN ? = 'rejected' THEN ? ELSE NULL END,
                updated_at = NOW()
            WHERE uuid = ?
        ");
        $stmt->bind_param(
            'ssssss',
            $newStatus,
            $newStatus,
            $reason,
            $newStatus,
            $reason,
            $appUuid
        );
        $stmt->execute();
        $stmt->close();

        if ($newStatus === 'approved') {
            // reserve slot — set company_uuid on student profile
            $stmt = $conn->prepare(" 
                UPDATE student_profiles
                SET company_uuid = ?
                WHERE uuid = ?
            ");
            $stmt->bind_param('ss', $app['company_uuid'], $app['student_uuid']);
            $stmt->execute();
            $stmt->close();

            // auto-generate endorsement letter on approval
            require_once __DIR__ . '/endorsement_functions.php';
            $letter = generateEndorsementLetter($conn, $appUuid, $actorUserUuid);
            if (!$letter['success']) {
                throw new Exception($letter['error'] ?? 'Failed to generate endorsement letter.');
            }
        }

        if (in_array($newStatus, ['rejected', 'withdrawn'])) {
            // release slot and supervisor assignment
            $stmt = $conn->prepare(" 
                UPDATE student_profiles
                SET company_uuid = NULL,
                    supervisor_uuid = NULL
                WHERE uuid = ?
            ");
            $stmt->bind_param('s', $app['student_uuid']);
            $stmt->execute();
            $stmt->close();
        }

        // log status history
        logApplicationStatus($conn, $appUuid, $currentStatus, $newStatus, $reason, $actorUserUuid);

        logActivity(
            conn: $conn,
            eventType: 'application_' . $newStatus,
            description: "Application status changed: {$currentStatus} → {$newStatus}" . ($reason ? " — {$reason}" : ''),
            module: 'applications',
            actorUuid: $actorUserUuid,
            targetUuid: $appUuid
        );

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }

    return ['success' => true, 'new_status' => $newStatus];
}

function resubmitApplication(
    $conn,
    string $appUuid,
    string $actorUserUuid,
    string $studentUuid,
    string $coverLetter,
    string $companyUuid = '',
    string $preferredDepartment = ''
): array {
    $stmt = $conn->prepare("
        SELECT uuid, status, student_uuid, company_uuid FROM ojt_applications
        WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $appUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if ($app['student_uuid'] !== $studentUuid) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    if ($app['status'] !== 'needs_revision') {
        return [
            'success' => false,
            'error'   => 'Only applications with needs_revision status can be resubmitted.',
        ];
    }

    $coverLetter = trim($coverLetter);
    $finalCompanyUuid = !empty($companyUuid) ? $companyUuid : $app['company_uuid'];

    $stmt = $conn->prepare("
        UPDATE ojt_applications
        SET status               = 'pending',
            cover_letter         = ?,
            company_uuid         = ?,
            preferred_department = ?,
            revision_reason      = NULL,
            updated_at           = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('ssss', $coverLetter, $finalCompanyUuid, $preferredDepartment, $appUuid);
    $stmt->execute();
    $stmt->close();

    logApplicationStatus($conn, $appUuid, 'needs_revision', 'pending', 'Student resubmitted', $actorUserUuid);

    return ['success' => true];
}

function logApplicationStatus(
    $conn,
    string  $appUuid,
    ?string $fromStatus,
    string  $toStatus,
    ?string $reason,
    string  $actorUuid
): void {
    $uuid = generateUuid();
    $stmt = $conn->prepare("
        INSERT INTO application_status_logs
          (uuid, application_uuid, from_status, to_status, reason, actor_uuid)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssss', $uuid, $appUuid, $fromStatus, $toStatus, $reason, $actorUuid);
    $stmt->execute();
    $stmt->close();
}

function formatApplication(array $row): array
{
    $statusColors = [
        'pending'        => ['bg' => '#EFF6FF', 'text' => '#185FA5'],
        'approved'       => ['bg' => '#E1F5EE', 'text' => '#0F6E56'],
        'endorsed'       => ['bg' => '#E1F5EE', 'text' => '#0F6E56'],
        'active'         => ['bg' => '#E1F5EE', 'text' => '#0F6E56'],
        'needs_revision' => ['bg' => '#FEF9EE', 'text' => '#BA7517'],
        'rejected'       => ['bg' => '#FEF2F2', 'text' => '#DC2626'],
        'withdrawn'      => ['bg' => '#F3F4F6', 'text' => '#6B7280'],
    ];

    $status = $row['status'];
    $colors = $statusColors[$status] ?? ['bg' => '#F3F4F6', 'text' => '#6B7280'];

    return [
        'uuid'              => $row['uuid'],
        'status'            => $status,
        'status_label'      => ucwords(str_replace('_', ' ', $status)),
        'status_bg'         => $colors['bg'],
        'status_text'       => $colors['text'],
        'cover_letter'      => $row['cover_letter']      ?? '',
        'preferred_department' => $row['preferred_department'] ?? '—',
        'rejection_reason'  => $row['rejection_reason']  ?? null,
        'revision_reason'   => $row['revision_reason']   ?? null,
        'company_uuid'      => $row['company_uuid'],
        'company_name'      => $row['company_name'],
        'company_city'      => $row['city']              ?? '—',
        'work_setup'        => $row['work_setup']        ?? '—',
        'industry'          => $row['industry']          ?? '—',
        'student_email'     => $row['student_email']     ?? '—',
        'student_mobile'    => $row['mobile']            ?? '—',
        'total_slots'       => $row['total_slots']       ?? 0,
        'filled_slots'      => $row['filled_slots']      ?? 0,
        'remaining_slots'   => ($row['total_slots'] ?? 0) - ($row['filled_slots'] ?? 0),
        'accepted_programs' => $row['accepted_programs'] ?? '—',
        'created_at'        => date('M j, Y', strtotime($row['created_at'])),
        'updated_at'        => date('M j, Y g:i A', strtotime($row['updated_at'] ?? $row['created_at'])),
        'time_ago'          => timeAgo($row['updated_at'] ?? $row['created_at']),
        // what actions are available — used by JS to show/hide buttons
        'can_withdraw'      => in_array($status, ['pending', 'needs_revision']),
        'can_resubmit'      => $status === 'needs_revision',
        'is_terminal'       => in_array($status, ['rejected', 'withdrawn', 'active']),
    ];
}
