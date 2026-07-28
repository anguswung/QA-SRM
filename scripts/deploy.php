<?php
/**
 * 绿色产品管理系统GPM - VPS部署脚本
 * 使用方法: php scripts/deploy.php
 */
echo "=== 绿色产品管理系统GPM 部署脚本 ===\n\n";

// 1. Check PHP version
$phpVer = PHP_VERSION;
echo "[1/5] PHP版本检查 $phpVer\n";
if (version_compare($phpVer, '8.0', '<')) {
    echo "  [错误] 需要PHP 8.0+，当前版本 $phpVer\n";
    exit(1);
}
echo "  [通过]\n";

// 2. Check required extensions
$required = ['pdo_sqlite', 'sqlite3', 'mbstring', 'json', 'fileinfo'];
$missing = [];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) $missing[] = $ext;
}
echo "[2/5] PHP扩展检查 " . implode(', ', $required) . "\n";
if ($missing) {
    echo "  [错误] 缺少扩展: " . implode(', ', $missing) . "\n";
    exit(1);
}
echo "  [通过]\n";

// 3. Check directory permissions
$dirs = [
    __DIR__ . '/../database',
    __DIR__ . '/../uploads',
    __DIR__ . '/../storage/logs',
];
echo "[3/5] 目录权限检查\n";
foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!is_writable($dir)) {
        echo "  [错误] 目录不可写 $dir\n";
        exit(1);
    }
    echo "  [通过] $dir\n";
}

// 4. Check poppler
$config = require __DIR__ . '/../config/app.php';
$pdftotext = $config['poppler']['pdftotext'] ?? '';
echo "[4/5] PDF识别组件检查\n";
if ($pdftotext && file_exists($pdftotext)) {
    echo "  [通过] poppler pdftotext: $pdftotext\n";
} else {
    echo "  [警告] poppler pdftotext未找到，将使用smalot/pdfparser作为备用\n";
}

// 5. Initialize database
echo "[5/5] 数据库初始化\n";
if (!class_exists('DB')) require __DIR__ . '/../app/models/DB.php';
$db = DB::getInstance();
try {
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    echo "  [通过] 已创建" . count($tables) . " 个数据表\n";
} catch (Exception $e) {
    echo "  [错误] 数据库初始化失败: " . $e->getMessage() . "\n";
    exit(1);
}

// Print server config
echo "\n=== 部署信息 ===\n";
echo "PHP 版本: " . PHP_VERSION . "\n";
echo "SQLite 版本: " . $db->single("SELECT sqlite_version()") . "\n";
echo "文档根目录 " . realpath(__DIR__ . '/../public') . "\n";
echo "数据库路径 " . realpath(__DIR__ . '/../database/srm.db') . "\n";

echo "\n=== Nginx 配置参考 ===\n";
echo "server {\n";
echo "    listen 80;\n";
echo "    server_name your-domain.com;\n";
echo "    root " . str_replace('\\', '/', realpath(__DIR__ . '/../public')) . ";\n";
echo "    index index.php;\n\n";
echo "    location / {\n";
echo "        try_files \$uri \$uri/ /index.php?\$query_string;\n";
echo "    }\n\n";
echo "    location ~ \.php$ {\n";
echo "        fastcgi_pass unix:/var/run/php/php8.x-fpm.sock;\n";
echo "        fastcgi_index index.php;\n";
echo "        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n";
echo "        include fastcgi_params;\n";
echo "    }\n\n";
echo "    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|zip)$ {\n";
echo "        expires 30d;\n";
echo "    }\n";
echo "}\n\n";

echo "=== 部署完成 ===\n";
echo "默认管理员账号 admin / 123456\n";
