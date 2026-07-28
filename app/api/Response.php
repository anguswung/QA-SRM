<?php
class ApiResponse {
    public static function json($data = null, $message = 'success', $code = 0, $meta = null, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        $res = ['code' => $code, 'msg' => $message, 'data' => $data];
        if ($meta) $res['meta'] = $meta;
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }
    public static function success($data = null, $message = 'success', $meta = null) {
        self::json($data, $message, 0, $meta);
    }
    public static function created($data = null, $message = '创建成功') {
        self::json($data, $message, 0, null, 201);
    }
    public static function error($message = '请求失败', $code = 400, $httpCode = 400) {
        self::json(null, $message, $code, null, $httpCode);
    }
    public static function validationError($errors, $message = '参数校验失败') {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'code' => 42200,
            'msg' => $message,
            'data' => null,
            'errors' => $errors,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    public static function unauthorized($message = '未登录或登录已过期') {
        self::json(null, $message, 40100, null, 401);
    }
    public static function forbidden($message = '无权限访问') {
        self::json(null, $message, 40300, null, 403);
    }
    public static function notFound($message = '资源不存在') {
        self::json(null, $message, 40400, null, 404);
    }
    public static function rateLimited($message = '请求过于频繁，请稍后再试') {
        self::json(null, $message, 42900, null, 429);
    }
    public static function serverError($message = '服务器内部错误') {
        self::json(null, $message, 50000, null, 500);
    }
    public static function paginated($data, $total, $page, $perPage) {
        $meta = [
            'page' => (int)$page,
            'limit' => (int)$perPage,
            'total' => (int)$total,
            'total_pages' => (int)ceil($total / max(1, $perPage)),
        ];
        self::json($data, 'success', 0, $meta);
    }
}
