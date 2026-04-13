<?php

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

function auditLog($eventType, $description, $module = null, $actorUuid = null, $targetUuid = null, $meta = null)
{
    global $conn;
    $metaJson = $meta ? json_encode($meta) : null;
    $stmt = $conn->prepare("INSERT INTO activity_log (actor_uuid, target_uuid, event_type, description, module, meta) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $actorUuid, $targetUuid, $eventType, $description, $module, $metaJson);
    $stmt->execute();
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