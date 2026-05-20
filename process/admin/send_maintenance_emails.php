<?php

if (php_sapi_name() !== 'cli') {
    die("Forbidden: CLI only.\n");
}

$feature = $argv[1] ?? '';
$status = $argv[2] ?? '';
$reason = $argv[3] ?? '';
$actorUuid = $argv[4] ?? '';

if (empty($feature) || empty($status) || empty($actorUuid)) {
    die("Error: Missing required arguments.\n");
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/settings_functions.php';

echo "Sending maintenance status email notification for feature: {$feature} (status: {$status})...\n";

notifyUsersOfMaintenance_Sync($conn, $feature, $status, $reason, $actorUuid);

echo "Email notification task completed successfully!\n";
?>
