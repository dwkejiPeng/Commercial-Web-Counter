<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_installed();

if (current_user()) {
    redirect('/dashboard/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = strtolower(post('email'));
    $password = (string)($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = '邮箱或密码错误。';
    } elseif ($user['status'] !== 'active') {
        $error = '账号已被禁用，请联系管理员。';
    } else {
        login_user($user);
        redirect($user['role'] === 'admin' ? '/admin/index.php' : '/dashboard/index.php');
    }
}

$appName = config('app.name', 'Commercial Web Counter');
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>登录 - <?= h($appName) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/layui/dist/css/layui.css">
  <style>body{background:#f5f7fb}.box{max-width:420px;margin:80px auto;background:#fff;padding:28px;border-radius:14px;box-shadow:0 10px 30px rgba(15,23,42,.08)}</style>
</head>
<body>
<div class="box">
  <h2 style="margin-bottom:20px;">登录</h2>
  <?php if ($error): ?><div class="layui-bg-red" style="padding:10px;border-radius:8px;color:#fff;margin-bottom:12px;"><?= h($error) ?></div><?php endif; ?>
  <form class="layui-form" method="post">
    <?= csrf_field() ?>
    <div class="layui-form-item"><input class="layui-input" type="email" name="email" placeholder="邮箱" required></div>
    <div class="layui-form-item"><input class="layui-input" type="password" name="password" placeholder="密码" required></div>
    <button class="layui-btn layui-btn-fluid">登录</button>
  </form>
  <p style="margin-top:16px;">还没有账号？<a href="/register.php">注册</a></p>
</div>
<script src="https://cdn.jsdelivr.net/npm/layui/dist/layui.js"></script>
</body>
</html>
