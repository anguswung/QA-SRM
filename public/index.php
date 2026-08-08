<?php
$config = require __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers.php';

spl_autoload_register(function ($class) {
    foreach (['controllers','models','core'] as $dir) {
        $file = __DIR__ . "/../app/$dir/$class.php";
        if (file_exists($file)) { require $file; return; }
    }
});

// Session hardening
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
date_default_timezone_set($config['timezone'] ?? 'Asia/Shanghai');

// Security response headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
header('X-Permitted-Cross-Domain-Policies: none');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/dashboard';

// Regex routes for detail/edit pages
$regexRoutes = [
    '#^/supplier/(\d+)/(edit|rating|detail)$#' => ['SupplierController', 'index'],
    '#^/bpm/(\d+)/detail$#' => ['BpmController', 'detail'],
    '#^/compliance/detail/(\d+)$#' => ['ComplianceController', 'detail'],
    '#^/regulation/(\d+)/detail$#' => ['RegulationController', 'index'],
    '#^/material/(\d+)/detail$#' => ['MaterialController', 'index'],
];
foreach ($regexRoutes as $pattern => [$ctrl, $method]) {
    if (preg_match($pattern, $uri, $m)) {
        if (!isset($_SESSION['user_id'])) { header('Location: /login'); exit; }
        $_GET['id'] = (int)$m[1];
        $parts = explode('/', trim($uri, '/'));
        $lastSeg = end($parts);
        if (in_array($lastSeg, ['detail', 'edit', 'rating', 'view'])) $_GET['action'] = $lastSeg;
        $obj = new $ctrl(); $obj->$method(); exit;
    }
}

// Route aliases
$routes = [
    '/login'            => ['AuthController', 'login'],
    '/logout'           => ['AuthController', 'logout'],
    '/password-change'  => ['AuthController', 'passwordChange'],
    '/dashboard'        => ['DashboardController', 'index'],
    '/material'         => ['MaterialController', 'index'],
    '/homogeneous'       => ['HomogeneousController', 'index'],
    '/report'           => ['ReportController', 'index'],
    '/report/audit'     => ['ReportController', 'audit'],
    '/compliance'       => ['ComplianceController', 'index'],
    '/compliance/analyze'  => ['ComplianceController', 'analyze'],
    '/compliance/detail'   => ['ComplianceController', 'detail'],
    '/compliance/manual'   => ['ComplianceController', 'manual'],
    '/compliance/exemption' => ['ComplianceController', 'exemption'],
    '/regulation'       => ['RegulationController', 'index'],
    '/regulation/focus' => ['RegulationController', 'focus'],
    '/substance'        => ['SubstanceController', 'index'],
    '/supplier'         => ['SupplierController', 'index'],
    '/supplier/rating'  => ['SupplierController', 'rating'],
    '/bpm'              => ['BpmController', 'index'],
    '/bpm/config'       => ['BpmController', 'config'],
    '/bpm/agent'        => ['BpmController', 'agent'],
    '/task'             => ['TaskController', 'index'],
    '/rule'             => ['RuleController', 'index'],
    '/notice'           => ['NoticeController', 'index'],
    '/survey'           => ['SurveyController', 'index'],
    '/warning'          => ['WarningController', 'index'],
    '/query/material'   => ['QueryController', 'material'],
    '/query/expired'    => ['QueryController', 'expired'],
    '/query/history'    => ['QueryController', 'history'],
    '/system/users'     => ['SystemController', 'users'],
    '/system/roles'     => ['SystemController', 'roles'],
    '/system/config'    => ['SystemController', 'config'],
    '/system/logs'      => ['SystemController', 'logs'],
    '/system/internal-standard' => ['SystemController', 'internalStandard'],
];

// Auth check
if ($uri !== '/login' && !isset($_SESSION['user_id'])) {
    header('Location: /login'); exit;
}


// CSRF token endpoint (fetch latest token before JS form submission)
if ($uri === '/api/csrf-token' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['token' => csrf_token()]);
    exit;
}

// CSRF protection for POST requests (non-API)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($uri, '/api/') !== 0) {
    verify_csrf();
}

// Blacklist check
if ($uri !== '/login' && isSupplier() && userSupplierId() > 0) {
    $sp = DB::getInstance()->single("SELECT blacklist FROM supplier WHERE id=?", [userSupplierId()], true);
    if ($sp && $sp['blacklist']) { session_destroy(); header('Location: /login?error=blacklist'); exit; }
}

// Force password change
if ($uri !== '/password-change' && $uri !== '/logout' && isset($_SESSION['user_id'])) {
    $u = DB::getInstance()->single("SELECT password_changed FROM users WHERE id=?", [$_SESSION['user_id']], true);
    if ($u && empty($u['password_changed'])) { header('Location: /password-change'); exit; }
}

// Route dispatch
if (isset($routes[$uri])) {
    [$ctrl, $action] = $routes[$uri];
    if (class_exists($ctrl)) {
        $obj = new $ctrl();
        if (method_exists($obj, $action)) { $obj->$action(); exit; }
    }
}

// Fallback
if ($uri === '/' || strpos($uri, '/index') !== false) {
    header('Location: /dashboard'); exit;
}


http_response_code(404);
$ctrl = new DashboardController();
$ctrl->render('errors/404', ['title' => '404']);

