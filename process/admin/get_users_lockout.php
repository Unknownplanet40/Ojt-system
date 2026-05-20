<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';
require_once '../../functions/auth_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_uuid']) || !in_array($_SESSION['user_role'], ['admin', 'coordinator'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchTerm = $search ? "%$search%" : "";
$isCoordinator = $_SESSION['user_role'] === 'coordinator';

$coordProfileUuid = null;
if ($isCoordinator) {
    $cpStmt = $conn->prepare("SELECT uuid FROM coordinator_profiles WHERE user_uuid = ? LIMIT 1");
    $cpStmt->bind_param('s', $_SESSION['user_uuid']);
    $cpStmt->execute();
    $cpRow = $cpStmt->get_result()->fetch_assoc();
    $cpStmt->close();

    if (!$cpRow) {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }
    $coordProfileUuid = $cpRow['uuid'];
}

if ($isCoordinator) {
    $query = "
        SELECT 
            u.uuid, 
            u.email, 
            u.role, 
            u.is_active, 
            u.login_attempts, 
            u.lockout_until, 
            u.manual_lockout,
            stp.first_name,
            stp.last_name
        FROM users u
        INNER JOIN student_profiles stp ON u.uuid = stp.user_uuid
            AND stp.coordinator_uuid = ?
        WHERE u.role = 'student'
    ";
} else {
    
    $query = "
        SELECT 
            u.uuid, 
            u.email, 
            u.role, 
            u.is_active, 
            u.login_attempts, 
            u.lockout_until, 
            u.manual_lockout,
            COALESCE(ap.first_name, cp.first_name, sp.first_name, stp.first_name) as first_name,
            COALESCE(ap.last_name,  cp.last_name,  sp.last_name,  stp.last_name)  as last_name
        FROM users u
        LEFT JOIN admin_profiles       ap  ON u.uuid = ap.user_uuid
        LEFT JOIN coordinator_profiles cp  ON u.uuid = cp.user_uuid
        LEFT JOIN supervisor_profiles  sp  ON u.uuid = sp.user_uuid
        LEFT JOIN student_profiles     stp ON u.uuid = stp.user_uuid
        WHERE u.role != 'admin'
          AND u.uuid != ?
    ";
}
 
if ($search) {
    if ($isCoordinator) {
        $query .= " AND (u.email LIKE ? OR stp.first_name LIKE ? OR stp.last_name LIKE ?)";
    } else {
        $query .= " AND (u.email LIKE ? 
                   OR ap.first_name LIKE ? OR ap.last_name LIKE ?
                   OR cp.first_name LIKE ? OR cp.last_name LIKE ?
                   OR sp.first_name LIKE ? OR sp.last_name LIKE ?
                   OR stp.first_name LIKE ? OR stp.last_name LIKE ?)";
    }
} else {
    $query .= " AND (u.lockout_until IS NOT NULL OR u.login_attempts > 0)";
    $query .= " ORDER BY u.lockout_until DESC, u.login_attempts DESC";
}

$query .= " LIMIT 20";

$stmt = $conn->prepare($query);

if ($isCoordinator && $search) {
    $stmt->bind_param('ssss', $coordProfileUuid, $searchTerm, $searchTerm, $searchTerm);
} elseif ($isCoordinator && !$search) {
    $stmt->bind_param('s', $coordProfileUuid);
} elseif (!$isCoordinator && $search) {
    $stmt->bind_param('ssssssssss', $_SESSION['user_uuid'], $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
} else {
    $stmt->bind_param('s', $_SESSION['user_uuid']);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $isLocked = false;
    $remainingSeconds = 0;
    $formattedLockout = "Not Locked";
    
    if ($row['lockout_until']) {
        $lockoutTime = strtotime($row['lockout_until']);
        $currentTime = time();
        if ($currentTime < $lockoutTime) {
            $isLocked = true;
            $remainingSeconds = $lockoutTime - $currentTime;
        }
        $formattedLockout = date('M d, Y - h:i A', $lockoutTime);
    }
    
    $users[] = [
        'uuid'            => $row['uuid'],
        'email'           => $row['email'],
        'role'            => strtoupper($row['role']),
        'name'            => trim($row['first_name'] . ' ' . $row['last_name']) ?: $row['email'],
        'initials'        => strtoupper(
                                substr($row['first_name'] ?? $row['email'], 0, 1) .
                                substr($row['last_name'] ?? ($row['first_name'] ? "" : substr($row['email'], 1, 1)), 0, 1)
                             ),
        'is_active'       => (int)$row['is_active'],
        'login_attempts'  => (int)$row['login_attempts'],
        'is_locked'       => $isLocked,
        'lockout_until'   => $formattedLockout,
        'lockout_until_raw' => $row['lockout_until'],
        'manual_lockout'  => (int)$row['manual_lockout'],
        'remaining_seconds' => $remainingSeconds
    ];
}

echo json_encode(['status' => 'success', 'data' => $users]);
?>
