<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function getAllBatches($conn): array
{
    $result = $conn->query("
        SELECT
          b.uuid,
          b.school_year,
          b.semester,
          b.start_date,
          b.end_date,
          b.required_hours,
          b.status,
          b.created_at,
          b.activated_at,
          b.closed_at,

          COUNT(DISTINCT sp.id) AS student_count,

          CONCAT(
            COALESCE(ap.first_name, ''),
            ' ',
            COALESCE(ap.last_name, '')
          ) AS created_by_name

        FROM batches b
        LEFT JOIN student_profiles sp ON b.uuid = sp.batch_uuid
        LEFT JOIN users u             ON b.created_by = u.uuid
        LEFT JOIN admin_profiles ap   ON u.uuid = ap.user_uuid

        GROUP BY b.id
        ORDER BY
          FIELD(b.status, 'active', 'upcoming', 'closed'),
          b.created_at DESC
    ");

    $batches = [];
    while ($row = $result->fetch_assoc()) {
        $batches[] = formatBatch($row);
    }

    return $batches;
}

function getBatch($conn, string $batchUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          b.*,
          COUNT(DISTINCT sp.id) AS student_count
        FROM batches b
        LEFT JOIN student_profiles sp ON b.uuid = sp.batch_uuid
        WHERE b.uuid = ?
        GROUP BY b.id
        LIMIT 1
    ");
    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? formatBatch($row) : null;
}

function getActiveBatch($conn): ?array
{
    $result = $conn->query("
        SELECT uuid, school_year, semester, start_date, end_date, required_hours
        FROM batches
        WHERE status = 'active'
        LIMIT 1
    ");

    $row = $result->fetch_assoc();
    if (!$row) return null;

    return [
        'uuid'           => $row['uuid'],
        'label'          => "AY {$row['school_year']} {$row['semester']} Semester",
        'school_year'    => $row['school_year'],
        'semester'       => $row['semester'],
        'start_date'     => $row['start_date'],
        'end_date'       => $row['end_date'],
        'required_hours' => (int) $row['required_hours'],
    ];
}

function getBatchStudents($conn, string $batchUuid): array
{
    $stmt = $conn->prepare("
        SELECT
          sp.uuid           AS profile_uuid,
          sp.student_number,
          sp.first_name,
          sp.last_name,
          sp.year_level,
          sp.section,

          u.uuid            AS user_uuid,
          u.email,
          u.is_active,
          u.last_login_at,

          p.code            AS program_code,

          CONCAT(cp.first_name, ' ', cp.last_name) AS coordinator_name,

          CASE
            WHEN u.is_active = 0         THEN 'inactive'
            WHEN u.last_login_at IS NULL THEN 'never_logged_in'
            ELSE 'active'
          END AS account_status

        FROM student_profiles sp
        JOIN users u
          ON sp.user_uuid = u.uuid
        LEFT JOIN programs p
          ON sp.program_uuid = p.uuid
        LEFT JOIN coordinator_profiles cp
          ON sp.coordinator_uuid = cp.uuid
        WHERE sp.batch_uuid = ?
        ORDER BY sp.last_name ASC, sp.first_name ASC
    ");
    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(fn($row) => [
        'profile_uuid'   => $row['profile_uuid'],
        'user_uuid'      => $row['user_uuid'],
        'student_number' => $row['student_number'],
        'full_name'      => $row['first_name'] . ' ' . $row['last_name'],
        'initials'       => strtoupper(substr($row['first_name'],0,1) . substr($row['last_name'],0,1)),
        'email'          => $row['email'],
        'program_code'   => $row['program_code']     ?? '—',
        'year_label'     => ordinal((int)$row['year_level']) . ' Year',
        'section'        => $row['section']          ?? '—',
        'coordinator'    => $row['coordinator_name'] ?? '—',
        'is_active'      => (int) $row['is_active'],
        'account_status' => $row['account_status'],
        'status_label'   => match($row['account_status']) {
            'active'          => 'Active',
            'inactive'        => 'Inactive',
            'never_logged_in' => 'Never logged in',
            default           => 'Unknown',
        },
        'last_login'     => $row['last_login_at']
                              ? date('M j, Y', strtotime($row['last_login_at']))
                              : null,
    ], $rows);
}

function createBatch($conn, array $data, string $actorUuid): array
{
    $errors = [];

    $schoolYear    = trim($data['school_year']    ?? '');
    $semester      = trim($data['semester']       ?? '');
    $startDate     = trim($data['start_date']     ?? '');
    $endDate       = trim($data['end_date']       ?? '');
    $requiredHours = (int) ($data['required_hours'] ?? 486);
    $activateNow   = (int) ($data['activate_now']   ?? 0);

    // validate
    if (empty($schoolYear)) {
        $errors['school_year'] = 'School year is required.';
    } elseif (!preg_match('/^\d{4}-\d{4}$/', $schoolYear)) {
        $errors['school_year'] = 'Format must be YYYY-YYYY (e.g. 2025-2026).';
    } else {
        [$y1, $y2] = explode('-', $schoolYear);
        if ((int)$y2 !== (int)$y1 + 1) {
            $errors['school_year'] = 'Second year must be one year after the first.';
        }
    }

    if (empty($semester) || !in_array($semester, ['1st','2nd','Summer'])) {
        $errors['semester'] = 'Select a valid semester.';
    }

    if (empty($startDate)) {
        $errors['start_date'] = 'Start date is required.';
    }

    if (empty($endDate)) {
        $errors['end_date'] = 'End date is required.';
    }

    if (!empty($startDate) && !empty($endDate) && $startDate >= $endDate) {
        $errors['end_date'] = 'End date must be after start date.';
    }

    if ($requiredHours < 1) {
        $errors['required_hours'] = 'Required hours must be at least 1.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // check duplicate school year + semester
    $stmt = $conn->prepare("
        SELECT id FROM batches
        WHERE school_year = ? AND semester = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $schoolYear, $semester);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        return [
            'success' => false,
            'errors'  => ['semester' => "A batch for {$schoolYear} {$semester} Semester already exists."],
        ];
    }

    // generate UUID in PHP — MariaDB 10.4 has no LAST_INSERT_UUID()
    $batchUuid = generateUuid();

    $stmt = $conn->prepare("
        INSERT INTO batches
          (uuid, school_year, semester, start_date, end_date, required_hours, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, 'upcoming', ?)
    ");
    $stmt->bind_param(
        'ssssiis',
        $batchUuid, $schoolYear, $semester,
        $startDate, $endDate, $requiredHours,
        $actorUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'batch_created',
        description: "Admin created batch AY {$schoolYear} {$semester} Semester",
        module: 'system',
        actorUuid: $actorUuid,
        targetUuid: $batchUuid
    );

    // activate immediately if toggled
    if ($activateNow === 1) {
        activateBatch($conn, $batchUuid, $actorUuid);
    }

    return ['success' => true, 'uuid' => $batchUuid];
}

function updateBatch($conn, string $batchUuid, array $data, string $actorUuid): array
{
    // fetch current batch
    $stmt = $conn->prepare("SELECT status FROM batches WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
        return ['success' => false, 'errors' => ['general' => 'Batch not found.']];
    }

    if ($current['status'] === 'closed') {
        return ['success' => false, 'errors' => ['general' => 'Closed batches cannot be edited.']];
    }

    $errors = [];

    $schoolYear    = trim($data['school_year']    ?? '');
    $semester      = trim($data['semester']       ?? '');
    $startDate     = trim($data['start_date']     ?? '');
    $endDate       = trim($data['end_date']       ?? '');
    $requiredHours = (int) ($data['required_hours'] ?? 486);

    if (empty($schoolYear) || !preg_match('/^\d{4}-\d{4}$/', $schoolYear)) {
        $errors['school_year'] = 'Invalid school year format.';
    } else {
        [$y1, $y2] = explode('-', $schoolYear);
        if ((int)$y2 !== (int)$y1 + 1) {
            $errors['school_year'] = 'Second year must be one year after the first.';
        }
    }

    if (empty($semester) || !in_array($semester, ['1st','2nd','Summer'])) {
        $errors['semester'] = 'Select a valid semester.';
    }

    if (empty($startDate)) $errors['start_date'] = 'Start date is required.';
    if (empty($endDate))   $errors['end_date']   = 'End date is required.';

    if (!empty($startDate) && !empty($endDate) && $startDate >= $endDate) {
        $errors['end_date'] = 'End date must be after start date.';
    }

    if ($requiredHours < 1) {
        $errors['required_hours'] = 'Required hours must be at least 1.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // check duplicate — exclude current batch
    $stmt = $conn->prepare("
        SELECT id FROM batches
        WHERE school_year = ? AND semester = ? AND uuid != ?
        LIMIT 1
    ");
    $stmt->bind_param('sss', $schoolYear, $semester, $batchUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        return [
            'success' => false,
            'errors'  => ['semester' => 'A batch for this school year and semester already exists.'],
        ];
    }

    $stmt = $conn->prepare("
        UPDATE batches
        SET school_year    = ?,
            semester       = ?,
            start_date     = ?,
            end_date       = ?,
            required_hours = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param(
        'ssssis',
        $schoolYear, $semester,
        $startDate, $endDate, $requiredHours,
        $batchUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'other',
        description: "Admin updated batch AY {$schoolYear} {$semester} Semester",
        module: 'system',
        actorUuid: $actorUuid,
        targetUuid: $batchUuid
    );

    return ['success' => true, 'uuid' => $batchUuid];
}

function activateBatch($conn, string $batchUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT uuid, status, school_year, semester
        FROM batches WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$batch) {
        return ['success' => false, 'error' => 'Batch not found.'];
    }
    if ($batch['status'] === 'active') {
        return ['success' => false, 'error' => 'Batch is already active.'];
    }
    if ($batch['status'] === 'closed') {
        return ['success' => false, 'error' => 'A closed batch cannot be reactivated.'];
    }

    // close current active batch first
    $stmt = $conn->prepare("
        UPDATE batches
        SET status    = 'closed',
            closed_by = ?,
            closed_at = NOW()
        WHERE status = 'active'
    ");
    $stmt->bind_param('s', $actorUuid);
    $stmt->execute();
    $stmt->close();

    // activate new batch
    $stmt = $conn->prepare("
        UPDATE batches
        SET status       = 'active',
            activated_by = ?,
            activated_at = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $actorUuid, $batchUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'batch_activated',
        description: "Admin activated batch AY {$batch['school_year']} {$batch['semester']} Semester",
        module: 'system',
        actorUuid: $actorUuid,
        targetUuid: $batchUuid
    );

    return ['success' => true];
}

function closeBatch($conn, string $batchUuid, string $confirmText, string $actorUuid): array
{
    // Done in JS for better UX — but keep this check here as a fallback in case JS fails or is bypassed
    if (strtoupper(trim($confirmText)) !== 'CLOSE') {
        return ['success' => false, 'error' => 'Type CLOSE to confirm.'];
    }

    $stmt = $conn->prepare("
        SELECT uuid, status, school_year, semester
        FROM batches WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$batch) {
        return ['success' => false, 'error' => 'Batch not found.'];
    }
    if ($batch['status'] === 'closed') {
        return ['success' => false, 'error' => 'Batch is already closed.'];
    }
    if ($batch['status'] === 'upcoming') {
        return ['success' => false, 'error' => 'Cannot close an upcoming batch. Activate it first.'];
    }

    $stmt = $conn->prepare("
        UPDATE batches
        SET status    = 'closed',
            closed_by = ?,
            closed_at = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $actorUuid, $batchUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'batch_closed',
        description: "Admin closed batch AY {$batch['school_year']} {$batch['semester']} Semester",
        module: 'system',
        actorUuid: $actorUuid,
        targetUuid: $batchUuid
    );

    return ['success' => true];
}

function formatBatch(array $row): array
{
    return [
        'uuid'           => $row['uuid'],
        'label'          => "AY {$row['school_year']} {$row['semester']} Semester",
        'school_year'    => $row['school_year'],
        'semester'       => $row['semester'],
        'start_date'     => $row['start_date']
                              ? date('M j, Y', strtotime($row['start_date']))
                              : '—',
        'start_date_raw' => $row['start_date'],
        'end_date'       => $row['end_date']
                              ? date('M j, Y', strtotime($row['end_date']))
                              : '—',
        'end_date_raw'   => $row['end_date'],
        'required_hours' => (int) $row['required_hours'],
        'status'         => $row['status'],
        'status_label'   => ucfirst($row['status']),
        'student_count'  => (int) ($row['student_count'] ?? 0),
        'created_by'     => isset($row['created_by_name'])
                              ? trim($row['created_by_name']) ?: 'System'
                              : 'System',
        'created_at'     => date('M j, Y', strtotime($row['created_at'])),
        'activated_at'   => !empty($row['activated_at'])
                              ? date('M j, Y', strtotime($row['activated_at']))
                              : null,
        'closed_at'      => !empty($row['closed_at'])
                              ? date('M j, Y', strtotime($row['closed_at']))
                              : null,
    ];
}