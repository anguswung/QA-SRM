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
    public function getDriver() { return $this->driver; }
    public function now() { return $this->driver === 'mysql' ? 'NOW()' : "datetime('now','localtime')"; }
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
    public function vacuum(): void {
        if ($this->driver === 'sqlite') {
            $this->pdo->exec('VACUUM');
        }
    }
    public function incrementalVacuum(int $pages = 0): void {
        if ($this->driver === 'sqlite') {
            $this->pdo->exec($pages > 0 ? "PRAGMA incremental_vacuum($pages)" : 'PRAGMA incremental_vacuum');
        }
    }
    public function freelistCount(): int {
        if ($this->driver !== 'sqlite') return 0;
        return (int)$this->pdo->query('PRAGMA freelist_count')->fetchColumn();
    }
    private function translate($sql) {
        if ($this->driver === 'mysql') {
            $sql = preg_replace("/datetime\('now','localtime'\)/i", 'NOW()', $sql);
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
