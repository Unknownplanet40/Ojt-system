<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function loginUser($conn, string $email, string $password): array
{
    $email = trim($email);

    if (empty($email) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Email and password are required.',
        ];
    }

    $stmt = $conn->prepare("
        SELECT
          uuid, email, password_hash,
          role, is_active, must_change_password
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        logFailedLogin($conn, $email, 'user_not_found');
        return [
            'success' => false,
            'message' => 'Invalid email or password.',
        ];
    }

    if ((int) $user['is_active'] === 0) {
        logFailedLogin($conn, $email, 'account_inactive', $user['uuid']);
        return [
            'success' => false,
            'message' => 'Your account has been deactivated. Contact your coordinator.',
        ];
    }

    if (!password_verify($password, $user['password_hash'])) {
        logFailedLogin($conn, $email, 'wrong_password', $user['uuid']);
        return [
            'success' => false,
            'message' => 'Invalid email or password.',
        ];
    }

    // update last login
    $stmt = $conn->prepare("UPDATE users SET last_login_at = NOW() WHERE uuid = ?");
    $stmt->bind_param('s', $user['uuid']);
    $stmt->execute();
    $stmt->close();

    logLoginAudit($conn, $user['uuid'], true);

    return [
        'success' => true,
        'user'    => [
            'uuid'                 => $user['uuid'],
            'email'                => $user['email'],
            'role'                 => $user['role'],
            'is_active'            => (int) $user['is_active'],
            'must_change_password' => (int) $user['must_change_password'],
        ],
    ];
}

function buildSession($conn, array $user): void
{
    $_SESSION['user_uuid']            = $user['uuid'];
    $_SESSION['user_email']           = $user['email'];
    $_SESSION['user_role']            = $user['role'];
    $_SESSION['must_change_password'] = (int) $user['must_change_password'];

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    match($user['role']) {
        'admin'       => loadAdminSession($conn, $user['uuid']),
        'coordinator' => loadCoordinatorSession($conn, $user['uuid']),
        'student'     => loadStudentSession($conn, $user['uuid']),
        'supervisor'  => loadSupervisorSession($conn, $user['uuid']),
        default       => null,
    };
}

function loadAdminSession($conn, string $userUuid): void
{
    $stmt = $conn->prepare("
        SELECT uuid, first_name, last_name
        FROM admin_profiles WHERE user_uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $_SESSION['profile_uuid']    = $row['uuid']       ?? null;
    $_SESSION['user_first_name'] = $row['first_name'] ?? '';
    $_SESSION['user_name']       = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $_SESSION['user_initials']   = strtoupper(
        substr($row['first_name'] ?? 'A', 0, 1) .
        substr($row['last_name']  ?? 'D', 0, 1)
    );
}

function loadCoordinatorSession($conn, string $userUuid): void
{
    $stmt = $conn->prepare("
        SELECT uuid, first_name, last_name
        FROM coordinator_profiles WHERE user_uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $_SESSION['profile_uuid']    = $row['uuid']       ?? null;
    $_SESSION['user_first_name'] = $row['first_name'] ?? '';
    $_SESSION['user_name']       = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $_SESSION['user_initials']   = strtoupper(
        substr($row['first_name'] ?? 'C', 0, 1) .
        substr($row['last_name']  ?? 'O', 0, 1)
    );
}

function loadStudentSession($conn, string $userUuid): void
{
    $stmt = $conn->prepare("
        SELECT uuid, first_name, last_name, program_uuid, batch_uuid
        FROM student_profiles WHERE user_uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $_SESSION['profile_uuid']         = $row['uuid']         ?? null;
    $_SESSION['user_first_name']      = $row['first_name']   ?? '';
    $_SESSION['user_name']            = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $_SESSION['user_initials']        = strtoupper(
        substr($row['first_name'] ?? 'S', 0, 1) .
        substr($row['last_name']  ?? 'T', 0, 1)
    );
    $_SESSION['student_program_uuid'] = $row['program_uuid'] ?? null;
    $_SESSION['active_batch_uuid']    = $row['batch_uuid']   ?? null;
}

function loadSupervisorSession($conn, string $userUuid): void
{
    $stmt = $conn->prepare("
        SELECT uuid, first_name, last_name, company_uuid
        FROM supervisor_profiles WHERE user_uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $_SESSION['profile_uuid']            = $row['uuid']         ?? null;
    $_SESSION['user_first_name']         = $row['first_name']   ?? '';
    $_SESSION['user_name']               = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $_SESSION['user_initials']           = strtoupper(
        substr($row['first_name'] ?? 'S', 0, 1) .
        substr($row['last_name']  ?? 'V', 0, 1)
    );
    $_SESSION['supervisor_company_uuid'] = $row['company_uuid'] ?? null;
}

function getRedirectUrl($conn, array $user): string
{
    if ((int) $user['must_change_password'] === 1) {
        return '../../Src/Pages/ChangePassword';
    }

    $table = match($user['role']) {
        'admin'       => 'admin_profiles',
        'coordinator' => 'coordinator_profiles',
        'student'     => 'student_profiles',
        'supervisor'  => 'supervisor_profiles',
        default       => null,
    };

    if ($table) {
        $stmt = $conn->prepare("SELECT id FROM {$table} WHERE user_uuid = ? LIMIT 1");
        $stmt->bind_param('s', $user['uuid']);
        $stmt->execute();
        $hasProfile = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$hasProfile) {
            return match($user['role']) {
                'admin'       => '../../Src/Pages/Admin/Admin_Profile',
                'coordinator' => '../../Src/Pages/Coordinator/Coordinator_Profile',
                'student'     => '../../Src/Pages/Students/Students_Profile',
                'supervisor'  => '../../Src/Pages/Supervisor/Supervisor_Profile',
                default       => '../../Src/Pages/login',
            };
        }
    }

    return match($user['role']) {
        'admin'       => '../../Src/Pages/Admin/AdminDashboard',
        'coordinator' => '../../Src/Pages/Coordinator/CoordinatorDashboard',
        'student'     => '../../Src/Pages/Students/StudentsDashboard',
        'supervisor'  => '../../Src/Pages/Supervisor/SupervisorDashboard',
        default       => '../../Src/Pages/login',
    };
}

function logoutUser($conn): void
{
    if (!empty($_SESSION['user_uuid'])) {
        logActivity(
            conn: $conn,
            eventType: 'logout',
            description: ($_SESSION['user_email'] ?? '') . ' signed out',
            module: 'auth',
            actorUuid: $_SESSION['user_uuid']
        );
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function logLoginAudit($conn, string $userUuid, bool $success, string $reason = ''): void
{
    $ip         = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $successInt = $success ? 1 : 0;
    $stmt = $conn->prepare("
        INSERT INTO login_audit_log (user_uuid, ip_address, user_agent, success, fail_reason)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssis', $userUuid, $ip, $userAgent, $successInt, $reason);
    $stmt->execute();
    $stmt->close();
}

function logFailedLogin($conn, string $email, string $reason, string $userUuid = null): void
{
    $ip   = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt = $conn->prepare("
        INSERT INTO login_audit_log (user_uuid, ip_address, user_agent, success, fail_reason)
        VALUES (?, ?, ?, 0, ?)
    ");
    $stmt->bind_param('ssss', $userUuid, $ip, $userAgent, $reason);
    $stmt->execute();
    $stmt->close();
}

function getAdminProfile($conn, string $userUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          ap.uuid,
          ap.user_uuid,
          ap.first_name,
          ap.last_name,
          ap.middle_name,
          ap.contact_number,
          ap.profile_path,
          ap.profile_name,
          u.email,
          u.is_active,
          u.last_login_at,
          u.created_at AS account_created_at
        FROM admin_profiles ap
        JOIN users u ON ap.user_uuid = u.uuid
        WHERE ap.user_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'profile_uuid'   => $row['uuid'],
        'user_uuid'      => $row['user_uuid'],
        'full_name'      => trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']),
        'first_name'     => $row['first_name'],
        'last_name'      => $row['last_name'],
        'middle_name'    => $row['middle_name'] ?? '',
        'initials'       => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)),
        'contact_number' => $row['contact_number'] ?? '—',
        'profile_path'   => $row['profile_path'] ?? null,
        'profile_name'   => $row['profile_name'] ?? null,
        'email'          => $row['email'],
        'is_active'      => (int) $row['is_active'],
        'last_login'     => $row['last_login_at']
                              ? date('M j, Y g:i A', strtotime($row['last_login_at']))
                              : 'Never',
        'created_at'     => date('M j, Y', strtotime($row['account_created_at'])),
    ];
}

function getCoordinatorProfile($conn, string $userUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          cp.uuid,
          cp.user_uuid,
          cp.first_name,
          cp.last_name,
          cp.middle_name,
          cp.employee_id,
          cp.department,
          cp.mobile,
          cp.profile_path,
          cp.profile_name,
          u.email,
          u.is_active,
          u.last_login_at,
          u.created_at AS account_created_at
        FROM coordinator_profiles cp
        JOIN users u ON cp.user_uuid = u.uuid
        WHERE cp.user_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'profile_uuid'   => $row['uuid'],
        'user_uuid'      => $row['user_uuid'],
        'full_name'      => trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']),
        'first_name'     => $row['first_name'],
        'last_name'      => $row['last_name'],
        'middle_name'    => $row['middle_name'] ?? '',
        'initials'       => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)),
        'employee_id'    => $row['employee_id']  ?? '—',
        'department'     => $row['department']   ?? '—',
        'mobile'         => $row['mobile']       ?? '—',
        'profile_path'   => $row['profile_path'] ?? null,
        'profile_name'   => $row['profile_name'] ?? null,
        'email'          => $row['email'],
        'is_active'      => (int) $row['is_active'],
        'last_login'     => $row['last_login_at']
                              ? date('M j, Y g:i A', strtotime($row['last_login_at']))
                              : 'Never',
        'created_at'     => date('M j, Y', strtotime($row['account_created_at'])),
    ];
}

function getStudentProfile($conn, string $userUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          sp.uuid,
          sp.user_uuid,
          sp.student_number,
          sp.first_name,
          sp.last_name,
          sp.middle_name,
          sp.mobile,
          sp.home_address,
          sp.emergency_contact,
          sp.emergency_phone,
          sp.year_level,
          sp.section,
          sp.program_uuid,
          sp.coordinator_uuid,
          sp.batch_uuid,
          sp.company_uuid,
          sp.profile_path,
          sp.profile_name,

          u.email,
          u.is_active,
          u.last_login_at,
          u.must_change_password,
          u.created_at AS account_created_at,

          p.code           AS program_code,
          p.name           AS program_name,
          p.required_hours AS required_hours,

          b.school_year,
          b.semester,

          CONCAT(cp.first_name, ' ', cp.last_name) AS coordinator_name,

          c.name AS company_name

        FROM student_profiles sp
        JOIN users u                  ON sp.user_uuid        = u.uuid
        LEFT JOIN programs p          ON sp.program_uuid     = p.uuid
        LEFT JOIN batches b           ON sp.batch_uuid       = b.uuid
        LEFT JOIN coordinator_profiles cp ON sp.coordinator_uuid = cp.uuid
        LEFT JOIN companies c         ON sp.company_uuid     = c.uuid
        WHERE sp.user_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'profile_uuid'          => $row['uuid'],
        'user_uuid'             => $row['user_uuid'],
        'student_number'        => $row['student_number'],
        'full_name'             => trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']),
        'first_name'            => $row['first_name'],
        'last_name'             => $row['last_name'],
        'middle_name'           => $row['middle_name']      ?? '',
        'initials'              => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)),
        'mobile'                => $row['mobile']           ?? '',
        'home_address'          => $row['home_address']     ?? '',
        'emergency_contact'     => $row['emergency_contact'] ?? '',
        'emergency_phone'       => $row['emergency_phone']  ?? '',
        'year_level'            => (int) $row['year_level'],
        'year_label'            => ordinal((int) $row['year_level']) . ' Year',
        'section'               => $row['section']          ?? '',
        'email'                 => $row['email'],
        'is_active'             => (int) $row['is_active'],
        'must_change_password'  => (int) $row['must_change_password'],
        'profile_path'          => $row['profile_path']     ?? null,
        'profile_name'          => $row['profile_name']     ?? null,

        // program
        'program_uuid'          => $row['program_uuid'],
        'program_code'          => $row['program_code']     ?? '—',
        'program_name'          => $row['program_name']     ?? '—',
        'required_hours'        => (int) ($row['required_hours'] ?? 486),

        // batch
        'batch_uuid'            => $row['batch_uuid'],
        'batch_label'           => $row['school_year']
                                     ? "AY {$row['school_year']} {$row['semester']} Semester"
                                     : '—',

        // coordinator
        'coordinator_uuid'      => $row['coordinator_uuid'],
        'coordinator_name'      => $row['coordinator_name'] ?? '—',

        // company
        'company_uuid'          => $row['company_uuid'],
        'company_name'          => $row['company_name']     ?? null,

        'last_login'            => $row['last_login_at']
                                     ? date('M j, Y g:i A', strtotime($row['last_login_at']))
                                     : 'Never',
        'created_at'            => date('M j, Y', strtotime($row['account_created_at'])),
    ];
}

function getSupervisorProfile($conn, string $userUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          svp.uuid,
          svp.user_uuid,
          svp.first_name,
          svp.last_name,
          svp.position,
          svp.department,
          svp.mobile,
          svp.company_uuid,
          svp.profile_path,
          svp.profile_name,

          u.email,
          u.is_active,
          u.last_login_at,
          u.created_at AS account_created_at,

          c.name AS company_name,
          c.work_setup,
          c.city

        FROM supervisor_profiles svp
        JOIN users u          ON svp.user_uuid    = u.uuid
        LEFT JOIN companies c ON svp.company_uuid = c.uuid
        WHERE svp.user_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'profile_uuid'   => $row['uuid'],
        'user_uuid'      => $row['user_uuid'],
        'full_name'      => trim($row['first_name'] . ' ' . $row['last_name']),
        'first_name'     => $row['first_name'],
        'last_name'      => $row['last_name'],
        'initials'       => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)),
        'position'       => $row['position']    ?? '—',
        'department'     => $row['department']  ?? '—',
        'mobile'         => $row['mobile']      ?? '—',
        'email'          => $row['email'],
        'is_active'      => (int) $row['is_active'],

        // company
        'company_uuid'   => $row['company_uuid'],
        'company_name'   => $row['company_name'] ?? '—',
        'work_setup'     => $row['work_setup']   ?? '—',
        'city'           => $row['city']         ?? '—',
        'profile_path'   => $row['profile_path'] ?? null,
        'profile_name'   => $row['profile_name'] ?? null,

        'last_login'     => $row['last_login_at']
                              ? date('M j, Y g:i A', strtotime($row['last_login_at']))
                              : 'Never',
        'created_at'     => date('M j, Y', strtotime($row['account_created_at'])),
    ];
}

function getProfileByRole($conn, string $userUuid, string $role): ?array
{
    return match($role) {
        'admin'       => getAdminProfile($conn, $userUuid),
        'coordinator' => getCoordinatorProfile($conn, $userUuid),
        'student'     => getStudentProfile($conn, $userUuid),
        'supervisor'  => getSupervisorProfile($conn, $userUuid),
        default       => null,
    };
}

function isStrongPassword(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
}

function forcedChangePassword($conn, string $userUuid, string $tempPassword, string $newPassword, string $confirmPassword): array {
    $errors = [];

    if (empty($tempPassword)) {
        $errors['temp_password'] = 'Enter your temporary password.';
    }
    if (empty($newPassword)) {
        $errors['new_password'] = 'Enter a new password.';
    } elseif (!isStrongPassword($newPassword)) {
        $errors['new_password'] = 'Password must be at least 8 characters with an uppercase letter, number, and special character.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    $stmt = $conn->prepare("
        SELECT password_hash FROM users
        WHERE uuid = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return [
            'success' => false,
            'errors'  => ['general' => 'Account not found.'],
        ];
    }

    if (!password_verify($tempPassword, $row['password_hash'])) {
        return [
            'success' => false,
            'errors'  => ['temp_password' => 'Incorrect temporary password.'],
        ];
    }

    if (password_verify($newPassword, $row['password_hash'])) {
        return [
            'success' => false,
            'errors'  => ['new_password' => 'New password must be different from your temporary password.'],
        ];
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
        UPDATE users
        SET password_hash        = ?,
            must_change_password = 0,
            last_login_at        = NOW()
        WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $newHash, $userUuid);
    $stmt->execute();
    $stmt->close();

    $_SESSION['must_change_password'] = 0;

    logActivity(
        conn: $conn,
        eventType: 'must_change_password_cleared',
        description: ($_SESSION['user_email'] ?? '') . ' set a new password on first login',
        module: 'auth',
        actorUuid: $userUuid,
        targetUuid: $userUuid
    );

    return ['success' => true, 'mode' => 'forced'];
}

function voluntaryChangePassword($conn, string $userUuid, string $currentPassword, string $newPassword, string $confirmPassword): array {

    $errors = [];

    if (empty($currentPassword)) {
        $errors['current_password'] = 'Enter your current password.';
    }
    if (empty($newPassword)) {
        $errors['new_password'] = 'Enter a new password.';
    } elseif (!isStrongPassword($newPassword)) {
        $errors['new_password'] = 'Password must be at least 8 characters with an uppercase letter, number, and special character.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $conn->prepare("
        SELECT password_hash FROM users
        WHERE uuid = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return [
            'success' => false,
            'errors'  => ['general' => 'Account not found.'],
        ];
    }

    if (!password_verify($currentPassword, $row['password_hash'])) {
        return [
            'success' => false,
            'errors'  => ['current_password' => 'Current password is incorrect.'],
        ];
    }

    if (password_verify($newPassword, $row['password_hash'])) {
        return [
            'success' => false,
            'errors'  => ['new_password' => 'New password must be different from your current password.'],
        ];
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
        UPDATE users SET password_hash = ? WHERE uuid = ?
    ");
    $stmt->bind_param('ss', $newHash, $userUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'password_changed',
        description: ($_SESSION['user_email'] ?? '') . ' changed their password',
        module: 'auth',
        actorUuid: $userUuid,
        targetUuid: $userUuid
    );

    return ['success' => true, 'mode' => 'voluntary'];
}

function getPostPasswordChangeRedirect($conn, string $userUuid, string $role): string
{
    $table = match($role) {
        'admin'       => 'admin_profiles',
        'coordinator' => 'coordinator_profiles',
        'student'     => 'student_profiles',
        'supervisor'  => 'supervisor_profiles',
        default       => null,
    };

    if ($table) {
        $stmt = $conn->prepare("SELECT id FROM {$table} WHERE user_uuid = ? LIMIT 1");
        $stmt->bind_param('s', $userUuid);
        $stmt->execute();
        $hasProfile = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$hasProfile) {
            return match($role) {
                'admin'       => '../../Src/Pages/Admin/Admin_Profile',
                'coordinator' => '../../Src/Pages/Coordinator/Coordinator_Profile',
                'student'     => '../../Src/Pages/Students/Student_Profile',
                'supervisor'  => '../../Src/Pages/Supervisor/Supervisor_Profile',
                default       => '../../Src/Pages/login',
            };
        }
    }

    return match($role) {
        'admin'       => '../../Src/Pages/Admin/Dashboard',
        'coordinator' => '../../Src/Pages/Coordinator/Dashboard',
        'student'     => '../../Src/Pages/Students/Dashboard',
        'supervisor'  => '../../Src/Pages/Supervisor/Dashboard',
        default       => '../../Src/Pages/Auth/Login',
    };
}

function sendResetLink($conn, string $email): array
{
    $email = trim($email);

    if (empty($email)) {
        return [
            'success' => false,
            'status'  => 'info',
            'message' => 'Email is required.',
        ];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'status'  => 'info',
            'message' => 'Please enter a valid email address.',
        ];
    }

    // check if email exists
    $stmt = $conn->prepare("
        SELECT uuid, is_active FROM users WHERE email = ? LIMIT 1
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return [
            'success' => true,
            'message' => 'If that email is registered you will receive a reset link shortly.',
        ];
    }

    if ((int) $user['is_active'] === 0) {
        return [
            'success' => false,
            'status'  => 'info',
            'message' => 'This account has been deactivated. Contact your coordinator.',
        ];
    }

    $userUuid = $user['uuid'];

    $stmt = $conn->prepare("
        DELETE FROM password_reset_tokens
        WHERE user_uuid = ? AND used = 0
    ");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $stmt->close();

    // generate token
    $token     = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $conn->prepare("
        INSERT INTO password_reset_tokens
          (user_uuid, token_hash, expires_at, used, created_at)
        VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->bind_param('sss', $userUuid, $token, $expiresAt);
    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        return [
            'success' => false,
            'status'  => 'critical',
            'message' => 'Failed to generate reset token. Please try again.',
        ];
    }

    // build reset link
    $resetLink = 'http://localhost/ojt-system/Src/Pages/ForgotPassword?token=' . $token;

    return [
        'success'    => true,
        'message'    => 'If that email is registered you will receive a reset link shortly.',
        'token'      => $token,
        'reset_link' => $resetLink,
        'expires_at' => $expiresAt,
        'email'      => $email,
    ];
}

function validateResetToken($conn, string $token): array
{
    $token = trim($token);

    if (empty($token)) {
        return [
            'success' => false,
            'status'  => 'info',
            'message' => 'Token is required.',
        ];
    }

    $stmt = $conn->prepare("
        SELECT user_uuid, expires_at, used
        FROM password_reset_tokens
        WHERE token_hash = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return [
            'success' => false,
            'status'  => 'error',
            'message' => 'Invalid or expired reset link.',
        ];
    }

    // check if already used
    if ((int) $row['used'] === 1) {
        return [
            'success' => false,
            'status'  => 'error',
            'message' => 'This reset link has already been used.',
        ];
    }

    // check expiry
    $expiresAt   = strtotime($row['expires_at']);
    $currentTime = time();

    if ($currentTime > $expiresAt) {
        // clean up expired token
        $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token_hash = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();

        return [
            'success' => false,
            'status'  => 'expired',
            'message' => 'This reset link has expired. Please request a new one.',
        ];
    }

    return [
        'success'    => true,
        'user_uuid'  => $row['user_uuid'],
        'expires_at' => $row['expires_at'], // datetime string — JS converts to Date object
        'expires_ts' => $expiresAt,         // unix timestamp
    ];
}

function resetPassword($conn, string $token, string $newPassword, string $confirmPassword): array
{
    $token = trim($token);

    if (empty($token)) {
        return [
            'success' => false,
            'status'  => 'info',
            'message' => 'Reset token is required.',
        ];
    }

    // validate inputs
    if (empty($newPassword)) {
        return [
            'success' => false,
            'status'  => 'info',
            'message' => 'New password is required.',
        ];
    }

    if (!isStrongPassword($newPassword)) {
        return [
            'success' => false,
            'status'  => 'info',
            'message' => 'Password does not meet the requirements.',
        ];
    }

    if ($newPassword !== $confirmPassword) {
        return [
            'success' => false,
            'status'  => 'info',
            'message' => 'Passwords do not match.',
        ];
    }

    // validate token
    $tokenResult = validateResetToken($conn, $token);

    if (!$tokenResult['success']) {
        return $tokenResult;
    }

    $userUuid = $tokenResult['user_uuid'];
    $newHash  = password_hash($newPassword, PASSWORD_BCRYPT);

    try {
        $conn->begin_transaction();

        // update password
        $stmt = $conn->prepare("
            UPDATE users
            SET password_hash        = ?,
                must_change_password = 0
            WHERE uuid = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare password update statement.');
        }

        $stmt->bind_param('ss', $newHash, $userUuid);
        $stmt->execute();
        $affectedUsers = $stmt->affected_rows;
        $stmt->close();

        if ($affectedUsers < 1) {
            $conn->rollback();
            return [
                'success' => false,
                'status'  => 'error',
                'message' => 'Unable to update password for this account.',
            ];
        }

        // mark token as used — never delete, keep for audit
        $stmt = $conn->prepare("
            UPDATE password_reset_tokens
            SET used = 1
            WHERE token_hash = ? AND used = 0
            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare token update statement.');
        }

        $stmt->bind_param('s', $token);
        $stmt->execute();
        $affectedTokens = $stmt->affected_rows;
        $stmt->close();

        if ($affectedTokens < 1) {
            $conn->rollback();
            return [
                'success' => false,
                'status'  => 'error',
                'message' => 'Reset token is no longer valid. Please request a new reset link.',
            ];
        }

        $conn->commit();
    } catch (Throwable $e) {
        if ($conn->errno) {
            $conn->rollback();
        }
        error_log('resetPassword error: ' . $e->getMessage());
        return [
            'success' => false,
            'status'  => 'critical',
            'message' => 'Failed to reset password. Please try again.',
            'error'   => $e->getMessage(),
        ];
    }

    logActivity(
        conn: $conn,
        eventType: 'password_reset',
        description: 'User reset their password via email link',
        module: 'auth',
        actorUuid: $userUuid,
        targetUuid: $userUuid
    );

    return [
        'success' => true,
        'message' => 'Password reset successfully. You can now log in.',
    ];
}

function sendResetEmail(string $toEmail, string $resetLink, string $expiresAt): bool
{
    require_once dirname(__DIR__, 1) . '/Libs/composer/vendor/autoload.php';

    // temporary - replace with database-stored email credentials in production
    $gmailUser = 'Your Gmail Address';
    $gmailPass = 'Your Gmail App Password'; // your gmail password won't work, you must create an app password for this to work

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $gmailUser;
        $mail->Password   = $gmailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($gmailUser, 'OJT System');
        $mail->addAddress($toEmail);
        $mail->addReplyTo($gmailUser, 'OJT System');

        $mail->isHTML(true);
        $mail->Subject = 'Reset Your OJT System Password';
        $mail->Body    = buildResetEmailHtml($resetLink, $expiresAt);
        $mail->AltBody = "Reset your password here: {$resetLink}\nThis link expires at {$expiresAt}.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $e->getMessage());
        return false;
    }
}

function buildResetEmailHtml(string $resetLink, string $expiresAt): string
{
    $expiryFormatted = date('F j, Y g:i A', strtotime($expiresAt));
    $SchoolName = 'Cavite State University - Imus Campus';
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Reset Your Password</title>
        </head>
        <body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:40px 0;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                            <!-- header -->
                             <tr>
                                <td style="background-color:#0F6E56;padding:32px 40px;text-align:center;">
                                    <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:600;letter-spacing:-0.3px;">On-the-Job Training (OJT)</h1>
                                    <p style="margin:6px 0 0;color:#9FE1CB;font-size:13px;">{$SchoolName}</p>
                                </td>
                            </tr>
                            <!-- body -->
                             <tr>
                                <td style="padding:40px 40px 32px;">
                                    <!-- icon -->
                                     <div style="text-align:center;margin-bottom:28px; display:none;">
                                        <div style="display:inline-block;background-color:#E1F5EE;border-radius:50%;width:64px;height:64px;line-height:64px;text-align:center;">
                                            <span style="font-size:28px;">&#128274;</span>
                                        </div>
                                    </div>
                                    <h2 style="margin:0 0 8px;color:#111827;font-size:20px;font-weight:600;text-align:center;">Reset your password</h2>
                                    <p style="margin:0 0 28px;color:#6b7280;font-size:14px;line-height:1.6;text-align:center;">We received a request to reset your password. Click the button below to choose a new one.</p>
                                    <!-- button -->
                                     <div style="text-align:center;margin-bottom:28px;">
                                        <a href="{$resetLink}"
                                        style="display:inline-block;background-color:#0F6E56;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:14px 36px;border-radius:8px;letter-spacing:0.1px;">Reset Password</a>
                                    </div>
                                    <!-- or copy link -->
                                     <p style="margin:0 0 8px;color:#6b7280;font-size:13px;text-align:center;">Or copy this link into your browser:</p>
                                     <div style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;margin-bottom:28px;word-break:break-all;">
                                        <a href="{$resetLink}" style="color:#0F6E56;font-size:12px;text-decoration:none;">
                                            {$resetLink}</a>
                                        </div>
                                        <!-- expiry notice -->
                                         <div style="background-color:#FEF9EE;border:1px solid #FDE68A;border-radius:8px;padding:14px 16px;margin-bottom:28px;">
                                            <p style="margin:0;color:#92400E;font-size:13px;line-height:1.5;">
                                                ⏱ This link expires on <strong>{$expiryFormatted} (Philippine Time)</strong>.
                                                If it expires, you can request a new one from the login page.
                                            </p>
                                        </div>
                                        <!-- security note -->
                                         <p style="margin:0;color:#9ca3af;font-size:13px;line-height:1.6;">
                                            If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged.
                                        </p>
                                    </td>
                                </tr>
                                <!-- footer -->
                                 <tr>
                                    <td style="background-color:#f9fafb;border-top:1px solid #f3f4f6;padding:20px 40px;text-align:center;">
                                        <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">
                                            This email was sent by <strong style="color:#6b7280;">OJT Management System</strong>.
                                            Do not reply to this email.
                                        </p>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
    HTML;
}