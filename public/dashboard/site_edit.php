<?php
require_once __DIR__ . '/../../app/ui.php';
$user = require_login();
$siteId = (int)getv('id');
$site = site_for_user($siteId, $user);
if (!$site) {
    http_response_code(404);
    exit('Site not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $displayMode = post('display_mode', 'text');
    $theme = post('theme', 'light');
    $allowedModes = ['text','number','badge','hidden','custom'];
    $allowedThemes = ['light','dark','primary','custom'];
    if (!in_array($displayMode, $allowedModes, true)) $displayMode = 'text';
    if (!in_array($theme, $allowedThemes, true)) $theme = 'light';

    $stmt = db()->prepare('UPDATE sites SET site_name=?, base_url=?, site_domain=?, display_mode=?, display_label=?, theme=?, custom_css=?, allowed_domains=? WHERE id=? AND user_id=?');
    $baseUrl = post('base_url');
    $domain = normalize_domain($baseUrl);
    $stmt->execute([
        post('site_name'),
        $baseUrl,
        $domain,
        $displayMode,
        post('display_label', '访问量'),
        $theme,
        (string)($_POST['custom_css'] ?? ''),
        (string)($_POST['allowed_domains'] ?? ''),
        $siteId,
        (int)$user['id']
    ]);
    flash('success', '站点配置已保存。');
    redirect('/dashboard/integration.php?id=' . $siteId);
}

layui_header('站点设置', 'sites');
?>
<div class="main-card">
  <h2 style="margin-bottom:14px;">站点设置：<?= h($site['site_name']) ?></h2>
  <form class="layui-form" method="post">
    <?= csrf_field() ?>
    <div class="layui-form-item"><label class="layui-form-label">站点名称</label><div class="layui-input-block"><input class="layui-input" name="site_name" value="<?= h($site['site_name']) ?>" required></div></div>
    <div class="layui-form-item"><label class="layui-form-label">网站地址</label><div class="layui-input-block"><input class="layui-input" name="base_url" value="<?= h($site['base_url']) ?>" required></div></div>
    <div class="layui-form-item"><label class="layui-form-label">显示方式</label><div class="layui-input-block">
      <select name="display_mode">
        <?php foreach (['text'=>'文字','number'=>'纯数字','badge'=>'徽章','hidden'=>'隐藏计数','custom'=>'自定义渲染'] as $k=>$v): ?>
          <option value="<?= h($k) ?>" <?= $site['display_mode']===$k?'selected':'' ?>><?= h($v) ?></option>
        <?php endforeach; ?>
      </select>
    </div></div>
    <div class="layui-form-item"><label class="layui-form-label">显示文案</label><div class="layui-input-block"><input class="layui-input" name="display_label" value="<?= h($site['display_label']) ?>"></div></div>
    <div class="layui-form-item"><label class="layui-form-label">主题</label><div class="layui-input-block">
      <select name="theme">
        <?php foreach (['light'=>'浅色','dark'=>'深色','primary'=>'蓝色','custom'=>'自定义 CSS'] as $k=>$v): ?>
          <option value="<?= h($k) ?>" <?= $site['theme']===$k?'selected':'' ?>><?= h($v) ?></option>
        <?php endforeach; ?>
      </select>
    </div></div>
    <div class="layui-form-item"><label class="layui-form-label">自定义 CSS</label><div class="layui-input-block"><textarea class="layui-textarea" name="custom_css" placeholder="例如：color:#e11d48;font-size:18px;"><?= h($site['custom_css']) ?></textarea></div></div>
    <div class="layui-form-item"><label class="layui-form-label">允许域名</label><div class="layui-input-block"><textarea class="layui-textarea" name="allowed_domains" placeholder="每行一个域名"><?= h($site['allowed_domains']) ?></textarea><div class="layui-form-mid layui-word-aux">系统会校验 Origin/Referer，建议填接入网站域名。</div></div></div>
    <div class="layui-form-item"><div class="layui-input-block"><button class="layui-btn">保存配置</button><a class="layui-btn layui-btn-primary" href="/dashboard/integration.php?id=<?= (int)$site['id'] ?>">返回接入页</a></div></div>
  </form>
</div>
<?php layui_footer(); ?>
