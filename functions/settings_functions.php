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

/**
 * Get a setting value. If $userUuid is provided, gets a per-user setting.
 * If null, gets a global setting.
 */
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

/**
 * Save a setting value. If $userUuid is provided, saves a per-user setting.
 * If null, saves a global setting.
 */
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

/**
 * Get per-user theme setting.
 */
function getUserTheme(mysqli $conn, string $userUuid): string
{
    return normalizeThemeSetting(getAdminSetting($conn, 'theme', 'dark', $userUuid));
}

/**
 * Save per-user theme setting.
 */
function saveUserTheme(mysqli $conn, string $theme, string $userUuid): array
{
    $theme = normalizeThemeSetting($theme);
    $result = saveAdminSetting($conn, 'theme', $theme, $userUuid, $userUuid);
    $result['theme'] = $theme;
    return $result;
}

/**
 * Get global system settings (backward-compatible).
 */
function getUserSettings(mysqli $conn): array
{
    return [
        'theme' => normalizeThemeSetting(getAdminSetting($conn, 'theme', 'dark')),
    ];
}

/**
 * Save global theme setting (backward-compatible).
 */
function saveThemeSetting(mysqli $conn, string $theme, string $adminUuid): array
{
    $theme = normalizeThemeSetting($theme);
    $result = saveAdminSetting($conn, 'theme', $theme, $adminUuid);
    $result['theme'] = $theme;
    return $result;
}
