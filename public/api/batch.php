<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

send_cors();

$key = getv('key');
if ($key === '' || !preg_match('/^[a-f0-9]{32}$/', $key)) {
    json_response(['code' => 400, 'message' => 'Invalid site key.', 'data' => null], 400);
}

$body = json_decode(file_get_contents('php://input') ?: '{}', true);
$keys = $body['page_keys'] ?? [];
if (!is_array($keys) || count($keys) === 0) {
    json_response(['code' => 400, 'message' => 'Missing page_keys.', 'data' => null], 400);
}
$keys = array_slice(array_values(array_unique(array_map('strval', $keys))), 0, 100);

$stmt = db()->prepare('SELECT id FROM sites WHERE site_key = ? LIMIT 1');
$stmt->execute([$key]);
$siteId = (int)($stmt->fetchColumn() ?: 0);
if (!$siteId) {
    json_response(['code' => 404, 'message' => 'Site not found.', 'data' => null], 404);
}

$items = array_fill_keys($keys, 0);
$placeholders = implode(',', array_fill(0, count($keys), '?'));
$stmt = db()->prepare("SELECT page_key, views FROM page_counters WHERE site_id=? AND page_key IN ($placeholders)");
$stmt->execute(array_merge([$siteId], $keys));
foreach ($stmt->fetchAll() as $row) {
    $items[$row['page_key']] = (int)$row['views'];
}

json_response(['code' => 200, 'message' => 'ok', 'data' => ['items' => $items]]);
