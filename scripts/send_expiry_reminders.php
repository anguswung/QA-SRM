<?php
/**
 * 到期报告邮件提醒 —— 计划任务入口。
 * 用法（命令行）：
 *   php scripts/send_expiry_reminders.php
 * 忽略「启用」开关强制发送：
 *   php scripts/send_expiry_reminders.php --force
 */
if (PHP_SAPI !== 'cli') { echo "请通过命令行运行。\n"; exit(1); }

$root = dirname(__DIR__);
$config = require $root . '/config/app.php';
require_once $root . '/app/helpers.php';

spl_autoload_register(function ($class) use ($root) {
    foreach (['controllers', 'models', 'core'] as $dir) {
        $file = "$root/app/$dir/$class.php";
        if (file_exists($file)) { require $file; return; }
    }
});

date_default_timezone_set($config['timezone'] ?? 'Asia/Shanghai');

$force = in_array('--force', $argv, true);

try {
    $mailer = new Mailer();
    $res = $mailer->sendExpiryReminders($force);
    printf(
        "到期提醒: 报告 %d 份, 成功 %d, 跳过 %d, 失败 %d\n",
        $res['reports'], $res['sent'], $res['skipped'], $res['failed']
    );
    foreach ($res['errors'] as $e) {
        echo '  - ' . $e . "\n";
    }
    if ($res['failed'] > 0) exit(1);
    exit(0);
} catch (\Throwable $e) {
    echo '发送异常: ' . $e->getMessage() . "\n";
    exit(1);
}