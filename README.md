# 绿色产品管理系统 GPM

> 供应商合规管理平台，涵盖物料管理、均质材料管控、RoHS/REACH/HF/MSDS 合规分析、供应商评价、检测报告审核等工作流。

**技术栈**: PHP 8.0+（自定义 MVC）+ SQLite3/MySQL + Tabler（Bootstrap 5）+ jQuery

---

## 功能模块

| 模块 | 说明 |
|------|------|
| 仪表盘 | 数据统计卡片、合规率趋势图、预警汇总一屏总览 |
| 物料管理 | 物料创建/编辑、成分管理、批量导入、批量提交审核、审核流与变更记录 |
| 均质材料 | 共用库管理、成分分析、RoHS/REACH/HF/MSDS 四种独立报告 |
| 检测报告 | 上传、PDF/Excel 解析（Poppler / Tesseract OCR / PhpSpreadsheet）、元素数据、版本控制、审核 |
| 合规分析 | 自动比对检测值与法规限值，判定合规/不合规/待分析，支持人工设定与豁免管理 |
| 法规管理 | RoHS/REACH/HF 等法规库维护、条款与限值管理、优先关注 |
| 物质库 | CAS 号、分子式、受控状态管理，支持在线物质检索（PubChem / ichemistry） |
| 供应商管理 | 供应商档案、联系人、信用评级、黑名单，供应商可自助查看评级 |
| 任务中心 | 我的任务、BPM 流程引擎（模板 / 节点 / 实例 / 流转代理） |
| 合规规则 | 合规规则库维护与规则检查 |
| 预警中心 | 报告到期、物质超标、豁免到期预警，黄 / 橙 / 红分级 |
| 查询报表 | 物料查询、到期检测查询、变更历史追踪 |
| 通知公告 | 富文本编辑（Quill.js）、附件、已读追踪 |
| 问卷调查 | 创建问卷、供应商回复、回收统计、审核 |
| 系统管理 | 用户、角色、系统参数、操作日志、内部标准限值 |

## 用户角色

角色在 `scripts/schema.sql` 中预置，权限点配置见 `config/permissions.php`：

| 角色 | 权限说明 |
|------|----------|
| 系统管理员（admin） | 全部权限，系统配置、用户 / 角色管理、全量数据 |
| 质量管理员（quality_manager） | 物料 / 报告审核、合规查看、供应商查看 |
| 环保专员（env_specialist） | 物质库 / 法规库维护、合规分析 |
| 采购员（purchaser） | 物料与报告查看 |
| 供应商（supplier） | 仅本人数据（物料、报告） |

## 实际技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端语言 | PHP 8.0+ | 自定义 MVC 框架（非 Laravel/Symfony），类映射自动加载 |
| 数据库 | SQLite3（开发）/ MySQL 8.0+（生产） | PDO 封装（`DB.php`），SQL 方言自动转换，SQLite 首次连接自动建表 |
| 前端渲染 | PHP 服务端渲染 | 无前端框架，无 SPA |
| UI 库 | Tabler（内置 Bootstrap 5.3）+ Font Awesome 6 | 响应式布局 |
| JavaScript | jQuery 3.7 + DataTables + Chart.js + toastr | 表格增强、图表、消息提示 |
| 富文本编辑器 | Quill.js | 通知 / 问卷内容编辑 |
| 页面认证 | Session + Cookie | 原生 PHP session，登录限流（5 次/分钟/IP）与登录日志 |
| 安全 | RBAC 权限、CSRF 令牌（`/api/csrf-token`）、安全响应头 | 敏感操作写操作日志 |
| PDF 解析 | Poppler pdftotext + smalot/pdfparser 兜底 | 文本 PDF 提取 |
| OCR | Tesseract（chi_sim + eng） | 扫描 PDF 兜底识别 |
| Excel | PhpSpreadsheet | 检测报告元素数据导入（xlsx / xls） |
| 依赖管理 | Composer 2.x | 自动加载 |

## 快速开始

### 环境要求

- PHP 8.0+（CLI + FPM），需扩展：pdo_sqlite / sqlite3 / mbstring / json / fileinfo（MySQL 场景另需 pdo_mysql）
- Composer 2.x
- Poppler / Tesseract（可选，仓库已内置 Windows 版本；缺省时 PDF 走 smalot/pdfparser 兜底）

### 安装

```bash
git clone <仓库地址>
cd QA-SRM

# 安装 PHP 依赖
composer install

# 复制环境配置
cp .env.example .env
# 编辑 .env 配置数据库、Poppler、Tesseract 路径等

# 启动开发服务器
php -S localhost:8080 -t public
```

访问 `http://localhost:8080`，默认管理员：`admin / 123456`。
首次登录会强制修改密码（至少 8 位且同时包含字母和数字）。
SQLite 模式下首次访问自动执行 `scripts/schema.sql` 建表，并写入初始角色与管理员账号。

### Windows 一键启动

```powershell
php -S 0.0.0.0:8080 -t public
```

## 常用入口

| 入口 | 说明 |
|------|------|
| `/api/csrf-token`（GET） | 前端获取 CSRF Token |
| `public/substance-lookup.php` | 物质库检索（本地库 → 内置常见物质表 → ichemistry.cn → PubChem，需 curl 与外网） |
| `public/report-recognize.php` | 检测报告 PDF / Excel 解析识别 |
| `public/upload-image.php` | 图片上传（Quill 编辑器） |
| `public/download.php` | 安全文件下载 |

## 项目结构

```
QA-SRM/
├── app/
│   ├── controllers/           # Web 控制器（17 个）
│   ├── core/                  # Controller 基类（渲染 / 权限 / 分页）
│   ├── helpers.php            # 全局辅助函数
│   ├── models/                # 业务引擎（DB/Compliance/Rule/Bpm/Warning/QueryRegistry/Mailer）
│   └── views/                 # PHP 视图模板（51 个视图，15 个模块）
│       └── layouts/           # 布局文件（main/sidebar/topbar）
├── config/                    # 配置（app.php + permissions.php）
├── database/                  # SQLite 数据库文件（首次访问自动初始化）
├── poppler/                   # Poppler PDF 工具（pdftotext / pdftoppm）
├── public/                    # Web 入口（文档根目录）
│   ├── index.php              # 路由器（34 条常规路由 + 5 条正则路由）
│   ├── assets/                # 静态资源（css/js/fonts/vendor）
│   ├── download.php           # 安全文件下载
│   ├── upload-image.php       # 图片上传（Quill 编辑器）
│   ├── report-recognize.php   # 检测报告 PDF/OCR/Excel 识别
│   └── substance-lookup.php   # 物质库检索接口
├── scripts/                   # deploy.php / schema.sql / seed_test_data.php / send_expiry_reminders.php
├── tesseract/                 # Tesseract OCR 引擎（chi_sim + eng）
├── uploads/                   # 用户上传文件
├── vendor/                    # Composer 依赖
├── 源文件/                    # 本地规划与模板文档（已 gitignore，不入仓）
├── 绿色产品合规系统-功能与收益说明.html  # 宣传文档
└── README.md                  # 本文件
```

## 脚本说明

- `php scripts/deploy.php`：部署自检（PHP 版本、扩展、目录权限、Poppler、数据库）并输出 Nginx 配置参考
- `php scripts/send_expiry_reminders.php`：检测报告到期邮件提醒（配合计划任务 / cron 使用）
- `php scripts/seed_test_data.php`：写入演示用供应商与均质材料测试数据

## 生产部署

```bash
# 安装依赖
composer install --no-dev --optimize-autoloader

# 配置 .env（切换 MySQL，键名以 .env.example 为准）
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=qa_srm
DB_USERNAME=your_user
DB_PASSWORD=your_password

# 环境自检
php scripts/deploy.php
```

Web 服务器文档根目录指向 `public/`（Apache 使用 .htaccess 或 Nginx + PHP-FPM），Nginx 配置参考由 `deploy.php` 输出。

## License

MIT
