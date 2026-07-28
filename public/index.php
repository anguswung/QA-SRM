<?php
$config = require __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers.php';

spl_autoload_register(function ($class) {
    foreach (['controllers','models','core','api','controllers/Api','middleware','services'] as $dir) {
        $file = __DIR__ . "/../app/$dir/$class.php";
        if (file_exists($file)) { require $file; return; }
    }
});

session_start();
date_default_timezone_set($config['timezone'] ?? 'Asia/Shanghai');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/dashboard';

// API routes (JWT auth, no session)
if (strpos($uri, '/api/') === 0) {
    ApiRequest::init();
    require __DIR__ . '/api_routes.php';
    exit;
}

// Regex routes for detail/edit pages
$regexRoutes = [
    '#^/supplier/(\d+)/(edit|rating|detail)$#' => ['SupplierController', 'index'],
    '#^/notice/(\d+)/view$#' => ['NoticeController', 'view'],
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
    '/report/elements'  => ['ReportController', 'elements'],
    '/compliance'       => ['ComplianceController', 'index'],
    '/compliance/analyze'  => ['ComplianceController', 'analyze'],
    '/compliance/detail'   => ['ComplianceController', 'detail'],
    '/compliance/manual'   => ['ComplianceController', 'manual'],
    '/compliance/exemption' => ['ComplianceController', 'exemption'],
    '/compliance/matrix'   => ['ComplianceController', 'matrix'],
    '/regulation'       => ['RegulationController', 'index'],
    '/regulation/focus' => ['RegulationController', 'focus'],
    '/substance'        => ['SubstanceController', 'index'],
    '/substance/groups' => ['SubstanceController', 'groups'],
    '/substance/uses'   => ['SubstanceController', 'uses'],
    '/document/categories' => ['DocumentController', 'categories'],
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
];

// Auth check
if ($uri !== '/login' && !isset($_SESSION['user_id'])) {
    header('Location: /login'); exit;
}


// CSRF protection for POST requests (non-API)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($uri, '/api/') !== 0 && $uri !== '/login' && !empty($_POST['_csrf_token'])) {
    verify_csrf();
}

// Load permissions on every request
if (isset($_SESSION['user_id'])) {
    loadUserPermissions($_SESSION['role'] ?? '');
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

