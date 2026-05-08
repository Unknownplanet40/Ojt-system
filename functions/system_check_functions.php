<?php
/**
 * Server Environment & System Check Functions
 * 
 * Core business logic for checking:
 * - Server environment (PHP version, extensions, modules)
 * - Dependencies (libraries, tools)
 * - Database connection
 * - Storage and directories
 */

/**
 * Get PHP version
 * @return array Status info with version and status
 */
function checkPHPVersion() {
    $version = phpversion();
    $minVersion = '7.4.0';
    $recommendedVersion = '8.0.0';
    
    $isOk = version_compare($version, $minVersion, '>=');
    $isOptimal = version_compare($version, $recommendedVersion, '>=');
    
    return [
        'name' => 'PHP Version',
        'value' => $version,
        'status' => $isOk ? 'ok' : 'error',
        'message' => $isOk ? ($isOptimal ? 'Optimal' : 'Compatible') : 'Too old'
    ];
}

/**
 * Check if mod_rewrite is enabled
 * @return array Status info
 */
function checkModRewrite() {
    $enabled = function_exists('apache_get_modules') 
        ? in_array('mod_rewrite', apache_get_modules())
        : getenv('HTTP_MOD_REWRITE') === 'On';
    
    return [
        'name' => 'mod_rewrite',
        'value' => 'Apache Module',
        'status' => $enabled ? 'ok' : 'error',
        'message' => $enabled ? 'Enabled' : 'Disabled'
    ];
}

/**
 * Check required PHP extensions
 * @return array Status info
 */
function checkPHPExtensions() {
    $required = [
        'pdo' => 'PDO Database',
        'pdo_mysql' => 'MySQL Driver',
        'json' => 'JSON',
        'curl' => 'cURL',
        'mbstring' => 'Multibyte String',
        'gd' => 'Image Processing',
        'zip' => 'ZIP Archive'
    ];
    
    $missing = [];
    $installed = [];
    
    foreach ($required as $ext => $name) {
        if (!extension_loaded($ext)) {
            $missing[] = $name;
        } else {
            $installed[] = $name;
        }
    }
    
    return [
        'name' => 'PHP Extensions',
        'value' => count($installed) . '/' . count($required),
        'status' => count($missing) === 0 ? 'ok' : (count($installed) >= count($required) - 1 ? 'warning' : 'error'),
        'message' => count($missing) === 0 ? 'All installed' : 'Missing: ' . implode(', ', $missing),
        'installed' => $installed,
        'missing' => $missing
    ];
}

/**
 * Check file upload functionality
 * @return array Status info
 */
function checkFileUpload() {
    $uploadMaxSize = ini_get('upload_max_filesize');
    $postMaxSize = ini_get('post_max_size');
    $fileUploads = ini_get('file_uploads');
    
    $isEnabled = $fileUploads == '1' || strtolower($fileUploads) === 'on';
    
    return [
        'name' => 'File Upload',
        'value' => $uploadMaxSize,
        'status' => $isEnabled ? 'ok' : 'error',
        'message' => $isEnabled ? "Max: $uploadMaxSize / POST: $postMaxSize" : 'Disabled'
    ];
}

/**
 * Check database connection
 * @param $conn Database connection resource
 * @return array Status info
 */
function checkDatabaseConnection($conn) {
    if (!$conn) {
        return [
            'name' => 'Database',
            'value' => 'MariaDB/MySQL',
            'status' => 'error',
            'message' => 'Not connected'
        ];
    }
    
    // Get database version
    $result = $conn->query("SELECT VERSION() as version");
    $version = $result ? $result->fetch_assoc()['version'] : 'Unknown';
    
    // Get database name
    $dbName = $conn->query("SELECT DATABASE() as db")->fetch_assoc()['db'] ?? 'Unknown';
    
    return [
        'name' => 'Database',
        'value' => $version,
        'status' => 'ok',
        'message' => "Connected to: $dbName",
        'database' => $dbName
    ];
}

/**
 * Check storage directories
 * @return array Array of directory status info
 */
function checkStorageDirectories() {
    $directories = [
        [
            'path' => '../uploads',
            'label' => 'Uploads Directory',
            'displayPath' => 'uploads/'
        ]
    ];
    
    $results = [];
    
    foreach ($directories as $dir) {
        $fullPath = __DIR__ . '/' . $dir['path'];
        $exists = is_dir($fullPath);
        $writable = $exists ? is_writable($fullPath) : false;
        
        // Calculate directory size
        $size = 0;
        if ($exists) {
            $size = getDirectorySize($fullPath);
        }
        
        $results[] = [
            'name' => $dir['label'],
            'path' => $dir['displayPath'],
            'exists' => $exists,
            'writable' => $writable,
            'size' => $size,
            'sizeFormatted' => formatBytes($size),
            'status' => !$exists ? 'error' : (!$writable ? 'warning' : 'ok'),
            'message' => !$exists ? 'Not found' : (!$writable ? 'Read-only' : 'Writable')
        ];
    }
    
    return $results;
}

/**
 * Get total size of a directory recursively
 * @param string $path Directory path
 * @return int Size in bytes
 */
function getDirectorySize($path) {
    $size = 0;
    
    if (is_file($path)) {
        return filesize($path);
    }
    
    if (!is_dir($path) || !is_readable($path)) {
        return 0;
    }
    
    if ($handle = opendir($path)) {
        while ($file = readdir($handle)) {
            if ($file !== '.' && $file !== '..') {
                $file = $path . '/' . $file;
                if (is_file($file)) {
                    $size += filesize($file);
                } elseif (is_dir($file)) {
                    $size += getDirectorySize($file);
                }
            }
        }
        closedir($handle);
    }
    
    return $size;
}

/**
 * Format bytes to human-readable format
 * @param int $bytes Number of bytes
 * @param int $precision Decimal places
 * @return string Formatted size
 */
function formatBytes($bytes, $precision = 1) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Check if a library is available by checking directory existence
 * @param string $library Library name (e.g., 'mpdf', 'phpspreadsheet')
 * @return bool True if library directory exists
 */
function isLibraryAvailable($library) {
    $basePath = __DIR__ . '/../libs/composer/vendor';
    
    switch (strtolower($library)) {
        case 'mpdf':
            return is_dir($basePath . '/mpdf/mpdf');
        case 'phpspreadsheet':
            return is_dir($basePath . '/phpoffice/phpspreadsheet');
        case 'phpmailer':
            return is_dir($basePath . '/phpmailer/phpmailer');
        default:
            return false;
    }
}

/** * Get server software info
 * @return array Status info
 */
function checkServerSoftware() {
    $software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    
    return [
        'name' => 'Server Software',
        'value' => $software,
        'status' => 'ok',
        'message' => 'Apache/Server'
    ];
}

/**
 * Get OS/Platform information
 * @return array Status info
 */
function checkOperatingSystem() {
    $os = php_uname();
    $osType = strtoupper(substr(php_uname('s'), 0, 3));
    $isWindows = $osType === 'WIN';
    
    return [
        'name' => 'Operating System',
        'value' => $isWindows ? 'Windows' : 'Linux/Unix',
        'status' => 'ok',
        'message' => $os
    ];
}

/**
 * Get server memory usage
 * @return array Status info
 */
function checkMemoryUsage() {
    $memoryUsed = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);
    $memoryLimit = ini_get('memory_limit');
    
    return [
        'name' => 'Memory Usage',
        'value' => formatBytes($memoryUsed),
        'status' => 'ok',
        'message' => "Peak: " . formatBytes($memoryPeak) . " / Limit: $memoryLimit"
    ];
}

/**
 * Get disk space information
 * @return array Status info
 */
function checkDiskSpace() {
    $diskTotal = disk_total_space('/');
    $diskFree = disk_free_space('/');
    $diskUsed = $diskTotal - $diskFree;
    $usagePercent = round(($diskUsed / $diskTotal) * 100, 1);
    
    $status = $usagePercent > 90 ? 'error' : ($usagePercent > 75 ? 'warning' : 'ok');
    
    return [
        'name' => 'Disk Space',
        'value' => formatBytes($diskFree),
        'status' => $status,
        'message' => formatBytes($diskUsed) . ' / ' . formatBytes($diskTotal) . ' (' . $usagePercent . '% used)'
    ];
}

/**
 * Get server uptime
 * @return array Status info
 */
function checkServerUptime() {
    if (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
        // Windows uptime
        $lastboot = shell_exec('wmic os get lastbootuptime /value 2>nul');
        $uptime = 'Windows System';
    } else {
        // Linux uptime
        $uptime_file = @file_get_contents('/proc/uptime');
        if ($uptime_file !== false) {
            $uptime_seconds = intval(explode(' ', $uptime_file)[0]);
            $days = floor($uptime_seconds / 86400);
            $hours = floor(($uptime_seconds % 86400) / 3600);
            $uptime = "$days days, $hours hours";
        } else {
            $uptime = 'Unable to retrieve';
        }
    }
    
    return [
        'name' => 'Server Uptime',
        'value' => $uptime,
        'status' => 'ok',
        'message' => 'System running'
    ];
}

/**
 * Check request handler/SAPI
 * @return array Status info
 */
function checkRequestHandler() {
    $sapi = php_sapi_name();
    $handler = 'Unknown';
    
    if (strpos($sapi, 'fpm') !== false) {
        $handler = 'PHP-FPM';
    } elseif (strpos($sapi, 'apache') !== false) {
        $handler = 'Apache Module (mod_php)';
    } elseif (strpos($sapi, 'cgi') !== false) {
        $handler = 'CGI/FastCGI';
    } elseif ($sapi === 'cli') {
        $handler = 'Command Line Interface';
    }
    
    return [
        'name' => 'Request Handler',
        'value' => $handler,
        'status' => 'ok',
        'message' => "SAPI: $sapi"
    ];
}

/** * Get all system information in one call
 * @param $conn Database connection resource
 * @return array Comprehensive system info
 */
function getAllSystemInfo($conn) {
    return [
        'php' => checkPHPVersion(),
        'database' => checkDatabaseConnection($conn),
        'modRewrite' => checkModRewrite(),
        'serverSoftware' => checkServerSoftware(),
        'operatingSystem' => checkOperatingSystem(),
        'memoryUsage' => checkMemoryUsage(),
        'diskSpace' => checkDiskSpace(),
        'serverUptime' => checkServerUptime(),
        'requestHandler' => checkRequestHandler(),
        'extensions' => checkPHPExtensions(),
        'fileUpload' => checkFileUpload(),
        'storage' => checkStorageDirectories(),
        'libraries' => [
            [
                'name' => 'mPDF',
                'label' => 'PDF Generation',
                'installed' => isLibraryAvailable('mpdf'),
                'status' => isLibraryAvailable('mpdf') ? 'ok' : 'warning'
            ],
            [
                'name' => 'PhpSpreadsheet',
                'label' => 'Excel/XLSX Export',
                'installed' => isLibraryAvailable('phpspreadsheet'),
                'status' => isLibraryAvailable('phpspreadsheet') ? 'ok' : 'warning'
            ],
            [
                'name' => 'PHPMailer',
                'label' => 'Email Library',
                'installed' => isLibraryAvailable('phpmailer'),
                'status' => isLibraryAvailable('phpmailer') ? 'ok' : 'warning'
            ]
        ]
    ];
}

?>
