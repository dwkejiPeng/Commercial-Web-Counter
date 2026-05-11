<?php
require_once __DIR__ . '/../../app/bootstrap.php';
$user = require_login();
$site = site_for_user((int)getv('id'), $user);
if (!$site) {
    json_response(['installed' => false, 'message' => 'not found'], 404);
}
json_response([
    'installed' => !empty($site['last_seen_at']) && $site['status'] === 'active',
    'status' => $site['status'],
    'last_seen_at' => $site['last_seen_at'],
    'verified_at' => $site['verified_at'],
]);
