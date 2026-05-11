<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$appDir = $root . '/app';
$configPath = $appDir . '/config.php';
$sqlPath = $root . '/database/install.sql';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function split_sql(string $sql): array
{
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    return array_values(array_filter(array_map('trim', explode(';', $sql))));
}

$installed = is_file($configPath);
$errors = [];
$values = [
    'app_name' => 'Commercial Web Counter',
    'base_url' => '',
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'counter_saas',
    'db_user' => '',
    'db_pass' => '',
    'create_db' => '1',
    'admin_email' => '',
    'admin_name' => 'admin',
    'admin_pass' => '',
    'cooldown' => '600',
    'store_raw_ip' => '1',
];

if (!$installed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $k => $v) {
        $values[$k] = trim((string)($_POST[$k] ?? $v));
    }
    $values['db_pass'] = (string)($_POST['db_pass'] ?? '');
    $values['admin_pass'] = (string)($_POST['admin_pass'] ?? '');

    if ($values['base_url'] === '') $errors[] = '系统访问域名必填。';
    if ($values['db_user'] === '') $errors[] = '数据库用户名必填。';
    if (!filter_var($values['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = '管理员邮箱无效。';
    if (strlen($values['admin_pass']) < 8) $errors[] = '管理员密码至少 8 位。';

    if (!$errors) {
        try {
            $host = $values['db_host'];
            $port = (int)$values['db_port'];
            $dbName = $values['db_name'];
            $charset = 'utf8mb4';

            $pdoRoot = new PDO("mysql:host={$host};port={$port};charset={$charset}", $values['db_user'], $values['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            if ($values['create_db'] === '1') {
                $safe = str_replace('`', '``', $dbName);
                $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$safe}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }

            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}", $values['db_user'], $values['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            foreach (split_sql(file_get_contents($sqlPath)) as $statement) {
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $stmt->execute([$values['admin_email']]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare('INSERT INTO users (email, username, password_hash, role, status) VALUES (?, ?, ?, "admin", "active")');
                $stmt->execute([
                    $values['admin_email'],
                    $values['admin_name'] ?: 'admin',
                    password_hash($values['admin_pass'], PASSWORD_DEFAULT)
                ]);
            }

            $config = [
                'app' => [
                    'name' => $values['app_name'] ?: 'Commercial Web Counter',
                    'base_url' => rtrim($values['base_url'], '/'),
                    'timezone' => 'Asia/Shanghai',
                    'allow_registration' => true,
                ],
                'db' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => $dbName,
                    'username' => $values['db_user'],
                    'password' => $values['db_pass'],
                    'charset' => 'utf8mb4',
                ],
                'security' => [
                    'cors_allowed_origins' => ['*'],
                    'cooldown_seconds' => max(0, (int)$values['cooldown']),
                    'trust_proxy_headers' => false,
                    'store_raw_ip' => $values['store_raw_ip'] === '1',
                    'visitor_hash_salt' => bin2hex(random_bytes(32)),
                    'session_name' => 'COUNTERSESSID',
                ],
            ];

            $content = "<?php\nreturn " . var_export($config, true) . ";\n";
            if (!is_writable($appDir)) {
                $errors[] = 'app 目录不可写，请手动创建 app/config.php：<pre>' . h($content) . '</pre>';
            } else {
                file_put_contents($configPath, $content, LOCK_EX);
                @chmod($configPath, 0640);
                $installed = true;
            }
        } catch (Throwable $e) {
            $errors[] = '安装失败：' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>安装向导 - Commercial Web Counter</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/layui/dist/css/layui.css">
  <style>body{background:#f5f7fb}.box{max-width:860px;margin:40px auto;background:#fff;padding:28px;border-radius:14px;box-shadow:0 10px 30px rgba(15,23,42,.08)}.hint{color:#64748b}</style>
</head>
<body>
<div class="box">
  <h1>Commercial Web Counter 安装向导</h1>
  <?php if ($installed): ?>
    <div class="layui-bg-green" style="padding:12px;border-radius:8px;color:#fff;margin:16px 0;">安装完成。请删除或禁用 <code>/install/</code> 目录，然后访问 <a style="color:#fff;text-decoration:underline;" href="/login.php">登录后台</a>。</div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="layui-bg-red" style="padding:12px;border-radius:8px;color:#fff;margin:16px 0;">
        <?php foreach ($errors as $e): ?><div><?= $e ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <form class="layui-form" method="post">
      <h2>系统信息</h2>
      <div class="layui-form-item"><label class="layui-form-label">系统名称</label><div class="layui-input-block"><input class="layui-input" name="app_name" value="<?= h($values['app_name']) ?>"></div></div>
      <div class="layui-form-item"><label class="layui-form-label">系统域名</label><div class="layui-input-block"><input class="layui-input" name="base_url" placeholder="https://counter.example.com" value="<?= h($values['base_url']) ?>" required><div class="hint">用于自动生成给用户复制的 JS 接入地址。</div></div></div>

      <h2>数据库</h2>
      <div class="layui-form-item"><label class="layui-form-label">Host</label><div class="layui-input-block"><input class="layui-input" name="db_host" value="<?= h($values['db_host']) ?>" required></div></div>
      <div class="layui-form-item"><label class="layui-form-label">Port</label><div class="layui-input-block"><input class="layui-input" name="db_port" value="<?= h($values['db_port']) ?>" required></div></div>
      <div class="layui-form-item"><label class="layui-form-label">Database</label><div class="layui-input-block"><input class="layui-input" name="db_name" value="<?= h($values['db_name']) ?>" required></div></div>
      <div class="layui-form-item"><label class="layui-form-label">Username</label><div class="layui-input-block"><input class="layui-input" name="db_user" value="<?= h($values['db_user']) ?>" required></div></div>
      <div class="layui-form-item"><label class="layui-form-label">Password</label><div class="layui-input-block"><input class="layui-input" type="password" name="db_pass" value="<?= h($values['db_pass']) ?>"></div></div>
      <div class="layui-form-item"><label class="layui-form-label">自动建库</label><div class="layui-input-block"><input type="checkbox" name="create_db" value="1" title="CREATE DATABASE IF NOT EXISTS" <?= $values['create_db']==='1'?'checked':'' ?>></div></div>

      <h2>管理员账号</h2>
      <div class="layui-form-item"><label class="layui-form-label">邮箱</label><div class="layui-input-block"><input class="layui-input" type="email" name="admin_email" value="<?= h($values['admin_email']) ?>" required></div></div>
      <div class="layui-form-item"><label class="layui-form-label">用户名</label><div class="layui-input-block"><input class="layui-input" name="admin_name" value="<?= h($values['admin_name']) ?>" required></div></div>
      <div class="layui-form-item"><label class="layui-form-label">密码</label><div class="layui-input-block"><input class="layui-input" type="password" name="admin_pass" required></div></div>

      <h2>统计策略</h2>
      <div class="layui-form-item"><label class="layui-form-label">防刷间隔</label><div class="layui-input-block"><input class="layui-input" name="cooldown" value="<?= h($values['cooldown']) ?>"><div class="hint">同一访客同一页面 N 秒内不重复计数；0 表示关闭。</div></div></div>
      <div class="layui-form-item"><label class="layui-form-label">保存 IP</label><div class="layui-input-block"><input type="checkbox" name="store_raw_ip" value="1" title="保存原始访问 IP" <?= $values['store_raw_ip']==='1'?'checked':'' ?>></div></div>

      <div class="layui-form-item"><div class="layui-input-block"><button class="layui-btn layui-btn-lg">开始安装</button></div></div>
    </form>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/layui/dist/layui.js"></script>
</body>
</html>
