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

function createCompany($conn, array $data, string $actorUuid): array
{
    $errors = [];

    $name   = trim($data['name']      ?? '');
    $email  = trim($data['email']     ?? '');
    $setup  = trim($data['work_setup'] ?? '');
    $status = trim($data['accreditation_status'] ?? 'pending');

    if (empty($name)) {
        $errors['name'] = 'Company name is required.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (!in_array($setup, ['on-site', 'remote', 'hybrid'])) {
        $errors['work_setup'] = 'Select a valid work setup.';
    }

    if (!in_array($status, ['pending', 'active', 'expired', 'blacklisted'])) {
        $errors['accreditation_status'] = 'Invalid accreditation status.';
    }

    // check duplicate name
    $stmt = $conn->prepare("SELECT id FROM companies WHERE name = ? LIMIT 1");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $errors['name'] = 'A company with this name already exists.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $uuid     = generateUuid();
    $industry = trim($data['industry'] ?? '');
    $address  = trim($data['address']  ?? '');
    $city     = trim($data['city']     ?? '');
    $phone    = trim($data['phone']    ?? '');
    $website  = trim($data['website']  ?? '');

    $stmt = $conn->prepare("
        INSERT INTO companies
          (uuid, name, industry, address, city, email, phone,
           website, work_setup, accreditation_status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'sssssssssss',
        $uuid,
        $name,
        $industry,
        $address,
        $city,
        $email,
        $phone,
        $website,
        $setup,
        $status,
        $actorUuid
    );
    $stmt->execute();
    $stmt->close();

    // insert primary contact if provided
    if (!empty($data['contact_name'])) {
        addCompanyContact($conn, $uuid, [
            'name'       => $data['contact_name'],
            'position'   => $data['contact_position'] ?? '',
            'email'      => $data['contact_email']    ?? '',
            'phone'      => $data['contact_phone']    ?? '',
            'is_primary' => 1,
        ]);
    }

    // insert slots for active batch if provided
    if (!empty($data['total_slots']) && !empty($data['batch_uuid'])) {
        setCompanySlots($conn, $uuid, $data['batch_uuid'], (int) $data['total_slots']);
    }

    // insert accepted programs
    if (!empty($data['program_uuids']) && is_array($data['program_uuids'])) {
        setAcceptedPrograms($conn, $uuid, $data['program_uuids']);
    }

    logActivity(
        conn: $conn,
        eventType: 'company_added',
        description: "Added company: {$name}",
        module: 'companies',
        actorUuid: $actorUuid,
        targetUuid: $uuid
    );

    return ['success' => true, 'uuid' => $uuid];
}

function updateCompany($conn, string $companyUuid, array $data, string $actorUuid): array
{
    $errors = [];

    $name   = trim($data['name']       ?? '');
    $email  = trim($data['email']      ?? '');
    $setup  = trim($data['work_setup'] ?? '');
    $status = trim($data['accreditation_status'] ?? '');

    if (empty($name)) {
        $errors['name'] = 'Company name is required.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (!in_array($setup, ['on-site', 'remote', 'hybrid'])) {
        $errors['work_setup'] = 'Select a valid work setup.';
    }

    if (!in_array($status, ['pending', 'active', 'expired', 'blacklisted'])) {
        $errors['accreditation_status'] = 'Invalid accreditation status.';
    }

    // check duplicate name — exclude current company
    $stmt = $conn->prepare("
        SELECT id FROM companies WHERE name = ? AND uuid != ? LIMIT 1
    ");
    $stmt->bind_param('ss', $name, $companyUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $errors['name'] = 'Another company with this name already exists.';
    }

    // if blacklisted, reason is required
    if ($status === 'blacklisted' && empty($data['blacklist_reason'])) {
        $errors['blacklist_reason'] = 'Provide a reason for blacklisting.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $industry        = trim($data['industry']         ?? '');
    $address         = trim($data['address']          ?? '');
    $city            = trim($data['city']             ?? '');
    $phone           = trim($data['phone']            ?? '');
    $website         = trim($data['website']          ?? '');
    $blacklistReason = trim($data['blacklist_reason'] ?? '');

    $stmt = $conn->prepare("
        UPDATE companies
        SET name                 = ?,
            industry             = ?,
            address              = ?,
            city                 = ?,
            email                = ?,
            phone                = ?,
            website              = ?,
            work_setup           = ?,
            accreditation_status = ?,
            blacklist_reason     = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param(
        'sssssssssss',
        $name,
        $industry,
        $address,
        $city,
        $email,
        $phone,
        $website,
        $setup,
        $status,
        $blacklistReason,
        $companyUuid
    );
    $stmt->execute();
    $stmt->close();

    if (isset($data['program_uuids']) && is_array($data['program_uuids'])) {
        setAcceptedPrograms($conn, $companyUuid, $data['program_uuids']);
    }

    if (isset($data['contact_name']) || isset($data['contact_position']) ||
        isset($data['contact_email']) || isset($data['contact_phone'])) {
        // update primary contact or add if none exists

        $stmt = $conn->prepare("
            SELECT uuid FROM company_contacts
            WHERE company_uuid = ? AND is_primary = 1 LIMIT 1
        ");
        $stmt->bind_param('s', $companyUuid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $contact = $result->fetch_assoc();
            $stmt->close();

            $contactData = [
                'name'       => $data['contact_name']     ?? '',
                'position'   => $data['contact_position'] ?? '',
                'email'      => $data['contact_email']    ?? '',
                'phone'      => $data['contact_phone']    ?? '',
                'is_primary' => 1,
            ];

            // update existing primary contact
            $stmt = $conn->prepare("
                UPDATE company_contacts
                SET name = ?, position = ?, email = ?, phone = ?
                WHERE uuid = ?
            ");
            $stmt->bind_param(
                'sssss',
                $contactData['name'],
                $contactData['position'],
                $contactData['email'],
                $contactData['phone'],
                $contact['uuid']
            );
            $stmt->execute();
            $stmt->close();
        } else {
            // add new primary contact
            addCompanyContact($conn, $companyUuid, [
                'name'       => $data['contact_name']     ?? '',
                'position'   => $data['contact_position'] ?? '',
                'email'      => $data['contact_email']    ?? '',
                'phone'      => $data['contact_phone']    ?? '',
                'is_primary' => 1,
            ]);
        }
    }

logActivity(
        conn: $conn,
        eventType: 'company_updated',
        description: "Updated company: {$name}",
        module: 'companies',
        actorUuid: $actorUuid,
        targetUuid: $companyUuid
    );

    return ['success' => true];
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

function getCompany($conn, string $companyUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT * FROM companies WHERE uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$company) {
        return null;
    }

    // contacts
    $stmt = $conn->prepare("
        SELECT * FROM company_contacts
        WHERE company_uuid = ?
        ORDER BY is_primary DESC, created_at ASC
    ");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $contacts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // accepted programs
    $stmt = $conn->prepare("
        SELECT p.uuid, p.code, p.name
        FROM company_accepted_programs cap
        JOIN programs p ON cap.program_uuid = p.uuid
        WHERE cap.company_uuid = ?
        ORDER BY p.code ASC
    ");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $programs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // documents
    $stmt = $conn->prepare("
        SELECT * FROM company_documents
        WHERE company_uuid = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // slots per batch
    $stmt = $conn->prepare("
        SELECT cs.*, b.school_year, b.semester,
               COUNT(DISTINCT sp.id) AS filled_slots
        FROM company_slots cs
        JOIN batches b ON cs.batch_uuid = b.uuid
        LEFT JOIN student_profiles sp
          ON cs.company_uuid = sp.company_uuid
          AND cs.batch_uuid  = sp.batch_uuid
        WHERE cs.company_uuid = ?
        GROUP BY cs.id
        ORDER BY b.created_at DESC
    ");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return [
        'company'   => $company,
        'contacts'  => $contacts,
        'programs'  => $programs,
        'documents' => $documents,
        'slots'     => $slots,
    ];
}

function addCompanyContact($conn, string $companyUuid, array $data): array
{
    $name     = trim($data['name']     ?? '');
    $position = trim($data['position'] ?? '');
    $email    = trim($data['email']    ?? '');
    $phone    = trim($data['phone']    ?? '');
    $isPrimary = (int) ($data['is_primary'] ?? 0);

    if (empty($name)) {
        return ['success' => false, 'error' => 'Contact name is required.'];
    }

    // if setting as primary — unset all others first
    if ($isPrimary === 1) {
        $stmt = $conn->prepare("
            UPDATE company_contacts SET is_primary = 0 WHERE company_uuid = ?
        ");
        $stmt->bind_param('s', $companyUuid);
        $stmt->execute();
        $stmt->close();
    }

    $uuid = generateUuid();
    $stmt = $conn->prepare("
        INSERT INTO company_contacts
          (uuid, company_uuid, name, position, email, phone, is_primary)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'ssssssi',
        $uuid,
        $companyUuid,
        $name,
        $position,
        $email,
        $phone,
        $isPrimary
    );
    $stmt->execute();
    $stmt->close();

    return ['success' => true, 'uuid' => $uuid];
}

function setCompanySlots($conn, string $companyUuid, string $batchUuid, int $totalSlots): void
{
    // upsert — update if exists, insert if not
    $stmt = $conn->prepare("
        INSERT INTO company_slots (uuid, company_uuid, batch_uuid, total_slots)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE total_slots = VALUES(total_slots)
    ");
    $uuid = generateUuid();
    $stmt->bind_param('sssi', $uuid, $companyUuid, $batchUuid, $totalSlots);
    $stmt->execute();
    $stmt->close();
}


function setAcceptedPrograms($conn, string $companyUuid, array $programUuids): void
{
    // delete existing
    $stmt = $conn->prepare("DELETE FROM company_accepted_programs WHERE company_uuid = ?");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $stmt->close();

    // insert new
    if (empty($programUuids)) {
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO company_accepted_programs (company_uuid, program_uuid)
        VALUES (?, ?)
    ");
    foreach ($programUuids as $programUuid) {
        $programUuid = trim($programUuid);
        if (!empty($programUuid)) {
            $stmt->bind_param('ss', $companyUuid, $programUuid);
            $stmt->execute();
        }
    }
    $stmt->close();
}


function uploadCompanyDocument($conn, string $companyUuid, array $data, array $file, string $actorUuid): array
{
    $docType   = trim($data['doc_type']   ?? '');
    $validFrom = trim($data['valid_from'] ?? '');
    $validUntil = trim($data['valid_until'] ?? '');

    if (!in_array($docType, ['moa','nda','insurance','bir_cert','sec_dti','other'])) {
        return ['success' => false, 'error' => 'Invalid document type.'];
    }

    if (empty($file['name'])) {
        return ['success' => false, 'error' => 'No file uploaded.'];
    }

    // validate file type — PDF only
    $allowedTypes = ['application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Only PDF files are allowed.'];
    }

    // max 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File must be 10MB or less.'];
    }

    $fileName = '';
    if ($docType === 'moa') {
        $fileName = "Memorandum_of_Agreement-{$companyUuid}-" . date('YmdHis') . ".pdf";
    } elseif ($docType === 'nda') {
        $fileName = "Non_Disclosure_Agreement-{$companyUuid}-" . date('YmdHis') . ".pdf";
    } elseif ($docType === 'insurance') {
        $fileName = "Insurance_Certificate-{$companyUuid}-" . date('YmdHis') . ".pdf";
    } elseif ($docType === 'bir_cert') {
        $fileName = "BIR_Certificate-{$companyUuid}-" . date('YmdHis') . ".pdf";
    } elseif ($docType === 'sec_dti') {
        $fileName = "SEC_DTI_Certificate-{$companyUuid}-" . date('YmdHis') . ".pdf";
    } else {
        $fileName = "Document-{$companyUuid}-" . date('YmdHis') . ".pdf";
    }

    // save file
    $safeFileName = generateUuid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));

    $absoluteDir  = dirname(__DIR__, 2) . '/uploads/company_documents/' . $docType . '/' . $companyUuid . '/';
    $absolutePath = $absoluteDir . $safeFileName;

    // relative path stored in DB — from project root
    $relativePath = 'uploads/company_documents/' . $docType . '/' . $companyUuid . '/' . $safeFileName;

    // create folder if it doesn't exist
    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        return ['success' => false, 'error' => 'Failed to save file. Check folder permissions.'];
    }

    $uuid = generateUuid();
    $stmt = $conn->prepare("
        INSERT INTO company_documents
          (uuid, company_uuid, doc_type, file_name, file_path,
           valid_from, valid_until, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'ssssssss',
        $uuid,
        $companyUuid,
        $docType,
        $fileName,
        $relativePath,
        $validFrom,
        $validUntil,
        $actorUuid
    );
    $stmt->execute();
    $stmt->close();

    // if MOA uploaded and company was pending — auto-update to active
    if ($docType === 'moa') {
        $conn->query("
            UPDATE companies
            SET accreditation_status = 'active'
            WHERE uuid = '{$conn->real_escape_string($companyUuid)}'
              AND accreditation_status = 'pending'
        ");

        logActivity(
            conn: $conn,
            eventType: 'moa_uploaded',
            description: "MOA uploaded for company — valid until " . date('M j, Y', strtotime($validUntil)),
            module: 'companies',
            actorUuid: $actorUuid,
            targetUuid: $companyUuid
        );
    }

    return ['success' => true, 'uuid' => $uuid];
}

function getAvailableCompanies($conn, string $batchUuid, string $programUuid): array
{
    $stmt = $conn->prepare("
        SELECT
          c.uuid,
          c.name,
          c.industry,
          c.city,
          c.work_setup,
          cs.total_slots,
          COUNT(DISTINCT sp.id) AS filled_slots

        FROM companies c
        JOIN company_accepted_programs cap
          ON c.uuid = cap.company_uuid
          AND cap.program_uuid = ?
        JOIN company_slots cs
          ON c.uuid = cs.company_uuid
          AND cs.batch_uuid = ?
        LEFT JOIN student_profiles sp
          ON c.uuid = sp.company_uuid
          AND sp.batch_uuid = ?

        WHERE c.accreditation_status = 'active'

        GROUP BY c.id
        HAVING filled_slots < cs.total_slots
        ORDER BY c.name ASC
    ");
    $stmt->bind_param('sss', $programUuid, $batchUuid, $batchUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(fn ($row) => [
        'uuid'            => $row['uuid'],
        'name'            => $row['name'],
        'industry'        => $row['industry'] ?? '—',
        'city'            => $row['city']     ?? '—',
        'work_setup'      => $row['work_setup'],
        'total_slots'     => (int) $row['total_slots'],
        'filled_slots'    => (int) $row['filled_slots'],
        'remaining_slots' => (int) $row['total_slots'] - (int) $row['filled_slots'],
    ], $rows);
}


function getExpiringMoas($conn, int $daysThreshold = 30): array
{
    $result = $conn->query("
        SELECT
          c.uuid,
          c.name,
          cd.valid_until,
          DATEDIFF(cd.valid_until, CURDATE()) AS days_left
        FROM company_documents cd
        JOIN companies c ON cd.company_uuid = c.uuid
        WHERE cd.doc_type = 'moa'
          AND cd.valid_until BETWEEN CURDATE()
          AND DATE_ADD(CURDATE(), INTERVAL {$daysThreshold} DAY)
          AND c.accreditation_status = 'active'
        ORDER BY cd.valid_until ASC
    ");

    $expiring = [];
    while ($row = $result->fetch_assoc()) {
        $expiring[] = [
            'company_uuid' => $row['uuid'],
            'company_name' => $row['name'],
            'expiry_date'  => date('M j, Y', strtotime($row['valid_until'])),
            'days_left'    => (int) $row['days_left'],
        ];
    }

    return $expiring;
}

function getAllPrograms($conn): array
{
    $result = $conn->query("SELECT uuid, code, name FROM programs WHERE is_active = 1 ORDER BY code ASC");
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = [
            'uuid' => $row['uuid'],
            'code' => $row['code'],
            'name' => $row['name'],
        ];
    }
    return $programs;
}

function getCompanyStatuses($conn): array
{
    $availableStatuses = [];

    // get distinct accreditation statuses
    $result = $conn->query("
        SELECT DISTINCT accreditation_status FROM companies
        WHERE accreditation_status IN ('pending', 'active', 'expired', 'blacklisted')
    ");
    while ($row = $result->fetch_assoc()) {
        $availableStatuses[] = $row['accreditation_status'];
    }

    // get MOA-based statuses (expiring, expired) if any exist
    $result = $conn->query("
        SELECT DISTINCT
          CASE
            WHEN DATEDIFF(cd.valid_until, CURDATE()) < 0 THEN 'expired'
            WHEN DATEDIFF(cd.valid_until, CURDATE()) <= 30 THEN 'expiring'
          END AS moa_status
        FROM company_documents cd
        JOIN companies c ON cd.company_uuid = c.uuid
        WHERE cd.doc_type = 'moa'
          AND cd.valid_until IS NOT NULL
          AND c.accreditation_status = 'active'
    ");
    while ($row = $result->fetch_assoc()) {
        if ($row['moa_status'] && !in_array($row['moa_status'], $availableStatuses)) {
            $availableStatuses[] = $row['moa_status'];
        }
    }

    return $availableStatuses;
}

function companyWorkSetup($conn): array
{
    $result = $conn->query("SELECT DISTINCT work_setup FROM companies");
    $setups = [];
    while ($row = $result->fetch_assoc()) {
        $setups[] = $row['work_setup'];
    }
    return $setups;
}

function activebatch($conn)
{
    $result = $conn->query("SELECT uuid, school_year, semester FROM batches WHERE status = 'active' LIMIT 1");
    return $result->fetch_assoc();
}

$action = isset($_POST['action']) ? $_POST['action'] : null;

if (empty($action)) {
    response([
        'status' => 'error',
        'message' => 'No action specified.'
    ]);
}

if (!isset($_SESSION['user'])) {
    response([
        'status' => 'error',
        'message' => 'Unauthorized. Please log in.'
    ]);
}

$actorUuid = $_SESSION['user']['uuid'];

if ($action === 'fetch_companies') {
    $batchUuid = $_POST['batch_uuid'] ?? null;
    $companies = getAllCompanies($conn, $batchUuid);
    response([
        'status' => 'success',
        'data' => $companies,
        'programs' => getAllPrograms($conn),
        'statuses' => getCompanyStatuses($conn),
        'work_setups' => companyWorkSetup($conn),
        'active_batch' => activebatch($conn),
    ]);
} elseif ($action === 'fetch_company_details') {
    $companyUuid = $_POST['uuid'] ?? null;
    if (empty($companyUuid)) {
        response([
            'status' => 'error',
            'message' => 'Company UUID is required.'
        ]);
    }
    $details = getCompany($conn, $companyUuid);
    if (!$details) {
        response([
            'status' => 'error',
            'message' => 'Company not found.'
        ]);
    }
    response([
        'status' => 'success',
        'data' => $details,
        'programs' => getAllPrograms($conn),
        'statuses' => getCompanyStatuses($conn),
        'work_setups' => companyWorkSetup($conn),
    ]);
} elseif ($action === 'add_company') {
    $companyData = $_POST['data'] ?? null;
    if (empty($companyData) || !is_array($companyData)) {
        response([
            'status' => 'error',
            'message' => 'Invalid company data.'
        ]);
    }

    $companyData = array_map('trim', $companyData);
    if (!empty($companyData['total_slots']) && empty($companyData['batch_uuid'])) {
        $activeBatch = activebatch($conn);
        $companyData['batch_uuid'] = $activeBatch['uuid'] ?? null;
    }


    $result = createCompany($conn, $companyData, $actorUuid);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Company added successfully.',
            'uuid' => $result['uuid']
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to add company.',
            'errors' => $result['errors']
        ]);
    }
} elseif ($action === 'edit_company') {
    $companyData = $_POST['data'] ?? null;
    if (empty($companyData) || !is_array($companyData) || empty($companyData['uuid'])) {
        response([
            'status' => 'error',
            'message' => 'Invalid company data. UUID is required.'
        ]);
    }

    $companyUuid = trim($companyData['uuid']);
    unset($companyData['uuid']);

    $companyData = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $companyData);
    if (!empty($companyData['total_slots']) && empty($companyData['batch_uuid'])) {
        $activeBatch = activebatch($conn);
        $companyData['batch_uuid'] = $activeBatch['uuid'] ?? null;
    }

    $result = updateCompany($conn, $companyUuid, $companyData, $actorUuid);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Company updated successfully.'
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to update company.',
            'errors' => $result['errors']
        ]);
    }
} elseif ($action === 'upload_company_document') {
    $companyUuid = $_POST['company_uuid'] ?? null;
    $docData = [
        'doc_type'    => $_POST['document_type'] ?? null,
        'valid_from'  => $_POST['moa_valid_from'] ?? null,
        'valid_until' => $_POST['moa_valid_until'] ?? null,
    ];
    $file = $_FILES['document_file'] ?? null;

    if (empty($companyUuid) || empty($docData['doc_type']) || empty($file)) {
        response([
            'status' => 'error',
            'message' => 'Company UUID, document type, and file are required.'
        ]);
    }

    $result = uploadCompanyDocument($conn, $companyUuid, $docData, $file, $actorUuid);
    if ($result['success']) {
        response([
            'status' => 'success',
            'message' => 'Document uploaded successfully.',
            'uuid' => $result['uuid']
        ]);
    } else {
        response([
            'status' => 'error',
            'message' => 'Failed to upload document.',
            'error' => $result['error']
        ]);
    }
} else {
    response([
        'status' => 'error',
        'message' => 'Invalid action specified.'
    ]);
}
