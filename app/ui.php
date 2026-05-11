<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function layui_header(string $title, string $active = 'dashboard', bool $admin = false): void
{
    $user = current_user();
    $appName = config('app.name', 'Commercial Web Counter');
    $navBase = $admin ? '/admin' : '/dashboard';
    ?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title><?= h($title) ?> - <?= h($appName) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/layui/dist/css/layui.css">
  <style>
    body { background: #f5f7fb; }
    .layui-layout-admin .layui-body { padding: 18px; }
    .main-card { background: #fff; border-radius: 10px; padding: 18px; box-shadow: 0 6px 18px rgba(15,23,42,.06); }
    .stat-grid { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:16px; margin-bottom:18px; }
    .stat-card { background:#fff; border-radius:10px; padding:18px; box-shadow:0 6px 18px rgba(15,23,42,.06); }
    .stat-title { color:#64748b; font-size:13px; }
    .stat-value { margin-top:8px; font-size:28px; font-weight:700; color:#111827; }
    .codebox { background:#0f172a; color:#e5e7eb; border-radius:8px; padding:14px; overflow:auto; }
    .muted { color:#64748b; }
    .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; }
    .badge-active { background:#dcfce7; color:#166534; }
    .badge-pending { background:#fef3c7; color:#92400e; }
    .badge-disabled { background:#fee2e2; color:#991b1b; }
    .table-actions .layui-btn { margin-bottom: 4px; }
    @media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 640px) { .stat-grid { grid-template-columns: 1fr; } .layui-side { display:none; } .layui-body { left:0!important; } }
  </style>
</head>
<body>
<div class="layui-layout layui-layout-admin">
  <div class="layui-header">
    <div class="layui-logo layui-hide-xs layui-bg-black"><?= h($appName) ?></div>
    <ul class="layui-nav layui-layout-left">
      <li class="layui-nav-item layui-hide-xs"><a href="<?= $admin ? '/admin/index.php' : '/dashboard/index.php' ?>"><?= $admin ? '系统管理后台' : '用户控制台' ?></a></li>
    </ul>
    <ul class="layui-nav layui-layout-right">
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin' && !$admin): ?>
          <li class="layui-nav-item"><a href="/admin/index.php">管理员后台</a></li>
        <?php elseif ($user['role'] === 'admin' && $admin): ?>
          <li class="layui-nav-item"><a href="/dashboard/index.php">用户后台</a></li>
        <?php endif; ?>
        <li class="layui-nav-item"><a href="javascript:;"><?= h($user['username']) ?></a></li>
        <li class="layui-nav-item"><a href="/logout.php">退出</a></li>
      <?php endif; ?>
    </ul>
  </div>

  <div class="layui-side layui-bg-black">
    <div class="layui-side-scroll">
      <ul class="layui-nav layui-nav-tree" lay-filter="nav">
        <?php if ($admin): ?>
          <li class="layui-nav-item <?= $active === 'dashboard' ? 'layui-this' : '' ?>"><a href="/admin/index.php">总览</a></li>
          <li class="layui-nav-item <?= $active === 'users' ? 'layui-this' : '' ?>"><a href="/admin/users.php">用户管理</a></li>
          <li class="layui-nav-item <?= $active === 'sites' ? 'layui-this' : '' ?>"><a href="/admin/sites.php">站点管理</a></li>
          <li class="layui-nav-item <?= $active === 'logs' ? 'layui-this' : '' ?>"><a href="/admin/logs.php">访问日志</a></li>
        <?php else: ?>
          <li class="layui-nav-item <?= $active === 'dashboard' ? 'layui-this' : '' ?>"><a href="/dashboard/index.php">数据概览</a></li>
          <li class="layui-nav-item <?= $active === 'sites' ? 'layui-this' : '' ?>"><a href="/dashboard/sites.php">我的站点</a></li>
          <li class="layui-nav-item <?= $active === 'logs' ? 'layui-this' : '' ?>"><a href="/dashboard/logs.php">访问日志</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="layui-body">
    <?php foreach (flashes() as $item): ?>
      <div class="layui-alert layui-bg-<?= $item['type'] === 'error' ? 'red' : 'green' ?>" style="margin-bottom:12px;padding:10px 14px;border-radius:8px;color:#fff;">
        <?= h($item['message']) ?>
      </div>
    <?php endforeach; ?>
<?php
}

function layui_footer(): void
{
    ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/layui/dist/layui.js"></script>
<script>
layui.use(function(){
  var element = layui.element;
  var form = layui.form;
});
function copyText(id) {
  var el = document.getElementById(id);
  var text = el ? el.innerText : '';
  navigator.clipboard.writeText(text).then(function(){
    layui.layer.msg('已复制');
  }).catch(function(){
    layui.layer.msg('复制失败，请手动复制');
  });
}
</script>
</body>
</html>
<?php
}

function status_badge(string $status): string
{
    $class = 'badge-pending';
    $text = '待检测';
    if ($status === 'active') {
        $class = 'badge-active';
        $text = '已接入';
    } elseif ($status === 'disabled') {
        $class = 'badge-disabled';
        $text = '已禁用';
    }
    return '<span class="badge ' . $class . '">' . $text . '</span>';
}

function paginate_offset(int $page, int $pageSize): int
{
    return max(0, ($page - 1) * $pageSize);
}
