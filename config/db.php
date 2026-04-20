<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage?error=403");
    exit;
}

$host     = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'ojt_system';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_ACTIVE) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $isProcessRequest = str_contains($scriptName, '/process/');

    if ($isProcessRequest) {
        $isExemptProcess = false;
        $processExemptions = [
            '/process/auth/login.php',
            '/process/auth/logout.php',
            '/process/auth/changepass.php',
            '/process/auth/send_reset_link.php',
            '/process/auth/validate_reset_token.php',
            '/process/auth/reset_password.php',
            '/process/profile/get_profile.php',
            '/process/profile/save_profile.php',
        ];

        foreach ($processExemptions as $exemptPath) {
            if (str_ends_with($scriptName, $exemptPath)) {
                $isExemptProcess = true;
                break;
            }
        }

        if (
            !$isExemptProcess
            && !empty($_SESSION['user_uuid'])
            && !empty($_SESSION['user_role'])
        ) {
            $role = (string) $_SESSION['user_role'];
            $userUuid = (string) $_SESSION['user_uuid'];

            $profileConfig = match ($role) {
                'admin' => ['table' => 'admin_profiles', 'redirect' => '../../Src/Pages/Admin/Admin_Profile'],
                'coordinator' => ['table' => 'coordinator_profiles', 'redirect' => '../../Src/Pages/Coordinator/Coordinator_Profile'],
                'student' => ['table' => 'student_profiles', 'redirect' => '../../Src/Pages/Students/Students_Profile'],
                'supervisor' => ['table' => 'supervisor_profiles', 'redirect' => '../../Src/Pages/Supervisor/Supervisor_Profile'],
                default => null,
            };

            if ($profileConfig !== null) {
                $table = $profileConfig['table'];
                $stmt = $conn->prepare("SELECT COALESCE(`isProfileDone`, 0) AS is_done FROM {$table} WHERE user_uuid = ? LIMIT 1");
                $stmt->bind_param('s', $userUuid);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $isProfileDone = (int)($row['is_done'] ?? 0) === 1;
                $_SESSION['is_profile_done'] = $isProfileDone ? 1 : 0;

                if (!$isProfileDone) {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'code' => 'PROFILE_INCOMPLETE',
                        'message' => 'Complete your profile setup first.',
                        'redirect_url' => $profileConfig['redirect'],
                    ], JSON_UNESCAPED_SLASHES);
                    exit;
                }
            }
        }
    }
}
