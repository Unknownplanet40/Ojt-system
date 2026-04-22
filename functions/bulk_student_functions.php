<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/student_functions.php';

function parseBulkFile(array $file): array
{
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext === 'csv') {
        return parseCsvFile($file['tmp_name']);
    } elseif ($ext === 'xlsx') {
        return parseXlsxFile($file['tmp_name']);
    }

    return ['error' => 'Unsupported file type. Use CSV or XLSX.'];
}


function parseCsvFile(string $filePath): array
{
    $rows = [];
    $headers = [];

    if (($handle = fopen($filePath, 'r')) === false) {
        return ['error' => 'Failed to open file.'];
    }

    $lineNum = 0;
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $lineNum++;

        if ($lineNum === 1) {
            // first row is headers — normalize
            $headers = array_map(fn ($h) => strtolower(trim($h)), $data);
            continue;
        }

        if (empty(array_filter($data))) {
            continue;
        } // skip blank rows

        $row = [];
        foreach ($headers as $i => $header) {
            $row[$header] = trim($data[$i] ?? '');
        }
        $row['_row'] = $lineNum;
        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
}


function parseXlsxFile(string $filePath): array
{
    $autoload = dirname(__DIR__, 2) . '/Libs/composer/vendor/autoload.php';

    if (!file_exists($autoload)) {
        return ['error' => 'PhpSpreadsheet not installed. Use CSV instead.'];
    }

    require_once $autoload;

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $data        = $sheet->toArray(null, true, true, false);

        if (empty($data)) {
            return ['error' => 'File is empty.'];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string)$h)), $data[0]);
        $rows    = [];

        for ($i = 1; $i < count($data); $i++) {
            $rawRow = $data[$i];

            if (empty(array_filter(array_map('strval', $rawRow)))) {
                continue;
            }

            $row = [];
            foreach ($headers as $j => $header) {
                $row[$header] = trim((string)($rawRow[$j] ?? ''));
            }
            $row['_row'] = $i + 1;
            $rows[] = $row;
        }

        return $rows;

    } catch (Exception $e) {
        return ['error' => 'Failed to read Excel file: ' . $e->getMessage()];
    }
}


function findCoordinatorUuidByName($conn, string $coordinatorName): ?string
{
    $coordinatorName = trim($coordinatorName);
    if ($coordinatorName === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT uuid FROM coordinator_profiles WHERE LOWER(TRIM(CONCAT(first_name, ' ', last_name))) = LOWER(TRIM(?)) LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $coordinatorName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row['uuid'] ?? null;
}

function validateBulkRows($conn, array $rows, string $batchUuid, string $coordinatorUuid): array
{
    $result   = $conn->query("SELECT uuid, code FROM programs WHERE is_active = 1");
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[strtoupper($row['code'])] = $row['uuid'];
    }

    $coordResult = $conn->query("SELECT uuid, first_name, last_name FROM coordinator_profiles");
    $coordinatorsByName = [];
    $coordinatorNamesByUuid = [];
    while ($row = $coordResult->fetch_assoc()) {
        $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
        $coordinatorsByName[strtolower($fullName)] = $row['uuid'];
        $coordinatorNamesByUuid[$row['uuid']] = $fullName;
    }

    $coordinatorName = $coordinatorNamesByUuid[$coordinatorUuid] ?? '';

    $existingEmails  = [];
    $existingNumbers = [];

    $emailResult = $conn->query("SELECT email FROM users");
    while ($row = $emailResult->fetch_assoc()) {
        $existingEmails[strtolower($row['email'])] = true;
    }

    $numResult = $conn->query("SELECT student_number FROM student_profiles");
    while ($row = $numResult->fetch_assoc()) {
        $existingNumbers[$row['student_number']] = true;
    }

    $validRows  = [];
    $errorRows  = [];

    $seenEmails  = [];
    $seenNumbers = [];

    foreach ($rows as $row) {
        $errors = [];

        $lastName      = trim($row['last_name']                 ?? '');
        $firstName     = trim($row['first_name']                ?? '');
        $middleName    = trim($row['middle_name']               ?? '');
        $email         = strtolower(trim($row['email']          ?? ''));
        $studentNumber = trim($row['student_number']            ?? '');
        $programCode   = strtoupper(trim($row['program_code']   ?? ''));
        $yearLevel     = (int) ($row['year_level']              ?? 0);
        $section       = trim($row['section']                   ?? '');
        $mobile        = trim($row['mobile']                    ?? '');
        $rowCoordinatorName = trim($row['coordinator_name']     ?? '');
        $rowNum        = $row['_row'];

        $rowCoordinatorUuid = $coordinatorUuid;
        $rowCoordinatorNameForRow = $coordinatorName;

        if (!empty($rowCoordinatorName)) {
            $coordNameLower = strtolower($rowCoordinatorName);
            if (isset($coordinatorsByName[$coordNameLower])) {
                $rowCoordinatorUuid = $coordinatorsByName[$coordNameLower];
                $rowCoordinatorNameForRow = $coordinatorNamesByUuid[$rowCoordinatorUuid] ?? $rowCoordinatorName;
            } else {
                $errors[] = "Coordinator '{$rowCoordinatorName}' not found";
            }
        }

        if (empty($rowCoordinatorUuid)) {
            $errors[] = 'Coordinator is required. Set coordinator_name in file or provide a default coordinator.';
        }

        if (empty($lastName)) {
            $errors[] = 'Last name is required';
        } elseif (strlen($lastName) < 2) {
            $errors[] = 'Last name must be at least 2 characters long';
        } elseif (strlen($lastName) > 50) {
            $errors[] = 'Last name cannot exceed 50 characters';
        }


        if (empty($firstName)) {
            $errors[] = 'First name is required';
        } elseif (strlen($firstName) > 50) {
            $errors[] = 'First name cannot exceed 50 characters';
        }

        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (isset($existingEmails[$email])) {
            $errors[] = "Email {$email} is already registered";
        } elseif (isset($seenEmails[$email])) {
            $errors[] = "Duplicate email in file (row {$seenEmails[$email]})";
        }

        if (empty($studentNumber)) {
            $errors[] = 'Student number is required';
        } elseif (isset($existingNumbers[$studentNumber])) {
            $errors[] = "Student number {$studentNumber} is already registered";
        } elseif (isset($seenNumbers[$studentNumber])) {
            $errors[] = "Duplicate student number in file (row {$seenNumbers[$studentNumber]})";
        } elseif (!empty($studentNumber) && strlen($studentNumber) < 9) {
            $errors[] = "Student number must be at least 9 characters long";
        }

        if (!empty($mobile) && !preg_match('/^09\d{9}$/', str_replace([' ', '-', '(', ')'], '', $mobile))) {
            $errors[] = 'Invalid mobile number format. Use 09XXXXXXXXX.';
        } elseif (!empty($mobile) && strlen(str_replace([' ', '-', '(', ')'], '', $mobile)) != 11) {
            $errors[] = 'Mobile number must be 11 digits long (including the leading 0).';
        } elseif (!empty($mobile) && !preg_match('/^09\d{9}$/', str_replace([' ', '-', '(', ')'], '', $mobile))) {
            $errors[] = 'Mobile number must start with 09 followed by 9 digits.';
        } elseif (!empty($mobile) && preg_match('/[^0-9\s\-\(\)]/', $mobile)) {
            $errors[] = 'Mobile number can only contain digits, spaces, dashes, and parentheses.';
        }


        if (empty($programCode)) {
            $errors[] = 'Program code is required';
        } elseif (!isset($programs[$programCode])) {
            $errors[] = "Program code {$programCode} not found. Use: " . implode(', ', array_keys($programs));
        }

        if ($yearLevel < 1 || $yearLevel > 4) {
            $errors[] = 'Year level must be 1, 2, 3, or 4';
        }

        $cleanRow = [
            'row_num'          => $rowNum,
            'last_name'        => $lastName,
            'first_name'       => $firstName,
            'middle_name'      => $middleName,
            'full_name'        => trim("{$firstName} {$middleName} {$lastName}"),
            'email'            => $email,
            'student_number'   => $studentNumber,
            'program_code'     => $programCode,
            'program_uuid'     => $programs[$programCode] ?? null,
            'year_level'       => $yearLevel,
            'year_label'       => ordinal($yearLevel) . ' Year',
            'section'          => $section,
            'mobile'           => $mobile,
            'coordinator_uuid' => $rowCoordinatorUuid,
            'coordinator_name' => $rowCoordinatorNameForRow,
            'batch_uuid'       => $batchUuid,
        ];

        if (empty($errors)) {
            $seenEmails[$email]          = $rowNum;
            $seenNumbers[$studentNumber] = $rowNum;
            $cleanRow['status'] = 'valid';
            $validRows[]        = $cleanRow;
        } else {
            $cleanRow['status'] = 'error';
            $cleanRow['errors'] = $errors;
            $errorRows[]        = $cleanRow;
        }
    }

    return [
        'valid_rows'  => $validRows,
        'error_rows'  => $errorRows,
        'total'       => count($rows),
        'valid_count' => count($validRows),
        'error_count' => count($errorRows),
    ];
}

function createBulkStudents($conn, array $validRows, string $actorUuid): array
{
    $created = [];
    $failed  = [];

    foreach ($validRows as $row) {
        $userUuid     = generateUuid();
        $profileUuid  = generateUuid();
        $tempPassword = generateTempPassword();
        $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                INSERT INTO users
                  (uuid, email, password_hash, role, is_active, must_change_password, created_by)
                VALUES (?, ?, ?, 'student', 1, 1, ?)
            ");
            $stmt->bind_param('ssss', $userUuid, $row['email'], $passwordHash, $actorUuid);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO student_profiles
                  (uuid, user_uuid, student_number, last_name, first_name, middle_name,
                   mobile, program_uuid, year_level, section, coordinator_uuid, batch_uuid)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                'ssssssssssss',
                $profileUuid,
                $userUuid,
                $row['student_number'],
                $row['last_name'],
                $row['first_name'],
                $row['middle_name'],
                $row['mobile'],
                $row['program_uuid'],
                $row['year_level'],
                $row['section'],
                $row['coordinator_uuid'],
                $row['batch_uuid']
            );
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            initializeRequirements($conn, $profileUuid, $row['batch_uuid']);

            $created[] = [
                'row_num'        => $row['row_num'],
                'full_name'      => $row['full_name'],
                'email'          => $row['email'],
                'student_number' => $row['student_number'],
                'program_code'   => $row['program_code'],
                'year_label'     => $row['year_label'],
                'section'        => $row['section'],
                'coordinator_name' => $row['coordinator_name'],
                'temp_password'  => $tempPassword,
            ];

        } catch (Exception $e) {
            $conn->rollback();
            $failed[] = [
                'row_num'   => $row['row_num'],
                'full_name' => $row['full_name'],
                'email'     => $row['email'],
                'error'     => 'Database error: ' . $e->getMessage(),
            ];
        }
    }

    if (!empty($created)) {
        logActivity(
            conn: $conn,
            eventType: 'account_created',
            description: count($created) . ' student accounts created via bulk import',
            module: 'students',
            actorUuid: $actorUuid
        );
    }

    return [
        'created'       => $created,
        'failed'        => $failed,
        'created_count' => count($created),
        'failed_count'  => count($failed),
    ];
}

function generateBulkTemplate($conn): string
{
    $result   = $conn->query("SELECT code FROM programs WHERE is_active = 1 ORDER BY code");
    $codes    = [];
    while ($row = $result->fetch_assoc()) {
        $codes[] = $row['code'];
    }
    $codeList = implode('/', $codes);

    $coordResult = $conn->query("SELECT uuid, first_name, last_name FROM coordinator_profiles ORDER BY first_name");
    $coordinators = [];
    $coordinatorNames = [];
    while ($row = $coordResult->fetch_assoc()) {
        $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
        $coordinators[$row['uuid']] = $fullName;
        $coordinatorNames[] = $fullName;
    }
    $coordinatorList = implode(' / ', $coordinatorNames);

    $headers = [
        'last_name',
        'first_name',
        'middle_name',
        'email',
        'student_number',
        'program_code',
        'year_level',
        'section',
        'mobile',
        'coordinator_name',
    ];

    $sample = [
        'Dela Cruz',
        'Juan',
        'Santos',
        'juan.delacruz@student.edu.ph',
        '2024-00001',
        'BSIT',
        '4',
        'A',
        '09171234567',
        'John Doe',
    ];

    $instructions = [
        '(Required)',
        '(Required)',
        '(Optional)',
        '(Required - valid email)',
        '(Required - unique)',
        "(Required - use: {$codeList})",
        '(Required - 1 2 3 or 4)',
        '(Optional)',
        '(Optional)',
        "(Required - use: {$coordinatorList})",
    ];

    $output  = '';
    $output .= implode(',', $headers) . "\n";
    $output .= implode(',', $instructions) . "\n";
    $output .= implode(',', $sample) . "\n";

    return $output;
}

function exportCreatedAccountsCsv(array $created): string
{
    $headers = ['#', 'Full Name', 'Email', 'Student Number', 'Program', 'Year', 'Section', 'Coordinator', 'Temporary Password'];
    $output  = implode(',', $headers) . "\n";

    foreach ($created as $i => $student) {
        $row = [
            $i + 1,
            '"' . $student['full_name']      . '"',
            $student['email'],
            $student['student_number'],
            $student['program_code'],
            '"' . $student['year_label']     . '"',
            $student['section'] ?: '—',
            '"' . ($student['coordinator_name'] ?? '') . '"',
            $student['temp_password'],
        ];
        $output .= implode(',', $row) . "\n";
    }

    return $output;
}
