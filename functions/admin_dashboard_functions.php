<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function getAdminDashboardData($conn): array
{
    $activeBatch = getAdminActiveBatch($conn);

    return [
        'active_batch'    => $activeBatch,
        'stat_cards'      => getAdminStatCards($conn, $activeBatch['uuid'] ?? null),
        'role_breakdown'  => getUserRoleBreakdown($conn),
        'needs_attention' => getAdminNeedsAttention($conn, $activeBatch['uuid'] ?? null),
        'recent_accounts' => getRecentAccounts($conn, 5),
        'recent_activity' => getRecentActivity($conn, 10),
    ];
}

function getAdminStatCards($conn, ?string $batchUuid): array
{
    // total students in active batch
    $totalStudents = 0;
    if ($batchUuid) {
        $safeBatch     = $conn->real_escape_string($batchUuid);
        $result        = $conn->query("
            SELECT COUNT(*) AS total
            FROM student_profiles
            WHERE batch_uuid = '{$safeBatch}'
        ");
        $totalStudents = (int) $result->fetch_assoc()['total'];
    }

    // active coordinators
    $result             = $conn->query("
        SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'coordinator' AND is_active = 1
    ");
    $totalCoordinators  = (int) $result->fetch_assoc()['total'];

    // accredited companies
    $result             = $conn->query("
        SELECT COUNT(*) AS total
        FROM companies
        WHERE accreditation_status = 'active'
    ");
    $totalCompanies     = (int) $result->fetch_assoc()['total'];

    // active supervisors
    $result             = $conn->query("
        SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'supervisor' AND is_active = 1
    ");
    $totalSupervisors   = (int) $result->fetch_assoc()['total'];

    // total users across all roles
    $result             = $conn->query("
        SELECT COUNT(*) AS total FROM users WHERE is_active = 1
    ");
    $totalUsers         = (int) $result->fetch_assoc()['total'];

    // MOA expiring within 30 days
    $expiringMoas = getExpiringMoaCount($conn, 30);

    return [
        'total_students'    => $totalStudents,
        'total_coordinators'=> $totalCoordinators,
        'total_companies'   => $totalCompanies,
        'total_supervisors' => $totalSupervisors,
        'total_users'       => $totalUsers,
        'expiring_moas'     => $expiringMoas,
    ];
}

function getUserRoleBreakdown($conn): array
{
    $result = $conn->query("
        SELECT role, COUNT(*) AS total
        FROM users
        WHERE is_active = 1
        GROUP BY role
        ORDER BY FIELD(role, 'admin', 'coordinator', 'student', 'supervisor')
    ");

    $roles  = [];
    $grand  = 0;

    while ($row = $result->fetch_assoc()) {
        $roles[$row['role']] = (int) $row['total'];
        $grand += (int) $row['total'];
    }

    $breakdown = [];
    $roleColors = [
        'admin'       => '#0F6E56',
        'coordinator' => '#185FA5',
        'student'     => '#BA7517',
        'supervisor'  => '#6B7280',
    ];
    $roleLabels = [
        'admin'       => 'Admin',
        'coordinator' => 'Coordinators',
        'student'     => 'Students',
        'supervisor'  => 'Supervisors',
    ];

    foreach (['admin','coordinator','student','supervisor'] as $role) {
        $count = $roles[$role] ?? 0;
        $breakdown[] = [
            'role'       => $role,
            'label'      => $roleLabels[$role],
            'count'      => $count,
            'percentage' => $grand > 0 ? round(($count / $grand) * 100) : 0,
            'color'      => $roleColors[$role],
        ];
    }

    return [
        'breakdown'   => $breakdown,
        'grand_total' => $grand,
    ];
}

function getAdminNeedsAttention($conn, ?string $batchUuid): array
{
    $alerts = [];

    // no active batch
    $result = $conn->query("SELECT COUNT(*) AS total FROM batches WHERE status = 'active'");
    if ((int) $result->fetch_assoc()['total'] === 0) {
        $alerts[] = [
            'type'           => 'danger',
            'severity'       => 'Critical',
            'priority'       => 100,
            'category'       => 'Batch Setup',
            'affected_count' => 1,
            'icon'           => 'calendar',
            'message'        => 'No active batch — students cannot apply for OJT',
            'action'         => 'Create batch',
            'link'           => 'Batches',
        ];
    }

    // students never logged in
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'student'
          AND is_active = 1
          AND last_login_at IS NULL
    ");
    $count = (int) $result->fetch_assoc()['total'];
    if ($count > 0) {
        $alerts[] = [
            'type'           => 'warning',
            'severity'       => 'High',
            'priority'       => 80,
            'category'       => 'User Engagement',
            'affected_count' => $count,
            'icon'           => 'user',
            'message'        => "{$count} student" . ($count > 1 ? 's have' : ' has') . " never logged in",
            'action'         => 'View students',
            'link'           => 'Students',
        ];
    }

    // MOA expiring within 30 days
    $count = getExpiringMoaCount($conn, 30);
    if ($count > 0) {
        $alerts[] = [
            'type'           => 'warning',
            'severity'       => 'High',
            'priority'       => 75,
            'category'       => 'Compliance',
            'affected_count' => $count,
            'icon'           => 'file',
            'message'        => "{$count} company MOA" . ($count > 1 ? 's are' : ' is') . " expiring within 30 days",
            'action'         => 'View companies',
            'link'           => 'Companies',
        ];
    }

    // companies with no slots set for active batch
    if ($batchUuid) {
        $safeBatch = $conn->real_escape_string($batchUuid);
        $result    = $conn->query("
            SELECT COUNT(*) AS total
            FROM companies c
            WHERE c.accreditation_status = 'active'
              AND NOT EXISTS (
                SELECT 1 FROM company_slots cs
                WHERE cs.company_uuid = c.uuid
                  AND cs.batch_uuid   = '{$safeBatch}'
                  AND cs.total_slots  > 0
              )
        ");
        $count = (int) $result->fetch_assoc()['total'];
        if ($count > 0) {
            $alerts[] = [
                'type'           => 'info',
                'severity'       => 'Medium',
                'priority'       => 55,
                'category'       => 'Capacity Planning',
                'affected_count' => $count,
                'icon'           => 'building',
                'message'        => "{$count} active " . ($count > 1 ? 'companies have' : 'company has') . " no slots set for this batch",
                'action'         => 'View companies',
                'link'           => 'Companies',
            ];
        }
    }

    // coordinators with no students assigned
    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM coordinator_profiles cp
        JOIN users u ON cp.user_uuid = u.uuid
        WHERE u.is_active = 1
          AND NOT EXISTS (
            SELECT 1 FROM student_profiles sp
            WHERE sp.coordinator_uuid = cp.uuid
          )
    ");
    $count = (int) $result->fetch_assoc()['total'];
    if ($count > 0) {
        $alerts[] = [
            'type'           => 'info',
            'severity'       => 'Medium',
            'priority'       => 50,
            'category'       => 'Assignment',
            'affected_count' => $count,
            'icon'           => 'user',
            'message'        => "{$count} coordinator" . ($count > 1 ? 's have' : ' has') . " no students assigned",
            'action'         => 'View coordinators',
            'link'           => 'Coordinators',
        ];
    }

    // deactivated accounts
    $result = $conn->query("
        SELECT COUNT(*) AS total FROM users WHERE is_active = 0
    ");
    $count = (int) $result->fetch_assoc()['total'];
    if ($count > 0) {
        $alerts[] = [
            'type'           => 'info',
            'severity'       => 'Low',
            'priority'       => 35,
            'category'       => 'Account Status',
            'affected_count' => $count,
            'icon'           => 'user',
            'message'        => "{$count} account" . ($count > 1 ? 's are' : ' is') . " deactivated",
            'action'         => 'View all users',
            'link'           => 'Students',
        ];
    }

    usort($alerts, function ($a, $b) {
        return (int)($b['priority'] ?? 0) <=> (int)($a['priority'] ?? 0);
    });

    return array_values($alerts);
}

function getRecentAccounts($conn, int $limit = 5): array
{
    $safeLimit = (int) $limit;

    $result = $conn->query("
        SELECT
          u.uuid,
          u.email,
          u.role,
          u.is_active,
          u.created_at,
          u.last_login_at,

          CASE u.role
            WHEN 'admin'       THEN CONCAT(ap.first_name,  ' ', ap.last_name)
            WHEN 'coordinator' THEN CONCAT(cp.first_name,  ' ', cp.last_name)
            WHEN 'student'     THEN CONCAT(sp.first_name,  ' ', sp.last_name)
            WHEN 'supervisor'  THEN CONCAT(svp.first_name, ' ', svp.last_name)
            ELSE '—'
          END AS full_name,

          CASE u.role
            WHEN 'admin'       THEN CONCAT(LEFT(ap.first_name,1),  LEFT(ap.last_name,1))
            WHEN 'coordinator' THEN CONCAT(LEFT(cp.first_name,1),  LEFT(cp.last_name,1))
            WHEN 'student'     THEN CONCAT(LEFT(sp.first_name,1),  LEFT(sp.last_name,1))
            WHEN 'supervisor'  THEN CONCAT(LEFT(svp.first_name,1), LEFT(svp.last_name,1))
            ELSE '??'
          END AS initials

        FROM users u
        LEFT JOIN admin_profiles ap        ON u.uuid = ap.user_uuid  AND u.role = 'admin'
        LEFT JOIN coordinator_profiles cp  ON u.uuid = cp.user_uuid  AND u.role = 'coordinator'
        LEFT JOIN student_profiles sp      ON u.uuid = sp.user_uuid  AND u.role = 'student'
        LEFT JOIN supervisor_profiles svp  ON u.uuid = svp.user_uuid AND u.role = 'supervisor'

        ORDER BY u.created_at DESC
        LIMIT {$safeLimit}
    ");

    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = [
            'uuid'         => $row['uuid'],
            'full_name'    => trim($row['full_name']) ?: 'No profile yet',
            'initials'     => strtoupper(trim($row['initials'])) ?: '??',
            'email'        => $row['email'],
            'role'         => $row['role'],
            'role_label'   => ucfirst($row['role']),
            'is_active'    => (int) $row['is_active'],
            'last_login'   => $row['last_login_at']
                                ? date('M j, Y', strtotime($row['last_login_at']))
                                : null,
            'created_at'   => date('M j, Y', strtotime($row['created_at'])),
            'time_ago'     => timeAgo($row['created_at']),
            'has_profile'  => !empty(trim($row['full_name'])),
        ];
    }

    return $accounts;
}

function getRecentActivity($conn, int $limit = 10): array
{
    $safeLimit = (int) $limit;

    $result = $conn->query("
        SELECT
                    al.id,
          al.event_type,
          al.description,
          al.module,
          al.created_at,

          -- actor name
          CASE u.role
            WHEN 'admin'       THEN CONCAT(ap.first_name,  ' ', ap.last_name)
            WHEN 'coordinator' THEN CONCAT(cp.first_name,  ' ', cp.last_name)
            WHEN 'student'     THEN CONCAT(sp.first_name,  ' ', sp.last_name)
            WHEN 'supervisor'  THEN CONCAT(svp.first_name, ' ', svp.last_name)
            ELSE 'System'
          END AS actor_name,

          u.role AS actor_role

        FROM activity_log al
        LEFT JOIN users u                  ON al.actor_uuid  = u.uuid
        LEFT JOIN admin_profiles ap        ON u.uuid = ap.user_uuid  AND u.role = 'admin'
        LEFT JOIN coordinator_profiles cp  ON u.uuid = cp.user_uuid  AND u.role = 'coordinator'
        LEFT JOIN student_profiles sp      ON u.uuid = sp.user_uuid  AND u.role = 'student'
        LEFT JOIN supervisor_profiles svp  ON u.uuid = svp.user_uuid AND u.role = 'supervisor'

        ORDER BY al.created_at DESC
        LIMIT {$safeLimit}
    ");

    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            'id'          => (int)($row['id'] ?? 0),
            'event_type'  => $row['event_type'],
            'description' => $row['description'],
            'module'      => $row['module'] ?? '—',
            'actor_name'  => trim($row['actor_name']) ?: 'System',
            'actor_role'  => $row['actor_role'] ?? 'system',
            'time_ago'    => timeAgo($row['created_at']),
            'created_at'  => date('M j, Y g:i A', strtotime($row['created_at'])),
        ];
    }

    return $activities;
}

function getAdminActiveBatch($conn): ?array
{
    $result = $conn->query("
        SELECT uuid, school_year, semester, start_date, end_date, required_hours, status
        FROM batches
        WHERE status = 'active'
        LIMIT 1
    ");

    $row = $result->fetch_assoc();
    if (!$row) return null;

    return [
        'uuid'           => $row['uuid'],
        'label'          => "AY {$row['school_year']} {$row['semester']} Semester",
        'school_year'    => $row['school_year'],
        'semester'       => $row['semester'],
        'start_date'     => $row['start_date']
                             ? date('M j, Y', strtotime($row['start_date']))
                             : '—',
        'end_date'       => $row['end_date']
                             ? date('M j, Y', strtotime($row['end_date']))
                             : '—',
        'required_hours' => (int) $row['required_hours'],
    ];
}


function getExpiringMoaCount($conn, int $days = 30): int
{
    $result = $conn->query("
        SELECT COUNT(DISTINCT c.uuid) AS total
        FROM companies c
        JOIN company_documents cd
          ON cd.uuid = (
            SELECT uuid FROM company_documents
            WHERE company_uuid = c.uuid AND doc_type = 'moa'
            ORDER BY created_at DESC LIMIT 1
          )
        WHERE cd.valid_until BETWEEN CURDATE()
          AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY)
          AND c.accreditation_status = 'active'
    ");

    return (int) $result->fetch_assoc()['total'];
}