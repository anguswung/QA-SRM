# 绿色产品管理系统 GPM

> 供应商合规管理平台，涵盖物料管理、均质材料管控、RoHS/REACH/HF/MSDS 合规分析、供应商评价、检测报告审核等工作流。

**技术栈**: PHP 8.0+ (自定义 MVC) + Bootstrap 4 + jQuery + SQLite3/MySQL

---

## 功能模块

| 模块 | 说明 |
|------|------|
| 仪表盘 | 数据统计卡片、合规率趋势图、最近预警 |
| 物料管理 | 多步骤向导创建、成分管理（均质材料）、审核流程、批量提交审核 |
| 均质材料 | 共用库管理、成分分析、4种独立报告（RoHS/REACH/HF/MSDS） |
| 检测报告 | 上传、PDF解析（pdftotext/Tesseract OCR）、元素数据、版本控制 |
| 合规分析 | 自动比对检测值与法规限值，判定合规/不合规/待分析 |
| BOM管理 | 产品结构管理、物料清单、变更记录 |
| 法规管理 | RoHS/REACH/HF/CA65 等法规库维护、条款与限值管理 |
| 物质库 | CAS号、分子式、受控状态管理 |
| 供应商管理 | 供应商信息、评级、黑名单 |
| 问卷管理 | 创建问卷、供应商回复、审核 |
| 通知公告 | 富文本编辑（Quill.js）、已读追踪 |
| 预警系统 | 物质超标预警、报告到期提醒 |
| 系统管理 | 用户/角色/权限（RBAC）、数据字典、操作日志 |
| 查询报表 | 物料查询、过期报告查询、操作日志查询 |

## 用户角色

| 角色 | 职责 | 数据范围 |
|------|------|----------|
| 超级管理员 | 系统配置、用户管理、全量数据 | all |
| 合规工程师 | 法规管理、合规分析、报告审核 | all |
| 质量工程师 | 物料管理、报告上传 | self |
| 供应商 | 上传检测报告、提交物料审核 | supplier |
| 只读用户 | 查看数据、导出报表 | 按配置 |

## 实际技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端语言 | PHP 8.0+ | 自定义 MVC 框架（非 Laravel/Symfony） |
| 数据库 | SQLite3（开发）/ MySQL 8.0+（生产） | PDO 封装（`DB.php`），双库兼容 |
| 前端渲染 | PHP 服务端渲染 | 无前端框架，无 SPA |
| UI 库 | Bootstrap 4.6 + Font Awesome 6 | 响应式布局 |
| JavaScript | jQuery 3.7 + DataTables + Chart.js | 表格增强、图表 |
| 富文本编辑器 | Quill.js 1.3.7 | 通知/问卷内容编辑 |
| API 认证 | JWT 双 Token（access 2h + refresh 7d） | 自定义 HS256 实现 |
| 页面认证 | Session + Cookie | PHP 原生 session |
| PDF 解析 | smalot/pdfparser + Poppler pdftotext | 文本 PDF 提取 |
| OCR | Tesseract 5.5.3（chi_sim + eng） | 扫描 PDF 兜底识别 |
| Excel | PhpSpreadsheet | 数据导入导出 |
| 依赖管理 | Composer 2.x | 自动加载 |

## 快速开始

### 环境要求

- PHP 8.0+（CLI + FPM）
- SQLite3 扩展（开发）/ MySQL 8.0+（生产）
- Composer 2.x
- Poppler（可选，PDF 解析增强）
- Tesseract OCR（可选，扫描件识别）

### 安装

```bash
# 克隆仓库
git clone <仓库地址>
cd QA-SRM

# 安装 PHP 依赖
composer install

# 复制环境配置
cp .env.example .env
# 编辑 .env 配置数据库连接等

# 启动开发服务器
php -S localhost:8080 -t public
```

访问 `http://localhost:8080`，默认管理员：`admin / admin123`

### Windows 一键启动

```powershell
php -S 0.0.0.0:8080 -t public
```

## 项目结构

```
QA-SRM/
├── app/
│   ├── api/                   # API 层（Auth/Request/Response）
│   ├── controllers/           # Web 控制器（19个）
│   │   └── Api/               # API 控制器（22个）
│   ├── core/                  # Controller 基类
│   ├── helpers.php            # 全局辅助函数
│   ├── middleware/             # 中间件（RateLimit）
│   ├── models/                # 业务引擎（DB/Compliance/Warning/Bpm）
│   ├── services/              # 服务层（ExportService）
│   └── views/                 # PHP 视图模板（20个模块，51个视图）
│       └── layouts/           # 布局文件（main/sidebar/topbar）
├── config/                    # 配置（app.php + permissions.php）
├── database/                  # SQLite 数据库文件
├── docs/                      # 文档
├── poppler/                   # Poppler PDF 工具
├── public/                    # Web 入口
│   ├── index.php              # 路由器
│   ├── api_routes.php         # API 路由定义
│   ├── assets/                # 静态资源
│   │   ├── css/               # app.css, icomoon.css
│   │   ├── js/                # app.js
│   │   └── vendor/            # bootstrap/jquery/datatables/chartjs/quill/toastr
│   ├── download.php           # 安全文件下载
│   ├── upload-image.php       # 图片上传（Quill编辑器）
│   └── report-recognize.php   # PDF/OCR识别
├── scripts/                   # 部署/数据库脚本
├── uploads/                   # 用户上传文件
├── vendor/                    # Composer 依赖
├── tesseract/                 # Tesseract OCR 引擎
├── 源文件/                    # 原始规划文档
└── README.md                  # 本文件
```

## API 接口

所有 API 统一前缀 `/api/v1/`，返回统一 JSON 格式：

```json
{ "code": 0, "msg": "success", "data": { ... }, "meta": { "page": 1, "limit": 20, "total": 156 } }
```

认证方式：`Authorization: Bearer <access_token>`

主要端点：

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/v1/auth/login | 登录（返回双 Token） |
| POST | /api/v1/auth/refresh | 刷新 Token |
| GET | /api/v1/auth/me | 当前用户信息 |
| GET/POST | /api/v1/materials | 物料列表/创建 |
| GET | /api/v1/material/{id} | 物料详情 |
| GET/POST | /api/v1/reports | 报告列表/创建 |
| GET/POST | /api/v1/suppliers | 供应商列表/创建 |
| GET/POST | /api/v1/regulations | 法规列表/创建 |
| GET | /api/v1/compliance | 合规分析 |
| GET | /api/v1/dashboard | 仪表盘数据 |
| POST | /api/v1/upload | 文件上传 |

完整路由见 `public/api_routes.php`。

## 生产部署

```bash
# 安装依赖
composer install --no-dev --optimize-autoloader

# 配置 .env（切换 MySQL）
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=qa_srm
DB_USER=root
DB_PASS=your_password

# Web 服务器配置（Apache .htaccess 或 Nginx + PHP-FPM）
```

详细部署步骤见 `docs/DEPLOY_LINUX.md`。

## License

MIT
