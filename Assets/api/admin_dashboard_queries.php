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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    response([
        'status' => 'info',
        'message' => 'Request method is not allowed.',
        'long_message' => 'Only GET requests are allowed for this endpoint.'
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
          AND cd.valid_until > CURDATE()
          AND cd.valid_until <= DATE_ADD(CURDATE(), INTERVAL {$daysThreshold} DAY)
          AND c.accreditation_status = 'active'
          AND cd.id = (
            SELECT id FROM company_documents cd2
            WHERE cd2.company_uuid = cd.company_uuid
              AND cd2.doc_type = 'moa'
            ORDER BY cd2.created_at DESC
            LIMIT 1
          )
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

function getStatCards($conn): array
{
    // user counts by role
    $result = $conn->query("
        SELECT
          SUM(CASE WHEN role = 'student'     THEN 1 ELSE 0 END) AS total_students,
          SUM(CASE WHEN role = 'coordinator' THEN 1 ELSE 0 END) AS total_coordinators,
          SUM(CASE WHEN role = 'supervisor'  THEN 1 ELSE 0 END) AS total_supervisors,
          SUM(CASE WHEN role = 'admin'       THEN 1 ELSE 0 END) AS total_admins,
          COUNT(*)                                               AS total_users
        FROM users
        WHERE is_active = 1
    ");
    $counts = $result->fetch_assoc();

    // students with no profile
    $result    = $conn->query("
        SELECT COUNT(*) AS total
        FROM users u
        LEFT JOIN student_profiles sp ON u.uuid = sp.user_uuid
        WHERE u.role = 'student'
          AND u.is_active = 1
          AND sp.id IS NULL
    ");
    $noProfile = $result->fetch_assoc()['total'];

    $result    = $conn->query("
    SELECT
      COUNT(*) AS total_companies,
      SUM(CASE WHEN accreditation_status = 'active' THEN 1 ELSE 0 END) AS active_companies
    FROM companies
    ");
    $companies = $result->fetch_assoc();

    return [
        'total_users'         => (int) $counts['total_users'],
        'total_students'      => (int) $counts['total_students'],
        'total_coordinators'  => (int) $counts['total_coordinators'],
        'total_supervisors'   => (int) $counts['total_supervisors'],
        'total_admins'        => (int) $counts['total_admins'],
        'students_no_profile' => (int) $noProfile,
        'total_companies'  => (int) ($companies['total_companies']  ?? 0),
        'active_companies' => (int) ($companies['active_companies'] ?? 0),
        'moa_expiring'     => count(getExpiringMoas($conn, 30)),
    ];
}

function getUsersByRole($conn): array
{
    $result = $conn->query("
        SELECT role, COUNT(*) AS total
        FROM users
        WHERE is_active = 1
        GROUP BY role
        ORDER BY total DESC
    ");

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $grandTotal = array_sum(array_column($rows, 'total'));

    $breakdown = array_map(fn ($row) => [
        'role'       => $row['role'],
        'label'      => ucfirst($row['role']) . 's',
        'total'      => (int) $row['total'],
        'percentage' => $grandTotal > 0
                          ? round(($row['total'] / $grandTotal) * 100)
                          : 0,
        'color'      => match($row['role']) {
            'student'     => '#BA7517',
            'supervisor'  => '#1D9E75',
            'coordinator' => '#185FA5',
            'admin'       => '#A32D2D',
            default       => '#888780'
        },
    ], $rows);

    return [
        'total'     => $grandTotal,
        'breakdown' => $breakdown,
    ];
}

function getRecentAccounts($conn, int $limit = 5): array
{
    $limit = (int) $limit;

    $result = $conn->query("
        SELECT
          u.uuid,
          u.email,
          u.role,
          u.is_active,
          u.created_at,
          u.last_login_at,

          CASE u.role
            WHEN 'student'     THEN CONCAT(COALESCE(sp.first_name,''),  ' ', COALESCE(sp.last_name,''))
            WHEN 'coordinator' THEN CONCAT(COALESCE(cp.first_name,''),  ' ', COALESCE(cp.last_name,''))
            WHEN 'supervisor'  THEN CONCAT(COALESCE(svp.first_name,''), ' ', COALESCE(svp.last_name,''))
            WHEN 'admin'       THEN CONCAT(COALESCE(ap.first_name,''),  ' ', COALESCE(ap.last_name,''))
          END AS full_name,

          CASE u.role
            WHEN 'student'     THEN CONCAT(COALESCE(LEFT(sp.first_name,1),'?'),  COALESCE(LEFT(sp.last_name,1),'?'))
            WHEN 'coordinator' THEN CONCAT(COALESCE(LEFT(cp.first_name,1),'?'),  COALESCE(LEFT(cp.last_name,1),'?'))
            WHEN 'supervisor'  THEN CONCAT(COALESCE(LEFT(svp.first_name,1),'?'), COALESCE(LEFT(svp.last_name,1),'?'))
            WHEN 'admin'       THEN CONCAT(COALESCE(LEFT(ap.first_name,1),'?'),  COALESCE(LEFT(ap.last_name,1),'?'))
            ELSE '??'
          END AS initials,

          CASE
            WHEN u.is_active = 0 THEN 'inactive'
            WHEN u.last_login_at IS NULL THEN 'never_logged_in'
            WHEN (
              (u.role = 'student'     AND sp.id  IS NULL) OR
              (u.role = 'coordinator' AND cp.id  IS NULL) OR
              (u.role = 'supervisor'  AND svp.id IS NULL) OR
              (u.role = 'admin'       AND ap.id  IS NULL)
            ) THEN 'no_profile'
            ELSE 'active'
          END AS status

        FROM users u
        LEFT JOIN student_profiles     sp  ON u.uuid = sp.user_uuid
        LEFT JOIN coordinator_profiles cp  ON u.uuid = cp.user_uuid
        LEFT JOIN supervisor_profiles  svp ON u.uuid = svp.user_uuid
        LEFT JOIN admin_profiles       ap  ON u.uuid = ap.user_uuid
        ORDER BY u.created_at DESC
        LIMIT {$limit}
    ");

    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = [
            'uuid'         => $row['uuid'],
            'email'        => $row['email'],
            'role'         => $row['role'],
            'full_name'    => trim($row['full_name']) ?: 'No name yet',
            'initials'     => $row['initials'] ?: '??',
            'status'       => $row['status'],
            'status_label' => match($row['status']) {
                'active'          => 'Active',
                'inactive'        => 'Inactive',
                'never_logged_in' => 'Never logged in',
                'no_profile'      => 'No profile',
                default           => 'Unknown'
            },
            'role_label'   => ucfirst($row['role']),
            'added_on'     => date('M j, Y', strtotime($row['created_at'])),
            'last_login'   => $row['last_login_at']
                                ? date('M j, Y g:i A', strtotime($row['last_login_at']))
                                : null,
        ];
    }

    return $accounts;
}

function getRecentActivity($conn, int $limit = 20): array
{
    $limit = (int) $limit;

    $result = $conn->query("
        SELECT
          al.event_type,
          al.description,
          al.module,
          al.created_at,

          CASE u.role
            WHEN 'student'     THEN CONCAT(COALESCE(sp.first_name,''),  ' ', COALESCE(sp.last_name,''))
            WHEN 'coordinator' THEN CONCAT(COALESCE(cp.first_name,''),  ' ', COALESCE(cp.last_name,''))
            WHEN 'supervisor'  THEN CONCAT(COALESCE(svp.first_name,''), ' ', COALESCE(svp.last_name,''))
            WHEN 'admin'       THEN CONCAT(COALESCE(ap.first_name,''),  ' ', COALESCE(ap.last_name,''))
            ELSE NULL
          END AS actor_name,

          u.role AS actor_role

        FROM activity_log al
        LEFT JOIN users u ON al.actor_uuid = u.uuid
        LEFT JOIN student_profiles     sp  ON u.uuid = sp.user_uuid
        LEFT JOIN coordinator_profiles cp  ON u.uuid = cp.user_uuid
        LEFT JOIN supervisor_profiles  svp ON u.uuid = svp.user_uuid
        LEFT JOIN admin_profiles       ap  ON u.uuid = ap.user_uuid
        ORDER BY al.created_at DESC
        LIMIT {$limit}
    ");

    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            'event_type'  => $row['event_type'],
            'description' => $row['description'],
            'module'      => $row['module'],
            'actor_name'  => trim($row['actor_name'] ?? '') ?: 'System',
            'actor_role'  => $row['actor_role'] ?? 'system',
            'time_ago'    => timeAgo($row['created_at']),
            'full_date'   => date('M j, Y g:i A', strtotime($row['created_at'])),
            'icon_type'   => match(true) {
                str_contains($row['event_type'], 'created'),
                str_contains($row['event_type'], 'approved'),
                str_contains($row['event_type'], 'activated'),
                str_contains($row['event_type'], 'success'),
                str_contains($row['event_type'], 'enabled') => 'success',

                str_contains($row['event_type'], 'updated'),
                str_contains($row['event_type'], 'submitted'),
                str_contains($row['event_type'], 'issued') => 'primary',

                str_contains($row['event_type'], 'deactivated'),
                str_contains($row['event_type'], 'rejected'),
                str_contains($row['event_type'], 'failed'),
                str_contains($row['event_type'], 'disabled') => 'danger',

                default => 'secondary'
            }
            ];
    }

    return $activities;
}

function getNeedsAttention($conn): array
{
    $alerts = [];

    // students with no profile
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM users u
        LEFT JOIN student_profiles sp ON u.uuid = sp.user_uuid
        WHERE u.role = 'student' AND u.is_active = 1 AND sp.id IS NULL
    ");
    $count = (int) $result->fetch_assoc()['total'];
    if ($count > 0) {
        $alerts[] = [
            'type'    => 'warning',
            'message' => "{$count} student" . ($count > 1 ? 's have' : ' has') . " incomplete profiles",
            'action'  => 'View students',
            'description' => 'These students have registered accounts but have not completed their profiles. Encourage them to complete their profiles to access all features.',
            'link'    => '/admin/users?role=student&filter=no_profile',
        ];
    }

    // accounts that never logged in
    $result = $conn->query("
        SELECT COUNT(*) AS total FROM users
        WHERE last_login_at IS NULL AND is_active = 1
    ");
    $count = (int) $result->fetch_assoc()['total'];
    $emails = [];
    $emailResult = $conn->query("
        SELECT email FROM users
        WHERE last_login_at IS NULL AND is_active = 1
        LIMIT 3
    ");
    while ($row = $emailResult->fetch_assoc()) {
        $emails[] = $row['email'];
    }
    if ($count > 0) {
        $alerts[] = [
            'type'    => 'info',
            'message' => "{$count} account" . ($count > 1 ? 's have' : ' has') . " never logged in",
            'description' => implode(', ', $emails) . ($count > 3 ? ', ...' : ''),
            'action'  => 'View accounts',
            'link'    => '/admin/users?filter=never_logged_in',
        ];
    }

    // no active batch
    $result = $conn->query("SELECT COUNT(*) AS total FROM batches WHERE status = 'active'");
    $hasActiveBatch = (int) $result->fetch_assoc()['total'];
    if ($hasActiveBatch === 0) {
        $alerts[] = [
            'type'    => 'danger',
            'message' => 'No active batch configured for current semester',
            'description' => 'Students cannot be assigned to a batch until an active batch is created.',
            'action'  => 'Create batch',
            'link'    => '../../Pages/Admin/batches?action=create',
        ];
    }

    $expiring = getExpiringMoas($conn, 30);
    foreach ($expiring as $co) {
        $alerts[] = [
            'type'    => 'warning',
            'message' => "MOA expiring soon",
            'action'  => 'View company',
            'description' => "{$co['company_name']} MOA expires in {$co['days_left']} day" . ($co['days_left'] > 1 ? 's' : ''),
            'link'    => "../../Pages/Admin/Companies?company_uuid={$co['company_uuid']}",
        ];
    }

    // deactivated accounts
    $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE is_active = 0");
    $count = (int) $result->fetch_assoc()['total'];
    if ($count > 0) {
        $alerts[] = [
            'type'    => 'info',
            'message' => "{$count} account" . ($count > 1 ? 's are' : ' is') . " deactivated",
            'description' => 'Review deactivated accounts to ensure they were intentionally deactivated and not due to an error.',
            'action'  => 'View all users',
            'link'    => '/admin/users?filter=inactive',
        ];
    }

    usort($alerts, function ($a, $b) {
        $priority = ['danger' => 3, 'warning' => 2, 'info' => 1];
        return ($priority[$b['type']] ?? 0) <=> ($priority[$a['type']] ?? 0);
    });


    return $alerts;
}

function getDashboardData($conn, string $type = null): array
{
    if ($type === 'stat_cards') {
        return getStatCards($conn);
    } elseif ($type === 'users_by_role') {
        return getUsersByRole($conn);
    } elseif ($type === 'recent_accounts') {
        return getRecentAccounts($conn);
    } elseif ($type === 'recent_activity') {
        return getRecentActivity($conn);
    } elseif ($type === 'alerts') {
        return getNeedsAttention($conn);
    } else {
        response([
            'status' => 'info',
            'message' => 'Invalid data type requested.',
            'long_message' => 'The "type" parameter is missing or invalid. Please specify a valid type to retrieve dashboard data.'
        ]);
    }
}

$dashboard = getDashboardData($conn, $type);

response([
    'status' => 'success',
    'data'   => $dashboard
]);
