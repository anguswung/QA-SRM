<?php
$method = $_SERVER['REQUEST_METHOD'];
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriPath = rtrim($uriPath, '/');

$apiRoutes = [
    // Auth (no rate limit middleware applied here, handled in controller)
    'POST /api/v1/auth/login'      => ['ApiAuthController', 'login'],
    'POST /api/v1/auth/logout'     => ['ApiAuthController', 'logout'],
    'GET  /api/v1/auth/me'          => ['ApiAuthController', 'me'],
    'POST /api/v1/auth/refresh'     => ['ApiAuthController', 'refresh'],

    // Dashboard
    'GET  /api/v1/dashboard/stats'  => ['ApiDashboardController', 'stats'],

    // Material
    'GET  /api/v1/material'         => ['ApiMaterialController', 'index'],
    'POST /api/v1/material'         => ['ApiMaterialController', 'create'],
    'PUT  /api/v1/material'         => ['ApiMaterialController', 'update'],
    'DELETE /api/v1/material'       => ['ApiMaterialController', 'delete'],
    'GET  /api/v1/material/detail'  => ['ApiMaterialController', 'detail'],
    'GET  /api/v1/material/composition' => ['ApiMaterialController', 'composition'],
    'GET  /api/v1/material/hm-data' => ['ApiMaterialController', 'hmData'],

    // Homogeneous
    'GET  /api/v1/homogeneous'      => ['ApiHomogeneousController', 'index'],
    'POST /api/v1/homogeneous'      => ['ApiHomogeneousController', 'create'],
    'PUT  /api/v1/homogeneous'      => ['ApiHomogeneousController', 'update'],
    'DELETE /api/v1/homogeneous'    => ['ApiHomogeneousController', 'delete'],
    'GET  /api/v1/homogeneous/detail' => ['ApiHomogeneousController', 'detail'],
    'GET  /api/v1/homogeneous/reports' => ['ApiHomogeneousController', 'reports'],
    'GET  /api/v1/homogeneous/compliance' => ['ApiHomogeneousController', 'compliance'],

    // Report
    'GET  /api/v1/report'           => ['ApiReportController', 'index'],
    'POST /api/v1/report'           => ['ApiReportController', 'create'],
    'PUT  /api/v1/report'           => ['ApiReportController', 'update'],
    'DELETE /api/v1/report'         => ['ApiReportController', 'delete'],
    'GET  /api/v1/report/audit'     => ['ApiReportController', 'audit'],
    'GET  /api/v1/report/elements'  => ['ApiReportController', 'elements'],

    // Part
    'GET  /api/v1/part'             => ['ApiPartController', 'index'],
    'POST /api/v1/part'             => ['ApiPartController', 'create'],
    'PUT  /api/v1/part'             => ['ApiPartController', 'update'],
    'DELETE /api/v1/part'           => ['ApiPartController', 'delete'],
    'GET  /api/v1/part/bom'         => ['ApiPartController', 'bom'],
    'GET  /api/v1/part/bom-tree'    => ['ApiPartController', 'bomTree'],
    'GET  /api/v1/part/bom-export'  => ['ApiPartController', 'bomExport'],

    // Regulation
    'GET  /api/v1/regulation'       => ['ApiRegulationController', 'index'],
    'POST /api/v1/regulation'       => ['ApiRegulationController', 'create'],
    'PUT  /api/v1/regulation'       => ['ApiRegulationController', 'update'],
    'DELETE /api/v1/regulation'     => ['ApiRegulationController', 'delete'],
    'GET  /api/v1/regulation/detail' => ['ApiRegulationController', 'detail'],
    'POST /api/v1/regulation/focus'  => ['ApiRegulationController', 'focus'],

    // Substance
    'GET  /api/v1/substance'        => ['ApiSubstanceController', 'index'],
    'POST /api/v1/substance'        => ['ApiSubstanceController', 'create'],
    'PUT  /api/v1/substance'        => ['ApiSubstanceController', 'update'],
    'DELETE /api/v1/substance'      => ['ApiSubstanceController', 'delete'],
    'GET  /api/v1/substance/lookup' => ['ApiSubstanceController', 'lookup'],

    // Supplier
    'GET  /api/v1/supplier'         => ['ApiSupplierController', 'index'],
    'POST /api/v1/supplier'         => ['ApiSupplierController', 'create'],
    'PUT  /api/v1/supplier'         => ['ApiSupplierController', 'update'],
    'DELETE /api/v1/supplier'       => ['ApiSupplierController', 'delete'],
    'GET  /api/v1/supplier/detail'  => ['ApiSupplierController', 'detail'],
    'GET  /api/v1/supplier/rating'  => ['ApiSupplierController', 'rating'],

    // Compliance
    'GET  /api/v1/compliance'       => ['ApiComplianceController', 'index'],
    'GET  /api/v1/compliance/detail' => ['ApiComplianceController', 'detail'],
    'POST /api/v1/compliance/analyze' => ['ApiComplianceController', 'analyze'],

    // BOM
    'GET  /api/v1/bom'              => ['ApiBomController', 'index'],

    // Task
    'GET  /api/v1/task'             => ['ApiTaskController', 'index'],
    'POST /api/v1/task'             => ['ApiTaskController', 'create'],

    // Dashboard / System
    'GET  /api/v1/notice'           => ['ApiNoticeController', 'index'],
    'GET  /api/v1/survey'           => ['ApiSurveyController', 'index'],
    'GET  /api/v1/warning'          => ['ApiWarningController', 'index'],
    'GET  /api/v1/query/material'   => ['ApiQueryController', 'material'],
    'GET  /api/v1/query/expired'    => ['ApiQueryController', 'expired'],
    'GET  /api/v1/query/history'    => ['ApiQueryController', 'history'],
    'GET  /api/v1/system/users'     => ['ApiSystemController', 'users'],
    'GET  /api/v1/system/logs'      => ['ApiSystemController', 'logs'],
    'GET  /api/v1/system/config'    => ['ApiSystemController', 'config'],
    'GET  /api/v1/document/categories' => ['ApiDocumentController', 'categories'],

    // Upload / Download
    'POST /api/v1/upload'           => ['ApiUploadController', 'upload'],
    'POST /api/v1/download'         => ['ApiDownloadController', 'token'],
    'GET  /api/v1/download'         => ['ApiDownloadController', 'file'],

    // Export (V2.0 new)
    'POST /api/v1/export/materials' => ['ApiExportController', 'materials'],
    'POST /api/v1/export/compliance' => ['ApiExportController', 'compliance'],
    'GET  /api/v1/export/tasks'     => ['ApiExportController', 'tasks'],
];

// Match route
$routeKey = "$method $uriPath";
if (isset($apiRoutes[$routeKey])) {
    [$ctrlName, $action] = $apiRoutes[$routeKey];
    if (class_exists($ctrlName)) {
        if ($ctrlName !== 'ApiAuthController') {
            RateLimitMiddleware::checkApi();
            ApiAuth::requireUser();
        }
        $obj = new $ctrlName();
        if (method_exists($obj, $action)) { $obj->$action(); exit; }
    }
}

// Try regex API routes (parameterized URLs)
$regexApiRoutes = [
    '#^/api/v1/material/(\d+)$#' => ['GET' => ['ApiMaterialController', 'detail']],
    '#^/api/v1/homogeneous/(\d+)$#' => ['GET' => ['ApiHomogeneousController', 'detail']],
    '#^/api/v1/report/(\d+)$#' => ['GET' => ['ApiReportController', 'detail']],
    '#^/api/v1/part/(\d+)$#' => ['GET' => ['ApiPartController', 'detail']],
    '#^/api/v1/regulation/(\d+)$#' => ['GET' => ['ApiRegulationController', 'detail']],
    '#^/api/v1/substance/(\d+)$#' => ['GET' => ['ApiSubstanceController', 'detail']],
    '#^/api/v1/supplier/(\d+)$#' => ['GET' => ['ApiSupplierController', 'detail']],
    '#^/api/v1/compliance/detail/(\d+)$#' => ['GET' => ['ApiComplianceController', 'detail']],
    '#^/api/v1/task/(\d+)$#' => ['GET' => ['ApiTaskController', 'detail']],
];
foreach ($regexApiRoutes as $pattern => $methodMap) {
    if (preg_match($pattern, $uriPath, $m)) {
        if (isset($methodMap[$method])) {
            [$ctrlName, $action] = $methodMap[$method];
            RateLimitMiddleware::checkApi();
            ApiAuth::requireUser();
            $_GET['id'] = (int)$m[1];
            $obj = new $ctrlName();
            if (method_exists($obj, $action)) { $obj->$action(); exit; }
        }
    }
}

// Backward compatibility: redirect old /api/* to /api/v1/*
if (strpos($uriPath, '/api/') === 0 && strpos($uriPath, '/api/v1/') !== 0) {
    $newUri = str_replace('/api/', '/api/v1/', $_SERVER['REQUEST_URI']);
    header('Location: ' . $newUri, true, 301);
    exit;
}

ApiResponse::notFound('API 接口不存在');
