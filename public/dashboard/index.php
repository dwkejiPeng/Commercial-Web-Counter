<?php
require_once __DIR__ . '/../../app/ui.php';
$user = require_login();

$stmt = db()->prepare('SELECT COUNT(*) AS c, COALESCE(SUM(total_views),0) AS views FROM sites WHERE user_id = ?');
$stmt->execute([(int)$user['id']]);
$summary = $stmt->fetch();

$stmt = db()->prepare('SELECT COALESCE(SUM(pv),0) AS pv, COALESCE(SUM(uv),0) AS uv FROM daily_stats ds JOIN sites s ON s.id = ds.site_id WHERE s.user_id = ? AND ds.stat_date = CURDATE()');
$stmt->execute([(int)$user['id']]);
$today = $stmt->fetch();

$stmt = db()->prepare('SELECT * FROM sites WHERE user_id = ? ORDER BY id DESC LIMIT 10');
$stmt->execute([(int)$user['id']]);
$sites = $stmt->fetchAll();

layui_header('数据概览', 'dashboard');
?>
<div class="stat-grid">
  <div class="stat-card"><div class="stat-title">我的站点</div><div class="stat-value"><?= (int)$summary['c'] ?></div></div>
  <div class="stat-card"><div class="stat-title">总访问量 PV</div><div class="stat-value"><?= (int)$summary['views'] ?></div></div>
  <div class="stat-card"><div class="stat-title">今日 PV</div><div class="stat-value"><?= (int)$today['pv'] ?></div></div>
  <div class="stat-card"><div class="stat-title">今日 UV</div><div class="stat-value"><?= (int)$today['uv'] ?></div></div>
</div>

<div class="main-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h2>最近站点</h2>
    <a class="layui-btn" href="/dashboard/sites.php">创建/管理站点</a>
  </div>
  <table class="layui-table">
    <thead><tr><th>站点</th><th>域名</th><th>状态</th><th>总访问</th><th>最后访问</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach ($sites as $site): ?>
      <tr>
        <td><?= h($site['site_name']) ?></td>
        <td><?= h($site['site_domain']) ?></td>
        <td><?= status_badge($site['status']) ?></td>
        <td><?= (int)$site['total_views'] ?></td>
        <td><?= h($site['last_seen_at'] ?: '-') ?></td>
        <td>
          <a class="layui-btn layui-btn-xs" href="/dashboard/integration.php?id=<?= (int)$site['id'] ?>">接入代码</a>
          <a class="layui-btn layui-btn-normal layui-btn-xs" href="/dashboard/stats.php?id=<?= (int)$site['id'] ?>">统计</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$sites): ?>
      <tr><td colspan="6" class="muted">还没有站点，请先创建。</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php layui_footer(); ?>
