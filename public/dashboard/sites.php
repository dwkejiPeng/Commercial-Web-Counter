<?php
require_once __DIR__ . '/../../app/ui.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $siteName = post('site_name');
    $baseUrl = post('base_url');
    $domain = normalize_domain($baseUrl ?: post('site_domain'));

    if ($siteName === '' || $baseUrl === '' || $domain === '') {
        flash('error', '站点名称和网址必填。');
    } else {
        $key = generate_site_key();
        $stmt = db()->prepare('INSERT INTO sites (user_id, site_name, site_domain, base_url, site_key, allowed_domains) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([(int)$user['id'], $siteName, $domain, $baseUrl, $key, $domain]);
        flash('success', '站点已创建，系统已自动分配 Key。');
        redirect('/dashboard/integration.php?id=' . db()->lastInsertId());
    }
}

$stmt = db()->prepare('SELECT * FROM sites WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([(int)$user['id']]);
$sites = $stmt->fetchAll();

layui_header('我的站点', 'sites');
?>
<div class="main-card" style="margin-bottom:18px;">
  <h2 style="margin-bottom:14px;">申请接入新站点</h2>
  <form class="layui-form" method="post">
    <?= csrf_field() ?>
    <div class="layui-form-item">
      <label class="layui-form-label">站点名称</label>
      <div class="layui-input-block"><input class="layui-input" name="site_name" placeholder="例如：我的博客" required></div>
    </div>
    <div class="layui-form-item">
      <label class="layui-form-label">网站地址</label>
      <div class="layui-input-block"><input class="layui-input" name="base_url" placeholder="https://example.com" required></div>
    </div>
    <div class="layui-form-item">
      <div class="layui-input-block"><button class="layui-btn">创建并生成接入代码</button></div>
    </div>
  </form>
</div>

<div class="main-card">
  <h2>站点列表</h2>
  <table class="layui-table">
    <thead><tr><th>ID</th><th>站点</th><th>Key</th><th>状态</th><th>总 PV</th><th>最后检测</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach ($sites as $site): ?>
      <tr>
        <td><?= (int)$site['id'] ?></td>
        <td><strong><?= h($site['site_name']) ?></strong><br><span class="muted"><?= h($site['base_url']) ?></span></td>
        <td><code><?= h($site['site_key']) ?></code></td>
        <td><?= status_badge($site['status']) ?></td>
        <td><?= (int)$site['total_views'] ?></td>
        <td><?= h($site['last_seen_at'] ?: '-') ?></td>
        <td class="table-actions">
          <a class="layui-btn layui-btn-xs" href="/dashboard/integration.php?id=<?= (int)$site['id'] ?>">接入</a>
          <a class="layui-btn layui-btn-normal layui-btn-xs" href="/dashboard/stats.php?id=<?= (int)$site['id'] ?>">统计</a>
          <a class="layui-btn layui-btn-primary layui-btn-xs" href="/dashboard/logs.php?id=<?= (int)$site['id'] ?>">日志</a>
          <a class="layui-btn layui-btn-warm layui-btn-xs" href="/dashboard/site_edit.php?id=<?= (int)$site['id'] ?>">设置</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$sites): ?><tr><td colspan="7" class="muted">暂无站点。</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php layui_footer(); ?>
