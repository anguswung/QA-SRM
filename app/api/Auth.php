<?php
class ApiAuth {
    private static $config = null;

    private static function config() {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/app.php';
        }
        return self::$config;
    }

    public static function generateAccessToken($user) {
        $cfg = self::config()['jwt'];
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + $cfg['access_expire'],
            'type' => 'access',
            'user_id' => (int)$user['id'],
            'username' => $user['username'],
            'role' => $user['role'] ?? '',
            'supplier_id' => (int)($user['supplier_id'] ?? 0),
            'realname' => $user['realname'] ?? $user['username'],
        ];
        return self::encode($payload);
    }

    public static function generateRefreshToken($user) {
        $cfg = self::config()['jwt'];
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + $cfg['refresh_expire'],
            'type' => 'refresh',
            'user_id' => (int)$user['id'],
            'username' => $user['username'],
        ];
        return self::encode($payload);
    }

    public static function generateToken($user) {
        return self::generateAccessToken($user);
    }

    public static function validateToken($token) {
        try {
            $payload = self::decode($token);
            if (!$payload || !isset($payload['user_id'])) return null;
            if (isset($payload['exp']) && $payload['exp'] < time()) return null;
            return $payload;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function refreshAccessToken($refreshToken) {
        $payload = self::validateToken($refreshToken);
        if (!$payload || ($payload['type'] ?? '') !== 'refresh') return null;
        $db = DB::getInstance();
        $user = $db->single("SELECT * FROM users WHERE id=? AND status='active'", [$payload['user_id']], true);
        if (!$user) return null;
        return [
            'access_token' => self::generateAccessToken($user),
            'refresh_token' => self::generateRefreshToken($user),
        ];
    }

    public static function getUser() {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$auth && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return self::validateToken($m[1]);
        }
        return null;
    }

    public static function requireUser() {
        $user = self::getUser();
        if (!$user) ApiResponse::unauthorized();
        return $user;
    }

    public static function requireRole(...$roles) {
        $user = self::requireUser();
        if (!in_array($user['role'], $roles)) ApiResponse::forbidden();
        return $user;
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function encode($payload) {
        $cfg = self::config()['jwt'];
        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $body = self::base64UrlEncode(json_encode($payload));
        $sig = self::base64UrlEncode(hash_hmac('sha256', "$header.$body", $cfg['secret'], true));
        return "$header.$body.$sig";
    }

    private static function decode($token) {
        $cfg = self::config()['jwt'];
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        list($header, $body, $sig) = $parts;
        $expected = self::base64UrlEncode(hash_hmac('sha256', "$header.$body", $cfg['secret'], true));
        if (!hash_equals($expected, $sig)) return null;
        return json_decode(self::base64UrlDecode($body), true);
    }
}
