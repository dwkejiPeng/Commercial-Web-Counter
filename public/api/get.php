<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

send_cors();

$key = getv('key');
$pageUrl = clean_url(getv('page_url') ?: ($_SERVER['HTTP_REFERER'] ?? ''));
$pageKey = getv('page_key') ?: ($pageUrl ? md5($pageUrl) : '');

if ($key === '' || !preg_match('/^[a-f0-9]{32}$/', $key)) {
    json_response(['code' => 400, 'message' => 'Invalid site key.', 'data' => null], 400);
}

$stmt = db()->prepare('SELECT * FROM sites WHERE site_key = ? LIMIT 1');
$stmt->execute([$key]);
$site = $stmt->fetch();
if (!$site) {
    json_response(['code' => 404, 'message' => 'Site not found.', 'data' => null], 404);
}

$pageViews = 0;
if ($pageKey !== '') {
    $stmt = db()->prepare('SELECT views FROM page_counters WHERE site_id=? AND page_key=? LIMIT 1');
    $stmt->execute([(int)$site['id'], $pageKey]);
    $pageViews = (int)($stmt->fetchColumn() ?: 0);
}

json_response([
    'code' => 200,
    'message' => 'ok',
    'data' => [
        'site_key' => $key,
        'page_key' => $pageKey,
        'views' => $pageViews,
        'total_views' => (int)$site['total_views'],
        'is_new' => false,
    ],
]);
