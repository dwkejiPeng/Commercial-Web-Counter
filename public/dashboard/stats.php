<?php
require_once __DIR__ . '/../../app/ui.php';
$user = require_login();
$site = site_for_user((int)getv('id'), $user);
if (!$site) {
    http_response_code(404);
    exit('Site not found.');
}

$stmt = db()->prepare('SELECT stat_date, SUM(pv) AS pv, SUM(uv) AS uv FROM daily_stats WHERE site_id=? GROUP BY stat_date ORDER BY stat_date DESC LIMIT 30');
$stmt->execute([(int)$site['id']]);
$daily = $stmt->fetchAll();

$stmt = db()->prepare('SELECT page_url, page_title, views FROM page_counters WHERE site_id=? ORDER BY views DESC LIMIT 20');
$stmt->execute([(int)$site['id']]);
$pages = $stmt->fetchAll();

$stmt = db()->prepare('SELECT COUNT(*) AS logs FROM visit_logs WHERE site_id=?');
$stmt->execute([(int)$site['id']]);
$logCount = $stmt->fetch();

layui_header('站点统计', 'sites');
?>
<div class="stat-grid">
  <div class="stat-card"><div class="stat-title">站点总 PV</div><div class="stat-value"><?= (int)$site['total_views'] ?></div></div>
  <div class="stat-card"><div class="stat-title">记录日志数</div><div class="stat-value"><?= (int)$logCount['logs'] ?></div></div>
  <div class="stat-card"><div class="stat-title">状态</div><div class="stat-value" style="font-size:18px;"><?= status_badge($site['status']) ?></div></div>
  <div class="stat-card"><div class="stat-title">最后访问</div><div class="stat-value" style="font-size:18px;"><?= h($site['last_seen_at'] ?: '-') ?></div></div>
</div>

<div class="main-card" style="margin-bottom:18px;">
  <h2>最近 30 天趋势</h2>
  <table class="layui-table">
    <thead><tr><th>日期</th><th>PV</th><th>UV</th></tr></thead>
    <tbody>
    <?php foreach ($daily as $row): ?>
      <tr><td><?= h($row['stat_date']) ?></td><td><?= (int)$row['pv'] ?></td><td><?= (int)$row['uv'] ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$daily): ?><tr><td colspan="3" class="muted">暂无数据。</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<div class="main-card">
  <h2>页面排行</h2>
  <table class="layui-table">
    <thead><tr><th>页面</th><th>标题</th><th>PV</th></tr></thead>
    <tbody>
    <?php foreach ($pages as $row): ?>
      <tr><td style="max-width:420px;word-break:break-all;"><?= h($row['page_url']) ?></td><td><?= h($row['page_title'] ?: '-') ?></td><td><?= (int)$row['views'] ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$pages): ?><tr><td colspan="3" class="muted">暂无数据。</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php layui_footer(); ?>
