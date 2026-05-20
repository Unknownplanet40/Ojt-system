<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}


function response(array $data, int $statusCode = 200): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Content-Security-Policy: default-src \'none\'; frame-ancestors \'none\'; base-uri \'none\'; form-action \'none\';');

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        header('X-Powered-By:');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
    }

    try {
        echo json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    } catch (JsonException $e) {
        if (!headers_sent()) {
            http_response_code(500);
        }

        echo '{"status":"error","message":"Failed to encode response as JSON."}';
    }

    exit;
}

function generateUuid(): string
{
    $bytes = random_bytes(16);

    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function isValidUuid(string $uuid): bool
{
    return (bool) preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        $uuid
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

function getAppBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return rtrim($scheme . '://' . $host . '/Ojt-system', '/');
}

function buildAppUrl(string $path): string
{
    return rtrim(getAppBaseUrl(), '/') . '/' . ltrim($path, '/');
}

function buildCertificateVerificationUrl(string $token): string
{
    return buildAppUrl('/Src/Pages/Public/VerifyCertificate.php?token=' . rawurlencode($token));
}

class Logger
{
    private $db = null;

    public function __construct()
    {
        try {
            if (class_exists('Database')) {
                $this->db = Database::getInstance();
            }
        } catch (Throwable $e) {
            $this->db = null;
        }
    }

    public function log($level, $message, $module = null, $meta = null)
    {
        $metaJson = null;
        if ($meta !== null) {
            $metaJson = is_string($meta) ? $meta : json_encode($meta);
        }

        $formatted = sprintf(
            '[%s] [%s] %s%s%s',
            date('Y-m-d H:i:s'),
            strtoupper((string) $level),
            (string) $message,
            $module ? ' | Module: ' . $module : '',
            $metaJson ? ' | Meta: ' . $metaJson : ''
        );

        error_log($formatted);

        if ($this->db) {
            try {
                $actorUuid = $_SESSION['user_uuid'] ?? null;
                $query = 'INSERT INTO activity_log (actor_uuid, event_type, description, module, meta) VALUES (?, ?, ?, ?, ?)';
                $stmt = $this->db->prepare($query);
                $stmt->execute([$actorUuid, $level, $message, $module, $metaJson]);
            } catch (Throwable $e) {
                try {
                    $actorUuid = $_SESSION['user_uuid'] ?? null;
                    $query = 'INSERT INTO activity_log (user_uuid, event_type, description) VALUES (?, ?, ?)';
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$actorUuid, $level, $message]);
                } catch (Throwable $fallbackEx) {
                    error_log('Logger DB insertion fallback failed: ' . $fallbackEx->getMessage());
                }
            }
        }
    }

    public function error($message, $module = null, $meta = null)
    {
        $this->log('error', $message, $module, $meta);
    }

    public function info($message, $module = null, $meta = null)
    {
        $this->log('info', $message, $module, $meta);
    }

    public function warn($message, $module = null, $meta = null)
    {
        $this->log('warning', $message, $module, $meta);
    }

    public function debug($message, $module = null, $meta = null)
    {
        $this->log('debug', $message, $module, $meta);
    }
}
