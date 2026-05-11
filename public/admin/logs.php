<?php
require_once __DIR__ . '/../../app/ui.php';
require_admin();

$logs = db()->query('SELECT l.*, s.site_name, u.email FROM visit_logs l JOIN sites s ON s.id=l.site_id JOIN users u ON u.id=s.user_id ORDER BY l.id DESC LIMIT 500')->fetchAll();

layui_header('全站访问日志', 'logs', true);
?>
<div class="main-card">
  <h2>全站访问日志</h2>
  <p class="muted">最多显示最近 500 条。</p>
  <table class="layui-table">
    <thead><tr><th>时间</th><th>用户</th><th>站点</th><th>IP</th><th>页面</th><th>User-Agent</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td><?= h($log['created_at']) ?></td>
        <td><?= h($log['email']) ?></td>
        <td><?= h($log['site_name']) ?></td>
        <td><?= h($log['visitor_ip'] ?: '-') ?></td>
        <td style="max-width:360px;word-break:break-all;"><?= h($log['page_url']) ?></td>
        <td style="max-width:360px;word-break:break-all;"><?= h($log['user_agent'] ?: '-') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php layui_footer(); ?>
