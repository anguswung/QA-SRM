<?php
class WarningController extends Controller {
    public function index() {
        $this->requirePermission('warning.view');
        $db = DB::getInstance();
        $engine = new WarningEngine();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'generate') {
                if (!isAdmin()) { flash('error', '无权操作'); header('Location: /warning'); exit; }
                $count = $engine->generateWarnings();
                flash('success', "预警生成完成，新增$count 条");
            }
            if ($action === 'resolve') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) { $this->requireSupplierWarning($id); $engine->resolve($id); logOp('update', 'warning', $id, '处理预警'); flash('success', '预警已处理'); }
            }
            if ($action === 'resolve_all') {
                $ids = $_POST['ids'] ?? [];
                foreach ($ids as $id) { $id = (int)$id; $this->requireSupplierWarning($id); $engine->resolve($id); }
                flash('success', '批量处理完毕');
            }
            header('Location: /warning'); exit;
        }
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        $where = []; $params = [];
        if ($type) { $where[] = "w.warning_type=?"; $params[] = $type; }
        if ($status === 'pending') { $where[] = "w.solved=0"; }
        elseif ($status === 'resolved') { $where[] = "w.solved=1"; }
        if (isSupplier()) { $where[] = "w.material_id IN (SELECT id FROM material WHERE supplier_id=?)"; $params[] = userSupplierId(); }
        $warnings = $db->query("SELECT w.*, m.material_name, s.name AS supplier_name FROM warning_record w LEFT JOIN material m ON w.material_id=m.id LEFT JOIN supplier s ON m.supplier_id=s.id" . ($where ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY w.created_at DESC", $params);
        $stats = $db->query("SELECT warning_type, COUNT(*) AS cnt, SUM(CASE WHEN solved=0 THEN 1 ELSE 0 END) AS pending_count FROM warning_record GROUP BY warning_type");
        $title = '预警中心';
        $this->render('warning/index', get_defined_vars());
    }
    private function requireSupplierWarning(int $id): void {
        if (!isSupplier()) return;
        $db = DB::getInstance();
        $w = $db->single("SELECT material_id FROM warning_record WHERE id=?", [$id], true);
        if (!$w) { flash('error', '记录不存在'); header('Location: /warning'); exit; }
        $m = $db->single("SELECT supplier_id FROM material WHERE id=?", [(int)$w['material_id']], true);
        if (!$m || (int)$m['supplier_id'] !== userSupplierId()) { flash('error', '无权操作'); header('Location: /warning'); exit; }
    }
}
