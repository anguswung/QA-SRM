<?php if (function_exists('h')) return;
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
function userRole(): string {
    return $_SESSION['role'] ?? '';
}
function can($perm): bool {
    $perms = $_SESSION['permissions'] ?? [];
    return in_array('*', $perms) || in_array($perm, $perms);
}
function loadUserPermissions($roleName): void {
    $perms = DB::getInstance()->single("SELECT permissions FROM roles WHERE name=?", [$roleName], false);
    $_SESSION['permissions'] = $perms ? json_decode($perms, true) : [];
}
function routeActive($path): string {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return $uri === $path ? 'active' : '';
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
    return '/download.php?path=' . urlencode($filePath);
}
function logOp($action, $module, $targetId, $detail) {
    $db = DB::getInstance();
    $db->execute(
        "INSERT INTO operation_log (user_id, action, module, target_type, target_id, detail, ip_address, created_at) VALUES (?,?,?,?,?,?,?,datetime('now','localtime'))",
        [$_SESSION['user_id'] ?? 0, $action, $module, $module, (int)$targetId, $detail, $_SERVER['REMOTE_ADDR'] ?? '']
    );
}
function generateNo($prefix): string {
    return $prefix . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
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
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void {
    $token = $_POST['_csrf_token'] ?? '';
    $expected = $_SESSION['_csrf_token'] ?? '';
    if (!$token || !$expected || !hash_equals($expected, $token)) {
        http_response_code(419);
        die('CSRF token validation failed');
    }
}
