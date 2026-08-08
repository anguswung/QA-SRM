<?php
class DB {
    private static $instance = null;
    private $pdo;
    private $driver;
    private function __construct() {
        $config = require __DIR__ . '/../../config/app.php';
        $db = $config['database'];
        $this->driver = $db['driver'] ?? 'sqlite';
        if ($this->driver === 'mysql') {
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}";
            $this->pdo = new PDO($dsn, $db['username'], $db['password']);
            $this->pdo->exec("SET time_zone='+08:00'");
            $isNew = false;
        } else {
            $dbPath = $db['path'];
            $isNew = !file_exists($dbPath);
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->exec('PRAGMA journal_mode=WAL');
            $this->pdo->exec('PRAGMA auto_vacuum=INCREMENTAL');
            $this->pdo->exec('PRAGMA foreign_keys=ON');
        }
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        if ($isNew) $this->migrate();
    }
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    public function query($sql, $params = []) {
        $sql = $this->translate($sql);
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function single($sql, $params = [], $fullRow = false) {
        $sql = $this->translate($sql);
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $fullRow ? $stmt->fetch() : $stmt->fetchColumn();
    }
    public function execute($sql, $params = []) {
        $sql = $this->translate($sql);
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $params);
        $result = $stmt->execute();
        if ($this->driver === 'sqlite' && preg_match('/^\s*DELETE\s+FROM/i', $sql)) {
            $this->pdo->exec('PRAGMA incremental_vacuum(10)');
        }
        return $result;
    }
    public function lastInsertId() { return $this->pdo->lastInsertId(); }
    private function translate($sql) {
        if ($this->driver === 'mysql') {
            // 顺序重要：先匹配更具体的形态
            $sql = preg_replace("/datetime\('now','localtime'\)/i", 'NOW()', $sql);
            $sql = preg_replace("/date\('now','localtime'\)/i", 'DATE(NOW())', $sql);
            $sql = preg_replace("/date\('now',\s*'(-?\d+)\s*(year|month|week|day|hour)s?'\)/i", 'DATE_SUB(CURDATE(), INTERVAL $1 $2)', $sql);
            $sql = preg_replace("/date\('now'\)/i", 'CURDATE()', $sql);
            // SQLite julianday 日期差 → MySQL DATEDIFF
            $sql = preg_replace("/julianday\(([^)]+)\)\s*-\s*julianday\('now'\)/i", 'DATEDIFF($1, CURDATE())', $sql);
            $sql = preg_replace("/julianday\('now'\)\s*-\s*julianday\(([^)]+)\)/i", 'DATEDIFF(CURDATE(), $1)', $sql);
            // SQLite strftime('%Y-%m', col) -> MySQL DATE_FORMAT(col, '%Y-%m')；SQLite %M=分钟 -> MySQL %i
            $sql = preg_replace_callback("/strftime\('([^']*)',\s*([^)]+)\)/i", function ($m) {
                return "DATE_FORMAT(" . $m[2] . ", '" . str_replace('%M', '%i', $m[1]) . "')";
            }, $sql);
            $sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);
            $sql = str_replace('"', '`', $sql);
        }
        return $sql;
    }
    private function bindParams(\PDOStatement $stmt, array $params): void {
        foreach ($params as $i => $v) {
            $type = is_int($v) ? PDO::PARAM_INT : (is_null($v) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue($i + 1, $v, $type);
        }
    }
    private function migrate() {
        $schema = file_get_contents(__DIR__ . '/../../scripts/schema.sql');
        $statements = explode(';', $schema);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt) {
                try { $this->pdo->exec($this->translate($stmt)); } catch (Exception $e) {}
            }
        }
    }
}
