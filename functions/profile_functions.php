<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function getProfileImageDirectory(): array
{
    $projectRoot = dirname(__DIR__);
    $candidates = [
        'Assets/Images/Profiles',
        'Assets/Images/profiles',
    ];

    foreach ($candidates as $relativeDir) {
        $absoluteDir = $projectRoot . '/' . $relativeDir . '/';
        if (is_dir($absoluteDir)) {
            return [
                'absolute_dir' => $absoluteDir,
                'relative_dir' => $relativeDir,
            ];
        }
    }

    // default location if neither exists yet
    return [
        'absolute_dir' => $projectRoot . '/Assets/Images/Profiles/',
        'relative_dir' => 'Assets/Images/Profiles',
    ];
}

function resolveOldProfileImagePath(?string $oldFilePath): ?string
{
    if (empty($oldFilePath)) {
        return null;
    }

    $projectRoot = dirname(__DIR__);
    $pathOnly = parse_url($oldFilePath, PHP_URL_PATH) ?: $oldFilePath;
    $normalized = ltrim(str_replace('\\', '/', $pathOnly), '/');

    if (str_contains($normalized, '..')) {
        return null;
    }

    $allowedRoots = [
        'Assets/Images/Profiles/',
        'Assets/Images/profiles/',
    ];

    foreach ($allowedRoots as $root) {
        if (str_starts_with($normalized, $root)) {
            return $projectRoot . '/' . $normalized;
        }
    }

    // fallback: if only filename is stored, resolve in active image directory
    $fileName = basename($normalized);
    if ($fileName === '' || $fileName === '.' || $fileName === '..') {
        return null;
    }

    $dirInfo = getProfileImageDirectory();
    return $dirInfo['absolute_dir'] . $fileName;
}

function saveProfileImage(string $base64Data, string $userUuid, string $oldFilePath = null): ?array
{
    // decode base64 — handle data URI prefix
    if (str_contains($base64Data, ',')) {
        $base64Data = explode(',', $base64Data)[1];
    }

    $imageData = base64_decode($base64Data);

    if ($imageData === false || strlen($imageData) === 0) {
        return null;
    }

    // validate it's actually an image
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);

    if (!in_array($mimeType, ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])) {
        return null;
    }

    // determine extension from mime type
    $ext = match($mimeType) {
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/webp'              => 'webp',
        default                   => 'png',
    };

    $timestamp    = time();
    $fileName     = $userUuid . '-' . $timestamp . '.' . $ext;
    $dirInfo      = getProfileImageDirectory();
    $absoluteDir  = $dirInfo['absolute_dir'];
    $absolutePath = $absoluteDir . $fileName;
    $relativePath = $dirInfo['relative_dir'] . '/' . $fileName;

    // create directory if not exists
    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    // delete old image if exists
    if ($oldFilePath) {
        $oldAbsolutePath = resolveOldProfileImagePath($oldFilePath);
        if ($oldAbsolutePath && file_exists($oldAbsolutePath)) {
            unlink($oldAbsolutePath);
        }
    }

    // save new image
    if (file_put_contents($absolutePath, $imageData) === false) {
        return null;
    }

    return [
        'relative_path' => $relativePath,
        'file_name'     => $fileName,
    ];
}

function saveAdminProfile($conn, string $userUuid, array $data, ?string $base64Image = null): array
{
    $errors = [];

    $firstName  = trim($data['first_name']  ?? '');
    $lastName   = trim($data['last_name']   ?? '');
    $middleName = trim($data['middle_name'] ?? '');
    $mobile     = trim($data['mobile']      ?? '');

    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required.';
    }
    if (empty($lastName)) {
        $errors['last_name']  = 'Last name is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // handle image upload
    $profilePath = null;
    $profileName = null;

    if (!empty($base64Image)) {
        // get current image path for deletion
        $stmt = $conn->prepare("SELECT profile_path FROM admin_profiles WHERE user_uuid = ? LIMIT 1");
        $stmt->bind_param('s', $userUuid);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $imageResult = saveProfileImage($base64Image, $userUuid, $current['profile_path'] ?? null);

        if (!$imageResult) {
            return ['success' => false, 'errors' => ['profile_image' => 'Invalid image. Use PNG, JPG, or WebP.']];
        }

        $profilePath = $imageResult['relative_path'];
        $profileName = $imageResult['file_name'];
    }

    // check if profile exists
    $stmt = $conn->prepare("SELECT id FROM admin_profiles WHERE user_uuid = ? LIMIT 1");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        // UPDATE
        if ($profilePath) {
            $stmt = $conn->prepare("
                UPDATE admin_profiles
                SET first_name   = ?,
                    last_name    = ?,
                    middle_name  = ?,
                    contact_number= ?,
                    profile_path = ?,
                    profile_name = ?
                WHERE user_uuid = ?
            ");
            $stmt->bind_param('sssssss', $firstName, $lastName, $middleName, $mobile, $profilePath, $profileName, $userUuid);
        } else {
            $stmt = $conn->prepare("
                UPDATE admin_profiles
                SET first_name  = ?,
                    last_name   = ?,
                    middle_name = ?,
                    contact_number= ?
                WHERE user_uuid = ?
            ");
            $stmt->bind_param('sssss', $firstName, $lastName, $middleName, $mobile, $userUuid);
        }
    } else {
        // INSERT
        $profileUuid = generateUuid();
        $stmt = $conn->prepare("
            INSERT INTO admin_profiles
              (uuid, user_uuid, first_name, last_name, middle_name, contact_number, profile_path, profile_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'ssssssss',
            $profileUuid,
            $userUuid,
            $firstName,
            $lastName,
            $middleName,
            $mobile,
            $profilePath,
            $profileName
        );
    }

    $stmt->execute();
    $stmt->close();

    // update session name
    $_SESSION['user_name']       = trim($firstName . ' ' . $lastName);
    $_SESSION['user_first_name'] = $firstName;
    $_SESSION['user_initials']   = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

    logActivity(
        conn: $conn,
        eventType: 'profile_updated',
        description: "{$firstName} {$lastName} updated their admin profile",
        module: 'users',
        actorUuid: $userUuid,
        targetUuid: $userUuid
    );

    return [
        'success'      => true,
        'profile_path' => $profilePath,
        'profile_name' => $profileName,
    ];
}

// -----------------------------------------------
function saveCoordinatorProfile($conn, string $userUuid, array $data, ?string $base64Image = null): array
{
    $errors = [];

    $firstName  = trim($data['first_name']  ?? '');
    $lastName   = trim($data['last_name']   ?? '');
    $middleName = trim($data['middle_name'] ?? '');
    $mobile     = trim($data['mobile']      ?? '');
    $department = trim($data['department']  ?? '');
    $employeeId = trim($data['employee_id'] ?? '');

    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required.';
    }
    if (empty($lastName)) {
        $errors['last_name']  = 'Last name is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // handle image
    $profilePath = null;
    $profileName = null;

    if (!empty($base64Image)) {
        $stmt = $conn->prepare("SELECT profile_path FROM coordinator_profiles WHERE user_uuid = ? LIMIT 1");
        $stmt->bind_param('s', $userUuid);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $imageResult = saveProfileImage($base64Image, $userUuid, $current['profile_path'] ?? null);

        if (!$imageResult) {
            return ['success' => false, 'errors' => ['profile_image' => 'Invalid image. Use PNG, JPG, or WebP.']];
        }

        $profilePath = $imageResult['relative_path'];
        $profileName = $imageResult['file_name'];
    }

    // check exists
    $stmt = $conn->prepare("SELECT id FROM coordinator_profiles WHERE user_uuid = ? LIMIT 1");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        if ($profilePath) {
            $stmt = $conn->prepare("
                UPDATE coordinator_profiles
                SET first_name   = ?,
                    last_name    = ?,
                    middle_name  = ?,
                    mobile       = ?,
                    department   = ?,
                    profile_path = ?,
                    profile_name = ?
                WHERE user_uuid = ?
            ");
            $stmt->bind_param('ssssssss', $firstName, $lastName, $middleName, $mobile, $department, $profilePath, $profileName, $userUuid);
        } else {
            $stmt = $conn->prepare("
                UPDATE coordinator_profiles
                SET first_name  = ?,
                    last_name   = ?,
                    middle_name = ?,
                    mobile      = ?,
                    department  = ?
                WHERE user_uuid = ?
            ");
            $stmt->bind_param('ssssss', $firstName, $lastName, $middleName, $mobile, $department, $userUuid);
        }
    } else {
        $profileUuid = generateUuid();
        $stmt = $conn->prepare("
            INSERT INTO coordinator_profiles
              (uuid, user_uuid, first_name, last_name, middle_name,
               mobile, department, employee_id, profile_path, profile_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'ssssssssss',
            $profileUuid,
            $userUuid,
            $firstName,
            $lastName,
            $middleName,
            $mobile,
            $department,
            $employeeId,
            $profilePath,
            $profileName
        );
    }

    $stmt->execute();
    $stmt->close();

    $_SESSION['user_name']       = trim($firstName . ' ' . $lastName);
    $_SESSION['user_first_name'] = $firstName;
    $_SESSION['user_initials']   = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

    logActivity(
        conn: $conn,
        eventType: 'profile_updated',
        description: "{$firstName} {$lastName} updated their coordinator profile",
        module: 'users',
        actorUuid: $userUuid,
        targetUuid: $userUuid
    );

    return [
        'success'      => true,
        'profile_path' => $profilePath,
        'profile_name' => $profileName,
    ];
}


// -----------------------------------------------
// SAVE student profile
// students can only edit personal info
// program, batch, coordinator — coordinator-only
// -----------------------------------------------
function saveStudentProfile($conn, string $userUuid, array $data, ?string $base64Image = null): array
{
    $errors = [];

    $firstName      = trim($data['first_name']        ?? '');
    $lastName       = trim($data['last_name']         ?? '');
    $middleName     = trim($data['middle_name']        ?? '');
    $mobile         = trim($data['mobile']             ?? '');
    $homeAddress    = trim($data['home_address']       ?? '');
    $emergContact   = trim($data['emergency_contact']  ?? '');
    $emergPhone     = trim($data['emergency_phone']    ?? '');
    $section         = trim($data['section']            ?? '');

    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required.';
    }
    if (empty($lastName)) {
        $errors['last_name']  = 'Last name is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // handle image
    $profilePath = null;
    $profileName = null;

    if (!empty($base64Image)) {
        $stmt = $conn->prepare("SELECT profile_path FROM student_profiles WHERE user_uuid = ? LIMIT 1");
        $stmt->bind_param('s', $userUuid);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $imageResult = saveProfileImage($base64Image, $userUuid, $current['profile_path'] ?? null);

        if (!$imageResult) {
            return ['success' => false, 'errors' => ['profile_image' => 'Invalid image. Use PNG, JPG, or WebP.']];
        }

        $profilePath = $imageResult['relative_path'];
        $profileName = $imageResult['file_name'];
    }

    // check exists
    $stmt = $conn->prepare("SELECT id FROM student_profiles WHERE user_uuid = ? LIMIT 1");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        if ($profilePath) {
            $stmt = $conn->prepare("
                UPDATE student_profiles
                SET first_name        = ?,
                    last_name         = ?,
                    middle_name       = ?,
                    mobile            = ?,
                    home_address      = ?,
                    emergency_contact = ?,
                    emergency_phone   = ?,
                    profile_path      = ?,
                    profile_name      = ?,
                    section           = ?
                WHERE user_uuid = ?
            ");
            $stmt->bind_param(
                'sssssssssss',
                $firstName,
                $lastName,
                $middleName,
                $mobile,
                $homeAddress,
                $emergContact,
                $emergPhone,
                $profilePath,
                $profileName,
                $section,
                $userUuid
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE student_profiles
                SET first_name        = ?,
                    last_name         = ?,
                    middle_name       = ?,
                    mobile            = ?,
                    home_address      = ?,
                    emergency_contact = ?,
                    emergency_phone   = ?,
                    section           = ?
                WHERE user_uuid = ?
            ");
            $stmt->bind_param(
                'sssssssss',
                $firstName,
                $lastName,
                $middleName,
                $mobile,
                $homeAddress,
                $emergContact,
                $emergPhone,
                $section,
                $userUuid
            );
        }

        $stmt->execute();
        $stmt->close();
    }
    // note: student profiles are created by coordinator
    // so INSERT is not needed here — only UPDATE

    $_SESSION['user_name']       = trim($firstName . ' ' . $lastName);
    $_SESSION['user_first_name'] = $firstName;
    $_SESSION['user_initials']   = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

    logActivity(
        conn: $conn,
        eventType: 'profile_updated',
        description: "{$firstName} {$lastName} updated their student profile",
        module: 'users',
        actorUuid: $userUuid,
        targetUuid: $userUuid
    );

    return [
        'success'      => true,
        'profile_path' => $profilePath,
        'profile_name' => $profileName,
    ];
}


// -----------------------------------------------
// SAVE supervisor profile
// -----------------------------------------------
function saveSupervisorProfile($conn, string $userUuid, array $data, ?string $base64Image = null): array
{
    $errors = [];

    $firstName  = trim($data['first_name']  ?? '');
    $lastName   = trim($data['last_name']   ?? '');
    $mobile     = trim($data['mobile']      ?? '');
    $position   = trim($data['position']    ?? '');
    $department = trim($data['department']  ?? '');

    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required.';
    }
    if (empty($lastName)) {
        $errors['last_name']  = 'Last name is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // handle image
    $profilePath = null;
    $profileName = null;

    if (!empty($base64Image)) {
        $stmt = $conn->prepare("SELECT profile_path FROM supervisor_profiles WHERE user_uuid = ? LIMIT 1");
        $stmt->bind_param('s', $userUuid);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $imageResult = saveProfileImage($base64Image, $userUuid, $current['profile_path'] ?? null);

        if (!$imageResult) {
            return ['success' => false, 'errors' => ['profile_image' => 'Invalid image. Use PNG, JPG, or WebP.']];
        }

        $profilePath = $imageResult['relative_path'];
        $profileName = $imageResult['file_name'];
    }

    // check exists
    $stmt = $conn->prepare("SELECT id FROM supervisor_profiles WHERE user_uuid = ? LIMIT 1");
    $stmt->bind_param('s', $userUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        if ($profilePath) {
            $stmt = $conn->prepare("
                UPDATE supervisor_profiles
                SET first_name   = ?,
                    last_name    = ?,
                    mobile       = ?,
                    position     = ?,
                    department   = ?,
                    profile_path = ?,
                    profile_name = ?
                WHERE user_uuid = ?
            ");
            $stmt->bind_param(
                'ssssssss',
                $firstName,
                $lastName,
                $mobile,
                $position,
                $department,
                $profilePath,
                $profileName,
                $userUuid
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE supervisor_profiles
                SET first_name  = ?,
                    last_name   = ?,
                    mobile      = ?,
                    position    = ?,
                    department  = ?
                WHERE user_uuid = ?
            ");
            $stmt->bind_param(
                'ssssss',
                $firstName,
                $lastName,
                $mobile,
                $position,
                $department,
                $userUuid
            );
        }

        $stmt->execute();
        $stmt->close();
    }
    // supervisors are created by coordinator — no INSERT needed

    $_SESSION['user_name']       = trim($firstName . ' ' . $lastName);
    $_SESSION['user_first_name'] = $firstName;
    $_SESSION['user_initials']   = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

    logActivity(
        conn: $conn,
        eventType: 'profile_updated',
        description: "{$firstName} {$lastName} updated their supervisor profile",
        module: 'users',
        actorUuid: $userUuid,
        targetUuid: $userUuid
    );

    return [
        'success'      => true,
        'profile_path' => $profilePath,
        'profile_name' => $profileName,
    ];
}


// -----------------------------------------------
// SAVE profile by role — single entry point
// -----------------------------------------------
function saveProfileByRole($conn, string $userUuid, string $role, array $data, ?string $base64Image = null): array
{
    return match($role) {
        'admin'       => saveAdminProfile($conn, $userUuid, $data, $base64Image),
        'coordinator' => saveCoordinatorProfile($conn, $userUuid, $data, $base64Image),
        'student'     => saveStudentProfile($conn, $userUuid, $data, $base64Image),
        'supervisor'  => saveSupervisorProfile($conn, $userUuid, $data, $base64Image),
        default       => ['success' => false, 'errors' => ['general' => 'Invalid role.']],
    };
}
