<?php
require_once __DIR__ . '/../../app/ui.php';
$user = require_login();
$siteId = (int)getv('id');
$site = site_for_user($siteId, $user);
if (!$site) {
    http_response_code(404);
    exit('Site not found.');
}

$base = app_base_url() ?: (($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . $_SERVER['HTTP_HOST']);
$mode = $site['display_mode'];
$theme = $site['theme'];
$label = $site['display_label'];
$key = $site['site_key'];

$defaultCode = '<span id="counter-' . h($key) . '"></span>' . "\n" .
'<script src="' . h($base) . '/counter.js"' . "\n" .
'        data-key="' . h($key) . '"' . "\n" .
'        data-target="#counter-' . h($key) . '"' . "\n" .
'        data-mode="' . h($mode) . '"' . "\n" .
'        data-theme="' . h($theme) . '"' . "\n" .
'        data-label="' . h($label) . '"></script>';

$customCode = '<script src="' . h($base) . '/counter.js" data-key="' . h($key) . '" data-mode="custom"></script>' . "\n" .
'<script>' . "\n" .
'CounterSaaS.fetch(function(data){' . "\n" .
'  document.getElementById("visits").innerText = data.views;' . "\n" .
'});' . "\n" .
'</script>';

layui_header('接入代码', 'sites');
?>
<div class="main-card" style="margin-bottom:18px;">
  <h2><?= h($site['site_name']) ?> 接入信息</h2>
  <p>站点 Key：<code><?= h($key) ?></code></p>
  <p>状态：<?= status_badge($site['status']) ?>　最后检测：<?= h($site['last_seen_at'] ?: '-') ?></p>
  <p class="muted">把下面代码复制到你的网站页面中。用户访问后，系统会自动记录数据并将站点状态从“待检测”变为“已接入”。</p>
  <button class="layui-btn" id="checkBtn">检测接入状态</button>
  <a class="layui-btn layui-btn-primary" href="/dashboard/site_edit.php?id=<?= (int)$site['id'] ?>">修改样式</a>
</div>

<div class="main-card" style="margin-bottom:18px;">
  <h3>推荐接入代码</h3>
  <pre id="code1" class="codebox"><?= h($defaultCode) ?></pre>
  <button class="layui-btn layui-btn-sm" onclick="copyText('code1')">复制代码</button>
</div>

<div class="main-card" style="margin-bottom:18px;">
  <h3>高级自定义接入</h3>
  <p class="muted">适合完全自己渲染访问数的场景。</p>
  <pre id="code2" class="codebox"><?= h($customCode) ?></pre>
  <button class="layui-btn layui-btn-sm" onclick="copyText('code2')">复制代码</button>
</div>

<div class="main-card">
  <h3>预览</h3>
  <div id="preview-counter"></div>
  <script src="<?= h($base) ?>/counter.js"
          data-key="<?= h($key) ?>"
          data-target="#preview-counter"
          data-mode="<?= h($mode) ?>"
          data-theme="<?= h($theme) ?>"
          data-label="<?= h($label) ?>"
          data-auto="false"></script>
  <button class="layui-btn layui-btn-normal layui-btn-sm" onclick="CounterSaaS.fetch()">加载预览并计数</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  document.getElementById('checkBtn').addEventListener('click', function(){
    fetch('/dashboard/check_install.php?id=<?= (int)$site['id'] ?>')
      .then(r => r.json()).then(function(res){
        if(res.installed){
          layui.layer.alert('检测通过。最后访问时间：' + res.last_seen_at, {icon:1}, function(){ location.reload(); });
        }else{
          layui.layer.alert('暂未检测到接入。请确认代码已放到目标网站并访问一次页面。', {icon:0});
        }
      });
  });
});
</script>
<?php layui_footer(); ?>
