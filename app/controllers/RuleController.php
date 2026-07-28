<?php
class RuleController extends Controller {
    public function index() {
        $this->requirePermission('rule.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $rule = $db->single("SELECT enabled FROM compliance_rule WHERE id=?", [$id], true);
                if ($rule) $db->execute("UPDATE compliance_rule SET enabled=? WHERE id=?", [$rule['enabled'] ? 0 : 1, $id]);
                header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit;
            }
            if ($action === 'check') {
                $supplierId = (int)($_POST['supplier_id'] ?? 0);
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
        $rules = $db->query("SELECT * FROM compliance_rule ORDER BY sort_order");
        $title = '合规规则管理';
        $this->render('rule/index', get_defined_vars());
    }
    private function showCheck() {
        $db = DB::getInstance();
        $suppliers = $db->query("SELECT id, code, name FROM supplier WHERE blacklist=0 ORDER BY code");
        $title = '合规检查';
        $this->render('rule/check', get_defined_vars());
    }
}
