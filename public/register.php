<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_installed();

if (!config('app.allow_registration', true)) {
    exit('Registration is disabled.');
}
if (current_user()) {
    redirect('/dashboard/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = strtolower(post('email'));
    $username = post('username');
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效邮箱。';
    } elseif (mb_strlen($username) < 2 || mb_strlen($username) > 30) {
        $error = '用户名长度应为 2-30 个字符。';
    } elseif (strlen($password) < 8) {
        $error = '密码至少 8 位。';
    } elseif ($password !== $password2) {
        $error = '两次密码不一致。';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO users (email, username, password_hash, role, status) VALUES (?, ?, ?, "user", "active")');
            $stmt->execute([$email, $username, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', '注册成功，请登录。');
            redirect('/login.php');
        } catch (Throwable $e) {
            $error = '该邮箱可能已注册。';
        }
    }
}

$appName = config('app.name', 'Commercial Web Counter');
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>注册 - <?= h($appName) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/layui/dist/css/layui.css">
  <style>body{background:#f5f7fb}.box{max-width:460px;margin:60px auto;background:#fff;padding:28px;border-radius:14px;box-shadow:0 10px 30px rgba(15,23,42,.08)}</style>
</head>
<body>
<div class="box">
  <h2 style="margin-bottom:20px;">注册账号</h2>
  <?php if ($error): ?><div class="layui-bg-red" style="padding:10px;border-radius:8px;color:#fff;margin-bottom:12px;"><?= h($error) ?></div><?php endif; ?>
  <form class="layui-form" method="post">
    <?= csrf_field() ?>
    <div class="layui-form-item"><input class="layui-input" type="email" name="email" placeholder="邮箱" required></div>
    <div class="layui-form-item"><input class="layui-input" name="username" placeholder="用户名" required></div>
    <div class="layui-form-item"><input class="layui-input" type="password" name="password" placeholder="密码，至少 8 位" required></div>
    <div class="layui-form-item"><input class="layui-input" type="password" name="password2" placeholder="重复密码" required></div>
    <button class="layui-btn layui-btn-fluid">注册</button>
  </form>
  <p style="margin-top:16px;">已有账号？<a href="/login.php">登录</a></p>
</div>
<script src="https://cdn.jsdelivr.net/npm/layui/dist/layui.js"></script>
</body>
</html>
