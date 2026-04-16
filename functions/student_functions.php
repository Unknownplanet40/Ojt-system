<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function generateTempPassword(): string
{
    $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lowercase = 'abcdefghjkmnpqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*';
    
    $all = $uppercase . $lowercase . $numbers . $special;
    $password = '';
    
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    for ($i = 0; $i < 8; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    $password = str_shuffle($password);
    
    return $password;
}

function getAllStudents($conn, string $batchUuid = null, array $filters = []): array
{
    if (empty($batchUuid)) {
        $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
        $row       = $result->fetch_assoc();
        $batchUuid = $row['uuid'] ?? null;
    }

    $safeBatch = $conn->real_escape_string($batchUuid ?? '');
    $conditions = ["sp.batch_uuid = '{$safeBatch}'"];

    if (!empty($filters['coordinator_uuid'])) {
        $c = $conn->real_escape_string($filters['coordinator_uuid']);
        $conditions[] = "sp.coordinator_uuid = '{$c}'";
    }
    if (!empty($filters['program_uuid'])) {
        $p = $conn->real_escape_string($filters['program_uuid']);
        $conditions[] = "sp.program_uuid = '{$p}'";
    }
    if (!empty($filters['year_level'])) {
        $y = (int) $filters['year_level'];
        $conditions[] = "sp.year_level = {$y}";
    }
    if (!empty($filters['status'])) {
        $conditions[] = match($filters['status']) {
            'active'          => "u.is_active = 1 AND u.last_login_at IS NOT NULL",
            'never_logged_in' => "u.is_active = 1 AND u.last_login_at IS NULL",
            'inactive'        => "u.is_active = 0",
            default           => "1=1",
        };
    }
    if (!empty($filters['search'])) {
        $s = $conn->real_escape_string($filters['search']);
        $conditions[] = "(
            sp.first_name     LIKE '%{$s}%' OR
            sp.last_name      LIKE '%{$s}%' OR
            sp.student_number LIKE '%{$s}%' OR
            u.email           LIKE '%{$s}%'
        )";
    }

    $where = implode(' AND ', $conditions);

    $result = $conn->query("
        SELECT
          sp.uuid            AS profile_uuid,
          sp.student_number,
          sp.first_name,
          sp.last_name,
          sp.middle_name,
          sp.year_level,
          sp.section,
          sp.mobile,
          sp.coordinator_uuid,
          sp.program_uuid,
          sp.profile_name,

          u.uuid             AS user_uuid,
          u.email,
          u.is_active,
          u.last_login_at,
          u.created_at,

          p.code             AS program_code,
          p.name             AS program_name,

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
        WHERE {$where}
        ORDER BY sp.last_name ASC, sp.first_name ASC
    ");

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = formatStudent($row);
    }

    return $students;
}

function getStudent($conn, string $profileUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          sp.*,
          u.uuid         AS user_uuid,
          u.email,
          u.is_active,
          u.last_login_at,
          u.must_change_password,
          u.created_at   AS account_created_at,

          p.code         AS program_code,
          p.name         AS program_name,
          p.required_hours,
          p.department,

          b.school_year,
          b.semester,

          CONCAT(cp.first_name, ' ', cp.last_name) AS coordinator_name,

          c.name         AS company_name

        FROM student_profiles sp
        JOIN users u                   ON sp.user_uuid        = u.uuid
        LEFT JOIN programs p           ON sp.program_uuid     = p.uuid
        LEFT JOIN batches b            ON sp.batch_uuid       = b.uuid
        LEFT JOIN coordinator_profiles cp ON sp.coordinator_uuid = cp.uuid
        LEFT JOIN companies c          ON sp.company_uuid     = c.uuid
        WHERE sp.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $profileUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;

    $student = formatStudent($row);

    // add extra fields only in detail view
    $student['home_address']          = $row['home_address']      ?? '';
    $student['emergency_contact']     = $row['emergency_contact'] ?? '';
    $student['emergency_phone']       = $row['emergency_phone']   ?? '';
    $student['required_hours']        = (int) ($row['required_hours'] ?? 486);
    $student['department']            = $row['department']         ?? '—';
    $student['company_name']          = $row['company_name']       ?? null;
    $student['must_change_password']  = (int) $row['must_change_password'];
    $student['batch_label']           = $row['school_year']
                                          ? "AY {$row['school_year']} {$row['semester']} Semester"
                                          : '—';
    $student['account_created_at']    = date('M j, Y', strtotime($row['account_created_at']));

    return $student;
}

function createStudent($conn, array $data, string $actorUuid): array
{
    $errors = [];

    $email           = trim($data['email']             ?? '');
    $studentNumber   = trim($data['student_number']    ?? '');
    $lastName        = trim($data['last_name']         ?? '');
    $firstName       = trim($data['first_name']        ?? '');
    $middleName      = trim($data['middle_name']       ?? '');
    $mobile          = trim($data['mobile']            ?? '');
    $emergContact    = trim($data['emergency_contact'] ?? '');
    $emergPhone      = trim($data['emergency_phone']   ?? '');
    $homeAddress     = trim($data['home_address']      ?? '');
    $programUuid     = trim($data['program_uuid']      ?? '');
    $yearLevel       = (int) ($data['year_level']      ?? 0);
    $section         = trim($data['section']           ?? '');
    $coordinatorUuid = trim($data['coordinator_uuid']  ?? '');
    $batchUuid       = trim($data['batch_uuid']        ?? '');

    // validate
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (empty($studentNumber)) {
        $errors['student_number'] = 'Student number is required.';
    }

    if (empty($lastName))  $errors['last_name']  = 'Last name is required.';
    if (empty($firstName)) $errors['first_name'] = 'First name is required.';

    if (empty($programUuid)) {
        $errors['program_uuid'] = 'Program is required.';
    }

    if ($yearLevel < 1 || $yearLevel > 4) {
        $errors['year_level'] = 'Select a valid year level.';
    }

    if (empty($coordinatorUuid)) {
        $errors['coordinator_uuid'] = 'Coordinator is required.';
    }

    if (empty($batchUuid)) {
        $errors['batch_uuid'] = 'No active batch found.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // check email not already used
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'errors' => ['email' => 'This email is already registered.']];
    }
    $stmt->close();

    // check student number not already used
    $stmt = $conn->prepare("SELECT id FROM student_profiles WHERE student_number = ? LIMIT 1");
    $stmt->bind_param('s', $studentNumber);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'errors' => ['student_number' => 'This student number is already registered.']];
    }
    $stmt->close();

    // check batch is active
    $stmt = $conn->prepare("SELECT uuid FROM batches WHERE uuid = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param('s', $batchUuid);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'errors' => ['batch_uuid' => 'Selected batch is not active.']];
    }
    $stmt->close();

    // generate credentials
    $userUuid     = generateUuid();
    $profileUuid  = generateUuid();
    $tempPassword = generateTempPassword();
    $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

    // transaction
    $conn->begin_transaction();

    try {
        // insert user
        $stmt = $conn->prepare("
            INSERT INTO users
              (uuid, email, password_hash, role, is_active, must_change_password, created_by)
            VALUES (?, ?, ?, 'student', 1, 1, ?)
        ");
        $stmt->bind_param('ssss', $userUuid, $email, $passwordHash, $actorUuid);
        $stmt->execute();
        $stmt->close();

        // insert student profile
        $stmt = $conn->prepare("
            INSERT INTO student_profiles
              (uuid, user_uuid, student_number, last_name, first_name, middle_name,
               mobile, home_address, emergency_contact, emergency_phone,
               program_uuid, year_level, section, coordinator_uuid, batch_uuid)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'sssssssssssssss',
            $profileUuid, $userUuid, $studentNumber,
            $lastName, $firstName, $middleName,
            $mobile, $homeAddress, $emergContact, $emergPhone,
            $programUuid, $yearLevel, $section,
            $coordinatorUuid, $batchUuid
        );
        $stmt->execute();
        $stmt->close();

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'errors' => ['general' => 'Failed to create account. Please try again.', 'details' => $e->getMessage()]];
    }

    // initialize requirements after successful creation
    initializeRequirements($conn, $profileUuid, $batchUuid);

    logActivity(
        conn: $conn,
        eventType: 'account_created',
        description: "Created student account for {$firstName} {$lastName} ({$email})",
        module: 'students',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return [
        'success'       => true,
        'user_uuid'     => $userUuid,
        'profile_uuid'  => $profileUuid,
        'temp_password' => $tempPassword,
        'full_name'     => trim("{$firstName} {$middleName} {$lastName}"),
    ];
}

function updateStudent($conn, string $profileUuid, array $data, string $actorUuid): array
{
    $errors = [];

    $lastName        = trim($data['last_name']         ?? '');
    $firstName       = trim($data['first_name']        ?? '');
    $middleName      = trim($data['middle_name']       ?? '');
    $mobile          = trim($data['mobile']            ?? '');
    $homeAddress     = trim($data['home_address']      ?? '');
    $emergContact    = trim($data['emergency_contact'] ?? '');
    $emergPhone      = trim($data['emergency_phone']   ?? '');
    $programUuid     = trim($data['program_uuid']      ?? '');
    $yearLevel       = (int) ($data['year_level']      ?? 0);
    $section         = trim($data['section']           ?? '');
    $coordinatorUuid = trim($data['coordinator_uuid']  ?? '');

    if (empty($lastName))    $errors['last_name']    = 'Last name is required.';
    if (empty($firstName))   $errors['first_name']   = 'First name is required.';
    if (empty($programUuid)) $errors['program_uuid'] = 'Program is required.';
    if ($yearLevel < 1 || $yearLevel > 4) {
        $errors['year_level'] = 'Select a valid year level.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $conn->prepare("
        UPDATE student_profiles
        SET last_name         = ?,
            first_name        = ?,
            middle_name       = ?,
            mobile            = ?,
            home_address      = ?,
            emergency_contact = ?,
            emergency_phone   = ?,
            program_uuid      = ?,
            year_level        = ?,
            section           = ?,
            coordinator_uuid  = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param(
        'ssssssssisss',
        $lastName, $firstName, $middleName,
        $mobile, $homeAddress,
        $emergContact, $emergPhone,
        $programUuid, $yearLevel, $section,
        $coordinatorUuid, $profileUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'profile_updated_by_admin',
        description: "Updated student profile of {$firstName} {$lastName}",
        module: 'students',
        actorUuid: $actorUuid,
        targetUuid: $profileUuid
    );

    return ['success' => true];
}

function deactivateStudent($conn, string $userUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT u.is_active, sp.first_name, sp.last_name
        FROM users u
        JOIN student_profiles sp ON u.uuid = sp.user_uuid
        WHERE u.uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'error' => 'Student not found.'];
    }
    if ((int) $row['is_active'] === 0) {
        return ['success' => false, 'error' => 'Account is already inactive.'];
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE uuid = ?");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'account_deactivated',
        description: "Deactivated student account of {$row['first_name']} {$row['last_name']}",
        module: 'students',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return ['success' => true];
}

function reactivateStudent($conn, string $userUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT sp.first_name, sp.last_name
        FROM users u
        JOIN student_profiles sp ON u.uuid = sp.user_uuid
        WHERE u.uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'error' => 'Student not found.'];
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE uuid = ?");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'account_activated',
        description: "Reactivated student account of {$row['first_name']} {$row['last_name']}",
        module: 'students',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return ['success' => true];
}

function resetStudentPassword($conn, string $userUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT sp.first_name, sp.last_name
        FROM users u
        JOIN student_profiles sp ON u.uuid = sp.user_uuid
        WHERE u.uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'error' => 'Student not found.'];
    }

    $tempPassword = generateTempPassword();
    $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
        UPDATE users
        SET password_hash        = ?,
            must_change_password = 1
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $passwordHash, $userUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'password_reset',
        description: "Password reset for {$row['first_name']} {$row['last_name']}",
        module: 'students',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return [
        'success'       => true,
        'temp_password' => $tempPassword,
    ];
}

function getCoordinatorsForDropdown($conn): array
{
    $result = $conn->query("
        SELECT cp.uuid, cp.first_name, cp.last_name, cp.department
        FROM coordinator_profiles cp
        JOIN users u ON cp.user_uuid = u.uuid
        WHERE u.is_active = 1
        ORDER BY cp.last_name ASC
    ");

    $coordinators = [];
    while ($row = $result->fetch_assoc()) {
        $coordinators[] = [
            'uuid'       => $row['uuid'],
            'full_name'  => $row['first_name'] . ' ' . $row['last_name'],
            'department' => $row['department'] ?? '',
        ];
    }

    return $coordinators;
}

function formatStudent(array $row): array
{
    return [
        'profile_uuid'    => $row['profile_uuid'] ?? $row['uuid'],
        'user_uuid'       => $row['user_uuid'],
        'student_number'  => $row['student_number'],
        'full_name'       => trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']),
        'first_name'      => $row['first_name'],
        'last_name'       => $row['last_name'],
        'middle_name'     => $row['middle_name'] ?? '',
        'profile_name'    => $row['profile_name'] ?? '',
        'initials'        => strtoupper(substr($row['first_name'],0,1) . substr($row['last_name'],0,1)),
        'email'           => $row['email'],
        'mobile'          => $row['mobile']      ?? '—',
        'section'         => $row['section']     ?? '—',
        'year_level'      => (int) $row['year_level'],
        'year_label'      => ordinal((int)$row['year_level']) . ' Year',
        'program_uuid'    => $row['program_uuid']     ?? null,
        'program_code'    => $row['program_code']     ?? '—',
        'program_name'    => $row['program_name']     ?? '—',
        'coordinator_uuid'=> $row['coordinator_uuid'] ?? null,
        'coordinator_name'=> $row['coordinator_name'] ?? '—',
        'is_active'       => (int) $row['is_active'],
        'account_status'  => $row['account_status']   ?? 'unknown',
        'status_label'    => match($row['account_status'] ?? '') {
            'active'          => 'Active',
            'inactive'        => 'Inactive',
            'never_logged_in' => 'Never logged in',
            default           => 'Unknown',
        },
        'last_login'      => !empty($row['last_login_at'])
                               ? date('M j, Y g:i A', strtotime($row['last_login_at']))
                               : null,
        'created_at'      => isset($row['created_at'])
                               ? date('M j, Y', strtotime($row['created_at']))
                               : null,
    ];
}

function initializeRequirements($conn, string $studentProfileUuid, string $batchUuid): void
{
    $reqTypes = [
        'medical_certificate',
        'parental_consent',
        'insurance',
        'nbi_clearance',
        'resume',
        'guardian_form',
    ];

    $stmt = $conn->prepare("
        INSERT IGNORE INTO student_requirements
          (uuid, student_uuid, batch_uuid, req_type, status)
        VALUES (?, ?, ?, ?, 'not_submitted')
    ");

    foreach ($reqTypes as $reqType) {
        $uuid = generateUuid();
        $stmt->bind_param('ssss', $uuid, $studentProfileUuid, $batchUuid, $reqType);
        $stmt->execute();
    }

    $stmt->close();
}