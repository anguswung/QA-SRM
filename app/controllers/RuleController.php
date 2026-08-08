<?php
class RuleController extends Controller {
    public function index() {
        $this->requirePermission('rule.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'toggle') {
                if (!isAdmin()) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'no permission']); exit; }
                $id = (int)($_POST['id'] ?? 0);
                $rule = $db->single("SELECT enabled FROM compliance_rule WHERE id=?", [$id], true);
                if ($rule) $db->execute("UPDATE compliance_rule SET enabled=? WHERE id=?", [$rule['enabled'] ? 0 : 1, $id]);
                header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit;
            }
            if ($action === 'check') {
                $supplierId = isAdmin() ? (int)($_POST['supplier_id'] ?? 0) : userSupplierId();
                $engine = new RuleEngine();
                $result = $supplierId ? $engine->checkSupplierAll($supplierId) : $engine->checkSupplierAll(userSupplierId());
                header('Content-Type: application/json'); echo json_encode($result); exit;
            }
            header('Location: /rule'); exit;
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'check') { $this->showCheck(); return; }
        $this->showList();
    }
    private function showList() {
        $db = DB::getInstance();
        if (($_GET['action'] ?? '') === 'data') { $this->data(); return; }
        $title = '合规规则管理';
        $this->render('rule/index', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data） */
    private function data() {
        $this->requirePermission('rule.view');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $total = (int)$db->single("SELECT COUNT(*) FROM compliance_rule");
        $offset = ($page - 1) * $perPage;
        $rows = $db->query("SELECT * FROM compliance_rule ORDER BY sort_order LIMIT ? OFFSET ?", [$perPage, $offset]);
        $html = '';
        foreach ($rows as $r) {
            $html .= '<tr>';
            $html .= colId($r['id'] ?? '') . '<td>' . h($r['rule_code']) . '</td>';
            $html .= '<td>' . h($r['name']) . '</td>';
            $html .= '<td>' . badge($r['doc_category_code'] ?: '-', 'info') . '</td>';
            $html .= '<td>' . ($r['rule_type'] === 'mandatory' ? '<span class="status-tag danger">强制</span>' : '<span class="status-tag warning">提醒</span>') . '</td>';
            $html .= '<td>' . ($r['enabled'] ? '<span class="status-tag success">启用</span>' : '<span class="status-tag secondary">禁用</span>') . '</td>';
            $html .= '<td><button class="btn btn-sm btn-outline-' . ($r['enabled'] ? 'warning' : 'success') . '" onclick="toggleRule(' . $r['id'] . ')">' . ($r['enabled'] ? '禁用' : '启用') . '</button></td>';
            $html .= '</tr>';
        }
        $this->paginationResponse($total, $html);
    }
    private function showCheck() {
        $db = DB::getInstance();
        $suppliers = QueryRegistry::supplierDropdown();
        $title = '合规检查';
        $this->render('rule/check', get_defined_vars());
    }
}
