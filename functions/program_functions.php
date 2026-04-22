<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function getAllPrograms($conn, bool $activeOnly = false): array
{
    $where  = $activeOnly ? 'WHERE p.is_active = 1' : '';

    $result = $conn->query("
        SELECT
          p.uuid,
          p.code,
          p.name,
          p.department,
          p.required_hours,
          p.is_active,
          p.created_at,

          -- student count using this program
          COUNT(DISTINCT sp.id) AS student_count

        FROM programs p
        LEFT JOIN student_profiles sp ON p.uuid = sp.program_uuid
        {$where}
        GROUP BY p.id
        ORDER BY student_count DESC, p.is_active DESC, p.code ASC
    ");

    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = formatProgram($row);
    }

    return $programs;
}


function getProgram($conn, string $programUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          p.*,
          COUNT(DISTINCT sp.id) AS student_count
        FROM programs p
        LEFT JOIN student_profiles sp ON p.uuid = sp.program_uuid
        WHERE p.uuid = ?
        GROUP BY p.id
        LIMIT 1
    ");
    $stmt->bind_param('s', $programUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? formatProgram($row) : null;
}

function getProgramsForDropdown($conn): array
{
    $result = $conn->query("
        SELECT uuid, code, name, required_hours
        FROM programs
        WHERE is_active = 1
        ORDER BY code ASC
    ");

    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = [
            'uuid'           => $row['uuid'],
            'code'           => $row['code'],
            'name'           => $row['name'],
            'label'          => $row['code'] . ' — ' . $row['name'],
            'required_hours' => (int) $row['required_hours'],
        ];
    }

    return $programs;
}

function createProgram($conn, array $data, string $actorUuid): array
{
    $errors = [];

    $code       = strtoupper(trim($data['code']           ?? ''));
    $name       = trim($data['name']                       ?? '');
    $department = trim($data['department']                 ?? '');
    $hours      = (int) ($data['required_hours']           ?? 486);
    $isActive   = (int) ($data['is_active']                ?? 1);

    // validate
    if (empty($code)) {
        $errors['code'] = 'Program code is required.';
    } elseif (!preg_match('/^[A-Z0-9]+$/', $code)) {
        $errors['code'] = 'Code must contain only letters and numbers.';
    } elseif (strlen($code) > 20) {
        $errors['code'] = 'Code must be 20 characters or less.';
    }

    if (empty($name)) {
        $errors['name'] = 'Program name is required.';
    }

    if ($hours < 1) {
        $errors['required_hours'] = 'Required hours must be at least 1.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // check duplicate code
    $stmt = $conn->prepare("SELECT id FROM programs WHERE code = ? LIMIT 1");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        return [
            'success' => false,
            'errors'  => ['code' => "Program code {$code} already exists."],
        ];
    }

    $programUuid = generateUuid();

    $stmt = $conn->prepare("
        INSERT INTO programs
          (uuid, code, name, department, required_hours, is_active, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'ssssiis',
        $programUuid, $code, $name,
        $department, $hours, $isActive,
        $actorUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'other',
        description: "Admin added program {$code} — {$name}",
        module: 'system',
        actorUuid: $actorUuid,
        targetUuid: $programUuid
    );

    return ['success' => true, 'uuid' => $programUuid];
}

function updateProgram($conn, string $programUuid, array $data, string $actorUuid): array
{
    $errors = [];

    $code       = strtoupper(trim($data['code']     ?? ''));
    $name       = trim($data['name']                 ?? '');
    $department = trim($data['department']           ?? '');
    $hours      = (int) ($data['required_hours']     ?? 486);
    $isActive   = (int) ($data['is_active']          ?? 1);

    if (empty($code)) {
        $errors['code'] = 'Program code is required.';
    } elseif (!preg_match('/^[A-Z0-9]+$/', $code)) {
        $errors['code'] = 'Code must contain only letters and numbers.';
    } elseif (strlen($code) > 20) {
        $errors['code'] = 'Code must be 20 characters or less.';
    }

    if (empty($name)) {
        $errors['name'] = 'Program name is required.';
    }

    if ($hours < 1) {
        $errors['required_hours'] = 'Required hours must be at least 1.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // check duplicate code — exclude current
    $stmt = $conn->prepare("
        SELECT id FROM programs WHERE code = ? AND uuid != ? LIMIT 1
    ");
    $stmt->bind_param('ss', $code, $programUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        return [
            'success' => false,
            'errors'  => ['code' => "Program code {$code} is already used by another program."],
        ];
    }

    $stmt = $conn->prepare("
        UPDATE programs
        SET code           = ?,
            name           = ?,
            department     = ?,
            required_hours = ?,
            is_active      = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param(
        'sssiss',
        $code, $name, $department,
        $hours, $isActive,
        $programUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'other',
        description: "Admin updated program {$code} — {$name}",
        module: 'system',
        actorUuid: $actorUuid,
        targetUuid: $programUuid
    );

    return ['success' => true];
}

function toggleProgram($conn, string $programUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("
        SELECT code, name, is_active FROM programs WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $programUuid);
    $stmt->execute();
    $prog = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prog) {
        return ['success' => false, 'error' => 'Program not found.'];
    }

    $newStatus = (int) $prog['is_active'] === 1 ? 0 : 1;
    $action    = $newStatus === 1 ? 'enabled' : 'disabled';

    $stmt = $conn->prepare("UPDATE programs SET is_active = ? WHERE uuid = ?");
    $stmt->bind_param('is', $newStatus, $programUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'other',
        description: "Admin {$action} program {$prog['code']} — {$prog['name']}",
        module: 'system',
        actorUuid: $actorUuid,
        targetUuid: $programUuid
    );

    return [
        'success'    => true,
        'new_status' => $newStatus,
        'action'     => $action,
    ];
}

function formatProgram(array $row): array
{
    return [
        'uuid'           => $row['uuid'],
        'code'           => $row['code'],
        'name'           => $row['name'],
        'department'     => $row['department']   ?? '—',
        'required_hours' => (int) $row['required_hours'],
        'is_active'      => (int) $row['is_active'],
        'status_label'   => (int) $row['is_active'] === 1 ? 'Active' : 'Inactive',
        'student_count'  => (int) ($row['student_count'] ?? 0),
        'created_at'     => isset($row['created_at'])
                              ? date('M j, Y', strtotime($row['created_at']))
                              : null,
    ];
}