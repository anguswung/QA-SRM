<?php
class ApiRequest {
    public static $body = null;
    public static function init() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            self::$body = json_decode($raw, true) ?? [];
        } else {
            self::$body = $_POST;
        }
    }
    public static function input($key = null, $default = null) {
        if ($key === null) return array_merge($_GET, self::$body ?? []);
        return $_GET[$key] ?? (self::$body[$key] ?? $default);
    }
    public static function get($key = null, $default = null) {
        return $key === null ? $_GET : ($_GET[$key] ?? $default);
    }
    public static function post($key = null, $default = null) {
        $data = self::$body ?? $_POST;
        return $key === null ? $data : ($data[$key] ?? $default);
    }
    public static function file($key) {
        return $_FILES[$key] ?? null;
    }
    public static function method() {
        return $_SERVER['REQUEST_METHOD'];
    }
    public static function header($key, $default = null) {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $default;
    }
    public static function validate($rules) {
        $data = self::input();
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $parts = is_array($rule) ? $rule : explode('|', $rule);
            foreach ($parts as $r) {
                $r = trim($r);
                if ($r === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = "$field 不能为空";
                }
                if ($r === 'integer' && $value !== null && !ctype_digit((string)$value)) {
                    $errors[$field][] = "$field 必须是整数";
                }
                if ($r === 'numeric' && $value !== null && !is_numeric($value)) {
                    $errors[$field][] = "$field 必须是数字";
                }
                if (preg_match('/^min:(\d+)$/', $r, $m) && $value !== null && (float)$value < (float)$m[1]) {
                    $errors[$field][] = "$field 不能小于 {$m[1]}";
                }
                if (preg_match('/^max:(\d+)$/', $r, $m) && $value !== null && (float)$value > (float)$m[1]) {
                    $errors[$field][] = "$field 不能大于 {$m[1]}";
                }
                if (preg_match('/^length:(\d+)$/', $r, $m) && $value !== null && mb_strlen($value) > (int)$m[1]) {
                    $errors[$field][] = "$field 长度不能超过 {$m[1]}";
                }
            }
        }
        if ($errors) {
            ApiResponse::error('参数验证失败', 400, 400);
        }
        return $data;
    }
}
