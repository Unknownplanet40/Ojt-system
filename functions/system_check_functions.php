<?php



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


function checkDatabaseConnection($conn) {
    if (!$conn) {
        return [
            'name' => 'Database',
            'value' => 'MariaDB/MySQL',
            'status' => 'error',
            'message' => 'Not connected'
        ];
    }
    
    
    $result = $conn->query("SELECT VERSION() as version");
    $version = $result ? $result->fetch_assoc()['version'] : 'Unknown';
    
    
    $dbName = $conn->query("SELECT DATABASE() as db")->fetch_assoc()['db'] ?? 'Unknown';
    
    return [
        'name' => 'Database',
        'value' => $version,
        'status' => 'ok',
        'message' => "Connected to: $dbName",
        'database' => $dbName
    ];
}


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


function formatBytes($bytes, $precision = 1) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}


function isLibraryAvailable($library) {
    $basePath = __DIR__ . '/../libs/composer/vendor';
    
    switch (strtolower($library)) {
        case 'mpdf':
            return is_dir($basePath . '/mpdf/mpdf');
        case 'phpspreadsheet':
            return is_dir($basePath . '/phpoffice/phpspreadsheet');
        case 'phpmailer':
            return is_dir($basePath . '/phpmailer/phpmailer');
        case 'ratchet':
            return is_dir($basePath . '/cboden/ratchet') || is_dir($basePath . '/ratchet/pawl');
        default:
            return false;
    }
}


function checkServerSoftware() {
    $software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    
    return [
        'name' => 'Server Software',
        'value' => $software,
        'status' => 'ok',
        'message' => 'Apache/Server'
    ];
}


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


function checkServerUptime() {
    if (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
        
        $lastboot = shell_exec('wmic os get lastbootuptime /value 2>nul');
        $uptime = 'Windows System';
    } else {
        
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
