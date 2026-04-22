<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function generateSupervisorTempPassword(): string
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

    return str_shuffle($password);
}

function getSupervisorCompanies($conn): array
{
    $result = $conn->query("
    SELECT uuid, name, city, work_setup
    FROM companies
    ORDER BY name ASC
    ");

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }

    return $companies;
}

function getAllSupervisors($conn, array $filters = []): array
{
    $conditions = ["1=1"];

    if (!empty($filters['status'])) {
        $conditions[] = match ($filters['status']) {
            'active'          => "u.is_active = 1 AND u.last_login_at IS NOT NULL",
            'never_logged_in'  => "u.is_active = 1 AND u.last_login_at IS NULL",
            'inactive'        => "u.is_active = 0",
            default           => "1=1",
        };
    }

    if (!empty($filters['company_uuid'])) {
        $companyUuid = $conn->real_escape_string($filters['company_uuid']);
        $conditions[] = "svp.company_uuid = '{$companyUuid}'";
    }

    if (!empty($filters['search'])) {
        $search = $conn->real_escape_string($filters['search']);
        $conditions[] = "(
            svp.first_name LIKE '%{$search}%' OR
            svp.last_name LIKE '%{$search}%' OR
            svp.position LIKE '%{$search}%' OR
            svp.department LIKE '%{$search}%' OR
            svp.mobile LIKE '%{$search}%' OR
            u.email LIKE '%{$search}%' OR
            c.name LIKE '%{$search}%'
        )";
    }

    $where = implode(' AND ', $conditions);

    $result = $conn->query("
        SELECT
        svp.id,
        svp.uuid            AS profile_uuid,
        svp.user_uuid,
        svp.company_uuid,
        svp.last_name,
        svp.first_name,
        svp.position,
        svp.department,
        svp.mobile,
        svp.profile_path,
        svp.profile_name,
        svp.is_active       AS profile_active,
        svp.isProfileDone,
        
        u.email,
        u.is_active,
        u.last_login_at,
        u.created_at,
        u.must_change_password,
        
        c.name              AS company_name,
        c.city              AS company_city,
        c.work_setup        AS company_work_setup,
        COALESCE(COUNT(DISTINCT sp.id), 0) AS students_count,

        CASE
            WHEN u.is_active = 0         THEN 'inactive'
            WHEN u.last_login_at IS NULL THEN 'never_logged_in'
            ELSE 'active'
        END AS account_status

        FROM supervisor_profiles svp
        JOIN users u
          ON svp.user_uuid = u.uuid
        LEFT JOIN companies c
          ON svp.company_uuid = c.uuid
        LEFT JOIN student_profiles sp
          ON sp.company_uuid = svp.company_uuid
        WHERE {$where}
        GROUP BY svp.id
        ORDER BY svp.last_name ASC, svp.first_name ASC
    ");

    $supervisors = [];
    while ($row = $result->fetch_assoc()) {
        $supervisors[] = formatSupervisor($row);
    }

    return $supervisors;
}

function getSupervisor($conn, string $profileUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
        svp.id,
        svp.uuid AS profile_uuid,
        svp.user_uuid,
        svp.company_uuid,
        svp.last_name,
        svp.first_name,
        svp.position,
        svp.department,
        svp.mobile,
        svp.profile_path,
        svp.profile_name,
        svp.is_active       AS profile_active,
        svp.isProfileDone,

        u.email,
        u.is_active,
        u.last_login_at,
        u.must_change_password,
        u.created_at        AS account_created_at,

        c.name              AS company_name,
        c.city              AS company_city,
        c.work_setup        AS company_work_setup,
        COALESCE(COUNT(DISTINCT sp.id), 0) AS students_count

        FROM supervisor_profiles svp
        JOIN users u
          ON svp.user_uuid = u.uuid
        LEFT JOIN companies c
          ON svp.company_uuid = c.uuid
        LEFT JOIN student_profiles sp
          ON sp.company_uuid = svp.company_uuid
        WHERE svp.uuid = ?
        GROUP BY svp.id
        LIMIT 1
    ");
    $stmt->bind_param('s', $profileUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $supervisor = formatSupervisor($row);
    $supervisor['must_change_password'] = (int) ($row['must_change_password'] ?? 1);
    $supervisor['account_created_at'] = !empty($row['account_created_at'])
        ? date('M j, Y', strtotime($row['account_created_at']))
        : null;

    return $supervisor;
}

function createSupervisor($conn, array $data, string $actorUuid): array
{
    $errors = [];

    $email       = trim($data['email'] ?? '');
    $companyUuid = trim($data['company_uuid'] ?? '');
    $lastName    = trim($data['last_name'] ?? '');
    $firstName   = trim($data['first_name'] ?? '');
    $position    = trim($data['position'] ?? '');
    $department  = trim($data['department'] ?? '');
    $mobile      = trim($data['mobile'] ?? '');

    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (empty($companyUuid)) {
        $errors['company_uuid'] = 'Company is required.';
    }

    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required.';
    }

    if (empty($lastName)) {
        $errors['last_name'] = 'Last name is required.';
    }

    if (empty($position)) {
        $errors['position'] = 'Position is required.';
    }

    if (empty($department)) {
        $errors['department'] = 'Department is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'errors' => ['email' => 'This email is already registered.']];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT id FROM companies WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'errors' => ['company_uuid' => 'Selected company was not found.']];
    }
    $stmt->close();

    $userUuid = generateUuid();
    $profileUuid = generateUuid();
    $tempPassword = generateSupervisorTempPassword();
    $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("\n            INSERT INTO users\n              (uuid, email, password_hash, role, is_active, must_change_password, created_by)\n            VALUES (?, ?, ?, 'supervisor', 1, 1, ?)\n        ");
        $stmt->bind_param('ssss', $userUuid, $email, $passwordHash, $actorUuid);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("\n            INSERT INTO supervisor_profiles\n              (uuid, user_uuid, company_uuid, last_name, first_name, position, department, mobile, is_active, isProfileDone)\n            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)\n        ");
        $stmt->bind_param(
            'ssssssss',
            $profileUuid,
            $userUuid,
            $companyUuid,
            $lastName,
            $firstName,
            $position,
            $department,
            $mobile
        );
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();

        return [
            'success' => false,
            'errors' => [
                'general' => 'Failed to create supervisor account. Please try again.',
                'details' => $e->getMessage(),
            ],
        ];
    }

    logActivity(
        conn: $conn,
        eventType: 'account_created',
        description: "Created supervisor account for {$firstName} {$lastName} ({$email})",
        module: 'supervisors',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return [
        'success' => true,
        'user_uuid' => $userUuid,
        'profile_uuid' => $profileUuid,
        'temp_password' => $tempPassword,
        'full_name' => trim("{$firstName} {$lastName}"),
    ];
}

function updateSupervisor($conn, string $profileUuid, array $data, string $actorUuid): array
{
    $errors = [];

    $companyUuid = trim($data['company_uuid'] ?? '');
    $lastName    = trim($data['last_name'] ?? '');
    $firstName   = trim($data['first_name'] ?? '');
    $position    = trim($data['position'] ?? '');
    $department  = trim($data['department'] ?? '');
    $mobile      = trim($data['mobile'] ?? '');

    if (empty($companyUuid)) {
        $errors['company_uuid'] = 'Company is required.';
    }
    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required.';
    }
    if (empty($lastName)) {
        $errors['last_name'] = 'Last name is required.';
    }
    if (empty($position)) {
        $errors['position'] = 'Position is required.';
    }
    if (empty($department)) {
        $errors['department'] = 'Department is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $conn->prepare("SELECT id FROM companies WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'errors' => ['company_uuid' => 'Selected company was not found.']];
    }
    $stmt->close();

    $stmt = $conn->prepare("\n        UPDATE supervisor_profiles\n        SET company_uuid = ?,\n            last_name = ?,\n            first_name = ?,\n            position = ?,\n            department = ?,\n            mobile = ?\n        WHERE uuid = ?\n    ");
    $stmt->bind_param(
        'sssssss',
        $companyUuid,
        $lastName,
        $firstName,
        $position,
        $department,
        $mobile,
        $profileUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'profile_updated_by_admin',
        description: "Updated supervisor profile of {$firstName} {$lastName}",
        module: 'supervisors',
        actorUuid: $actorUuid,
        targetUuid: $profileUuid
    );

    return ['success' => true];
}

function deactivateSupervisor($conn, string $userUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("\n        SELECT u.is_active, svp.first_name, svp.last_name\n        FROM users u\n        JOIN supervisor_profiles svp ON u.uuid = svp.user_uuid\n        WHERE u.uuid = ?\n        LIMIT 1\n    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'error' => 'Supervisor not found.'];
    }

    if ((int) $row['is_active'] === 0) {
        return ['success' => false, 'error' => 'Account is already inactive.'];
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE uuid = ?");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE supervisor_profiles SET is_active = 0 WHERE user_uuid = ?");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'account_deactivated',
        description: "Deactivated supervisor account of {$row['first_name']} {$row['last_name']}",
        module: 'supervisors',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return ['success' => true];
}

function reactivateSupervisor($conn, string $userUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("\n        SELECT svp.first_name, svp.last_name\n        FROM users u\n        JOIN supervisor_profiles svp ON u.uuid = svp.user_uuid\n        WHERE u.uuid = ?\n        LIMIT 1\n    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'error' => 'Supervisor not found.'];
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE uuid = ?");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE supervisor_profiles SET is_active = 1 WHERE user_uuid = ?");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'account_activated',
        description: "Reactivated supervisor account of {$row['first_name']} {$row['last_name']}",
        module: 'supervisors',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return ['success' => true];
}

function resetSupervisorPassword($conn, string $userUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("\n        SELECT svp.first_name, svp.last_name\n        FROM users u\n        JOIN supervisor_profiles svp ON u.uuid = svp.user_uuid\n        WHERE u.uuid = ?\n        LIMIT 1\n    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'error' => 'Supervisor not found.'];
    }

    $tempPassword = generateSupervisorTempPassword();
    $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("\n        UPDATE users\n        SET password_hash = ?,\n            must_change_password = 1\n        WHERE uuid = ?\n    ");
    $stmt->bind_param('ss', $passwordHash, $userUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'password_reset',
        description: "Password reset for {$row['first_name']} {$row['last_name']}",
        module: 'supervisors',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return [
        'success' => true,
        'temp_password' => $tempPassword,
    ];
}

function formatSupervisor(array $row): array
{
    $status = $row['account_status'] ?? null;

    if (empty($status) || $status === 'unknown') {
        $isActive = (int) ($row['is_active'] ?? $row['profile_active'] ?? 0);
        $lastLogin = $row['last_login_at'] ?? null;

        $status = $isActive === 0
            ? 'inactive'
            : (empty($lastLogin) ? 'never_logged_in' : 'active');
    }

    return [
        'profile_uuid' => $row['profile_uuid'] ?? $row['uuid'] ?? null,
        'user_uuid' => $row['user_uuid'] ?? null,
        'company_uuid' => $row['company_uuid'] ?? null,
        'company_name' => $row['company_name'] ?? '—',
        'company_city' => $row['company_city'] ?? '—',
        'company_work_setup' => $row['company_work_setup'] ?? '—',
        'full_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
        'first_name' => $row['first_name'] ?? '',
        'last_name' => $row['last_name'] ?? '',
        'initials' => strtoupper(substr((string) ($row['first_name'] ?? 'S'), 0, 1) . substr((string) ($row['last_name'] ?? 'V'), 0, 1)),
        'email' => $row['email'] ?? '',
        'position' => $row['position'] ?? '—',
        'department' => $row['department'] ?? '—',
        'students_count' => (int) ($row['students_count'] ?? 0),
        'mobile' => $row['mobile'] ?? '—',
        'profile_path' => $row['profile_path'] ?? null,
        'profile_name' => $row['profile_name'] ?? null,
        'is_active' => (int) ($row['is_active'] ?? $row['profile_active'] ?? 0),
        'is_profile_done' => (int) ($row['isProfileDone'] ?? 0),
        'account_status' => $status,
        'status_label' => match ($status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'never_logged_in' => 'Never logged in',
            default => 'Unknown',
        },
        'last_login' => !empty($row['last_login_at'])
            ? date('M j, Y g:i A', strtotime($row['last_login_at']))
            : null,
        'created_at' => !empty($row['created_at'])
            ? date('M j, Y', strtotime($row['created_at']))
            : null,
    ];
}