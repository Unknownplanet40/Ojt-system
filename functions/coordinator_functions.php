<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function generateCoordinatorTempPassword(): string
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

function getAllCoordinators($conn, array $filters = []): array
{
    $conditions = ["u.role = 'coordinator'"];

    if (!empty($filters['status'])) {
        $conditions[] = match($filters['status']) {
            'active'          => "u.is_active = 1 AND u.last_login_at IS NOT NULL",
            'never_logged_in' => "u.is_active = 1 AND u.last_login_at IS NULL",
            'inactive'        => "u.is_active = 0",
            default           => "1=1",
        };
    }

    if (!empty($filters['department'])) {
        $department = $conn->real_escape_string($filters['department']);
        $conditions[] = "cp.department = '{$department}'";
    }

    if (!empty($filters['search'])) {
        $search = $conn->real_escape_string($filters['search']);
        $conditions[] = "(
            cp.first_name LIKE '%{$search}%' OR
            cp.last_name LIKE '%{$search}%' OR
            cp.employee_id LIKE '%{$search}%' OR
            cp.department LIKE '%{$search}%' OR
            u.email LIKE '%{$search}%'
        )";
    }

    $where = implode(' AND ', $conditions);

    $result = $conn->query("
        SELECT
          cp.uuid AS profile_uuid,
          cp.user_uuid,
          cp.first_name,
          cp.last_name,
          cp.middle_name,
          cp.mobile,
          cp.department,
          cp.employee_id,
          cp.profile_name,

          u.email,
          u.is_active,
          u.last_login_at,
          u.created_at,

          COALESCE(COUNT(sp.id), 0) AS assigned_students,

          CASE
            WHEN u.is_active = 0         THEN 'inactive'
            WHEN u.last_login_at IS NULL THEN 'never_logged_in'
            ELSE 'active'
          END AS account_status

        FROM coordinator_profiles cp
        JOIN users u
          ON cp.user_uuid = u.uuid
        LEFT JOIN student_profiles sp
          ON sp.coordinator_uuid = cp.uuid
        WHERE {$where}
        GROUP BY cp.id
        ORDER BY cp.last_name ASC, cp.first_name ASC
    ");

    $coordinators = [];
    while ($row = $result->fetch_assoc()) {
        $coordinators[] = formatCoordinator($row);
    }

    return $coordinators;
}

function getCoordinator($conn, string $profileUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          cp.*,
          u.uuid AS user_uuid,
          u.email,
          u.is_active,
          u.last_login_at,
          u.must_change_password,
          u.created_at AS account_created_at,

          COALESCE(COUNT(sp.id), 0) AS assigned_students,

          CASE
            WHEN u.is_active = 0         THEN 'inactive'
            WHEN u.last_login_at IS NULL THEN 'never_logged_in'
            ELSE 'active'
          END AS account_status

        FROM coordinator_profiles cp
        JOIN users u
          ON cp.user_uuid = u.uuid
        LEFT JOIN student_profiles sp
          ON sp.coordinator_uuid = cp.uuid
        WHERE cp.uuid = ?
        GROUP BY cp.id
        LIMIT 1
    ");
    $stmt->bind_param('s', $profileUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $coordinator = formatCoordinator($row);
    $coordinator['must_change_password'] = (int) ($row['must_change_password'] ?? 1);
    $coordinator['account_created_at'] = !empty($row['account_created_at'])
        ? date('M j, Y', strtotime($row['account_created_at']))
        : null;

    return $coordinator;
}

function createCoordinator($conn, array $data, string $actorUuid): array
{
    $errors = [];

    $email = trim($data['email'] ?? '');
    $employeeId = trim($data['employee_id'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $firstName = trim($data['first_name'] ?? '');
    $middleName = trim($data['middle_name'] ?? '');
    $mobile = trim($data['mobile'] ?? '');
    $department = trim($data['department'] ?? '');

    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (empty($employeeId)) {
        $errors['employee_id'] = 'Employee ID is required.';
    }

    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required.';
    }

    if (empty($lastName)) {
        $errors['last_name'] = 'Last name is required.';
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

    $stmt = $conn->prepare("SELECT id FROM coordinator_profiles WHERE employee_id = ? LIMIT 1");
    $stmt->bind_param('s', $employeeId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'errors' => ['employee_id' => 'This employee ID is already registered.']];
    }
    $stmt->close();

    $userUuid = generateUuid();
    $profileUuid = generateUuid();
    $tempPassword = generateCoordinatorTempPassword();
    $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(" 
            INSERT INTO users
              (uuid, email, password_hash, role, is_active, must_change_password, created_by)
            VALUES (?, ?, ?, 'coordinator', 1, 1, ?)
        ");
        $stmt->bind_param('ssss', $userUuid, $email, $passwordHash, $actorUuid);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(" 
            INSERT INTO coordinator_profiles
              (uuid, user_uuid, employee_id, last_name, first_name, middle_name, mobile, department)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'ssssssss',
            $profileUuid,
            $userUuid,
            $employeeId,
            $lastName,
            $firstName,
            $middleName,
            $mobile,
            $department
        );
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'errors' => [
                'general' => 'Failed to create coordinator account. Please try again.',
                'details' => $e->getMessage(),
            ],
        ];
    }

    logActivity(
        conn: $conn,
        eventType: 'account_created',
        description: "Created coordinator account for {$firstName} {$lastName} ({$email})",
        module: 'coordinators',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return [
        'success' => true,
        'user_uuid' => $userUuid,
        'profile_uuid' => $profileUuid,
        'temp_password' => $tempPassword,
        'full_name' => trim("{$firstName} {$middleName} {$lastName}"),
    ];
}

function updateCoordinator($conn, string $profileUuid, array $data, string $actorUuid): array
{
    $errors = [];

    $lastName = trim($data['last_name'] ?? '');
    $firstName = trim($data['first_name'] ?? '');
    $middleName = trim($data['middle_name'] ?? '');
    $mobile = trim($data['mobile'] ?? '');
    $department = trim($data['department'] ?? '');

    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required.';
    }

    if (empty($lastName)) {
        $errors['last_name'] = 'Last name is required.';
    }

    if (empty($department)) {
        $errors['department'] = 'Department is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $conn->prepare("
        UPDATE coordinator_profiles
        SET last_name = ?,
            first_name = ?,
            middle_name = ?,
            mobile = ?,
            department = ?
        WHERE uuid = ?
    ");

    $stmt->bind_param(
        'ssssss',
        $lastName,
        $firstName,
        $middleName,
        $mobile,
        $department,
        $profileUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'profile_updated_by_admin',
        description: "Updated coordinator profile of {$firstName} {$lastName}",
        module: 'coordinators',
        actorUuid: $actorUuid,
        targetUuid: $profileUuid
    );

    return ['success' => true];
}

function deactivateCoordinator($conn, string $userUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT u.is_active, cp.first_name, cp.last_name
        FROM users u
        JOIN coordinator_profiles cp ON u.uuid = cp.user_uuid
        WHERE u.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'error' => 'Coordinator not found.'];
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
        description: "Deactivated coordinator account of {$row['first_name']} {$row['last_name']}",
        module: 'coordinators',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return ['success' => true];
}

function reactivateCoordinator($conn, string $userUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT cp.first_name, cp.last_name
        FROM users u
        JOIN coordinator_profiles cp ON u.uuid = cp.user_uuid
        WHERE u.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'error' => 'Coordinator not found.'];
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE uuid = ?");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'account_activated',
        description: "Reactivated coordinator account of {$row['first_name']} {$row['last_name']}",
        module: 'coordinators',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return ['success' => true];
}

function resetCoordinatorPassword($conn, string $userUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT cp.first_name, cp.last_name
        FROM users u
        JOIN coordinator_profiles cp ON u.uuid = cp.user_uuid
        WHERE u.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'error' => 'Coordinator not found.'];
    }

    $tempPassword = generateCoordinatorTempPassword();
    $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
        UPDATE users
        SET password_hash = ?,
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
        module: 'coordinators',
        actorUuid: $actorUuid,
        targetUuid: $userUuid
    );

    return [
        'success' => true,
        'temp_password' => $tempPassword,
    ];
}

function getCoordinatorDepartments($conn): array
{
    $result = $conn->query(" 
        SELECT DISTINCT department
        FROM coordinator_profiles
        WHERE department IS NOT NULL AND department <> ''
        ORDER BY department ASC
    ");

    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row['department'];
    }

    return $departments;
}

function formatCoordinator(array $row): array
{
    $status = $row['account_status'] ?? 'unknown';

    return [
        'profile_uuid' => $row['profile_uuid'] ?? $row['uuid'],
        'user_uuid' => $row['user_uuid'] ?? null,
        'employee_id' => $row['employee_id'] ?? '—',
        'full_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
        'first_name' => $row['first_name'] ?? '',
        'last_name' => $row['last_name'] ?? '',
        'middle_name' => $row['middle_name'] ?? '',
        'profile_name' => $row['profile_name'] ?? '',
        'initials' => strtoupper(substr((string) ($row['first_name'] ?? 'C'), 0, 1) . substr((string) ($row['last_name'] ?? 'P'), 0, 1)),
        'email' => $row['email'] ?? '',
        'mobile' => $row['mobile'] ?? '—',
        'department' => $row['department'] ?? '—',
        'assigned_students' => (int) ($row['assigned_students'] ?? 0),
        'is_active' => (int) ($row['is_active'] ?? 0),
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
