<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

send_cors();
no_cache();

if (!is_installed()) {
    json_response(['code' => 500, 'message' => 'System is not installed.', 'data' => null], 500);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_response(['code' => 405, 'message' => 'Method not allowed.', 'data' => null], 405);
}

$key = getv('key');
if ($key === '' || !preg_match('/^[a-f0-9]{32}$/', $key)) {
    json_response(['code' => 400, 'message' => 'Invalid site key.', 'data' => null], 400);
}

$stmt = db()->prepare('SELECT * FROM sites WHERE site_key = ? LIMIT 1');
$stmt->execute([$key]);
$site = $stmt->fetch();

if (!$site || $site['status'] === 'disabled') {
    json_response(['code' => 403, 'message' => 'Site is disabled or not found.', 'data' => null], 403);
}

if (!is_request_domain_allowed($site)) {
    json_response(['code' => 403, 'message' => 'Request domain is not allowed for this site.', 'data' => null], 403);
}

$pageUrl = clean_url(getv('page_url') ?: ($_SERVER['HTTP_REFERER'] ?? ''));
if ($pageUrl === '') {
    $pageUrl = 'unknown';
}
$pageKey = getv('page_key') ?: md5($pageUrl);
$pageTitle = mb_substr(getv('page_title'), 0, 255);
$userAgent = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
$referer = mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 800);
$ip = client_ip();
$hash = visitor_hash($ip, $userAgent);
$cooldown = max(0, (int)config('security.cooldown_seconds', 600));
$storeRawIp = (bool)config('security.store_raw_ip', true);

$isNew = true;
$isUniqueToday = true;

try {
    $pdo = db();

    if ($cooldown > 0) {
        $threshold = (new DateTimeImmutable())->modify('-' . $cooldown . ' seconds')->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('SELECT id FROM visit_logs WHERE site_id=? AND page_key=? AND visitor_hash=? AND created_at >= ? LIMIT 1');
        $stmt->execute([(int)$site['id'], $pageKey, $hash, $threshold]);
        $isNew = !$stmt->fetch();
    }

    $todayStart = date('Y-m-d 00:00:00');
    $stmt = $pdo->prepare('SELECT id FROM visit_logs WHERE site_id=? AND page_key=? AND visitor_hash=? AND created_at >= ? LIMIT 1');
    $stmt->execute([(int)$site['id'], $pageKey, $hash, $todayStart]);
    $isUniqueToday = !$stmt->fetch();

    $pdo->beginTransaction();

    if ($isNew) {
        $stmt = $pdo->prepare('UPDATE sites SET total_views = total_views + 1, last_seen_at = NOW(), verified_at = COALESCE(verified_at, NOW()), status = IF(status="pending", "active", status) WHERE id=?');
        $stmt->execute([(int)$site['id']]);

        $stmt = $pdo->prepare('INSERT INTO page_counters (site_id, page_key, page_url, page_title, views) VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE views = views + 1, page_url = VALUES(page_url), page_title = VALUES(page_title)');
        $stmt->execute([(int)$site['id'], $pageKey, $pageUrl, $pageTitle ?: null]);

        $stmt = $pdo->prepare('INSERT INTO visit_logs (site_id, page_key, page_url, page_title, visitor_ip, visitor_hash, user_agent, referer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([(int)$site['id'], $pageKey, $pageUrl, $pageTitle ?: null, $storeRawIp ? $ip : null, $hash, $userAgent ?: null, $referer ?: null]);

        $stmt = $pdo->prepare('INSERT INTO daily_stats (site_id, stat_date, page_key, page_url, pv, uv) VALUES (?, CURDATE(), ?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE pv = pv + 1, uv = uv + VALUES(uv), page_url = VALUES(page_url)');
        $stmt->execute([(int)$site['id'], $pageKey, $pageUrl, $isUniqueToday ? 1 : 0]);
    } else {
        $stmt = $pdo->prepare('UPDATE sites SET last_seen_at = NOW(), verified_at = COALESCE(verified_at, NOW()), status = IF(status="pending", "active", status) WHERE id=?');
        $stmt->execute([(int)$site['id']]);
    }

    $stmt = $pdo->prepare('SELECT total_views FROM sites WHERE id=?');
    $stmt->execute([(int)$site['id']]);
    $totalViews = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT views FROM page_counters WHERE site_id=? AND page_key=? LIMIT 1');
    $stmt->execute([(int)$site['id'], $pageKey]);
    $pageViews = (int)($stmt->fetchColumn() ?: 0);

    $pdo->commit();

    json_response([
        'code' => 200,
        'message' => 'ok',
        'data' => [
            'site_key' => $key,
            'page_key' => $pageKey,
            'views' => $pageViews,
            'total_views' => $totalViews,
            'is_new' => $isNew,
        ],
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['code' => 500, 'message' => 'Collect failed.', 'data' => null], 500);
}
