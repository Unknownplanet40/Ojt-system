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

function generateUuid(): string
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function resolveUserUuidOrNull($conn, $uuid): ?string
{
    if (empty($uuid)) {
        return null;
    }

    $candidate = trim((string) $uuid);
    $stmt = $conn->prepare("SELECT uuid FROM users WHERE uuid = ? LIMIT 1");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $candidate);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    return $exists ? $candidate : null;
}

function logActivity(
    $conn,
    string $eventType,
    string $description,
    string $module     = null,
    string $actorUuid  = null,
    string $targetUuid = null,
    array  $meta       = []
): void {
    $metaJson = !empty($meta) ? json_encode($meta) : null;

    $actorUuid = resolveUserUuidOrNull($conn, $actorUuid);
    $targetUuid = resolveUserUuidOrNull($conn, $targetUuid);

    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_log
              (actor_uuid, target_uuid, event_type, description, module, meta)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return;
        }

        $stmt->bind_param(
            'ssssss',
            $actorUuid,
            $targetUuid,
            $eventType,
            $description,
            $module,
            $metaJson
        );
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('logActivity failed: ' . $e->getMessage());
    }
}

function loginAudit($userUuid, $success, $reason = '')
{
    global $conn;
    $stmt = $conn->prepare("INSERT INTO login_audit_log (user_uuid, ip_address, user_agent, success, fail_reason) VALUES (?, ?, ?, ?, ?)");
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $stmt->bind_param("sssis", $userUuid, $ipAddress, $userAgent, $success, $reason);
    $stmt->execute();
}


function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);

    return match(true) {
        $diff < 60     => 'Just now',
        $diff < 3600   => floor($diff / 60)   . ' min ago',
        $diff < 86400  => floor($diff / 3600)  . ' hr ago',
        $diff < 604800 => floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago',
        default        => date('M j, Y', strtotime($datetime)),
    };
}

function isStrongPassword(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
}

if (!function_exists('ordinal')) {
    function ordinal(int $number): string
    {
        $abs = abs($number);
        $lastTwo = $abs % 100;

        if ($lastTwo >= 11 && $lastTwo <= 13) {
            return $number . 'th';
        }

        return match ($abs % 10) {
            1 => $number . 'st',
            2 => $number . 'nd',
            3 => $number . 'rd',
            default => $number . 'th',
        };
    }
}