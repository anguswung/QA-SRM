<?php
class QueryRegistry {
    private static array $cache = [];
    private static ?DB $db = null;

    private static function db(): DB {
        if (self::$db === null) self::$db = DB::getInstance();
        return self::$db;
    }

    public static function supplierDropdown(): array {
        $k = 'supplier_dropdown';
        if (!isset(self::$cache[$k])) {
            self::$cache[$k] = self::db()->query("SELECT id, code, name FROM supplier WHERE blacklist=0 ORDER BY code");
        }
        return self::$cache[$k];
    }

    public static function supplierDropdownActive(): array {
        $k = 'supplier_dropdown_active';
        if (!isset(self::$cache[$k])) {
            self::$cache[$k] = self::db()->query("SELECT id, code, name FROM supplier WHERE blacklist=0 AND status='active' ORDER BY code");
        }
        return self::$cache[$k];
    }

    public static function supplierNameMap(): array {
        $k = 'supplier_name_map';
        if (!isset(self::$cache[$k])) {
            $rows = self::db()->query("SELECT id, name FROM supplier");
            self::$cache[$k] = array_column($rows, 'name', 'id');
        }
        return self::$cache[$k];
    }

    public static function supplierIdByName(string $name): ?int {
        $k = "sid_$name";
        if (!array_key_exists($k, self::$cache)) {
            $row = self::db()->single("SELECT id FROM supplier WHERE name=? OR name_short=?", [$name]);
            self::$cache[$k] = $row ? (int)$row['id'] : null;
        }
        return self::$cache[$k];
    }

    public static function complianceRules(): array {
        $k = 'compliance_rules';
        if (!isset(self::$cache[$k])) {
            self::$cache[$k] = self::db()->query("SELECT * FROM compliance_rule WHERE enabled=1 ORDER BY sort_order");
        }
        return self::$cache[$k];
    }

    public static function validMaterials(): array {
        $k = 'valid_materials';
        if (!isset(self::$cache[$k])) {
            self::$cache[$k] = self::db()->query("SELECT id, material_name, material_no, env_status FROM material WHERE valid_status='Valid' ORDER BY material_name");
        }
        return self::$cache[$k];
    }
}
