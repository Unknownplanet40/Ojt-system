<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/supervisor_functions.php';

// -----------------------------------------------
// GET all companies
// -----------------------------------------------
function getAllCompanies($conn, string $batchUuid = null): array
{
    // auto-fetch active batch if not provided
    if (empty($batchUuid)) {
        $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
        $row       = $result->fetch_assoc();
        $batchUuid = $row['uuid'] ?? null;
    }

    $safeBatch = $conn->real_escape_string($batchUuid ?? '');

    $result = $conn->query("
        SELECT
          c.uuid,
          c.name,
          c.industry,
          c.city,
          c.email,
          c.phone,
          c.website,
          c.address,
          c.work_setup,
          c.accreditation_status,
          c.blacklist_reason,
          c.created_at,

          -- primary contact
          cc.name  AS contact_name,
          cc.email AS contact_email,
          cc.phone AS contact_phone,
          cc.position AS contact_position,

          -- slots for active batch
          cs.total_slots,
          COUNT(DISTINCT sp.id) AS filled_slots,

          -- accepted programs
          GROUP_CONCAT(
            DISTINCT p.code
            ORDER BY p.code
            SEPARATOR ', '
          ) AS accepted_programs,

          -- latest MOA only
          cd.valid_until AS moa_expiry,
          cd.file_path   AS moa_path,
          cd.uuid        AS moa_uuid

        FROM companies c
        LEFT JOIN company_contacts cc
          ON c.uuid = cc.company_uuid AND cc.is_primary = 1
        LEFT JOIN company_slots cs
          ON c.uuid = cs.company_uuid
          AND cs.batch_uuid = '{$safeBatch}'
        LEFT JOIN student_profiles sp
          ON c.uuid = sp.company_uuid
          AND sp.batch_uuid = '{$safeBatch}'
        LEFT JOIN company_accepted_programs cap
          ON c.uuid = cap.company_uuid
        LEFT JOIN programs p
          ON cap.program_uuid = p.uuid AND p.is_active = 1
        LEFT JOIN company_documents cd
          ON cd.uuid = (
            SELECT uuid FROM company_documents
            WHERE company_uuid = c.uuid AND doc_type = 'moa'
            ORDER BY created_at DESC LIMIT 1
          )

        GROUP BY c.id
        ORDER BY
          FIELD(c.accreditation_status,'active','pending','expired','blacklisted'),
          c.name ASC
    ");

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = formatCompany($row);
    }

    return $companies;
}

// -----------------------------------------------
// GET single company
// -----------------------------------------------
function getCompany($conn, string $companyUuid, string $batchUuid = null): ?array
{
    if (empty($batchUuid)) {
        $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
        $row       = $result->fetch_assoc();
        $batchUuid = $row['uuid'] ?? null;
    }

    $stmt = $conn->prepare("SELECT * FROM companies WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$company) return null;

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

    // documents — latest per type
    $stmt = $conn->prepare("
        SELECT * FROM company_documents
        WHERE company_uuid = ?
        ORDER BY doc_type ASC, created_at DESC
    ");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $supervisors = getAllSupervisors($conn, ['company_uuid' => $companyUuid]);

    // slots per batch
    $safeBatch = $conn->real_escape_string($batchUuid ?? '');
    $stmt = $conn->prepare("
        SELECT cs.total_slots, COUNT(DISTINCT sp.id) AS filled_slots
        FROM company_slots cs
        LEFT JOIN student_profiles sp
          ON cs.company_uuid = sp.company_uuid
          AND cs.batch_uuid  = sp.batch_uuid
        WHERE cs.company_uuid = ? AND cs.batch_uuid = ?
        GROUP BY cs.id
        LIMIT 1
    ");
    $stmt->bind_param('ss', $companyUuid, $batchUuid);
    $stmt->execute();
    $slots = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $latestMoa = null;
    foreach ($documents as $doc) {
        if ($doc['doc_type'] === 'moa') {
            $latestMoa = $doc;
            break;
        }
    }
    
    if ($latestMoa) {
        $company['moa_expiry'] = $latestMoa['valid_until'];
        $company['moa_uuid']   = $latestMoa['uuid'];
    }


    return [
        'company'          => formatCompany($company),
        'contacts'         => $contacts,
        'accepted_programs'=> $programs,
        'program_uuids'    => array_column($programs, 'uuid'),
        'documents'        => $documents,
        'supervisors'      => $supervisors,
        'total_slots'      => (int) ($slots['total_slots']  ?? 0),
        'filled_slots'     => (int) ($slots['filled_slots'] ?? 0),
        'remaining_slots'  => max(0, (int)($slots['total_slots'] ?? 0) - (int)($slots['filled_slots'] ?? 0)),
    ];
}

// -----------------------------------------------
// CREATE company
// -----------------------------------------------
function createCompany($conn, array $data, string $actorUuid): array
{
    $errors = [];

    $name   = trim($data['name']       ?? '');
    $email  = trim($data['email']      ?? '');
    $setup  = trim($data['work_setup'] ?? '');
    $status = trim($data['accreditation_status'] ?? 'pending');
    $supervisorFirstName = trim($data['supervisor_first_name'] ?? '');
    $supervisorLastName  = trim($data['supervisor_last_name'] ?? '');
    $supervisorEmail     = trim($data['supervisor_email'] ?? '');
    $supervisorPosition  = trim($data['supervisor_position'] ?? '');
    $supervisorDepartment = trim($data['supervisor_department'] ?? '');
    $supervisorMobile    = trim($data['supervisor_mobile'] ?? '');

    if (empty($name)) {
        $errors['name'] = 'Company name is required.';
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!in_array($setup, ['on-site','remote','hybrid'])) {
        $errors['work_setup'] = 'Select a valid work setup.';
    }
    if (!in_array($status, ['pending','active','expired','blacklisted'])) {
        $errors['accreditation_status'] = 'Invalid accreditation status.';
    }
    if ($status === 'blacklisted' && empty($data['blacklist_reason'])) {
        $errors['blacklist_reason'] = 'Blacklist reason is required.';
    }
    if (empty($supervisorFirstName)) {
        $errors['supervisor_first_name'] = 'Supervisor first name is required.';
    }
    if (empty($supervisorLastName)) {
        $errors['supervisor_last_name'] = 'Supervisor last name is required.';
    }
    if (empty($supervisorEmail)) {
        $errors['supervisor_email'] = 'Supervisor email is required.';
    } elseif (!filter_var($supervisorEmail, FILTER_VALIDATE_EMAIL)) {
        $errors['supervisor_email'] = 'Enter a valid supervisor email address.';
    }
    if (empty($supervisorPosition)) {
        $errors['supervisor_position'] = 'Supervisor position is required.';
    }
    if (empty($supervisorDepartment)) {
        $errors['supervisor_department'] = 'Supervisor department is required.';
    }
    if (empty($supervisorMobile)) {
        $errors['supervisor_mobile'] = 'Supervisor mobile number is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // check duplicate name
    $stmt = $conn->prepare("SELECT id FROM companies WHERE name = ? LIMIT 1");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        return ['success' => false, 'errors' => ['name' => 'A company with this name already exists.']];
    }

    $uuid            = generateUuid();
    $industry        = trim($data['industry']         ?? '');
    $address         = trim($data['address']          ?? '');
    $city            = trim($data['city']             ?? '');
    $phone           = trim($data['phone']            ?? '');
    $website         = trim($data['website']          ?? '');
    $blacklistReason = trim($data['blacklist_reason'] ?? '');

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("\n        INSERT INTO companies\n          (uuid, name, industry, address, city, email, phone,\n           website, work_setup, accreditation_status, blacklist_reason, created_by)\n        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n    ");
        $stmt->bind_param(
            'ssssssssssss',
            $uuid, $name, $industry, $address, $city,
            $email, $phone, $website, $setup, $status,
            $blacklistReason, $actorUuid
        );
        $stmt->execute();
        $stmt->close();

        // primary contact
        if (!empty($data['contact_name'])) {
            $contactResult = addCompanyContact($conn, $uuid, [
                'name'       => $data['contact_name'],
                'position'   => $data['contact_position'] ?? '',
                'email'      => $data['contact_email']    ?? '',
                'phone'      => $data['contact_phone']    ?? '',
                'is_primary' => 1,
            ]);

            if (!$contactResult['success']) {
                throw new RuntimeException($contactResult['error'] ?? 'Failed to add company contact.');
            }
        }

        // slots for active batch
        if (!empty($data['total_slots']) && !empty($data['batch_uuid'])) {
            setCompanySlots($conn, $uuid, $data['batch_uuid'], (int)$data['total_slots']);
        }

        // accepted programs
        if (!empty($data['program_uuids']) && is_array($data['program_uuids'])) {
            setAcceptedPrograms($conn, $uuid, $data['program_uuids']);
        }

        $supervisorResult = createSupervisor($conn, [
            'email'            => $supervisorEmail,
            'company_uuid'     => $uuid,
            'last_name'        => $supervisorLastName,
            'first_name'       => $supervisorFirstName,
            'position'         => $supervisorPosition,
            'department'       => $supervisorDepartment,
            'mobile'           => $supervisorMobile,
        ], $actorUuid, false);

        if (!$supervisorResult['success']) {
            throw new RuntimeException(reset($supervisorResult['errors']) ?: 'Failed to create supervisor account.');
        }

        $conn->commit();

        logActivity(
            conn: $conn,
            eventType: 'company_added',
            description: "Added company: {$name}",
            module: 'companies',
            actorUuid: $actorUuid,
            targetUuid: $uuid
        );

        return [
            'success' => true,
            'uuid' => $uuid,
            'supervisor_uuid' => $supervisorResult['user_uuid'] ?? null,
            'supervisor_profile_uuid' => $supervisorResult['profile_uuid'] ?? null,
            'supervisor_full_name' => $supervisorResult['full_name'] ?? trim("{$supervisorFirstName} {$supervisorLastName}"),
            'supervisor_email' => $supervisorEmail,
            'supervisor_temp_password' => $supervisorResult['temp_password'] ?? null,
        ];
    } catch (Throwable $e) {
        $conn->rollback();

        return [
            'success' => false,
            'errors' => [
                'general' => 'Failed to create company. Please try again.',
                'details' => $e->getMessage(),
            ],
        ];
    }
}

// -----------------------------------------------
// UPDATE company
// -----------------------------------------------
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
    if (!in_array($setup, ['on-site','remote','hybrid'])) {
        $errors['work_setup'] = 'Select a valid work setup.';
    }
    if (!in_array($status, ['pending','active','expired','blacklisted'])) {
        $errors['accreditation_status'] = 'Invalid accreditation status.';
    }
    if ($status === 'blacklisted' && empty($data['blacklist_reason'])) {
        $errors['blacklist_reason'] = 'Blacklist reason is required when blacklisting.';
    }

    $supervisorProfileUuid = trim($data['supervisor_profile_uuid'] ?? '');
    $supervisorFirstName   = trim($data['supervisor_first_name'] ?? '');
    $supervisorLastName    = trim($data['supervisor_last_name'] ?? '');
    $supervisorEmail       = trim($data['supervisor_email'] ?? '');
    $supervisorPosition    = trim($data['supervisor_position'] ?? '');
    $supervisorDepartment  = trim($data['supervisor_department'] ?? '');
    $supervisorMobile      = trim($data['supervisor_mobile'] ?? '');

    if (empty($supervisorFirstName)) {
        $errors['supervisor_first_name'] = 'Supervisor first name is required.';
    }
    if (empty($supervisorLastName)) {
        $errors['supervisor_last_name'] = 'Supervisor last name is required.';
    }
    if (empty($supervisorEmail)) {
        $errors['supervisor_email'] = 'Supervisor email is required.';
    } elseif (!filter_var($supervisorEmail, FILTER_VALIDATE_EMAIL)) {
        $errors['supervisor_email'] = 'Enter a valid supervisor email address.';
    }
    if (empty($supervisorPosition)) {
        $errors['supervisor_position'] = 'Supervisor position is required.';
    }
    if (empty($supervisorDepartment)) {
        $errors['supervisor_department'] = 'Supervisor department is required.';
    }
    if (empty($supervisorMobile)) {
        $errors['supervisor_mobile'] = 'Supervisor mobile number is required.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // check duplicate name — exclude current
    $stmt = $conn->prepare("SELECT id FROM companies WHERE name = ? AND uuid != ? LIMIT 1");
    $stmt->bind_param('ss', $name, $companyUuid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        return ['success' => false, 'errors' => ['name' => 'Another company with this name already exists.']];
    }

    $industry        = trim($data['industry']         ?? '');
    $address         = trim($data['address']          ?? '');
    $city            = trim($data['city']             ?? '');
    $phone           = trim($data['phone']            ?? '');
    $website         = trim($data['website']          ?? '');
    $blacklistReason = trim($data['blacklist_reason'] ?? '');

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("\n        UPDATE companies\n        SET name                 = ?,\n            industry             = ?,\n            address              = ?,\n            city                 = ?,\n            email                = ?,\n            phone                = ?,\n            website              = ?,\n            work_setup           = ?,\n            accreditation_status = ?,\n            blacklist_reason     = ?\n        WHERE uuid = ?\n    ");
        $stmt->bind_param(
            'sssssssssss',
            $name, $industry, $address, $city,
            $email, $phone, $website, $setup,
            $status, $blacklistReason, $companyUuid
        );
        $stmt->execute();
        $stmt->close();

        // update accepted programs
        if (isset($data['program_uuids'])) {
            $uuids = is_array($data['program_uuids'])
                ? $data['program_uuids']
                : [];
            setAcceptedPrograms($conn, $companyUuid, $uuids);
        }

        // update slots if provided
        if (isset($data['total_slots']) && !empty($data['batch_uuid'])) {
            setCompanySlots($conn, $companyUuid, $data['batch_uuid'], (int)$data['total_slots']);
        }

        if (!empty($data['contacts']) && is_array($data['contacts'])) {
            foreach ($data['contacts'] as $contact) {
                $contactUuid = trim($contact['uuid'] ?? '');

                if (empty($contactUuid)) {
                    // new contact — add it
                    addCompanyContact($conn, $companyUuid, $contact);
                } else {
                    // existing contact — update it
                    updateCompanyContact($conn, $contactUuid, $contact, $companyUuid);
                }
            }
        }

        $supervisorData = [
            'email'      => $supervisorEmail,
            'company_uuid' => $companyUuid,
            'last_name'  => $supervisorLastName,
            'first_name' => $supervisorFirstName,
            'position'   => $supervisorPosition,
            'department' => $supervisorDepartment,
            'mobile'     => $supervisorMobile,
        ];

        if (!empty($supervisorProfileUuid)) {
            $supervisorResult = updateSupervisor($conn, $supervisorProfileUuid, $supervisorData, $actorUuid, false);
        } else {
            $supervisorResult = createSupervisor($conn, $supervisorData, $actorUuid, false);
        }

        if (!$supervisorResult['success']) {
            throw new RuntimeException(reset($supervisorResult['errors']) ?: 'Failed to save supervisor account.');
        }

        $conn->commit();

        logActivity(
            conn: $conn,
            eventType: 'company_updated',
            description: "Updated company: {$name}",
            module: 'companies',
            actorUuid: $actorUuid,
            targetUuid: $companyUuid
        );

        return [
            'success' => true,
            'supervisor_profile_uuid' => $supervisorResult['profile_uuid'] ?? $supervisorProfileUuid,
        ];
    } catch (Throwable $e) {
        $conn->rollback();

        return [
            'success' => false,
            'errors' => [
                'general' => 'Failed to update company. Please try again.',
                'details' => $e->getMessage(),
            ],
        ];
    }
}

function updateCompanyContact($conn, string $contactUuid, array $data, string $companyUuid): array
{
    $name      = trim($data['name']      ?? '');
    $position  = trim($data['position']  ?? '');
    $email     = trim($data['email']     ?? '');
    $phone     = trim($data['phone']     ?? '');
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

    $stmt = $conn->prepare("
        UPDATE company_contacts
        SET name       = ?,
            position   = ?,
            email      = ?,
            phone      = ?,
            is_primary = ?
        WHERE uuid = ? AND company_uuid = ?
    ");
    $stmt->bind_param(
        'ssssiis',
        $name, $position, $email, $phone,
        $isPrimary, $contactUuid, $companyUuid
    );
    $stmt->execute();
    $stmt->close();

    return ['success' => true];
}

function deleteCompanyContact($conn, string $contactUuid, string $companyUuid): array
{
    // check how many contacts this company has
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total FROM company_contacts WHERE company_uuid = ?
    ");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int) $row['total'] <= 1) {
        return [
            'success' => false,
            'error'   => 'Cannot delete the only contact. Add another contact first.',
        ];
    }

    // check if this contact is primary
    $stmt = $conn->prepare("
        SELECT is_primary FROM company_contacts
        WHERE uuid = ? AND company_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $contactUuid, $companyUuid);
    $stmt->execute();
    $contact = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$contact) {
        return ['success' => false, 'error' => 'Contact not found.'];
    }

    // delete the contact
    $stmt = $conn->prepare("
        DELETE FROM company_contacts WHERE uuid = ? AND company_uuid = ?
    ");
    $stmt->bind_param('ss', $contactUuid, $companyUuid);
    $stmt->execute();
    $stmt->close();

    // if deleted contact was primary — auto-assign first remaining as primary
    if ((int) $contact['is_primary'] === 1) {
        $stmt = $conn->prepare("
            UPDATE company_contacts
            SET is_primary = 1
            WHERE company_uuid = ?
            ORDER BY created_at ASC
            LIMIT 1
        ");
        $stmt->bind_param('s', $companyUuid);
        $stmt->execute();
        $stmt->close();
    }

    return ['success' => true];
}

// -----------------------------------------------
// ADD company contact
// -----------------------------------------------
function addCompanyContact($conn, string $companyUuid, array $data): array
{
    $name      = trim($data['name']      ?? '');
    $position  = trim($data['position']  ?? '');
    $email     = trim($data['email']     ?? '');
    $phone     = trim($data['phone']     ?? '');
    $isPrimary = (int) ($data['is_primary'] ?? 0);

    if (empty($name)) {
        return ['success' => false, 'error' => 'Contact name is required.'];
    }

    // unset other primary contacts if setting as primary
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
        $uuid, $companyUuid, $name, $position, $email, $phone, $isPrimary
    );
    $stmt->execute();
    $stmt->close();

    return ['success' => true, 'uuid' => $uuid];
}

// -----------------------------------------------
// SET company slots (upsert)
// -----------------------------------------------
function setCompanySlots($conn, string $companyUuid, string $batchUuid, int $totalSlots): void
{
    $uuid = generateUuid();
    $stmt = $conn->prepare("
        INSERT INTO company_slots (uuid, company_uuid, batch_uuid, total_slots)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE total_slots = VALUES(total_slots)
    ");
    $stmt->bind_param('sssi', $uuid, $companyUuid, $batchUuid, $totalSlots);
    $stmt->execute();
    $stmt->close();
}

// -----------------------------------------------
// SET accepted programs (replace all)
// -----------------------------------------------
function setAcceptedPrograms($conn, string $companyUuid, array $programUuids): void
{
    // delete existing
    $stmt = $conn->prepare("DELETE FROM company_accepted_programs WHERE company_uuid = ?");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $stmt->close();

    if (empty($programUuids)) return;

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

// -----------------------------------------------
// UPLOAD company document
// -----------------------------------------------
function uploadCompanyDocument(
    $conn,
    string $companyUuid,
    array  $data,
    array  $file,
    string $actorUuid
): array {
    $docType    = trim($data['doc_type']    ?? '');
    $validFrom  = trim($data['valid_from']  ?? '');
    $validUntil = trim($data['valid_until'] ?? '');

    $allowedTypes = ['moa','nda','insurance','bir_cert','sec_dti','other'];

    if (!in_array($docType, $allowedTypes)) {
        error_log("[company upload] rejected invalid doc type '{$docType}' for company {$companyUuid}");
        return ['success' => false, 'error' => 'Invalid document type.'];
    }
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        error_log("[company upload] no file uploaded for company {$companyUuid}; PHP upload error=" . ($file['error'] ?? 'n/a'));
        return ['success' => false, 'error' => 'No file uploaded.'];
    }
    if ($file['type'] !== 'application/pdf') {
        error_log("[company upload] rejected non-PDF upload '{$file['type']}' for company {$companyUuid}");
        return ['success' => false, 'error' => 'Only PDF files are allowed.'];
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        error_log("[company upload] rejected oversized file for company {$companyUuid}; size={$file['size']}");
        return ['success' => false, 'error' => 'File must be 10MB or less.'];
    }

    $safeFileName = generateUuid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $projectRoot  = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
    $uploadBase   = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'company_documents';
    $absoluteDir  = $uploadBase . DIRECTORY_SEPARATOR . $docType . DIRECTORY_SEPARATOR . $companyUuid . DIRECTORY_SEPARATOR;
    $absolutePath = $absoluteDir . $safeFileName;
    $relativePath = 'uploads/company_documents/' . $docType . '/' . $companyUuid . '/' . $safeFileName;

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
        error_log("[company upload] failed to create directory: {$absoluteDir}");
        return ['success' => false, 'error' => 'Failed to create upload directory. Check folder permissions.'];
    }

    $moved = false;
    if (is_uploaded_file($file['tmp_name'])) {
        $moved = move_uploaded_file($file['tmp_name'], $absolutePath);
    }

    if (!$moved) {
        $moved = @copy($file['tmp_name'], $absolutePath);
    }

    if (!$moved || !file_exists($absolutePath)) {
        error_log("[company upload] failed to save file for company {$companyUuid} to {$absolutePath}");
        return ['success' => false, 'error' => 'Failed to save file. Check folder permissions.'];
    }

    $uuid = generateUuid();
    $stmt = $conn->prepare("\n        INSERT INTO company_documents\n          (uuid, company_uuid, doc_type, file_name, file_path,\n           valid_from, valid_until, uploaded_by)\n        VALUES (?, ?, ?, ?, ?, ?, ?, ?)\n    ");
    $stmt->bind_param(
        'ssssssss',
        $uuid, $companyUuid, $docType,
        $file['name'], $relativePath,
        $validFrom, $validUntil, $actorUuid
    );
    $stmt->execute();
    $stmt->close();

    error_log("[company upload] saved {$docType} for company {$companyUuid} as {$relativePath}");

    // auto-activate if MOA uploaded and status is pending
    if ($docType === 'moa') {
        $safe = $conn->real_escape_string($companyUuid);
        $conn->query("
            UPDATE companies
            SET accreditation_status = 'active'
            WHERE uuid = '{$safe}'
              AND accreditation_status = 'pending'
        ");

        logActivity(
            conn: $conn,
            eventType: 'moa_uploaded',
            description: "MOA uploaded — valid until " . ($validUntil ? date('M j, Y', strtotime($validUntil)) : '—'),
            module: 'companies',
            actorUuid: $actorUuid,
            targetUuid: $companyUuid
        );
    }

    return ['success' => true, 'uuid' => $uuid];
}

// -----------------------------------------------
// GET available companies for student application
// -----------------------------------------------
function getAvailableCompanies($conn, string $batchUuid, string $programUuid): array
{
    $safeBatch   = $conn->real_escape_string($batchUuid);
    $safeProgram = $conn->real_escape_string($programUuid);

    $result = $conn->query("
        SELECT
          c.uuid,
          c.name,
          c.industry,
          c.city,
          c.work_setup,
          c.address,
          cs.total_slots,
          COUNT(DISTINCT sp.id) AS filled_slots

        FROM companies c
        JOIN company_accepted_programs cap
          ON c.uuid = cap.company_uuid
          AND cap.program_uuid = '{$safeProgram}'
        JOIN company_slots cs
          ON c.uuid = cs.company_uuid
          AND cs.batch_uuid = '{$safeBatch}'
        LEFT JOIN student_profiles sp
          ON c.uuid = sp.company_uuid
          AND sp.batch_uuid = '{$safeBatch}'

        WHERE c.accreditation_status = 'active'

        GROUP BY c.id
        HAVING filled_slots < cs.total_slots
        ORDER BY c.name ASC
    ");

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = [
            'uuid'            => $row['uuid'],
            'name'            => $row['name'],
            'industry'        => $row['industry']  ?? '—',
            'city'            => $row['city']       ?? '—',
            'work_setup'      => $row['work_setup'],
            'address'         => $row['address']    ?? '—',
            'total_slots'     => (int) $row['total_slots'],
            'filled_slots'    => (int) $row['filled_slots'],
            'remaining_slots' => (int)$row['total_slots'] - (int)$row['filled_slots'],
        ];
    }

    return $companies;
}

// -----------------------------------------------
// GET Students associated with a company
// -----------------------------------------------
function getCompanyStudents($conn, string $companyUuid, string $batchUuid): array
{
    if (empty($batchUuid)) {
        $result    = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
        $row       = $result->fetch_assoc();
        $batchUuid = $row['uuid'] ?? null;
    }

    // only the important fields for the company view
    $stmt = $conn->prepare("
        SELECT uuid, first_name, last_name, program, year_level, profile_path
        FROM student_profiles
        WHERE company_uuid = ? AND batch_uuid = ?
        ORDER BY last_name ASC, first_name ASC
    ");
    $stmt->bind_param('ss', $companyUuid, $batchUuid);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($students as &$student) {
        $student['full_name'] = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
    }
    unset($student);

    return $students;
}


// -----------------------------------------------
// GET expiring MOAs
// -----------------------------------------------
function getExpiringMoas($conn, int $daysThreshold = 30): array
{
    $result = $conn->query("
        SELECT
          c.uuid,
          c.name,
          cd.valid_until,
          DATEDIFF(cd.valid_until, CURDATE()) AS days_left
        FROM companies c
        JOIN company_documents cd
          ON cd.uuid = (
            SELECT uuid FROM company_documents
            WHERE company_uuid = c.uuid AND doc_type = 'moa'
            ORDER BY created_at DESC LIMIT 1
          )
        WHERE cd.valid_until BETWEEN CURDATE()
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

// -----------------------------------------------
// FORMAT company row
// -----------------------------------------------
function formatCompany(array $row): array
{
    $moaExpiry    = $row['moa_expiry'] ?? null;
    $daysToExpiry = $moaExpiry
        ? (int) ceil((strtotime($moaExpiry) - time()) / 86400)
        : null;

    return [
        'uuid'                 => $row['uuid'],
        'name'                 => $row['name'],
        'industry'             => $row['industry']    ?? '—',
        'city'                 => $row['city']         ?? '—',
        'address'              => $row['address']      ?? '—',
        'email'                => $row['email']        ?? '—',
        'phone'                => $row['phone']        ?? '—',
        'website'              => $row['website']      ?? '—',
        'work_setup'           => $row['work_setup'],
        'accreditation_status' => $row['accreditation_status'],
        'status_label'         => ucfirst($row['accreditation_status']),
        'blacklist_reason'     => $row['blacklist_reason'] ?? null,
        'contact_name'         => $row['contact_name']  ?? '—',
        'contact_email'        => $row['contact_email'] ?? '—',
        'contact_phone'        => $row['contact_phone'] ?? '—',
        'contact_position'     => $row['contact_position'] ?? '—',
        'total_slots'          => (int) ($row['total_slots']  ?? 0),
        'filled_slots'         => (int) ($row['filled_slots'] ?? 0),
        'remaining_slots'      => max(0, (int)($row['total_slots'] ?? 0) - (int)($row['filled_slots'] ?? 0)),
        'accepted_programs'    => $row['accepted_programs'] ?? '—',
        'moa_expiry'           => $moaExpiry
                                    ? date('M j, Y', strtotime($moaExpiry))
                                    : null,
        'moa_days_left'        => $daysToExpiry,
        'moa_uuid'             => $row['moa_uuid'] ?? null,
        'moa_status'           => match(true) {
            $daysToExpiry === null => 'none',
            $daysToExpiry < 0     => 'expired',
            $daysToExpiry <= 30   => 'expiring',
            default               => 'valid',
        },
        'created_at'           => isset($row['created_at'])
                                    ? date('M j, Y', strtotime($row['created_at']))
                                    : null,
    ];
}