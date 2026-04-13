<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

function response(array $data): void
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
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

function logActivity(
    $conn,
    string  $eventType,
    string  $description,
    string  $module      = null,
    string  $actorUuid   = null,
    string  $targetUuid  = null,
    array   $meta        = []
): void {
    $metaJson = !empty($meta) ? json_encode($meta) : null;
    $stmt = $conn->prepare("
        INSERT INTO activity_log
          (actor_uuid, target_uuid, event_type, description, module, meta)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssss', $actorUuid, $targetUuid, $eventType, $description, $module, $metaJson);
    $stmt->execute();
    $stmt->close();
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

function ordinal(int $n): string
{
    $suffixes = ['th', 'st', 'nd', 'rd'];
    $v = $n % 100;
    return $n . ($suffixes[($v - 20) % 10] ?? $suffixes[$v] ?? $suffixes[0]);
}