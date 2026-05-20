<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/company_functions.php';

function getCompanyHRAnalytics($conn, string $companyUuid, string $batchUuid = null): array
{
    if (empty($batchUuid)) {
        $result = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
        $row = $result->fetch_assoc();
        $batchUuid = $row['uuid'] ?? null;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM supervisor_profiles WHERE company_uuid = ? AND is_active = 1");
    $stmt->bind_param('s', $companyUuid);
    $stmt->execute();
    $totalSupervisors = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM student_profiles WHERE company_uuid = ? AND batch_uuid = ?");
    $stmt->bind_param('ss', $companyUuid, $batchUuid);
    $stmt->execute();
    $totalInterns = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare("SELECT total_slots FROM company_slots WHERE company_uuid = ? AND batch_uuid = ? LIMIT 1");
    $stmt->bind_param('ss', $companyUuid, $batchUuid);
    $stmt->execute();
    $slotsRow = $stmt->get_result()->fetch_assoc();
    $totalSlots = (int) ($slotsRow['total_slots'] ?? 0);
    $stmt->close();

    return [
        'total_supervisors' => $totalSupervisors,
        'total_interns'     => $totalInterns,
        'total_slots'       => $totalSlots,
        'remaining_slots'   => max(0, $totalSlots - $totalInterns)
    ];
}

function promoteToHRAdmin($conn, string $supervisorUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("UPDATE supervisor_profiles SET is_hr_admin = 1 WHERE uuid = ?");
    $stmt->bind_param('s', $supervisorUuid);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        logActivity(
            conn: $conn,
            eventType: 'hr_admin_promoted',
            description: "Promoted supervisor to HR Admin",
            module: 'companies',
            actorUuid: $actorUuid,
            targetUuid: $supervisorUuid
        );
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Failed to promote supervisor.'];
}

function revokeHRAdmin($conn, string $supervisorUuid, string $actorUuid): array
{
    $stmt = $conn->prepare("UPDATE supervisor_profiles SET is_hr_admin = 0 WHERE uuid = ?");
    $stmt->bind_param('s', $supervisorUuid);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        logActivity(
            conn: $conn,
            eventType: 'hr_admin_revoked',
            description: "Revoked HR Admin role from supervisor",
            module: 'companies',
            actorUuid: $actorUuid,
            targetUuid: $supervisorUuid
        );
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Failed to revoke HR Admin role.'];
}
