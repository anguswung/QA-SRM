# 绿色物料管理系统 GPM

> 基于 GDGroup 绿色产品合规性管理系统逆向分析，构建的供应商环保资料管理系统。

**技术栈**: Laravel 12 + Vue 3 + Element Plus + Inertia.js + SQLite3/MySQL

---

## 功能模块

| 模块 | 说明 |
|------|------|
| 法规管理 | RoHS/REACH/HF/CA65 等法规库维护、条款与限值管理 |
| 物质库 | CAS号、分子式、受控状态管理 |
| 物料管理 | 物料成分、供应商关联、合规状态追踪 |
| 报告管理 | 检测报告上传、版本控制、审核流程 |
| 合规分析 | 自动比对检测值与法规限值，判定合规性 |
| 供应商管理 | 供应商信息、评级、联系人管理 |
| 仪表盘 | 数据统计、预警提醒、合规率趋势 |
| 系统管理 | 用户/角色/权限（RBAC）、数据字典、操作日志 |

## 用户角色

| 角色 | 数据范围 |
|------|----------|
| 超级管理员 | 全部数据 |
| 合规工程师 | 全部数据 |
| 质量工程师 | 仅自己 |
| 供应商 | 仅本供应商 |
| 只读用户 | 按配置 |

## 快速开始

### 环境要求

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- SQLite3（开发）/ MySQL 8.0+（生产）

### 安装

```bash
# 克隆仓库
git clone https://github.com/<你的用户名>/QA-SRM.git
cd QA-SRM

# 安装 PHP 依赖
composer install

# 安装前端依赖
npm install

# 复制环境配置
cp .env.example .env

# 生成应用密钥
php artisan key:generate

# 初始化数据库
php artisan migrate --seed

# 构建前端
npm run build

# 启动开发服务器
php artisan serve
```

访问 `http://localhost:8000`，默认管理员：`admin / admin123`

## 项目结构

```
QA-SRM/
├── app/
│   ├── Http/Controllers/    # 控制器
│   ├── Models/              # Eloquent 模型
│   └── Services/            # 业务服务层
├── config/                  # 配置文件
├── database/                # 数据库迁移与填充
├── public/                  # 公共资源（入口）
├── resources/js/
│   ├── Pages/               # Vue 页面组件
│   ├── Components/          # 公共组件
│   └── Layouts/             # 布局组件
├── routes/                  # 路由定义
├── storage/                 # 文件存储
└── 源文件/                   # 原始规划文档
```

## 生产部署

```bash
# 安装依赖
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 配置 .env（切换为 MySQL）
# 设置数据库连接、Redis 缓存等

# 运行迁移
php artisan migrate --force

# 缓存配置
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

建议使用 Nginx + PHP-FPM + Supervisor 部署。

## License

MIT
