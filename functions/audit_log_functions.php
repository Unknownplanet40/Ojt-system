<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

require_once __DIR__ . '/../helpers/helpers.php';

function normalizeAuditLogFilters(array $input): array
{
    $source = strtolower(trim((string)($input['source'] ?? 'all')));
    if (!in_array($source, ['all', 'activity', 'login'], true)) {
        $source = 'all';
    }

    $userUuid = trim((string)($input['user_uuid'] ?? ''));
    $eventType = trim((string)($input['event_type'] ?? ''));
    $module = trim((string)($input['module'] ?? ''));
    $search = trim((string)($input['search'] ?? ''));

    $dateFrom = trim((string)($input['date_from'] ?? ''));
    $dateTo = trim((string)($input['date_to'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $dateFrom = '';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $dateTo = '';
    }

    $page = max(1, (int)($input['page'] ?? 1));
    $pageSize = (int)($input['page_size'] ?? 25);
    $pageSize = max(10, min($pageSize, 100));

    return [
        'source' => $source,
        'user_uuid' => $userUuid,
        'event_type' => $eventType,
        'module' => $module,
        'search' => $search,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'page' => $page,
        'page_size' => $pageSize,
    ];
}

function getAuditLogs(mysqli $conn, array $filters = []): array
{
    $filters = normalizeAuditLogFilters($filters);
    $page = $filters['page'];
    $pageSize = $filters['page_size'];
    $offset = ($page - 1) * $pageSize;

    [$unionSql, $unionTypes, $unionParams] = buildAuditLogUnionQuery($conn, $filters);

    $countSql = "SELECT COUNT(*) AS total FROM ({$unionSql}) logs";
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        return [
            'rows' => [],
            'total' => 0,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => 0,
        ];
    }

    if ($unionTypes !== '') {
        bindAuditLogParams($countStmt, $unionTypes, $unionParams);
    }
    $countStmt->execute();
    $total = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();

    $totalPages = $total > 0 ? (int)ceil($total / $pageSize) : 0;
    if ($totalPages > 0 && $page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $pageSize;
    }

    $dataSql = "
        SELECT *
        FROM ({$unionSql}) logs
        ORDER BY occurred_at DESC
        LIMIT ? OFFSET ?
    ";

    $dataStmt = $conn->prepare($dataSql);
    if (!$dataStmt) {
        return [
            'rows' => [],
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => $totalPages,
        ];
    }

    $dataTypes = $unionTypes . 'ii';
    $dataParams = array_merge($unionParams, [$pageSize, $offset]);
    bindAuditLogParams($dataStmt, $dataTypes, $dataParams);

    $dataStmt->execute();
    $result = $dataStmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = formatAuditLogRow($row);
    }

    $dataStmt->close();

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'page_size' => $pageSize,
        'total_pages' => $totalPages,
    ];
}

function getAuditLogsForExport(mysqli $conn, array $filters = [], int $maxRows = 5000): array
{
    $filters = normalizeAuditLogFilters($filters);
    $maxRows = max(100, min($maxRows, 20000));

    [$unionSql, $unionTypes, $unionParams] = buildAuditLogUnionQuery($conn, $filters);

    $dataSql = "
        SELECT *
        FROM ({$unionSql}) logs
        ORDER BY occurred_at DESC
        LIMIT ?
    ";

    $dataStmt = $conn->prepare($dataSql);
    if (!$dataStmt) {
        return [];
    }

    $dataTypes = $unionTypes . 'i';
    $dataParams = array_merge($unionParams, [$maxRows]);
    bindAuditLogParams($dataStmt, $dataTypes, $dataParams);

    $dataStmt->execute();
    $result = $dataStmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = formatAuditLogRow($row);
    }

    $dataStmt->close();
    return $rows;
}

function getAuditLogFilterOptions(mysqli $conn): array
{
    $actors = [];
    $actorSql = "
        SELECT
            lu.user_uuid,
            u.email,
            u.role,
            TRIM(
                CASE u.role
                    WHEN 'student' THEN CONCAT(COALESCE(sp.first_name, ''), ' ', COALESCE(sp.last_name, ''))
                    WHEN 'coordinator' THEN CONCAT(COALESCE(cp.first_name, ''), ' ', COALESCE(cp.last_name, ''))
                    WHEN 'supervisor' THEN CONCAT(COALESCE(svp.first_name, ''), ' ', COALESCE(svp.last_name, ''))
                    WHEN 'admin' THEN CONCAT(COALESCE(ap.first_name, ''), ' ', COALESCE(ap.last_name, ''))
                    ELSE ''
                END
            ) AS actor_name
        FROM (
            SELECT actor_uuid AS user_uuid FROM activity_log WHERE actor_uuid IS NOT NULL
            UNION
            SELECT user_uuid FROM login_audit_log WHERE user_uuid IS NOT NULL
        ) lu
        JOIN users u ON lu.user_uuid = u.uuid
        LEFT JOIN student_profiles sp ON u.uuid = sp.user_uuid
        LEFT JOIN coordinator_profiles cp ON u.uuid = cp.user_uuid
        LEFT JOIN supervisor_profiles svp ON u.uuid = svp.user_uuid
        LEFT JOIN admin_profiles ap ON u.uuid = ap.user_uuid
        ORDER BY actor_name ASC, u.email ASC
    ";

    $actorResult = $conn->query($actorSql);
    if ($actorResult) {
        while ($row = $actorResult->fetch_assoc()) {
            $name = trim((string)($row['actor_name'] ?? ''));
            $email = trim((string)($row['email'] ?? ''));
            $role = trim((string)($row['role'] ?? ''));

            $label = $name !== ''
                ? "{$name} ({$email})"
                : ($email !== '' ? $email : 'System');

            if ($role !== '') {
                $label .= ' • ' . ucfirst($role);
            }

            $actors[] = [
                'value' => $row['user_uuid'],
                'label' => $label,
            ];
        }
    }

    $modules = ['auth'];
    $moduleResult = $conn->query("SELECT DISTINCT module FROM activity_log WHERE module IS NOT NULL AND module <> '' ORDER BY module ASC");
    if ($moduleResult) {
        while ($row = $moduleResult->fetch_assoc()) {
            $m = strtolower(trim((string)$row['module']));
            if ($m === 'authentication') {
                $m = 'auth';
            }
            $modules[] = $m;
        }
    }
    $modules = array_values(array_unique(array_filter($modules, static fn($v) => $v !== '')));
    sort($modules);

    $moduleOptions = [];
    foreach ($modules as $module) {
        $moduleOptions[] = [
            'value' => $module,
            'label' => humanizeAuditToken($module),
        ];
    }

    $eventTypes = [];
    $eventResult = $conn->query("SELECT DISTINCT event_type FROM activity_log WHERE event_type IS NOT NULL AND event_type <> '' ORDER BY event_type ASC");
    if ($eventResult) {
        while ($row = $eventResult->fetch_assoc()) {
            $eventTypes[] = trim((string)$row['event_type']);
        }
    }
    $eventTypes[] = 'login_success';
    $eventTypes[] = 'login_failed';
    $eventTypes = array_values(array_unique(array_filter($eventTypes, static fn($v) => $v !== '')));
    sort($eventTypes);

    $eventOptions = [];
    foreach ($eventTypes as $eventType) {
        $eventOptions[] = [
            'value' => $eventType,
            'label' => humanizeAuditToken($eventType),
        ];
    }

    return [
        'sources' => [
            ['value' => 'all', 'label' => 'All Sources'],
            ['value' => 'activity', 'label' => 'Activity Logs'],
            ['value' => 'login', 'label' => 'Login Attempts'],
        ],
        'actors' => $actors,
        'modules' => $moduleOptions,
        'event_types' => $eventOptions,
    ];
}

function formatAuditLogRow(array $row): array
{
    $occurredAt = $row['occurred_at'] ?? null;
    $metaRaw = $row['meta'] ?? null;
    $metaArray = decodeAuditMeta($metaRaw);

    $actorName = trim((string)($row['actor_name'] ?? ''));
    $actorEmail = trim((string)($row['actor_email'] ?? ''));
    if ($actorName === '') {
        $actorName = $actorEmail !== '' ? $actorEmail : 'System';
    }

    $actorRole = trim((string)($row['actor_role'] ?? ''));
    if ($actorRole === '') {
        $actorRole = 'system';
    }

    return [
        'row_id' => $row['row_id'],
        'source' => $row['source'],
        'source_id' => (int)$row['source_id'],
        'actor_uuid' => $row['actor_uuid'] ?? null,
        'actor_name' => $actorName,
        'actor_email' => $actorEmail,
        'actor_role' => $actorRole,
        'actor_role_label' => ucfirst($actorRole),
        'target_uuid' => $row['target_uuid'] ?? null,
        'event_type' => $row['event_type'] ?? 'unknown',
        'event_label' => humanizeAuditToken((string)($row['event_type'] ?? 'unknown')),
        'description' => $row['description'] ?? '—',
        'module' => $row['module'] ?? 'system',
        'module_label' => humanizeAuditToken((string)($row['module'] ?? 'system')),
        'meta' => $metaArray,
        'meta_raw' => $metaRaw,
        'ip_address' => $row['ip_address'] ?? null,
        'user_agent' => $row['user_agent'] ?? null,
        'login_success' => isset($row['login_success']) ? (int)$row['login_success'] : null,
        'fail_reason' => $row['fail_reason'] ?? null,
        'occurred_at' => $occurredAt,
        'occurred_at_display' => !empty($occurredAt) ? date('M j, Y g:i A', strtotime($occurredAt)) : null,
        'time_ago' => !empty($occurredAt) ? timeAgo($occurredAt) : null,
    ];
}

function decodeAuditMeta($metaRaw): ?array
{
    if ($metaRaw === null || $metaRaw === '') {
        return null;
    }

    $raw = (string)$metaRaw;

    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
        $decodedNested = json_decode($decoded, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedNested)) {
            return $decodedNested;
        }
    }

    $trimmed = trim($raw);
    if (strlen($trimmed) >= 2 && $trimmed[0] === '"' && $trimmed[strlen($trimmed) - 1] === '"') {
        $unwrapped = stripslashes(substr($trimmed, 1, -1));
        $decodedUnwrapped = json_decode($unwrapped, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedUnwrapped)) {
            return $decodedUnwrapped;
        }
    }

    return null;
}

function humanizeAuditToken(string $value): string
{
    $token = trim($value);
    if ($token === '') {
        return 'Unknown';
    }

    return ucwords(str_replace('_', ' ', $token));
}

function buildAuditLogUnionQuery(mysqli $conn, array $filters): array
{
    $includeActivity = in_array($filters['source'], ['all', 'activity'], true);
    $includeLogin = in_array($filters['source'], ['all', 'login'], true);

    $selects = [];
    $types = '';
    $params = [];

    if ($includeActivity) {
        [$sql, $sqlTypes, $sqlParams] = buildActivityLogSelect($conn, $filters);
        $selects[] = $sql;
        $types .= $sqlTypes;
        $params = array_merge($params, $sqlParams);
    }

    if ($includeLogin) {
        [$sql, $sqlTypes, $sqlParams] = buildLoginAuditSelect($conn, $filters);
        $selects[] = $sql;
        $types .= $sqlTypes;
        $params = array_merge($params, $sqlParams);
    }

    if (empty($selects)) {
        $selects[] = "
            SELECT
                'none-0' AS row_id,
                'activity' AS source,
                0 AS source_id,
                NULL AS actor_uuid,
                NULL AS target_uuid,
                '' AS event_type,
                '' AS description,
                'system' AS module,
                NULL AS meta,
                NULL AS ip_address,
                NULL AS user_agent,
                NULL AS login_success,
                NULL AS fail_reason,
                NULL AS occurred_at,
                NULL AS actor_email,
                NULL AS actor_role,
                '' AS actor_name
            WHERE 1 = 0
        ";
    }

    return [implode("\nUNION ALL\n", $selects), $types, $params];
}

function buildActivityLogSelect(mysqli $conn, array $filters): array
{
    $conditions = ['1 = 1'];
    $types = '';
    $params = [];

    if (!empty($filters['date_from'])) {
        $conditions[] = 'al.created_at >= ?';
        $types .= 's';
        $params[] = $filters['date_from'] . ' 00:00:00';
    }

    if (!empty($filters['date_to'])) {
        $conditions[] = 'al.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
        $types .= 's';
        $params[] = $filters['date_to'];
    }

    if (!empty($filters['user_uuid'])) {
        $conditions[] = 'al.actor_uuid = ?';
        $types .= 's';
        $params[] = $filters['user_uuid'];
    }

    if (!empty($filters['event_type'])) {
        $conditions[] = 'al.event_type = ?';
        $types .= 's';
        $params[] = $filters['event_type'];
    }

    if (!empty($filters['module'])) {
        if ($filters['module'] === 'auth') {
            $conditions[] = '(LOWER(al.module) = "auth" OR LOWER(al.module) = "authentication")';
        } else {
            $conditions[] = 'LOWER(al.module) = LOWER(?)';
            $types .= 's';
            $params[] = $filters['module'];
        }
    }

    if (!empty($filters['search'])) {
        $conditions[] = '(al.description LIKE ? OR al.event_type LIKE ? OR al.module LIKE ? OR al.target_uuid LIKE ? OR al.meta LIKE ?)';
        $types .= 'sssss';
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    $where = implode(' AND ', $conditions);

    $sql = "
        SELECT
            CONCAT('activity-', al.id) AS row_id,
            'activity' AS source,
            al.id AS source_id,
            al.actor_uuid AS actor_uuid,
            al.target_uuid AS target_uuid,
            al.event_type AS event_type,
            al.description AS description,
            COALESCE(NULLIF(al.module, ''), 'system') AS module,
            al.meta AS meta,
            NULL AS ip_address,
            NULL AS user_agent,
            NULL AS login_success,
            NULL AS fail_reason,
            al.created_at AS occurred_at,
            u.email AS actor_email,
            u.role AS actor_role,
            TRIM(
                CASE u.role
                    WHEN 'student' THEN CONCAT(COALESCE(sp.first_name, ''), ' ', COALESCE(sp.last_name, ''))
                    WHEN 'coordinator' THEN CONCAT(COALESCE(cp.first_name, ''), ' ', COALESCE(cp.last_name, ''))
                    WHEN 'supervisor' THEN CONCAT(COALESCE(svp.first_name, ''), ' ', COALESCE(svp.last_name, ''))
                    WHEN 'admin' THEN CONCAT(COALESCE(ap.first_name, ''), ' ', COALESCE(ap.last_name, ''))
                    ELSE ''
                END
            ) AS actor_name
        FROM activity_log al
        LEFT JOIN users u ON al.actor_uuid = u.uuid
        LEFT JOIN student_profiles sp ON u.uuid = sp.user_uuid
        LEFT JOIN coordinator_profiles cp ON u.uuid = cp.user_uuid
        LEFT JOIN supervisor_profiles svp ON u.uuid = svp.user_uuid
        LEFT JOIN admin_profiles ap ON u.uuid = ap.user_uuid
        WHERE {$where}
    ";

    return [$sql, $types, $params];
}

function buildLoginAuditSelect(mysqli $conn, array $filters): array
{
    $conditions = ['1 = 1'];
    $types = '';
    $params = [];

    if (!empty($filters['date_from'])) {
        $conditions[] = 'lal.attempted_at >= ?';
        $types .= 's';
        $params[] = $filters['date_from'] . ' 00:00:00';
    }

    if (!empty($filters['date_to'])) {
        $conditions[] = 'lal.attempted_at < DATE_ADD(?, INTERVAL 1 DAY)';
        $types .= 's';
        $params[] = $filters['date_to'];
    }

    if (!empty($filters['user_uuid'])) {
        $conditions[] = 'lal.user_uuid = ?';
        $types .= 's';
        $params[] = $filters['user_uuid'];
    }

    if (!empty($filters['module']) && strtolower($filters['module']) !== 'auth') {
        $conditions[] = '1 = 0';
    }

    if (!empty($filters['event_type'])) {
        if ($filters['event_type'] === 'login_success') {
            $conditions[] = 'lal.success = 1';
        } elseif ($filters['event_type'] === 'login_failed') {
            $conditions[] = 'lal.success = 0';
        } else {
            $conditions[] = '1 = 0';
        }
    }

    if (!empty($filters['search'])) {
        $conditions[] = '(u.email LIKE ? OR lal.ip_address LIKE ? OR lal.user_agent LIKE ? OR lal.fail_reason LIKE ?)';
        $types .= 'ssss';
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    $where = implode(' AND ', $conditions);

    $sql = "
        SELECT
            CONCAT('login-', lal.id) AS row_id,
            'login' AS source,
            lal.id AS source_id,
            lal.user_uuid AS actor_uuid,
            NULL AS target_uuid,
            CASE WHEN lal.success = 1 THEN 'login_success' ELSE 'login_failed' END AS event_type,
            CASE
                WHEN lal.success = 1 THEN 'Login successful'
                WHEN COALESCE(lal.fail_reason, '') = '' THEN 'Login failed'
                ELSE CONCAT('Login failed: ', lal.fail_reason)
            END AS description,
            'auth' AS module,
            NULL AS meta,
            lal.ip_address AS ip_address,
            lal.user_agent AS user_agent,
            lal.success AS login_success,
            lal.fail_reason AS fail_reason,
            lal.attempted_at AS occurred_at,
            u.email AS actor_email,
            u.role AS actor_role,
            TRIM(
                CASE u.role
                    WHEN 'student' THEN CONCAT(COALESCE(sp.first_name, ''), ' ', COALESCE(sp.last_name, ''))
                    WHEN 'coordinator' THEN CONCAT(COALESCE(cp.first_name, ''), ' ', COALESCE(cp.last_name, ''))
                    WHEN 'supervisor' THEN CONCAT(COALESCE(svp.first_name, ''), ' ', COALESCE(svp.last_name, ''))
                    WHEN 'admin' THEN CONCAT(COALESCE(ap.first_name, ''), ' ', COALESCE(ap.last_name, ''))
                    ELSE ''
                END
            ) AS actor_name
        FROM login_audit_log lal
        LEFT JOIN users u ON lal.user_uuid = u.uuid
        LEFT JOIN student_profiles sp ON u.uuid = sp.user_uuid
        LEFT JOIN coordinator_profiles cp ON u.uuid = cp.user_uuid
        LEFT JOIN supervisor_profiles svp ON u.uuid = svp.user_uuid
        LEFT JOIN admin_profiles ap ON u.uuid = ap.user_uuid
        WHERE {$where}
    ";

    return [$sql, $types, $params];
}

function bindAuditLogParams(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '' || empty($params)) {
        return;
    }

    $bindArgs = [$types];
    foreach ($params as $index => $value) {
        $bindArgs[] = &$params[$index];
    }

    call_user_func_array([$stmt, 'bind_param'], $bindArgs);
}
