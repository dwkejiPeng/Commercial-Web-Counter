<?php
require_once __DIR__ . '/../../app/ui.php';
$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $userId = (int)post('user_id');
    $action = post('action');
    if ($userId === (int)$admin['id']) {
        flash('error', '不能操作当前登录管理员账号。');
        redirect('/admin/users.php');
    }
    if ($action === 'disable') {
        db()->prepare('UPDATE users SET status="disabled" WHERE id=?')->execute([$userId]);
        flash('success', '用户已禁用。');
    } elseif ($action === 'enable') {
        db()->prepare('UPDATE users SET status="active" WHERE id=?')->execute([$userId]);
        flash('success', '用户已启用。');
    } elseif ($action === 'make_admin') {
        db()->prepare('UPDATE users SET role="admin" WHERE id=?')->execute([$userId]);
        flash('success', '已设为管理员。');
    } elseif ($action === 'make_user') {
        db()->prepare('UPDATE users SET role="user" WHERE id=?')->execute([$userId]);
        flash('success', '已设为普通用户。');
    }
    redirect('/admin/users.php');
}

$users = db()->query('SELECT u.*, COUNT(s.id) AS site_count, COALESCE(SUM(s.total_views),0) AS total_views FROM users u LEFT JOIN sites s ON s.user_id=u.id GROUP BY u.id ORDER BY u.id DESC LIMIT 300')->fetchAll();

layui_header('用户管理', 'users', true);
?>
<div class="main-card">
  <h2>用户管理</h2>
  <table class="layui-table">
    <thead><tr><th>ID</th><th>用户</th><th>角色</th><th>状态</th><th>站点数</th><th>总 PV</th><th>注册时间</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td><?= h($u['username']) ?><br><span class="muted"><?= h($u['email']) ?></span></td>
        <td><?= h($u['role']) ?></td>
        <td><?= h($u['status']) ?></td>
        <td><?= (int)$u['site_count'] ?></td>
        <td><?= (int)$u['total_views'] ?></td>
        <td><?= h($u['created_at']) ?></td>
        <td>
          <form method="post" style="display:inline;">
            <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
            <?php if ($u['status'] === 'active'): ?>
              <button class="layui-btn layui-btn-danger layui-btn-xs" name="action" value="disable">禁用</button>
            <?php else: ?>
              <button class="layui-btn layui-btn-xs" name="action" value="enable">启用</button>
            <?php endif; ?>
            <?php if ($u['role'] === 'user'): ?>
              <button class="layui-btn layui-btn-warm layui-btn-xs" name="action" value="make_admin">设管理员</button>
            <?php else: ?>
              <button class="layui-btn layui-btn-primary layui-btn-xs" name="action" value="make_user">设普通用户</button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php layui_footer(); ?>
