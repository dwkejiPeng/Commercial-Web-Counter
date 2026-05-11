# Commercial Web Counter

这是按商业项目流程重构的多租户网页访问计数系统，技术栈为：

- 前端 SDK：原生 JavaScript
- 后台界面：PHP + layui CDN
- 后端：PHP + PDO
- 数据库：MySQL 5.7+ / MariaDB 10.2+
- Web Server：Nginx + PHP-FPM

## 功能清单

### 用户侧

1. 用户注册、登录、退出
2. 用户创建要接入计数的网站
3. 系统自动生成站点 `site_key`
4. 用户选择展示模式和样式
5. 系统生成可复制的 JS 接入代码
6. 用户把 JS 放到自己网站后，系统自动检测接入
7. 用户后台查看：
   - 总 PV
   - 今日 PV / UV
   - 页面排行
   - 访问 IP
   - Referer
   - User-Agent
   - 最近访问日志

### 管理员侧

1. 管理员总览全部数据
2. 查看所有用户
3. 启用 / 禁用用户
4. 设置用户为管理员 / 普通用户
5. 查看所有站点
6. 启用 / 禁用站点
7. 查看全站访问日志

## 目录结构

```text
commercial-web-counter/
├── app/
│   ├── bootstrap.php
│   ├── config.sample.php
│   └── ui.php
├── database/
│   └── install.sql
├── public/
│   ├── counter.js
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── api/
│   │   ├── collect.php
│   │   ├── get.php
│   │   └── batch.php
│   ├── dashboard/
│   │   ├── index.php
│   │   ├── sites.php
│   │   ├── site_edit.php
│   │   ├── integration.php
│   │   ├── check_install.php
│   │   ├── stats.php
│   │   └── logs.php
│   ├── admin/
│   │   ├── index.php
│   │   ├── users.php
│   │   ├── sites.php
│   │   └── logs.php
│   └── install/
│       └── index.php
├── nginx.example.conf
├── tools_cleanup_visit_logs.php
└── README.md
```

## 安装步骤

### 1. 上传源码

上传到：

```bash
/var/www/commercial-web-counter
```

### 2. 配置 Nginx

重点：Nginx root 必须指向 `public` 目录：

```nginx
root /var/www/commercial-web-counter/public;
```

参考 `nginx.example.conf`。

PHP-FPM 路径根据服务器调整：

```nginx
fastcgi_pass unix:/run/php/php8.2-fpm.sock;
```

检查并重载：

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 3. 运行安装向导

访问：

```text
https://counter.example.com/install/
```

填写：

- 系统域名
- MySQL 信息
- 管理员邮箱和密码
- 防刷间隔
- 是否保存原始 IP

安装器会自动：

- 创建数据库表
- 创建管理员账号
- 生成 `app/config.php`

### 4. 删除安装目录

安装成功后务必删除：

```bash
rm -rf /var/www/commercial-web-counter/public/install
```

## 用户接入流程

用户登录后台后：

1. 进入「我的站点」
2. 填写站点名称和网址
3. 系统自动生成 Key
4. 进入「接入代码」页面
5. 复制 JS 代码到自己网站
6. 访问一次接入页面
7. 点击「检测接入状态」

示例代码：

```html
<span id="counter-box"></span>
<script src="https://counter.example.com/counter.js"
        data-key="系统生成的32位key"
        data-target="#counter-box"
        data-mode="text"
        data-theme="light"
        data-label="访问量"></script>
```

## SDK 参数

| 参数 | 说明 |
| --- | --- |
| `data-key` | 站点 key，必填 |
| `data-target` | 渲染目标 CSS 选择器 |
| `data-mode` | `text` / `number` / `badge` / `hidden` / `custom` |
| `data-theme` | `light` / `dark` / `primary` / `custom` |
| `data-label` | 显示文案，例如“访问量” |
| `data-page-key` | 可选，自定义页面唯一标识 |
| `data-auto` | 是否自动计数，默认 `true` |
| `data-lazy` | 是否延迟计数，默认 `false` |
| `data-custom-css` | 自定义 CSS，仅 theme=custom 时使用明显 |

## API

### 计数并返回

```http
GET /api/collect.php?key=site_key&page_url=https%3A%2F%2Fexample.com%2Fpost
```

返回：

```json
{
  "code": 200,
  "message": "ok",
  "data": {
    "site_key": "xxx",
    "page_key": "xxx",
    "views": 12,
    "total_views": 100,
    "is_new": true
  }
}
```

### 只查询，不计数

```http
GET /api/get.php?key=site_key&page_url=https%3A%2F%2Fexample.com%2Fpost
```

### 批量查询

```http
POST /api/batch.php?key=site_key
Content-Type: application/json

{"page_keys":["page_1","page_2"]}
```

## 数据口径

- `PV`：通过防刷后实际计数的访问量
- `UV`：同一天同一访客 hash + 同一页面首次访问
- 防刷：同一访客 hash + 同一页面在 `cooldown_seconds` 秒内不重复计数
- 访客 hash：`IP + User-Agent + salt` 的 SHA-256
- 原始 IP：由安装时选项 `store_raw_ip` 控制是否保存

## 隐私与合规建议

这是商业项目，访问 IP 和 User-Agent 可能属于个人信息。正式上线前建议：

1. 在接入方网站隐私政策中说明统计用途
2. 给客户提供数据保留期说明
3. 设置日志自动清理
4. 如不必须，关闭原始 IP 存储，仅保留 hash
5. 对管理员账号启用更强的密码策略和二次验证

## 日志清理

保留最近 90 天访问日志：

```bash
php /var/www/commercial-web-counter/tools_cleanup_visit_logs.php 90
```

Cron 示例：

```cron
0 3 * * * php /var/www/commercial-web-counter/tools_cleanup_visit_logs.php 90 >/dev/null 2>&1
```
