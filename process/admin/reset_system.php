<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db.php';

try {
    // 1. Delete uploads and profiles directories contents
    function deleteFolderContents($dir) {
        if (!is_dir($dir)) return;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->getFilename() === '.gitkeep') {
                continue;
            }
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }
    }

    $uploadsDir = __DIR__ . '/../../uploads';
    $profilesDir = __DIR__ . '/../../Assets/Images/profiles';

    deleteFolderContents($uploadsDir);
    deleteFolderContents($profilesDir);

    // 2. Execute init.sql schema
    $schemaFile = __DIR__ . '/../../config/init.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("init.sql schema file not found.");
    }

    $sql = file_get_contents($schemaFile);

    // Temporarily turn off foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
    } else {
        throw new Exception("Schema execution failed: " . $conn->error);
    }

    $conn->query("SET FOREIGN_KEY_CHECKS=1");

    // 3. Clear session completely
    session_unset();
    session_destroy();

    // Determine the base path redirect URL
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $base_path = preg_replace('/(\/Src\/Pages|\/process\/.*)$/', '', $base_path);

    echo json_encode([
        'status' => 'success',
        'message' => 'System has been reset completely. All data and user accounts are deleted. Redirecting you to the system setup wizard.',
        'redirect_url' => "$base_path/Src/Pages/Setup"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'System reset failed: ' . $e->getMessage()
    ]);
}
?>
