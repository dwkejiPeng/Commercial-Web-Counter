# Commercial-Web-Counter

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1)](https://www.mysql.com/)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.2%2B-003545)](https://mariadb.org/)
[![Nginx](https://img.shields.io/badge/Nginx-PHP--FPM-009639)](https://nginx.org/)

一个基于 **PHP + MySQL + 原生 JavaScript SDK** 的多租户网页访问计数系统。

本项目适合作为个人网站、博客、内容站、SaaS 后台或商业化统计服务的基础版本。它提供用户注册、站点管理、JS 接入代码生成、访问量统计、访问日志、管理员后台等功能。

> 当前版本定位为可运行的 MVP。正式用于生产环境前，请根据业务需要补充安全、风控、隐私合规和性能优化能力。

## 仓库地址

```bash
git clone https://github.com/dwkejiPeng/Commercial-Web-Counter.git
cd Commercial-Web-Counter
```

---

## 目录

- [功能特性](#功能特性)
- [仓库地址](#仓库地址)
- [技术栈](#技术栈)
- [运行环境](#运行环境)
- [目录结构](#目录结构)
- [快速开始](#快速开始)
- [Nginx 配置要点](#nginx-配置要点)
- [安装向导](#安装向导)
- [用户接入方式](#用户接入方式)
- [SDK 参数](#sdk-参数)
- [API 文档](#api-文档)
- [统计口径](#统计口径)
- [日志清理](#日志清理)
- [隐私与合规建议](#隐私与合规建议)
- [安全建议](#安全建议)
- [Roadmap](#roadmap)
- [贡献指南](#贡献指南)
- [License](#license)

---

## 功能特性

### 用户侧功能

- 用户注册、登录、退出
- 创建和管理需要接入计数的网站
- 自动生成站点唯一 `site_key`
- 选择计数展示模式和主题样式
- 生成可复制的 JavaScript 接入代码
- 自动检测网站是否完成接入
- 查看访问统计数据：
  - 总 PV
  - 今日 PV / UV
  - 页面排行
  - 访问 IP
  - Referer
  - User-Agent
  - 最近访问日志

### 管理员侧功能

- 管理员总览全部数据
- 查看所有用户
- 启用 / 禁用用户
- 设置用户为管理员 / 普通用户
- 查看所有站点
- 启用 / 禁用站点
- 查看全站访问日志

---

## 技术栈

| 模块 | 技术 |
| --- | --- |
| 前端 SDK | 原生 JavaScript |
| 后台界面 | PHP + layui CDN |
| 后端 | PHP + PDO |
| 数据库 | MySQL 5.7+ / MariaDB 10.2+ |
| Web Server | Nginx + PHP-FPM |

---

## 运行环境

建议环境：

- PHP 7.4+
- MySQL 5.7+ 或 MariaDB 10.2+
- Nginx
- PHP-FPM
- PDO MySQL 扩展
- OpenSSL 扩展

请确认 PHP 已启用以下扩展：

```bash
php -m | grep -E "pdo_mysql|openssl|json|mbstring"
```

---

## 目录结构

```text
Commercial-Web-Counter/
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
├── LICENSE
└── README.md
```

---

## 快速开始

### 1. 上传源码

将项目上传到服务器目录，例如：

```bash
/var/www/Commercial-Web-Counter
```

### 2. 配置 Web 根目录

Nginx 的 `root` 必须指向项目的 `public` 目录：

```nginx
root /var/www/Commercial-Web-Counter/public;
```

### 3. 运行安装向导

访问：

```text
https://counter.example.com/install/
```

填写以下信息：

- 系统域名
- MySQL 连接信息
- 管理员邮箱和密码
- 防刷间隔
- 是否保存原始 IP

安装器会自动完成：

- 创建数据库表
- 创建管理员账号
- 生成 `app/config.php`

### 4. 删除安装目录

安装成功后，请立即删除安装目录：

```bash
rm -rf /var/www/Commercial-Web-Counter/public/install
```

---

## Nginx 配置要点

请确保 Nginx 的站点配置将根目录指向 `public`：

```nginx
server {
    listen 80;
    server_name counter.example.com;

    root /var/www/Commercial-Web-Counter/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
```

检查并重载 Nginx：

```bash
sudo nginx -t
sudo systemctl reload nginx
```

> `fastcgi_pass` 路径需要根据服务器的 PHP-FPM 版本调整。

---

## 安装向导

安装页面地址：

```text
https://counter.example.com/install/
```

安装向导会生成核心配置文件：

```text
app/config.php
```

如需手动配置，可参考：

```text
app/config.sample.php
```

---

## 用户接入方式

用户登录后台后：

1. 进入「我的站点」
2. 填写站点名称和网址
3. 系统自动生成站点 Key
4. 进入「接入代码」页面
5. 复制 JavaScript 代码到自己的网站
6. 访问一次接入页面
7. 点击「检测接入状态」

示例代码：

```html
<span id="counter-box"></span>
<script
  src="https://counter.example.com/counter.js"
  data-key="系统生成的32位key"
  data-target="#counter-box"
  data-mode="text"
  data-theme="light"
  data-label="访问量"></script>
```

---

## SDK 参数

| 参数 | 是否必填 | 默认值 | 说明 |
| --- | --- | --- | --- |
| `data-key` | 是 | - | 站点 Key |
| `data-target` | 否 | - | 渲染目标 CSS 选择器 |
| `data-mode` | 否 | `text` | 展示模式：`text` / `number` / `badge` / `hidden` / `custom` |
| `data-theme` | 否 | `light` | 主题：`light` / `dark` / `primary` / `custom` |
| `data-label` | 否 | `访问量` | 显示文案 |
| `data-page-key` | 否 | 当前页面 URL | 自定义页面唯一标识 |
| `data-auto` | 否 | `true` | 是否自动计数 |
| `data-lazy` | 否 | `false` | 是否延迟计数 |
| `data-custom-css` | 否 | - | 自定义 CSS，仅在 `data-theme="custom"` 时使用 |

---

## API 文档

### 计数并返回结果

```http
GET /api/collect.php?key=site_key&page_url=https%3A%2F%2Fexample.com%2Fpost
```

响应示例：

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

{
  "page_keys": ["page_1", "page_2"]
}
```

---

## 统计口径

- `PV`：通过防刷规则后实际计数的访问量
- `UV`：同一天、同一访客 Hash、同一页面的首次访问
- 防刷规则：同一访客 Hash + 同一页面在 `cooldown_seconds` 秒内不重复计数
- 访客 Hash：`IP + User-Agent + salt` 的 SHA-256 值
- 原始 IP：是否保存由安装时的 `store_raw_ip` 配置控制

---

## 日志清理

保留最近 90 天访问日志：

```bash
php /var/www/Commercial-Web-Counter/tools_cleanup_visit_logs.php 90
```

Cron 示例：

```cron
0 3 * * * php /var/www/Commercial-Web-Counter/tools_cleanup_visit_logs.php 90 >/dev/null 2>&1
```

---

## 隐私与合规建议

访问 IP、User-Agent、Referer 等信息可能属于个人信息或可识别信息。正式上线前建议：

1. 在接入方网站隐私政策中说明统计用途
2. 明确数据保留周期
3. 启用日志自动清理机制
4. 如非必须，关闭原始 IP 存储，仅保留 Hash
5. 向客户提供数据删除或导出机制
6. 根据实际服务地区评估 GDPR、CCPA、PIPL 等合规要求

---

## 安全建议

生产环境部署前，建议至少完成以下增强：

- 删除 `public/install` 安装目录
- 使用 HTTPS
- 为管理员账号启用强密码策略
- 限制后台登录失败次数
- 增加 CSRF 防护
- 增加 API 请求频控
- 对关键配置文件设置合理权限
- 定期备份数据库
- 定期清理访问日志
- 根据需要接入二次验证

---

## Roadmap

计划或可扩展方向：

- [ ] 套餐与计费
- [ ] 用户站点数量限制
- [ ] API 请求频控
- [ ] Redis 缓冲计数
- [ ] GeoIP 地域统计
- [ ] 设备 / 浏览器解析
- [ ] 图表可视化
- [ ] CSV 导出
- [ ] 操作审计日志
- [ ] 客户端 Token 签名
- [ ] 管理员二次验证
- [ ] Docker / Docker Compose 部署
- [ ] 多语言支持

---

## 贡献指南

欢迎提交 Issue 和 Pull Request。

建议流程：

1. Fork 本仓库
2. 创建功能分支

   ```bash
   git checkout -b feature/your-feature-name
   ```

3. 提交代码

   ```bash
   git commit -m "feat: add your feature"
   ```

4. 推送分支

   ```bash
   git push origin feature/your-feature-name
   ```

5. 创建 Pull Request

提交前请尽量确保：

- 代码风格清晰
- 不提交敏感配置
- 不提交真实数据库密码、密钥或用户数据
- 新增功能附带必要说明

---

## License

本项目基于 [MIT License](LICENSE) 开源。

你可以自由使用、复制、修改、合并、发布、分发、再授权及销售本项目的副本，但需保留原始版权声明和许可声明。
