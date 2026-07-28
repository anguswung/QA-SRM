<?php
class RateLimitMiddleware {
    private static $cache = [];

    public static function check($key, $maxAttempts, $windowSeconds = 60) {
        $now = time();
        $redis = self::getRedis();
        if ($redis) {
            $redisKey = "rate_limit:{$key}";
            $current = (int)$redis->get($redisKey);
            if ($current >= $maxAttempts) {
                $ttl = $redis->ttl($redisKey);
                ApiResponse::rateLimited("请求过于频繁，请在 {$ttl} 秒后重试");
            }
            $redis->incr($redisKey);
            if ($current === 0) {
                $redis->expire($redisKey, $windowSeconds);
            }
        } else {
            if (!isset(self::$cache[$key])) {
                self::$cache[$key] = ['count' => 0, 'start' => $now];
            }
            $entry = &self::$cache[$key];
            if ($now - $entry['start'] > $windowSeconds) {
                $entry = ['count' => 0, 'start' => $now];
            }
            $entry['count']++;
            if ($entry['count'] > $maxAttempts) {
                $remaining = $windowSeconds - ($now - $entry['start']);
                ApiResponse::rateLimited("请求过于频繁，请在 {$remaining} 秒后重试");
            }
        }
    }

    public static function checkLogin() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $config = require __DIR__ . '/../../config/app.php';
        $maxAttempts = $config['rate_limit']['login'] ?? 5;
        self::check("login:{$ip}", $maxAttempts, 60);
    }

    public static function checkApi() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $config = require __DIR__ . '/../../config/app.php';
        $maxAttempts = $config['rate_limit']['api'] ?? 60;
        self::check("api:{$ip}", $maxAttempts, 60);
    }

    private static function getRedis() {
        static $redis = null;
        if ($redis !== null) return $redis;
        try {
            $config = require __DIR__ . '/../../config/app.php';
            $r = $config['redis'];
            if (!class_exists('Redis')) return null;
            $redis = new Redis();
            $connected = $redis->connect($r['host'], $r['port'], 1.0);
            if (!$connected) { $redis = null; return null; }
            if (!empty($r['password'])) {
                $redis->auth($r['password']);
            }
            $redis->select($r['db']);
            return $redis;
        } catch (Exception $e) {
            $redis = null;
            return null;
        }
    }
}
