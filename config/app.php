<?php
if (!function_exists('env')) {
function env($key, $default = null) {
    static $env = null;
    if ($env === null) {
        $env = [];
        $path = __DIR__ . '/../.env';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($k, $v) = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v);
                if (($v[0] ?? '') === '"' && substr($v, -1) === '"') $v = substr($v, 1, -1);
                if (($v[0] ?? '') === "'" && substr($v, -1) === "'") $v = substr($v, 1, -1);
                $env[$k] = $v;
            }
        }
    }
    return $env[$key] ?? $default;
}
}

return [
    'version'   => env('APP_VERSION', '2.0.0'),
    'timezone'  => env('APP_TIMEZONE', 'Asia/Shanghai'),
    'database'  => [
        'driver'   => env('DB_DRIVER', 'sqlite'),
        'path'     => dirname(__DIR__) . '/' . env('DB_PATH', './database/srm.db'),
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => (int)env('DB_PORT', 3306),
        'dbname'   => env('DB_NAME', 'qa_srm'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset'  => env('DB_CHARSET', 'utf8mb4'),
    ],
    'redis' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => (int)env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', ''),
        'db'       => (int)env('REDIS_DB', 0),
    ],
    'jwt' => [
        'secret'          => env('JWT_SECRET', hash('sha256', __FILE__ . php_uname() . __DIR__)),
        'access_expire'   => (int)env('JWT_ACCESS_EXPIRE', 7200),
        'refresh_expire'  => (int)env('JWT_REFRESH_EXPIRE', 604800),
    ],
    'upload' => [
        'path'     => dirname(__DIR__) . '/' . env('UPLOAD_PATH', './uploads'),
    ],
    'poppler' => [
        'pdftotext' => dirname(__DIR__) . '/' . env('POPPLER_PDFTEXT', './poppler/poppler-26.02.0/Library/bin/pdftotext.exe'),
        'pdftoppm'  => dirname(__DIR__) . '/' . env('POPPLER_PDFPPM', './poppler/poppler-26.02.0/Library/bin/pdftoppm.exe'),
        'tesseract' => dirname(__DIR__) . '/' . env('TESSERACT_PATH', './tesseract/tesseract.exe'),
    ],
    'warning' => [
        'yellow' => (int)env('WARNING_YELLOW', 30),
        'orange' => (int)env('WARNING_ORANGE', 15),
        'red'    => (int)env('WARNING_RED', 7),
    ],
];
