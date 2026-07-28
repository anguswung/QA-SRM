<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/models/DB.php';

$db = DB::getInstance();
echo "=== 写入测试数据 ===\n\n";

// 先创建供应商
$db->execute("INSERT OR IGNORE INTO supplier (id, code, name, status) VALUES (1, 'SUP20250101', '深圳供应商', 'active')");
echo "  [供应商] 已创建\n\n";

// ---- 10 个均质原材料 ----
$homogeneous = [
    ['name' => '锡膏 Sn96.5Ag3Cu0.5', 'spec' => '均质原材料', 'category' => '焊接材料', 'supplier_id' => 1],
    ['name' => '铜线 C1100 0.5mm', 'spec' => '均质原材料', 'category' => '金属材料', 'supplier_id' => 1],
    ['name' => 'ABS塑料颗粒 PA-765', 'spec' => '均质原材料', 'category' => '塑胶材料', 'supplier_id' => 1],
    ['name' => '不锈钢 SUS304 1.0mm', 'spec' => '均质原材料', 'category' => '金属材料', 'supplier_id' => 1],
    ['name' => '环氧树脂 E-44', 'spec' => '均质原材料', 'category' => '化工材料', 'supplier_id' => 1],
    ['name' => '铁氧体磁芯 PC40', 'spec' => '均质原材料', 'category' => '磁性材料', 'supplier_id' => 1],
    ['name' => '镀锌钢板 SGCC 0.8mm', 'spec' => '均质原材料', 'category' => '金属材料', 'supplier_id' => 1],
    ['name' => '硅胶垫片 T0.5', 'spec' => '均质原材料', 'category' => '包材辅材', 'supplier_id' => 1],
    ['name' => '铝电解电容 1000uF/25V', 'spec' => '均质原材料', 'category' => '电子元器件', 'supplier_id' => 1],
    ['name' => '双面FR4玻纤板 1.6mm', 'spec' => '均质原材料', 'category' => 'PCB板材', 'supplier_id' => 1],
];

$hmIds = [];
foreach ($homogeneous as $h) {
    $no = 'HM' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $db->execute(
        "INSERT INTO material (material_no, material_name, spec, category, supplier_id, env_status, audit_status, valid_status, created_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,datetime('now','localtime'))",
        [$no, $h['name'], $h['spec'], $h['category'], $h['supplier_id'], 'pending', 'N', 'Valid', 1]
    );
    $id = $db->lastInsertId();
    $hmIds[] = $id;
    echo "  [均质] {$no} - {$h['name']}\n";
}

echo "\n";

// ---- 2 个产品（零部件） ----
$parts = [
    ['name' => '电源适配器 DC12V/2A', 'spec' => '零部件', 'category' => '电源类'],
    ['name' => '控制主板 V3.2', 'spec' => '零部件', 'category' => 'PCBA类'],
];

$partIds = [];
foreach ($parts as $p) {
    $no = 'PRT' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    $db->execute(
        "INSERT INTO part (part_no, part_name, spec, unit, supplier_id, status, created_at) VALUES (?,?,?,?,?,?,datetime('now','localtime'))",
        [$no, $p['name'], $p['spec'], 'PCS', 1, 'active']
    );
    $id = $db->lastInsertId();
    $partIds[] = $id;
    echo "  [产品] {$no} - {$p['name']}\n";
}

echo "\n=== 写入完成 ===\n";
echo "均质原材料 " . count($hmIds) . " 个\n";
echo "产品(零部件): " . count($partIds) . " 个\n";
echo "\n提示: 请手工在 BOM 清单中绑定物料关系。\n";
