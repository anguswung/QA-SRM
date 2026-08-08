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
            header('Location: /warning'); exit;
        }
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        $action = $_GET['action'] ?? '';
        if ($action === 'data') { $this->data(); return; }
        $where = []; $params = [];
        if ($type) { $where[] = "w.warning_type=?"; $params[] = $type; }
        if ($status === 'pending') { $where[] = "w.solved=0"; }
        elseif ($status === 'resolved') { $where[] = "w.solved=1"; }
        if (isSupplier()) { $where[] = "w.material_id IN (SELECT id FROM material WHERE supplier_id=?)"; $params[] = userSupplierId(); }
        $stats = $db->query("SELECT warning_type, COUNT(*) AS cnt, SUM(CASE WHEN solved=0 THEN 1 ELSE 0 END) AS pending_count FROM warning_record GROUP BY warning_type");
        $title = '预警中心';
        $this->render('warning/index', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data） */
    private function data() {
        $this->requirePermission('warning.view');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        $where = []; $params = [];
        if ($type) { $where[] = "w.warning_type=?"; $params[] = $type; }
        if ($status === 'pending') { $where[] = "w.solved=0"; }
        elseif ($status === 'resolved') { $where[] = "w.solved=1"; }
        if (isSupplier()) { $where[] = "w.material_id IN (SELECT id FROM material WHERE supplier_id=?)"; $params[] = userSupplierId(); }
        $whereSql = $where ? " WHERE " . implode(' AND ', $where) : '';
        $total = (int)$db->single("SELECT COUNT(*) FROM warning_record w" . $whereSql, $params);
        $offset = ($page - 1) * $perPage;
        $rows = $db->query(
            "SELECT w.*, m.material_name, s.name AS supplier_name FROM warning_record w LEFT JOIN material m ON w.material_id=m.id LEFT JOIN supplier s ON m.supplier_id=s.id" . $whereSql . " ORDER BY w.created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        $html = '';
        foreach ($rows as $w) {
            $html .= '<tr>';
            $html .= colId($w['id'] ?? '') . '<td>' . h($w['supplier_name'] ?? '-') . '</td>';
            $html .= '<td>' . h($w['material_name'] ?? '-') . '</td>';
            $html .= '<td>' . badge($w['warning_type'], 'info') . '</td>';
            $html .= '<td>' . statusBadge($w['warning_level']) . '</td>';
            $html .= '<td><small>' . h($w['warning_desc']) . '</small></td>';
            $html .= '<td>' . h($w['days_left'] ?? '-') . '天</td>';
            $html .= '<td>' . ($w['solved'] ? '<span class="status-tag success">已处理</span>' : '<span class="status-tag warning">待处理</span>') . '</td>';
            $html .= '<td><small>' . h($w['created_at']) . '</small></td>';
            $html .= '<td>';
            if (!$w['solved']) {
                $html .= '<form method="post" class="d-inline">' . csrf_field() . '<input type="hidden" name="action" value="resolve"><input type="hidden" name="id" value="' . $w['id'] . '"><button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i> 处理</button></form>';
            }
            $html .= '</td></tr>';
        }
        $this->paginationResponse($total, $html);
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
