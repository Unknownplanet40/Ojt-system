<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function ensureAdminSettingsTable(mysqli $conn): bool
{
    $sql = "
        CREATE TABLE IF NOT EXISTS user_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_uuid CHAR(36) DEFAULT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value LONGTEXT NOT NULL,
            updated_by VARCHAR(36) NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_setting (user_uuid, setting_key),
            CONSTRAINT fk_user_settings_updated_by
                FOREIGN KEY (updated_by) REFERENCES users(uuid)
                ON DELETE SET NULL,
            CONSTRAINT fk_user_settings_user
                FOREIGN KEY (user_uuid) REFERENCES users(uuid)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    return (bool)$conn->query($sql);
}

function normalizeThemeSetting(string $theme): string
{
    $theme = strtolower(trim($theme));
    return in_array($theme, ['light', 'dark', 'auto'], true) ? $theme : 'dark';
}

function getAdminSetting(mysqli $conn, string $key, string $defaultValue = '', ?string $userUuid = null): string
{
    if (!ensureAdminSettingsTable($conn)) {
        return $defaultValue;
    }

    if ($userUuid !== null) {
        $stmt = $conn->prepare("SELECT setting_value FROM user_settings WHERE user_uuid = ? AND setting_key = ? LIMIT 1");
        if (!$stmt) return $defaultValue;
        $stmt->bind_param('ss', $userUuid, $key);
    } else {
        $stmt = $conn->prepare("SELECT setting_value FROM user_settings WHERE user_uuid IS NULL AND setting_key = ? LIMIT 1");
        if (!$stmt) return $defaultValue;
        $stmt->bind_param('s', $key);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (string)($row['setting_value'] ?? $defaultValue);
}

function saveAdminSetting(mysqli $conn, string $key, string $value, string $actorUuid, ?string $userUuid = null): array
{
    if (!ensureAdminSettingsTable($conn)) {
        return [
            'success' => false,
            'message' => 'Unable to prepare settings storage.',
        ];
    }

    $stmt = $conn->prepare("
        INSERT INTO user_settings (user_uuid, setting_key, setting_value, updated_by)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_by = VALUES(updated_by),
            updated_at = CURRENT_TIMESTAMP
    ");

    if (!$stmt) {
        return [
            'success' => false,
            'message' => 'Unable to prepare setting update.',
        ];
    }

    $stmt->bind_param('ssss', $userUuid, $key, $value, $actorUuid);
    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        return [
            'success' => false,
            'message' => 'Unable to save setting.',
        ];
    }

    return [
        'success' => true,
        'message' => 'Setting saved.',
    ];
}

function getUserTheme(mysqli $conn, string $userUuid): string
{
    return normalizeThemeSetting(getAdminSetting($conn, 'theme', 'dark', $userUuid));
}

function saveUserTheme(mysqli $conn, string $theme, string $userUuid): array
{
    $theme = normalizeThemeSetting($theme);
    $result = saveAdminSetting($conn, 'theme', $theme, $userUuid, $userUuid);
    $result['theme'] = $theme;
    return $result;
}

function getUserSettings(mysqli $conn): array
{
    return [
        'theme' => normalizeThemeSetting(getAdminSetting($conn, 'theme', 'dark')),
    ];
}

function saveThemeSetting(mysqli $conn, string $theme, string $adminUuid): array
{
    $theme = normalizeThemeSetting($theme);
    $result = saveAdminSetting($conn, 'theme', $theme, $adminUuid);
    $result['theme'] = $theme;
    return $result;
}

function ensureEmailConfigTable(mysqli $conn): bool
{
    $sql = "
        CREATE TABLE IF NOT EXISTS email_config (
            id INT UNSIGNED PRIMARY KEY DEFAULT 1,
            smtp_host VARCHAR(255) DEFAULT '',
            smtp_port INT DEFAULT 587,
            smtp_user VARCHAR(255) DEFAULT '',
            smtp_pass VARCHAR(255) DEFAULT '',
            smtp_crypto VARCHAR(20) DEFAULT 'tls',
            from_email VARCHAR(255) DEFAULT '',
            from_name VARCHAR(255) DEFAULT '',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    return (bool)$conn->query($sql);
}

function getEmailSettings(mysqli $conn): array
{
    if (!ensureEmailConfigTable($conn)) {
        return [
            'host' => '',
            'port' => '587',
            'user' => '',
            'pass' => '',
            'crypto' => 'tls',
            'from_email' => '',
            'from_name' => '',
        ];
    }

    $sql = "SELECT * FROM email_config LIMIT 1";
    $result = $conn->query($sql);
    $data = $result ? $result->fetch_assoc() : null;

    return [
        'host' => (string)($data['smtp_host'] ?? ''),
        'port' => (string)($data['smtp_port'] ?? '587'),
        'user' => (string)($data['smtp_user'] ?? ''),
        'pass' => (string)($data['smtp_pass'] ?? ''),
        'crypto' => (string)($data['smtp_crypto'] ?? 'tls'),
        'from_email' => (string)($data['from_email'] ?? ''),
        'from_name' => (string)($data['from_name'] ?? ''),
    ];
}


function saveEmailSettings(mysqli $conn, array $data, string $adminUuid): array
{
    if (!ensureEmailConfigTable($conn)) {
        return ['success' => false, 'message' => 'Unable to prepare email settings storage.'];
    }

    $host = $data['host'] ?? '';
    $port = (int)($data['port'] ?? 587);
    $user = $data['user'] ?? '';
    $pass = $data['pass'] ?? '';
    $crypto = $data['crypto'] ?? 'tls';
    $fromEmail = $data['from_email'] ?? '';
    $fromName = $data['from_name'] ?? '';

    
    $stmt = $conn->prepare("
        INSERT INTO email_config (id, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_crypto, from_email, from_name)
        VALUES (1, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            smtp_host = VALUES(smtp_host),
            smtp_port = VALUES(smtp_port),
            smtp_user = VALUES(smtp_user),
            smtp_pass = VALUES(smtp_pass),
            smtp_crypto = VALUES(smtp_crypto),
            from_email = VALUES(from_email),
            from_name = VALUES(from_name),
            updated_at = CURRENT_TIMESTAMP
    ");

    if (!$stmt) {
        return ['success' => false, 'message' => 'Failed to prepare email config update.'];
    }

    $stmt->bind_param('sisssss', $host, $port, $user, $pass, $crypto, $fromEmail, $fromName);
    $success = $stmt->execute();
    $stmt->close();

    return $success ? ['success' => true, 'message' => 'Email settings saved.'] : ['success' => false, 'message' => 'Failed to execute email config update.'];
}

function getSystemConfig(mysqli $conn): array
{
    $sql = "SELECT * FROM system_config WHERE id = 1 LIMIT 1";
    $result = $conn->query($sql);
    $data = $result ? $result->fetch_assoc() : null;

    if (!$data) {
        return [
            'long_title' => 'OJT Management System',
            'short_title' => 'OJT-SMS',
            'system_description' => '',
            'author' => '',
            'school_name' => 'Cavite State University - Imus Campus',
            'school_motto' => '',
            'school_address' => '',
            'school_website' => '',
            'school_email' => '',
            'school_phone' => '',
            'logo_1' => null,
            'logo_2' => null,
            'footer_note' => '',
            'verification_note' => '',
            'page_link' => '',
        ];
    }

    return [
        'long_title' => (string)($data['long_title'] ?? ''),
        'short_title' => (string)($data['short_title'] ?? ''),
        'system_description' => (string)($data['system_description'] ?? ''),
        'author' => (string)($data['author'] ?? ''),
        'school_name' => (string)($data['school_name'] ?? ''),
        'school_motto' => (string)($data['school_motto'] ?? ''),
        'school_address' => (string)($data['school_address'] ?? ''),
        'school_website' => (string)($data['school_website'] ?? ''),
        'school_email' => (string)($data['school_email'] ?? ''),
        'school_phone' => (string)($data['school_phone'] ?? ''),
        'logo_1' => $data['logo_1'] ? '../../../Assets/Images/systemImages/' . $data['logo_1'] : null,
        'logo_2' => $data['logo_2'] ? '../../../Assets/Images/systemImages/' . $data['logo_2'] : null,
        'footer_note' => (string)($data['footer_note'] ?? ''),
        'verification_note' => (string)($data['verification_note'] ?? ''),
        'page_link' => (string)($data['page_link'] ?? ''),
    ];
}


function saveSystemConfig(mysqli $conn, array $data): array
{
    
    $conn->query("INSERT IGNORE INTO system_config (id) VALUES (1)");

    $stmt = $conn->prepare("
        UPDATE system_config 
        SET long_title = ?, short_title = ?, system_description = ?, author = ?,
            school_name = ?, school_motto = ?, school_address = ?, 
            school_website = ?, school_email = ?, school_phone = ?,
            footer_note = ?, verification_note = ?, page_link = ?
        WHERE id = 1
    ");

    if (!$stmt) {
        return ['success' => false, 'message' => 'Failed to prepare system config update.'];
    }

    $stmt->bind_param('sssssssssssss', 
        $data['long_title'], $data['short_title'], $data['system_description'], $data['author'],
        $data['school_name'], $data['school_motto'], $data['school_address'],
        $data['school_website'], $data['school_email'], $data['school_phone'],
        $data['footer_note'], $data['verification_note'], $data['page_link']
    );
    
    $success = $stmt->execute();
    $stmt->close();

    return $success ? ['success' => true, 'message' => 'Institutional profile saved.'] : ['success' => false, 'message' => 'Failed to update system config.'];
}

function updateSystemLogo(mysqli $conn, string $field, string $fileName): bool
{
    if (!in_array($field, ['logo_1', 'logo_2'])) return false;
    $stmt = $conn->prepare("UPDATE system_config SET $field = ? WHERE id = 1");
    $stmt->bind_param('s', $fileName);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
