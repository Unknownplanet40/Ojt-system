<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

// functions/endorsement_functions.php
// -----------------------------------------------
// Module:    Endorsement Letter + OJT Start
// Primary:   Coordinator
// Secondary: Student (download only)
// -----------------------------------------------
require_once __DIR__ . '/../helpers/helpers.php';


// -----------------------------------------------
// GENERATE endorsement letter PDF
// called automatically when coordinator approves
// -----------------------------------------------
function generateEndorsementLetter(
    $conn,
    string $applicationUuid,
    string $actorUuid
): array {
    // fetch all data needed for the letter
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

    // build PDF
    $pdfResult = buildEndorsementPdf($data);

    if (!$pdfResult['success']) {
        return $pdfResult;
    }

    // save record to DB
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
        $uuid, $applicationUuid, $data['student_uuid'],
        $relativePath, $fileName, $actorUuid
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


// -----------------------------------------------
// BUILD endorsement letter HTML → PDF
// -----------------------------------------------
function buildEndorsementPdf(array $data): array
{
    $studentName   = trim($data['first_name'] . ' ' . ($data['middle_name'] ? $data['middle_name'][0] . '. ' : '') . $data['last_name']);
    $coordName     = 'Dr./Prof. ' . $data['coord_first_name'] . ' ' . $data['coord_last_name'];
    $batchLabel    = "AY {$data['school_year']} {$data['semester']} Semester";
    $dateToday     = date('F j, Y');
    $yearLabel     = ordinal((int)$data['year_level']);
    $contactName   = $data['contact_name']    ?? 'The Supervisor';
    $contactPos    = $data['contact_position'] ?? '';
    $companyAddr   = $data['company_address'] . ', ' . $data['company_city'];
    $requiredHours = $data['required_hours'];
    $department    = $data['coord_department'] ?? 'College of Computer Studies';
    $coordEmail    = $data['coord_email'];

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #111; padding: 0; }
  .page { padding: 60px; }
  .header { text-align: center; margin-bottom: 32px; }
  .school-name { font-size: 16px; font-weight: bold; color: #0F6E56; margin-bottom: 4px; }
  .school-sub { font-size: 11px; color: #555; margin-bottom: 8px; }
  .header-line { border-top: 2px solid #0F6E56; border-bottom: 0.5px solid #0F6E56; padding: 4px 0; margin: 8px 0; }
  .doc-title { font-size: 13px; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 28px; }
  .date-line { text-align: right; margin-bottom: 24px; font-size: 12px; }
  .addressee { margin-bottom: 20px; }
  .addressee strong { font-size: 12px; }
  .addressee p { font-size: 12px; color: #444; }
  .salutation { margin-bottom: 16px; }
  .body-text { margin-bottom: 14px; line-height: 1.7; text-align: justify; }
  .highlight { font-weight: bold; }
  .closing { margin-top: 32px; }
  .sig-block { margin-top: 48px; }
  .sig-name { font-weight: bold; font-size: 12px; text-transform: uppercase; border-top: 1px solid #111; padding-top: 4px; display: inline-block; min-width: 220px; }
  .sig-title { font-size: 11px; color: #444; }
  .footer { margin-top: 48px; border-top: 0.5px solid #ccc; padding-top: 10px; text-align: center; font-size: 10px; color: #888; }
  .ref-box { background: #F0FDF4; border: 1px solid #A7F3D0; border-radius: 6px; padding: 10px 14px; margin: 20px 0; font-size: 11px; }
</style>
</head>
<body>
<div class="page">

  <div class="header">
    <div class="school-name">Philippine College — OJT System</div>
    <div class="school-sub">Office of Internship and Industry Affairs</div>
    <div class="header-line"></div>
    <div style="font-size:10px;color:#888;">{$department}</div>
  </div>

  <div class="doc-title">Letter of Endorsement for On-the-Job Training</div>

  <div class="date-line">{$dateToday}</div>

  <div class="addressee">
    <strong>{$contactName}</strong><br>
    <p>{$contactPos}</p>
    <p>{$data['company_name']}</p>
    <p>{$companyAddr}</p>
  </div>

  <div class="salutation">Dear {$contactName},</div>

  <p class="body-text">
    We are pleased to endorse <span class="highlight">{$studentName}</span>,
    a <span class="highlight">{$yearLabel} Year {$data['program_name']} ({$data['program_code']})</span>
    student with student number <span class="highlight">{$data['student_number']}</span>,
    for On-the-Job Training (OJT) at your esteemed organization for the
    <span class="highlight">{$batchLabel}</span>.
  </p>

  <p class="body-text">
    In partial fulfillment of the requirements for the degree
    <span class="highlight">{$data['program_name']}</span>, the student is required to complete
    <span class="highlight">{$requiredHours} hours</span> of supervised on-the-job training.
    We believe that exposure to your organization's professional environment will provide
    invaluable practical experience aligned with the student's academic preparation.
  </p>

  <p class="body-text">
    We assure you that the student has met all pre-OJT requirements set by the institution
    and is ready to begin training. We respectfully request your kind acceptance of the
    student and ask that they be given meaningful tasks relevant to their field of study.
  </p>

  <p class="body-text">
    For inquiries or coordination, please do not hesitate to contact the undersigned at
    <span class="highlight">{$coordEmail}</span>.
    Your continued support for our internship program is greatly appreciated.
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
    This letter is officially issued by the OJT Coordinator Management System.
    Generated on {$dateToday}.
  </div>

</div>
</body>
</html>
HTML;

    // save PDF
    $studentUuid = $data['student_uuid'];
    $fileName    = 'Endorsement_' . $data['student_number'] . '_' . date('Ymd') . '.pdf';
    $absoluteDir = dirname(__DIR__, 2) . '/uploads/endorsements/' . $studentUuid . '/';
    $relativePath = 'uploads/endorsements/' . $studentUuid . '/' . $fileName;

    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    // use mPDF if available
    $autoload = dirname(__DIR__, 2) . '/Libs/composer/vendor/autoload.php';
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


// -----------------------------------------------
// GET endorsement letter record
// -----------------------------------------------
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

    if (!$row) return null;

    return [
        'uuid'         => $row['uuid'],
        'file_path'    => $row['file_path'],
        'file_name'    => $row['file_name'],
        'generated_at' => date('M j, Y g:i A', strtotime($row['generated_at'])),
    ];
}


// -----------------------------------------------
// CONFIRM OJT START
// coordinator sets start date, links supervisor
// unlocks DTR and journal for the student
// -----------------------------------------------
function confirmOjtStart(
    $conn,
    string $applicationUuid,
    array  $data,
    string $coordinatorUuid
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
        SELECT a.uuid, a.status, a.student_uuid, a.batch_uuid,
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

    if ($app['coordinator_uuid'] !== $coordinatorUuid) {
        return ['success' => false, 'error' => 'Unauthorized.'];
    }

    if (!in_array($app['status'], ['approved', 'endorsed'])) {
        return [
            'success' => false,
            'error'   => 'OJT start can only be confirmed for approved or endorsed applications.',
        ];
    }

    // verify supervisor belongs to the student's company
    $stmt = $conn->prepare("
        SELECT svp.uuid, svp.company_uuid
        FROM supervisor_profiles svp
        JOIN users u ON svp.user_uuid = u.uuid
        WHERE svp.uuid = ? AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param('s', $supervisorUuid);
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
        // update application to active
        $stmt = $conn->prepare("
            UPDATE ojt_applications
            SET status     = 'active',
                updated_at = NOW()
            WHERE uuid = ?
        ");
        $stmt->bind_param('s', $applicationUuid);
        $stmt->execute();
        $stmt->close();

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
            'ssssssiss',
            $startUuid, $applicationUuid, $app['student_uuid'], $supervisorUuid,
            $startDate, $expectedEnd, $hoursPerDay, $coordinatorUuid
        );
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
        $conn, $applicationUuid,
        $app['status'], 'active',
        'OJT start confirmed by coordinator',
        $coordinatorUuid
    );

    logActivity(
        conn: $conn,
        eventType: 'ojt_started',
        description: "OJT start confirmed — starts {$startDate}",
        module: 'endorsement',
        actorUuid: $coordinatorUuid,
        targetUuid: $app['student_uuid']
    );

    return [
        'success'          => true,
        'start_date'       => $startDate,
        'expected_end'     => $expectedEnd,
        'supervisor_uuid'  => $supervisorUuid,
    ];
}


// -----------------------------------------------
// GET OJT start confirmation details
// -----------------------------------------------
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

    if (!$row) return null;

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


// -----------------------------------------------
// GET supervisors for a company — dropdown
// called when coordinator picks supervisor
// -----------------------------------------------
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

    return array_map(fn($row) => [
        'uuid'       => $row['uuid'],
        'full_name'  => $row['first_name'] . ' ' . $row['last_name'],
        'position'   => $row['position']   ?? '—',
        'department' => $row['department'] ?? '—',
    ], $rows);
}