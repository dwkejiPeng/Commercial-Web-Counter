<?php
require_once __DIR__ . '/../../app/ui.php';
$user = require_login();
$siteId = (int)getv('id');
$params = [];
$sql = 'SELECT l.*, s.site_name FROM visit_logs l JOIN sites s ON s.id = l.site_id WHERE s.user_id = ?';
$params[] = (int)$user['id'];

if ($siteId > 0) {
    $site = site_for_user($siteId, $user);
    if (!$site) { http_response_code(404); exit('Site not found.'); }
    $sql .= ' AND l.site_id = ?';
    $params[] = $siteId;
}

$sql .= ' ORDER BY l.id DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

layui_header('访问日志', 'logs');
?>
<div class="main-card">
  <h2>访问日志</h2>
  <p class="muted">最多显示最近 200 条。商业正式版建议增加分页、导出和数据保留策略。</p>
  <table class="layui-table">
    <thead><tr><th>时间</th><th>站点</th><th>IP</th><th>页面</th><th>Referer</th><th>User-Agent</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td><?= h($log['created_at']) ?></td>
        <td><?= h($log['site_name']) ?></td>
        <td><?= h($log['visitor_ip'] ?: '-') ?></td>
        <td style="max-width:300px;word-break:break-all;"><?= h($log['page_url']) ?></td>
        <td style="max-width:220px;word-break:break-all;"><?= h($log['referer'] ?: '-') ?></td>
        <td style="max-width:320px;word-break:break-all;"><?= h($log['user_agent'] ?: '-') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="6" class="muted">暂无日志。</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php layui_footer(); ?>
