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

$action = isset($_POST['action']) ? $_POST['action'] : null;

if (!$action) {
    response([
        'status' => 'error',
        'message' => 'No action specified.',
        'long_message' => 'The "action" parameter is required to determine which data to fetch.'
    ]);
}
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

function getCoordinatorDashboardData($conn, string $coordinatorProfileUuid): array
{
    $activeBatch = getActiveBatch($conn);
    $batchUuid   = $activeBatch['uuid'] ?? null;

    return [
        'active_batch'   => $activeBatch,
        'stats'          => getCoordinatorStats_Dashboard($conn, $coordinatorProfileUuid, $batchUuid),
        'needs_action'   => getCoordinatorNeedsAction($conn, $coordinatorProfileUuid, $batchUuid),
        'my_students'    => getCoordinatorStudentsSummary($conn, $coordinatorProfileUuid, $batchUuid),
        'hours_progress' => getCoordinatorHoursProgress($conn, $coordinatorProfileUuid, $batchUuid),
        'companies'      => getCoordinatorCompanies($conn, $batchUuid),
        'upcoming_visits'=> getCoordinatorUpcomingVisits($conn, $coordinatorProfileUuid),
    ];
}

function getCoordinatorStats_Dashboard($conn, string $coordinatorUuid, ?string $batchUuid): array
{
    $safeBatch = $conn->real_escape_string($batchUuid ?? '');
    $safeCoord = $conn->real_escape_string($coordinatorUuid);

    $result        = $conn->query("
        SELECT COUNT(*) AS total
        FROM student_profiles
        WHERE coordinator_uuid = '{$safeCoord}'
          AND batch_uuid = '{$safeBatch}'
    ");
    $totalStudents = (int) $result->fetch_assoc()['total'];

    $result    = $conn->query("
        SELECT COUNT(*) AS total
        FROM student_profiles
        WHERE coordinator_uuid = '{$safeCoord}'
          AND batch_uuid = '{$safeBatch}'
          AND company_uuid IS NOT NULL
    ");
    $activeOjt = (int) $result->fetch_assoc()['total'];

    $pendingDtr          = 0; // getCoordinatorPendingDtr($conn, $coordinatorUuid)
    $pendingApplications = 0; // getCoordinatorPendingApplications($conn, $coordinatorUuid)
    $pendingRequirements = 0; // getCoordinatorPendingRequirements($conn, $coordinatorUuid)
    $totalPending        = $pendingDtr + $pendingApplications + $pendingRequirements;

    $avgHours = 0;

    return [
        'total_students'      => $totalStudents,
        'active_ojt'          => $activeOjt,
        'not_started'         => $totalStudents - $activeOjt,
        'pending_approvals'   => $totalPending,
        'pending_dtr'         => $pendingDtr,
        'pending_applications'=> $pendingApplications,
        'pending_requirements'=> $pendingRequirements,
        'avg_hours'           => $avgHours,
    ];
}

function getCoordinatorNeedsAction($conn, string $coordinatorUuid, ?string $batchUuid): array
{
    $tasks = [];
    $safeCoord = $conn->real_escape_string($coordinatorUuid);
    $safeBatch = $conn->real_escape_string($batchUuid ?? '');

    // students with no application yet
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM student_profiles sp
        WHERE sp.coordinator_uuid = '{$safeCoord}'
          AND sp.batch_uuid       = '{$safeBatch}'
          AND sp.company_uuid IS NULL
    ");
    $noApp = (int) $result->fetch_assoc()['total'];
    if ($noApp > 0) {
        $tasks[] = [
            'type'    => 'info',
            'message' => "{$noApp} student" . ($noApp > 1 ? 's have' : ' has') . " no OJT application yet",
            'count'   => $noApp,
            'link'    => '/coordinator/students',
            'module'  => 'students',
        ];
    }

    // students never logged in
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM student_profiles sp
        JOIN users u ON sp.user_uuid = u.uuid
        WHERE sp.coordinator_uuid = '{$safeCoord}'
          AND sp.batch_uuid       = '{$safeBatch}'
          AND u.last_login_at IS NULL
    ");
    $neverLoggedIn = (int) $result->fetch_assoc()['total'];
    if ($neverLoggedIn > 0) {
        $tasks[] = [
            'type'    => 'warning',
            'message' => "{$neverLoggedIn} student" . ($neverLoggedIn > 1 ? 's have' : ' has') . " never logged in",
            'count'   => $neverLoggedIn,
            'link'    => '/coordinator/students?filter=never_logged_in',
            'module'  => 'students',
        ];
    }

    // temp
    $tasks[] = [
        'type'    => 'danger',
        'message' => 'Pending OJT applications to review',
        'count'   => 0,
        'link'    => '/coordinator/applications?status=pending',
        'module'  => 'applications',
    ];

    // temp
    $tasks[] = [
        'type'    => 'warning',
        'message' => 'DTR entries awaiting approval',
        'count'   => 0, 
        'link'    => '/coordinator/dtr?status=pending',
        'module'  => 'dtr',
    ];

    // temp
    $tasks[] = [
        'type'    => 'info',
        'message' => 'Pre-OJT requirements pending review',
        'count'   => 0,
        'link'    => '/coordinator/requirements?status=pending',
        'module'  => 'requirements',
    ];

    // temp
    $tasks[] = [
        'type'    => 'warning',
        'message' => 'Weekly journals submitted — not yet reviewed',
        'count'   => 0,
        'link'    => '/coordinator/journals?status=submitted',
        'module'  => 'journals',
    ];

    return $tasks;
}

function getCoordinatorStudentsSummary($conn, string $coordinatorUuid, ?string $batchUuid): array
{
    $safeCoord = $conn->real_escape_string($coordinatorUuid);
    $safeBatch = $conn->real_escape_string($batchUuid ?? '');

    $result = $conn->query("
        SELECT
          sp.uuid           AS profile_uuid,
          sp.first_name,
          sp.last_name,
          sp.year_level,
          sp.company_uuid,
          p.code            AS program_code,
          u.last_login_at,
          u.is_active,
          c.name            AS company_name,

          -- ojt status — simplified until application module is built
          CASE
            WHEN sp.company_uuid IS NOT NULL THEN 'ojt_active'
            WHEN u.last_login_at IS NULL     THEN 'never_logged_in'
            ELSE 'not_started'
          END AS ojt_status

        FROM student_profiles sp
        JOIN users u              ON sp.user_uuid     = u.uuid
        LEFT JOIN programs p      ON sp.program_uuid  = p.uuid
        LEFT JOIN companies c     ON sp.company_uuid  = c.uuid
        WHERE sp.coordinator_uuid = '{$safeCoord}'
          AND sp.batch_uuid       = '{$safeBatch}'
        ORDER BY sp.last_name ASC
    ");

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'profile_uuid' => $row['profile_uuid'],
            'full_name'    => $row['first_name'] . ' ' . $row['last_name'],
            'initials'     => strtoupper(substr($row['first_name'],0,1) . substr($row['last_name'],0,1)),
            'program_code' => $row['program_code'] ?? '—',
            'year_level'   => ordinal((int)$row['year_level']) . ' Year',
            'company_name' => $row['company_name'] ?? null,
            'ojt_status'   => $row['ojt_status'],
            'status_label' => match($row['ojt_status']) {
                'ojt_active'      => 'OJT active',
                'never_logged_in' => 'Never logged in',
                'not_started'     => 'Not started',
                default           => 'Unknown',
            },
            'status_color' => match($row['ojt_status']) {
                'ojt_active'      => 'success',
                'never_logged_in' => 'info',
                'not_started'     => 'secondary',
                default           => 'secondary',
            },
        ];
    }

    return $students;
}

function getCoordinatorHoursProgress($conn, string $coordinatorUuid, ?string $batchUuid): array
{
    $safeCoord = $conn->real_escape_string($coordinatorUuid);
    $safeBatch = $conn->real_escape_string($batchUuid ?? '');

    $result = $conn->query("
        SELECT
          sp.first_name,
          sp.last_name,
          COALESCE(p.required_hours, 486) AS required_hours,
          0 AS hours_rendered
          -- replace 0 with:
          -- COALESCE(SUM(de.hours), 0) AS hours_rendered
          -- LEFT JOIN dtr_entries de ON sp.uuid = de.student_uuid
          --   AND de.batch_uuid = '{$safeBatch}'
          --   AND de.status = 'approved'
        FROM student_profiles sp
        LEFT JOIN programs p ON sp.program_uuid = p.uuid
        WHERE sp.coordinator_uuid = '{$safeCoord}'
          AND sp.batch_uuid       = '{$safeBatch}'
        ORDER BY sp.last_name ASC
    ");

    $progress = [];
    while ($row = $result->fetch_assoc()) {
        $rendered  = (int) $row['hours_rendered'];
        $required  = (int) $row['required_hours'];
        $pct       = $required > 0 ? round(($rendered / $required) * 100) : 0;

        $progress[] = [
            'name'           => $row['first_name'] . ' ' . $row['last_name'],
            'hours_rendered' => $rendered,
            'required_hours' => $required,
            'percentage'     => $pct,
            'color'          => match(true) {
                $pct >= 75 => '#1D9E75',
                $pct >= 50 => '#185FA5',
                $pct >= 25 => '#BA7517',
                default    => '#888780',
            },
        ];
    }

    return $progress;
}

function getCoordinatorCompanies($conn, ?string $batchUuid): array
{
    $safeBatch = $conn->real_escape_string($batchUuid ?? '');

    $result = $conn->query("
        SELECT
          c.uuid,
          c.name,
          c.work_setup,
          cs.total_slots,
          COUNT(DISTINCT sp.id) AS filled_slots,
          cd.valid_until        AS moa_expiry,
          DATEDIFF(cd.valid_until, CURDATE()) AS moa_days_left
        FROM companies c
        LEFT JOIN company_slots cs
          ON c.uuid = cs.company_uuid
          AND cs.batch_uuid = '{$safeBatch}'
        LEFT JOIN student_profiles sp
          ON c.uuid = sp.company_uuid
          AND sp.batch_uuid = '{$safeBatch}'
        LEFT JOIN company_documents cd
          ON c.uuid = cd.company_uuid AND cd.doc_type = 'moa'
        WHERE c.accreditation_status = 'active'
        GROUP BY c.id
        ORDER BY c.name ASC
    ");

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $daysLeft = $row['moa_days_left'] !== null ? (int) $row['moa_days_left'] : null;

        $companies[] = [
            'uuid'          => $row['uuid'],
            'name'          => $row['name'],
            'work_setup'    => $row['work_setup'],
            'total_slots'   => (int) ($row['total_slots']  ?? 0),
            'filled_slots'  => (int) ($row['filled_slots'] ?? 0),
            'moa_days_left' => $daysLeft,
            'moa_warning'   => $daysLeft !== null && $daysLeft <= 30,
        ];
    }

    return $companies;
}

function getCoordinatorUpcomingVisits($conn, string $coordinatorUuid): array
{
    // uncomment when coordinator_visits table is created:
    // $safeCoord = $conn->real_escape_string($coordinatorUuid);
    // $result = $conn->query("
    //     SELECT cv.*, c.name AS company_name
    //     FROM coordinator_visits cv
    //     JOIN companies c ON cv.company_uuid = c.uuid
    //     WHERE cv.coordinator_uuid = '{$safeCoord}'
    //       AND cv.visit_date >= CURDATE()
    //       AND cv.status = 'scheduled'
    //     ORDER BY cv.visit_date ASC
    //     LIMIT 5
    // ");
    // $visits = [];
    // while ($row = $result->fetch_assoc()) {
    //     $visits[] = [
    //         'company_name' => $row['company_name'],
    //         'visit_date'   => date('M j, Y', strtotime($row['visit_date'])),
    //         'notes'        => $row['notes'] ?? '',
    //     ];
    // }
    // return $visits;

    return []; // empty until visits module is built
}

function getActiveBatch($conn): ?array
{
    $result = $conn->query("
        SELECT uuid, school_year, semester, required_hours
        FROM batches WHERE status = 'active' LIMIT 1
    ");
    $row = $result->fetch_assoc();
    if (!$row) return null;

    return [
        'uuid'           => $row['uuid'],
        'label'          => "AY {$row['school_year']} {$row['semester']} Semester",
        'required_hours' => (int) $row['required_hours'],
    ];
}

//fetch_dashboard_data
if ($action === 'fetch_dashboard_data') {
    //fetch all

    $coordinatorProfileUuid = $_SESSION['user']['uuid'] ?? null;
    if (!$coordinatorProfileUuid) {
        response([
            'status' => 'error',
            'message' => 'User not authenticated.',
            'long_message' => 'No user session found. Please log in again.'
        ]);
    }

    $data = getCoordinatorDashboardData($conn, $coordinatorProfileUuid);
    response([
        'status' => 'success',
        'message' => 'Dashboard data fetched successfully.',
        'data' => $data
    ]);

} else {
    response([
        'status' => 'error',
        'message' => 'Invalid action specified.',
        'long_message' => "The action '{$action}' is not recognized by this endpoint."
    ]);
}