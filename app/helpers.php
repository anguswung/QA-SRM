<?php
if (!defined('HELPERS_LOADED')) {
define('HELPERS_LOADED', 1);
function h($str, $flags = ENT_QUOTES, $encoding = 'UTF-8'): string {
    return htmlspecialchars($str ?? '', $flags, $encoding);
}
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] !== 'supplier';
}
function isSupplier(): bool {
    return ($_SESSION['role'] ?? '') === 'supplier';
}
function userId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}
function userSupplierId(): int {
    return (int)($_SESSION['supplier_id'] ?? 0);
}
function userRealname(): string {
    return $_SESSION['realname'] ?? '';
}
function can($perm): bool {
    if (!isset($_SESSION['permissions'])) {
        loadUserPermissions($_SESSION['role'] ?? '');
    }
    $perms = $_SESSION['permissions'] ?? [];
    return in_array('*', $perms) || in_array($perm, $perms);
}
function loadUserPermissions($roleName): void {
    $perms = DB::getInstance()->single("SELECT permissions FROM roles WHERE name=?", [$roleName], false);
    $_SESSION['permissions'] = $perms ? json_decode($perms, true) : [];
}
function stdOfficialLimits($type): array {
    $types = [
        'RoHS' => [
            ['substance_name'=>'铅（Pb）','cas_no'=>'7439-92-1','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'汞（Hg）','cas_no'=>'7439-97-6','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'镉（Cd）','cas_no'=>'7440-43-9','limit_value'=>100,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'六价铬（Cr VI）','cas_no'=>'18540-29-9','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'多溴联苯（PBBs）','cas_no'=>'59080-40-9','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'多溴二苯醚（PBDEs）','cas_no'=>'32534-81-9','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'邻苯二甲酸二正丁酯（DBP）','cas_no'=>'84-74-2','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'邻苯二甲酸丁基苄酯（BBP）','cas_no'=>'85-68-7','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'邻苯二甲酸二(2-乙基己)酯（DEHP）','cas_no'=>'117-81-7','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'邻苯二甲酸二异丁酯（DIBP）','cas_no'=>'84-69-5','limit_value'=>1000,'limit_unit'=>'mg/kg'],
        ],
        'REACH' => [
            ['substance_name'=>'铅（Pb）','cas_no'=>'7439-92-1','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'汞（Hg）','cas_no'=>'7439-97-6','limit_value'=>1000,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'镉（Cd）','cas_no'=>'7440-43-9','limit_value'=>100,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'六价铬（Cr VI）','cas_no'=>'18540-29-9','limit_value'=>1000,'limit_unit'=>'mg/kg'],
        ],
        'HF' => [
            ['substance_name'=>'氟（F）','cas_no'=>'7782-41-4','limit_value'=>900,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'氯（Cl）','cas_no'=>'7782-50-5','limit_value'=>900,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'溴（Br）','cas_no'=>'7726-95-6','limit_value'=>900,'limit_unit'=>'mg/kg'],
            ['substance_name'=>'碘（I）','cas_no'=>'7553-56-2','limit_value'=>900,'limit_unit'=>'mg/kg'],
        ],
    ];
    return $types[$type] ?? [];
}

function menuOpen($path): string {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return strpos($uri, $path) === 0 ? 'menu-open' : '';
}
function uploadPath($subdir = ''): string {
    $base = rtrim((require __DIR__ . '/../config/app.php')['upload']['path'], '/');
    $resolved = realpath($base);
    if ($resolved !== false) $base = $resolved;
    if ($subdir) $base .= '/' . ltrim($subdir, '/');
    if (!is_dir($base)) mkdir($base, 0755, true);
    return $base;
}
function fileUrl($filePath): string {
    $path = str_replace('\\', '/', (string)$filePath);
    // 只暴露相对 uploads 的路径，避免在 URL 中泄露服务器绝对路径
    $uploadsBase = str_replace('\\', '/', realpath(__DIR__ . '/../uploads'));
    if ($uploadsBase && strpos($path, $uploadsBase) === 0) {
        $path = substr($path, strlen($uploadsBase));
    }
    $path = ltrim($path, '/');
    if ($path === '') { return ''; }
    return '/download.php?path=' . rawurlencode($path);
}
function logOp($action, $module, $targetId, $detail) {
    $db = DB::getInstance();
    $db->execute(
        "INSERT INTO operation_log (user_id, action, module, target_type, target_id, detail, ip_address, created_at) VALUES (?,?,?,?,?,?,?,datetime('now','localtime'))",
        [$_SESSION['user_id'] ?? 0, $action, $module, $module, (int)$targetId, $detail, $_SERVER['REMOTE_ADDR'] ?? '']
    );
}
function flash($type, $message): void {
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}
function priorityBadge($priority): string {
    if ($priority >= 2) return '<span class="badge badge-danger">高优先级</span>';
    if ($priority == 1) return '<span class="badge badge-warning text-white">紧急</span>';
    return '<span class="badge badge-secondary">普通</span>';
}
function statusBadge($status): string {
    $map = [
        'pending' => ['info', '待处理'], 'draft' => ['info', '草稿'],
        'published' => ['success', '已发布'], 'superseded' => ['secondary', '已废弃'],
        'compliant' => ['success', '合规'], 'non_compliant' => ['danger', '不合规'],
        'N' => ['info', '待提交'], 'S' => ['warning', '已提交'], 'A' => ['success', '已认证'],
        'R' => ['danger', '已驳回'], 'Valid' => ['success', '有效'],
        'Invalid' => ['danger', '无效'], 'Obsolete' => ['secondary', '废弃'],
        'active' => ['success', '启用'], 'inactive' => ['secondary', '停用'],
        'approved' => ['success', '已通过'], 'rejected' => ['danger', '已退回'],
        'current' => ['success', '有效'], 'superseded' => ['secondary', '已废弃'],
        'resolved' => ['success', '已处理'], 'yellow' => ['warning', '黄色预警'],
        'orange' => ['warning', '橙色预警'], 'red' => ['danger', '红色预警'],
        'A_' => ['success', 'A级'], 'B_' => ['warning', 'B级'], 'C_' => ['info', 'C级'],
        'completed' => ['success', '已完成'], 'terminated' => ['secondary', '已终止'],
    ];
    $m = $map[$status] ?? ['secondary', $status];
    return '<span class="status-tag ' . $m[0] . '">' . h($m[1]) . '</span>';
}
function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    // 保留最近 5 个 token（解决后退/旧标签页提交时报 419 的问题）
    $_SESSION['_csrf_tokens'] = array_values(array_unique(
        array_merge([$_SESSION['_csrf_token']], $_SESSION['_csrf_tokens'] ?? [])
    ));
    if (count($_SESSION['_csrf_tokens']) > 5) {
        $_SESSION['_csrf_tokens'] = array_slice($_SESSION['_csrf_tokens'], 0, 5);
    }
    return $_SESSION['_csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}
function verify_csrf(): void {
    $token = $_POST['_csrf_token'] ?? '';
    $expected = $_SESSION['_csrf_token'] ?? '';
    $history = $_SESSION['_csrf_tokens'] ?? [];
    if (!$token || (!$expected && !$history)) {
        http_response_code(419);
        csrf_expired_page();
    }
    if ($token && $expected && hash_equals($expected, $token)) return;
    foreach ($history as $t) {
        if (hash_equals($t, $token)) return;
    }
    http_response_code(419);
    csrf_expired_page();
}

function csrf_expired_page(): void {
    $back = htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '/', ENT_QUOTES, 'UTF-8');
    die('<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>表单已过期</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#f3f5f9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}'
        . '.box{background:#fff;border-radius:12px;padding:40px 48px;box-shadow:0 4px 24px rgba(0,0,0,.08);text-align:center;max-width:420px}'
        . 'h1{font-size:20px;color:#1d2733;margin:0 0 8px}p{color:#667382;font-size:14px;margin:0 0 24px}'
        . 'a{display:inline-block;background:#0054a6;color:#fff;padding:9px 20px;border-radius:8px;text-decoration:none;font-size:14px}'
        . 'a:hover{background:#003d7a}</style></head><body><div class="box">'
        . '<h1>表单已过期</h1><p>页面停留时间过长或会话已更新，请刷新页面后重试。</p>'
        . '<a href="' . $back . '">刷新页面</a></div></body></html>');
}

/* ===== 列表行渲染公共单元（封装常用 HTML 片段，统一风格） ===== */

/** ID 单元格：<td class="col-id">值</td> */
function colId($v): string {
    return '<td class="col-id">' . h($v) . '</td>';
}

/** 软徽章：<span class="badge badge-soft-颜色">文字</span>，颜色默认 info */
function badge($text, $color = 'info'): string {
    return '<span class="badge badge-soft-' . $color . '">' . h($text) . '</span>';
}

function canManage($perm): bool {
    return isAdmin() && can($perm);
}
}
