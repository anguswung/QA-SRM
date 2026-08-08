<?php
class ComplianceController extends Controller {
    public function index() {
        $this->requirePermission('compliance.view');
        $db = DB::getInstance();
        $supplierFilter = isSupplier() ? " AND m.supplier_id=" . userSupplierId() : '';
        $envStats = $db->query("SELECT env_status, COUNT(*) AS cnt FROM material m WHERE m.valid_status='Valid'$supplierFilter GROUP BY env_status");
        $supplierCompliance = isSupplier()
            ? $db->query("SELECT s.id, s.code, s.name, s.level, COUNT(m.id) AS material_count, SUM(CASE WHEN m.env_status='compliant' THEN 1 ELSE 0 END) AS compliant_count, SUM(CASE WHEN m.env_status='non_compliant' THEN 1 ELSE 0 END) AS non_compliant_count FROM supplier s LEFT JOIN material m ON s.id=m.supplier_id AND m.valid_status='Valid' WHERE s.id=? GROUP BY s.id", [userSupplierId()])
            : $db->query("SELECT s.id, s.code, s.name, s.level, COUNT(m.id) AS material_count, SUM(CASE WHEN m.env_status='compliant' THEN 1 ELSE 0 END) AS compliant_count, SUM(CASE WHEN m.env_status='non_compliant' THEN 1 ELSE 0 END) AS non_compliant_count FROM supplier s LEFT JOIN material m ON s.id=m.supplier_id AND m.valid_status='Valid' GROUP BY s.id ORDER BY s.name");
        $title = '合规分析';
        $this->render('compliance/index', get_defined_vars());
    }
    public function analyze() {
        $this->requirePermission('compliance.analyze');
        $db = DB::getInstance();
        $materialId = (int)($_GET['material_id'] ?? $_POST['material_id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $materialId) {
            $this->requireSupplierRecord('material', $materialId, '/compliance/analyze');
            $engine = new ComplianceEngine();
            $regulationId = (int)($_POST['regulation_id'] ?? 0) ?: null;
            $results = $engine->analyze($materialId, $regulationId);
            $_SESSION['just_analyzed'] = $materialId;
            flash('success', '分析完成，共 ' . count($results) . ' 条');
            header('Location: /compliance/analyze?material_id=' . $materialId); exit;
        }
        $materials = QueryRegistry::validMaterials();
        $results = [];
        if ($materialId) {
            $results = $db->query("SELECT ar.*, rl.name AS regulation_name, rl.regulation_no FROM analysis_record ar JOIN regulation_lib rl ON ar.regulation_id=rl.id WHERE ar.material_id=? ORDER BY ar.analyzed_at DESC LIMIT 50", [$materialId]);
        }
        $justAnalyzed = ($_SESSION['just_analyzed'] ?? 0) == $materialId;
        unset($_SESSION['just_analyzed']);
        $title = '合规评估';
        $this->render('compliance/analyze', get_defined_vars());
    }
    public function detail() {
        $this->requirePermission('compliance.view');
        $materialId = (int)($_GET['material_id'] ?? 0);
        if (!$materialId) { header('Location: /compliance'); exit; }
        $db = DB::getInstance();
        $material = $db->single("SELECT m.*, s.name AS supplier_name FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id WHERE m.id=?", [$materialId], true);
        if (!$material) { header('Location: /compliance'); exit; }
        if (isSupplier() && (int)$material['supplier_id'] !== userSupplierId()) { header('Location: /compliance?error=no_permission'); exit; }
        $records = $db->query("SELECT ar.*, rl.name AS regulation_name FROM analysis_record ar JOIN regulation_lib rl ON ar.regulation_id=rl.id WHERE ar.material_id=? ORDER BY ar.analyzed_at DESC LIMIT 20", [$materialId]);
        $exemptions = $db->query("SELECT er.*, s.name AS substance_name FROM exemption_record er LEFT JOIN substance s ON er.substance_id=s.id WHERE er.material_id=?", [$materialId]);
        $title = '合规详情 - ' . $material['material_name'];
        $this->render('compliance/detail', get_defined_vars());
    }
    public function manual() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePermission('compliance.analyze');
            $materialId = (int)($_POST['material_id'] ?? 0);
            $regulationId = (int)($_POST['regulation_id'] ?? 0);
            $result = $_POST['manual_result'] ?? '';
            $remark = $_POST['remark'] ?? '';
            if ($materialId && $regulationId && $result) {
                $this->requireSupplierRecord('material', $materialId, '/compliance/detail?material_id=' . $materialId);
                $engine = new ComplianceEngine();
                $engine->setManualResult($materialId, $regulationId, $result, $remark);
                logOp('update', 'compliance', $materialId, "人工设定: $result - $remark");
                flash('success', '人工设定完成');
            }
            header('Location: /compliance/detail?material_id=' . $materialId); exit;
        }
    }
    public function exemption() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePermission('compliance.analyze');
            $materialId = (int)($_POST['material_id'] ?? 0);
            $substanceId = (int)($_POST['substance_id'] ?? 0);
            $regulationId = (int)($_POST['regulation_id'] ?? 0);
            $contentId = (int)($_POST['content_id'] ?? 0);
            $db = DB::getInstance();
            $this->requireSupplierRecord('material', $materialId, '/compliance/detail?material_id=' . $materialId);
            $db->execute("INSERT INTO exemption_record (material_id, substance_id, regulation_id, content_id, exempt_id, exempt_name, is_limit, remark) VALUES (?,?,?,?,?,?,?,?)",
                [$materialId, $substanceId, $regulationId, $contentId ?: null,
                 $_POST['exempt_id'] ?? '', $_POST['exempt_name'] ?? '', (int)($_POST['is_limit'] ?? 0),
                 $_POST['remark'] ?? '']);
            logOp('create', 'exemption', $materialId, '添加豁免记录');
            flash('success', '豁免已添加');
            header('Location: /compliance/detail?material_id=' . $materialId); exit;
        }
    }
}
