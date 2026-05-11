<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (!is_installed()) {
    redirect('/install/');
}
if (current_user()) {
    redirect('/dashboard/index.php');
}
$appName = config('app.name', 'Commercial Web Counter');
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title><?= h($appName) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/layui/dist/css/layui.css">
  <style>
    body { margin:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; background:linear-gradient(135deg,#eef2ff,#f8fafc); color:#0f172a; }
    .hero { max-width:980px; margin:0 auto; padding:80px 20px; }
    .hero h1 { font-size:46px; margin:0 0 18px; }
    .hero p { font-size:18px; color:#475569; line-height:1.8; max-width:720px; }
    .actions { margin-top:32px; }
    .features { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-top:48px; }
    .card { background:#fff; border-radius:14px; padding:22px; box-shadow:0 10px 30px rgba(15,23,42,.08); }
    @media(max-width:800px){.features{grid-template-columns:1fr}.hero h1{font-size:34px}}
  </style>
</head>
<body>
<div class="hero">
  <h1><?= h($appName) ?></h1>
  <p>商业化网页访问计数 SaaS：用户注册登录后创建站点，系统自动分配接入 Key，生成可复制 JS 代码，接入检测通过后即可在后台查看 PV、UV、访问 IP、页面排行与日志。管理员可统一管理全部用户与站点。</p>
  <div class="actions">
    <a class="layui-btn layui-btn-lg" href="/register.php">立即注册</a>
    <a class="layui-btn layui-btn-primary layui-btn-lg" href="/login.php">登录后台</a>
  </div>
  <div class="features">
    <div class="card"><h3>自动接入</h3><p>每个站点独立 key，自动生成 script 代码，支持多种展示样式。</p></div>
    <div class="card"><h3>访问统计</h3><p>记录页面 PV、UV、访客 IP、User-Agent、Referer 与每日趋势。</p></div>
    <div class="card"><h3>管理后台</h3><p>用户后台与管理员后台分离，layui 风格界面，易于二次开发。</p></div>
  </div>
</div>
</body>
</html>
