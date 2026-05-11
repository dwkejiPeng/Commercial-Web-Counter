<?php
require_once __DIR__ . '/../../app/ui.php';
require_admin();

$users = db()->query('SELECT COUNT(*) AS c FROM users')->fetch();
$sites = db()->query('SELECT COUNT(*) AS c, COALESCE(SUM(total_views),0) AS views FROM sites')->fetch();
$today = db()->query('SELECT COALESCE(SUM(pv),0) AS pv, COALESCE(SUM(uv),0) AS uv FROM daily_stats WHERE stat_date = CURDATE()')->fetch();
$logs = db()->query('SELECT COUNT(*) AS c FROM visit_logs')->fetch();

$latestSites = db()->query('SELECT s.*, u.email FROM sites s JOIN users u ON u.id=s.user_id ORDER BY s.id DESC LIMIT 10')->fetchAll();

layui_header('管理员总览', 'dashboard', true);
?>
<div class="stat-grid">
  <div class="stat-card"><div class="stat-title">用户数</div><div class="stat-value"><?= (int)$users['c'] ?></div></div>
  <div class="stat-card"><div class="stat-title">站点数</div><div class="stat-value"><?= (int)$sites['c'] ?></div></div>
  <div class="stat-card"><div class="stat-title">总 PV</div><div class="stat-value"><?= (int)$sites['views'] ?></div></div>
  <div class="stat-card"><div class="stat-title">今日 PV / UV</div><div class="stat-value"><?= (int)$today['pv'] ?> / <?= (int)$today['uv'] ?></div></div>
</div>

<div class="main-card">
  <h2>最新站点</h2>
  <table class="layui-table">
    <thead><tr><th>ID</th><th>站点</th><th>用户</th><th>Key</th><th>状态</th><th>PV</th><th>最后访问</th></tr></thead>
    <tbody>
    <?php foreach ($latestSites as $site): ?>
      <tr>
        <td><?= (int)$site['id'] ?></td>
        <td><?= h($site['site_name']) ?><br><span class="muted"><?= h($site['base_url']) ?></span></td>
        <td><?= h($site['email']) ?></td>
        <td><code><?= h($site['site_key']) ?></code></td>
        <td><?= status_badge($site['status']) ?></td>
        <td><?= (int)$site['total_views'] ?></td>
        <td><?= h($site['last_seen_at'] ?: '-') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php layui_footer(); ?>
