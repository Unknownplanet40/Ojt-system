<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}
require_once __DIR__ . '/../helpers/helpers.php';

function generateEndorsementLetter(
    $conn,
    string $applicationUuid,
    string $actorUuid
): array {
    $stmt = $conn->prepare("
        SELECT
          a.uuid          AS app_uuid,
          a.student_uuid,
          a.company_uuid,
          a.batch_uuid,

          sp.first_name,
          sp.last_name,
          sp.middle_name,
          sp.student_number,
          sp.year_level,

          p.name          AS program_name,
          p.code          AS program_code,
          p.required_hours,

          b.school_year,
          b.semester,

          c.name          AS company_name,
          c.address       AS company_address,
          c.city          AS company_city,

          cc.name         AS contact_name,
          cc.position     AS contact_position,

          cp.first_name   AS coord_first_name,
          cp.last_name    AS coord_last_name,
          cp.department   AS coord_department,

          u_coord.email   AS coord_email

        FROM ojt_applications a
        JOIN student_profiles sp ON a.student_uuid  = sp.uuid
        JOIN programs p          ON sp.program_uuid  = p.uuid
        JOIN batches b           ON a.batch_uuid     = b.uuid
        JOIN companies c         ON a.company_uuid   = c.uuid
        LEFT JOIN company_contacts cc
          ON cc.company_uuid = c.uuid AND cc.is_primary = 1
        JOIN coordinator_profiles cp ON sp.coordinator_uuid = cp.uuid
        JOIN users u_coord ON cp.user_uuid = u_coord.uuid
        WHERE a.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $applicationUuid);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    $pdfResult = buildEndorsementPdf($data);

    if (!$pdfResult['success']) {
        return $pdfResult;
    }

    $uuid        = generateUuid();
    $relativePath = $pdfResult['relative_path'];
    $fileName    = $pdfResult['file_name'];

    $stmt = $conn->prepare("
        INSERT INTO endorsement_letters
          (uuid, application_uuid, student_uuid, file_path, file_name, generated_by)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          file_path    = VALUES(file_path),
          file_name    = VALUES(file_name),
          generated_by = VALUES(generated_by),
          generated_at = NOW()
    ");
    $stmt->bind_param(
        'ssssss',
        $uuid,
        $applicationUuid,
        $data['student_uuid'],
        $relativePath,
        $fileName,
        $actorUuid
    );
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'endorsement_generated',
        description: "Endorsement letter generated for {$data['first_name']} {$data['last_name']}",
        module: 'endorsement',
        actorUuid: $actorUuid,
        targetUuid: $applicationUuid
    );

    return [
        'success'       => true,
        'uuid'          => $uuid,
        'file_path'     => $relativePath,
        'file_name'     => $fileName,
    ];
}

function buildEndorsementPdf(array $data): array
{
    require_once __DIR__ . '/../Assets/SystemInfo.php';
    $studentName   = trim($data['first_name'] . ' ' . ($data['middle_name'] ? $data['middle_name'][0] . '. ' : '') . $data['last_name']);
    $coordName     = 'Dr./Prof. ' . $data['coord_first_name'] . ' ' . $data['coord_last_name'];
    $batchLabel    = "AY {$data['school_year']} {$data['semester']} Semester";
    $dateToday     = date('F j, Y');
    $yearLabel     = ordinal((int)$data['year_level']);
    $contactName   = $data['contact_name']    ?? 'The Supervisor';
    $contactPos    = $data['contact_position'] ?? '';
    $companyAddr   = trim(($data['company_address'] ?? '') . ', ' . ($data['company_city'] ?? ''), " ,");
    $requiredHours = $data['required_hours'];
    $department    = $data['coord_department'] ?? 'College of Computer Studies';
    $coordEmail    = $data['coord_email'];

    $schoolName = $SchoolName ?? 'Your School Name Here';
    $LongTitle   = $LongTitle ?? 'Your System Long Title Here';
    $SchoolMotto = $SchoolMotto ?? '';
    $SchoolAddress = $SchoolAddress ?? '';
    $SchoolWebsite = $SchoolWebsite ?? '';
    $SchoolEmail = $SchoolEmail ?? '';
    $SchoolPhone = $SchoolPhone ?? '';
    $DocumentFooterNote = $DocumentFooterNote ?? 'Officially issued by the OJT Coordinator Management System';
    $DocumentVerificationNote = $DocumentVerificationNote ?? 'Please verify document authenticity with the coordinator\'s office.';
    $LogoPath1      = $SchoolLogoLeft ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';
    $LogoPath2      = $SchoolLogoRight ?? 'https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans';
    $generatedAt = $dateToday;

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.55; color: #111827; background: #fff; padding: 0; }
    .page { padding: 44px 50px 38px; }

    .header { text-align: center; border-bottom: 2px solid #0F6E56; padding-bottom: 16px; margin-bottom: 22px; }
    .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 8px; margin-bottom: 18px; }
    .header-table td { vertical-align: middle; }
    .header-left { width: 20%; text-align: left; }
    .header-center { width: 60%; text-align: center; }
    .header-right { width: 20%; text-align: right; }
    .header-logo { width: 60px; height: 60px; object-fit: contain; }
    .school-name { font-size: 15px; font-weight: 700; color: #0F6E56; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
    .school-meta { font-size: 10px; color: #64748b; margin-top: 2px; }
    .doc-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px; letter-spacing: .01em; }
    .doc-subtitle { font-size: 11px; color: #6b7280; }
    .doc-meta { font-size: 10px; color: #64748b; margin-top: 2px; }

    .date-line { text-align: right; margin-bottom: 18px; font-size: 12px; color: #374151; }
    .addressee { margin-bottom: 18px; }
    .addressee-label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; margin-bottom: 2px; }
    .addressee strong { font-size: 13px; color: #111827; }
    .addressee p { font-size: 12px; color: #374151; margin-top: 2px; }
    .salutation { margin-bottom: 14px; font-size: 12px; }
    .body-text { margin-bottom: 12px; text-align: justify; }
    .highlight { font-weight: 700; color: #111827; }
    .ref-box { background: #f8fafc; border: 1px solid #dbe3ea; border-left: 4px solid #0F6E56; border-radius: 6px; padding: 10px 14px; margin: 18px 0 16px; font-size: 11px; color: #334155; }
    .closing { margin-top: 18px; margin-bottom: 36px; }
    .sig-block { margin-top: 8px; }
    .sig-name { font-size: 12px; font-weight: 700; color: #111827; margin-bottom: 2px; }
    .sig-title { font-size: 11px; color: #374151; line-height: 1.45; }
    .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #cbd5e1; text-align: center; font-size: 9.5px; color: #64748b; line-height: 1.5; }
    .footer strong { color: #334155; }
    .footer-contact { margin-top: 6px; font-size: 9px; color: #64748b; }
</style>
</head>
<body>
<div class="page">
    
<div class="header">
                <table class="header-table" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                                <td class="header-left">
                                        <img src="{$LogoPath1}" alt="Logo Left" class="header-logo" />
                                </td>
                                <td class="header-center" style="line-height:1.35;">
                                <div class="school-name">{$schoolName}</div>
                                    <div class="school-meta">{$SchoolMotto}</div>
                                <div class="doc-subtitle">Official Digital Credential Document</div>
                                <div class="doc-meta">{$LongTitle} · Endorsement Letter</div>
                                <div class="doc-meta">Issued on {$generatedAt}</div>
                                </td>
                                <td class="header-right">
                                        <img src="{$LogoPath2}" alt="Logo Right" class="header-logo" />
                                </td>
                        </tr>
                </table>
        </div>

                <div class="doc-title">Letter of Endorsement for On-the-Job Training</div>
                <div class="doc-meta" style="margin-bottom: 14px;">Reference: Academic endorsement for internship placement</div>

    <div class="date-line">{$dateToday}</div>

    <div class="addressee">
                <div class="addressee-label">To:</div>
                <strong>{$contactName}</strong><br>
        <p>{$contactPos}</p>
        <p>{$data['company_name']}</p>
        <p>{$companyAddr}</p>
    </div>

                <div class="salutation">Dear {$contactName},</div>

    <p class="body-text">
                We respectfully endorse <span class="highlight">{$studentName}</span>,
                a <span class="highlight">{$yearLabel} Year {$data['program_name']} ({$data['program_code']})</span>
                student with student number <span class="highlight">{$data['student_number']}</span>,
                for On-the-Job Training (OJT) at your organization for the
                <span class="highlight">{$batchLabel}</span>.
    </p>

  <p class="body-text">
                In partial fulfillment of the requirements for the degree
                <span class="highlight">{$data['program_name']}</span>, the student is required to complete
                <span class="highlight">{$requiredHours} hours</span> of supervised on-the-job training.
                We trust that the student will benefit from meaningful industry exposure aligned with
                their academic preparation.
  </p>

  <p class="body-text">
                The student has completed the required pre-OJT requirements and is now cleared to begin
                training. We kindly request your acceptance and support as they undertake tasks relevant
                to their field of study.
  </p>

  <p class="body-text">
                For coordination or verification, please contact the undersigned at
                <span class="highlight">{$coordEmail}</span>.
                We sincerely appreciate your continued support for our internship program.
  </p>

  <div class="ref-box">
    <strong>Reference Details</strong><br>
    Student: {$studentName} ({$data['student_number']})<br>
    Program: {$data['program_code']} — {$data['program_name']}<br>
    Required OJT Hours: {$requiredHours} hours<br>
    Batch: {$batchLabel}
  </div>

    <p class="body-text">Thank you for your continued support of our academic programs.</p>

  <div class="closing">Respectfully yours,</div>

  <div class="sig-block">
    <div class="sig-name">{$coordName}</div>
    <div class="sig-title">OJT Coordinator</div>
    <div class="sig-title">{$department}</div>
    <div class="sig-title">{$coordEmail}</div>
  </div>

  <div class="footer">
        <strong>{$DocumentFooterNote}.</strong><br>
        Document generated on {$dateToday}. {$DocumentVerificationNote}
        <div class="footer-contact">
            {$SchoolName} · {$SchoolAddress}<br>
            {$SchoolWebsite} · {$SchoolEmail} · {$SchoolPhone}
        </div>
  </div>

</div>
</body>
</html>
HTML;

    // save PDF
    $studentUuid = $data['student_uuid'];
    $fileName    = 'Endorsement_' . $data['student_number'] . '_' . date('Ymd') . '.pdf';

    // compute project root and upload paths (use one level up from functions/)
    $projectRoot  = dirname(__DIR__);
    $absoluteDir  = $projectRoot . '/uploads/endorsements/' . $studentUuid . '/';
    $relativePath = 'uploads/endorsements/' . $studentUuid . '/' . $fileName;

    if (!is_dir($absoluteDir)) {
        if (!mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            return ['success' => false, 'error' => 'Failed to create uploads directory.'];
        }
    }

    // use mPDF if available
    $autoload = $projectRoot . '/libs/composer/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'margin_top'    => 0,
                'margin_bottom' => 0,
                'margin_left'   => 0,
                'margin_right'  => 0,
            ]);
            $mpdf->WriteHTML($html);
            $mpdf->Output($absoluteDir . $fileName, 'F'); // F = save to file
            return [
                'success'       => true,
                'file_name'     => $fileName,
                'relative_path' => $relativePath,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'PDF generation failed: ' . $e->getMessage()];
        }
    }

    // fallback — save raw HTML as PDF placeholder
    file_put_contents($absoluteDir . $fileName, $html);
    return [
        'success'       => true,
        'file_name'     => $fileName,
        'relative_path' => $relativePath,
    ];
}

function getEndorsementLetter($conn, string $applicationUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT uuid, file_path, file_name, generated_at
        FROM endorsement_letters
        WHERE application_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $applicationUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'uuid'         => $row['uuid'],
        'file_path'    => $row['file_path'],
        'file_name'    => $row['file_name'],
        'generated_at' => date('M j, Y g:i A', strtotime($row['generated_at'])),
    ];
}

function confirmOjtStart(
    $conn,
    string $applicationUuid,
    array  $data,
    string $coordinatorUserUuid,
    string $coordinatorProfileUuid
): array {
    $startDate      = trim($data['start_date']          ?? '');
    $expectedEnd    = trim($data['expected_end_date']   ?? '');
    $supervisorUuid = trim($data['supervisor_uuid']     ?? '');
    $hoursPerDay    = (int) ($data['working_hours_per_day'] ?? 8);

    // validate
    $errors = [];

    if (empty($startDate)) {
        $errors['start_date'] = 'Start date is required.';
    }
    if (empty($supervisorUuid)) {
        $errors['supervisor_uuid'] = 'Supervisor is required.';
    }
    if ($hoursPerDay < 1 || $hoursPerDay > 12) {
        $errors['working_hours_per_day'] = 'Working hours per day must be between 1 and 12.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // fetch application
    $stmt = $conn->prepare("
        SELECT a.uuid, a.status, a.student_uuid, a.batch_uuid, a.company_uuid,
               sp.coordinator_uuid
        FROM ojt_applications a
        JOIN student_profiles sp ON a.student_uuid = sp.uuid
        WHERE a.uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $applicationUuid);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$app) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if ($app['coordinator_uuid'] !== $coordinatorProfileUuid) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    if ($app['status'] !== 'endorsed') {
        return [
            'success' => false,
            'error'   => 'OJT start can only be confirmed for endorsed applications.',
        ];
    }

    // verify supervisor belongs to the student's company
    $stmt = $conn->prepare("
        SELECT svp.uuid, svp.company_uuid
        FROM supervisor_profiles svp
        JOIN users u ON svp.user_uuid = u.uuid
        WHERE svp.uuid = ?
          AND svp.company_uuid = ?
          AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param('ss', $supervisorUuid, $app['company_uuid']);
    $stmt->execute();
    $supervisor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$supervisor) {
        return ['success' => false, 'error' => 'Supervisor not found or inactive.'];
    }

    // compute expected end if not provided
    if (empty($expectedEnd) && !empty($startDate)) {
        $stmt = $conn->prepare("
            SELECT p.required_hours
            FROM student_profiles sp
            JOIN programs p ON sp.program_uuid = p.uuid
            WHERE sp.uuid = ? LIMIT 1
        ");
        $stmt->bind_param('s', $app['student_uuid']);
        $stmt->execute();
        $prog = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $requiredHours = (int) ($prog['required_hours'] ?? 486);
        $workingDays   = ceil($requiredHours / $hoursPerDay);
        // add working days to start date (rough estimate — no weekends excluded)
        $expectedEnd   = date('Y-m-d', strtotime($startDate . " +{$workingDays} days"));
    }

    $conn->begin_transaction();

    try {
        // save OJT start record
        $startUuid = generateUuid();
        $stmt = $conn->prepare("
            INSERT INTO ojt_start_confirmations
              (uuid, application_uuid, student_uuid, supervisor_uuid,
               start_date, expected_end_date, working_hours_per_day, confirmed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              supervisor_uuid       = VALUES(supervisor_uuid),
              start_date            = VALUES(start_date),
              expected_end_date     = VALUES(expected_end_date),
              working_hours_per_day = VALUES(working_hours_per_day),
              confirmed_by          = VALUES(confirmed_by),
              confirmed_at          = NOW()
        ");
        $stmt->bind_param(
            'ssssssis',
            $startUuid,
            $applicationUuid,
            $app['student_uuid'],
            $supervisorUuid,
            $startDate,
            $expectedEnd,
            $hoursPerDay,
            $coordinatorProfileUuid
        );
        $stmt->execute();
        $stmt->close();

        // update application to active after start details are saved
        $stmt = $conn->prepare(" 
            UPDATE ojt_applications
            SET status = 'active',
                updated_at = NOW()
            WHERE uuid = ?
        ");
        $stmt->bind_param('s', $applicationUuid);
        $stmt->execute();
        $stmt->close();

        // link supervisor to student profile
        $stmt = $conn->prepare("
            UPDATE student_profiles
            SET supervisor_uuid = ?
            WHERE uuid = ?
        ");
        $stmt->bind_param('ss', $supervisorUuid, $app['student_uuid']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => 'Failed to confirm OJT start: ' . $e->getMessage()];
    }

    // log status history
    logApplicationStatus(
        $conn,
        $applicationUuid,
        $app['status'],
        'active',
        'OJT start confirmed by coordinator',
        $coordinatorUserUuid
    );

    logActivity(
        conn: $conn,
        eventType: 'ojt_started',
        description: "OJT start confirmed — starts {$startDate}",
        module: 'endorsement',
        actorUuid: $coordinatorUserUuid,
        targetUuid: $app['student_uuid']
    );

    return [
        'success'          => true,
        'start_date'       => $startDate,
        'expected_end'     => $expectedEnd,
        'supervisor_uuid'  => $supervisorUuid,
    ];
}

function getOjtStartConfirmation($conn, string $applicationUuid): ?array
{
    $stmt = $conn->prepare("
        SELECT
          osc.uuid,
          osc.start_date,
          osc.expected_end_date,
          osc.working_hours_per_day,
          osc.confirmed_at,

          svp.uuid        AS supervisor_uuid,
          svp.first_name  AS sv_first,
          svp.last_name   AS sv_last,
          svp.position    AS sv_position,
          svp.department  AS sv_department,

          c.name          AS company_name

        FROM ojt_start_confirmations osc
        JOIN supervisor_profiles svp ON osc.supervisor_uuid = svp.uuid
        JOIN student_profiles sp     ON osc.student_uuid    = sp.uuid
        LEFT JOIN companies c        ON sp.company_uuid     = c.uuid
        WHERE osc.application_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $applicationUuid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'uuid'                  => $row['uuid'],
        'start_date'            => date('M j, Y', strtotime($row['start_date'])),
        'start_date_raw'        => $row['start_date'],
        'expected_end_date'     => date('M j, Y', strtotime($row['expected_end_date'])),
        'working_hours_per_day' => (int) $row['working_hours_per_day'],
        'confirmed_at'          => date('M j, Y g:i A', strtotime($row['confirmed_at'])),
        'supervisor_uuid'       => $row['supervisor_uuid'],
        'supervisor_name'       => $row['sv_first'] . ' ' . $row['sv_last'],
        'supervisor_position'   => $row['sv_position'] ?? '—',
        'supervisor_department' => $row['sv_department'] ?? '—',
        'company_name'          => $row['company_name'] ?? '—',
    ];
}

function getSupervisorsForCompany($conn, string $companyUuid): array
{
    $stmt = $conn->prepare("
        SELECT svp.uuid, svp.first_name, svp.last_name, svp.position, svp.department
        FROM supervisor_profiles svp
        JOIN users u ON svp.user_uuid = u.uuid
        WHERE svp.company_uuid = ? AND u.is_active = 1
        ORDER BY svp.last_name ASC
    ");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(fn ($row) => [
        'uuid'       => $row['uuid'],
        'full_name'  => $row['first_name'] . ' ' . $row['last_name'],
        'position'   => $row['position']   ?? '—',
        'department' => $row['department'] ?? '—',
    ], $rows);
}
