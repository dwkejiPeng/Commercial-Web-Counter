<?php
require_once __DIR__ . '/../../app/ui.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $siteId = (int)post('site_id');
    $action = post('action');
    if ($action === 'disable') {
        db()->prepare('UPDATE sites SET status="disabled" WHERE id=?')->execute([$siteId]);
        flash('success', '站点已禁用。');
    } elseif ($action === 'enable') {
        db()->prepare('UPDATE sites SET status="active", verified_at = COALESCE(verified_at, NOW()) WHERE id=?')->execute([$siteId]);
        flash('success', '站点已启用。');
    }
    redirect('/admin/sites.php');
}

$sites = db()->query('SELECT s.*, u.email, u.username FROM sites s JOIN users u ON u.id=s.user_id ORDER BY s.id DESC LIMIT 300')->fetchAll();

layui_header('站点管理', 'sites', true);
?>
<div class="main-card">
  <h2>站点管理</h2>
  <table class="layui-table">
    <thead><tr><th>ID</th><th>站点</th><th>用户</th><th>Key</th><th>状态</th><th>PV</th><th>最后访问</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach ($sites as $s): ?>
      <tr>
        <td><?= (int)$s['id'] ?></td>
        <td><?= h($s['site_name']) ?><br><span class="muted"><?= h($s['base_url']) ?></span></td>
        <td><?= h($s['username']) ?><br><span class="muted"><?= h($s['email']) ?></span></td>
        <td><code><?= h($s['site_key']) ?></code></td>
        <td><?= status_badge($s['status']) ?></td>
        <td><?= (int)$s['total_views'] ?></td>
        <td><?= h($s['last_seen_at'] ?: '-') ?></td>
        <td>
          <form method="post" style="display:inline;">
            <?= csrf_field() ?><input type="hidden" name="site_id" value="<?= (int)$s['id'] ?>">
            <?php if ($s['status'] === 'disabled'): ?>
              <button class="layui-btn layui-btn-xs" name="action" value="enable">启用</button>
            <?php else: ?>
              <button class="layui-btn layui-btn-danger layui-btn-xs" name="action" value="disable">禁用</button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php layui_footer(); ?>
