<?php
class ReportController extends Controller {
    public function index() {
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') { $this->doCreate(); return; }
            if ($action === 'update') { $this->doUpdate(); return; }
            if ($action === 'delete') { $this->doDelete(); return; }
            if ($action === 'element_add') { $this->elementAdd(); return; }
            if ($action === 'element_delete') { $this->elementDelete(); return; }
            if ($action === 'element_batch') { $this->elementBatch(); return; }
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'edit') { $this->showForm((int)($_GET['id'] ?? 0)); return; }
        $this->showList();
    }
    public function audit() {
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['audit_status'] ?? '';
            $comment = $_POST['audit_comment'] ?? '';
            if ($id && $status) {
                $db->execute("UPDATE test_report SET audit_status=?, audit_user=?, audit_time=datetime('now','localtime'), audit_comment=? WHERE id=?", [$status, $_SESSION['realname'] ?? '', $comment, $id]);
                if ($status === 'approved') {
                    $r = $db->single("SELECT material_id FROM test_report WHERE id=?", [$id], true);
                    if ($r && $r['material_id']) {
                        $this->syncMaterialStatus($r['material_id']);
                    }
                }
                logOp('audit', 'report', $id, "报告审核 $status - $comment");
                flash('success', '审核完成');
            }
            header('Location: /report/audit'); exit;
        }
        $status = $_GET['status'] ?? 'pending';
        $reports = $db->query("SELECT tr.*, m.material_name, s.name AS supplier_name FROM test_report tr LEFT JOIN material m ON tr.material_id=m.id LEFT JOIN supplier s ON m.supplier_id=s.id WHERE tr.audit_status=? ORDER BY tr.created_at DESC", [$status]);
        $title = '报告审核';
        $this->render('report/audit', get_defined_vars());
    }
    public function elements() {
        $db = DB::getInstance();
        $reportId = (int)($_GET['report_id'] ?? $_POST['report_id'] ?? 0);
        $search = trim($_GET['search'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'element_add') { $this->elementAdd(); return; }
            if ($action === 'element_delete') { $this->elementDelete(); return; }
            if ($action === 'element_batch') { $this->elementBatch(); return; }
        }
        $reportWhere = $search ? " WHERE tr.report_no LIKE ? OR tr.file_path LIKE ?" : '';
        $reportParams = $search ? ["%$search%", "%$search%"] : [];
        $reports = $db->query("SELECT tr.id, tr.report_no, tr.file_path FROM test_report tr$reportWhere ORDER BY tr.report_no", $reportParams);
        $elements = $reportId ? $db->query("SELECT ec.*, tr.report_no FROM element_content ec JOIN test_report tr ON ec.report_id=tr.id WHERE ec.report_id=? ORDER BY ec.id", [$reportId]) : [];
        $title = '物质含量管理';
        $this->render('report/elements', get_defined_vars());
    }
    private function showList() {
        $db = DB::getInstance();
        $search = $_GET['search'] ?? '';
        $type = $_GET['report_type'] ?? '';
        $audit = $_GET['audit_status'] ?? '';
        $where = []; $params = [];
        if (isSupplier()) {
            $sid = userSupplierId();
            $where[] = "((r.source_type='material' AND r.source_id IN (SELECT id FROM material WHERE supplier_id=$sid)) OR (r.source_type='hm' AND r.source_id IN (SELECT id FROM homogeneous_material WHERE supplier_id=$sid)))";
        }
        if ($search) { $where[] = "(r.report_no LIKE ? OR r.test_agency LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($type) { $where[] = "r.report_type=?"; $params[] = $type; }
        if ($audit) { $where[] = "r.audit_status=?"; $params[] = $audit; }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT r.id, r.report_no, r.report_type, r.test_agency, r.test_date, r.expiry_date,
                   r.file_path, r.file_size, r.audit_status, r.created_at,
                   r.source_type, r.source_id,
                   COALESCE(r.material_name, r.hm_name) AS item_name
            FROM (
                SELECT tr.id, tr.report_no, tr.report_type, tr.test_agency, tr.test_date, tr.expiry_date,
                       tr.file_path, tr.file_size, tr.audit_status, tr.created_at,
                       'material' AS source_type, tr.material_id AS source_id,
                       m.material_name, NULL AS hm_name
                FROM test_report tr
                LEFT JOIN material m ON tr.material_id=m.id

                UNION ALL

                SELECT hr.id, hr.report_no, hr.report_type, hr.test_agency, hr.test_date, hr.expiry_date,
                       hr.file_path, hr.file_size, 'approved' AS audit_status, hr.created_at,
                       'hm' AS source_type, hr.hm_id AS source_id,
                       NULL AS material_name, hm.material_name AS hm_name
                FROM hm_report hr
                LEFT JOIN homogeneous_material hm ON hr.hm_id=hm.id
            ) r
            $whereSql
            ORDER BY r.created_at DESC
        ";
        $reports = $db->query($sql, $params);
        $title = '检测报告';
        $this->render('report/index', get_defined_vars());
    }
    private function showForm($id) {
        $db = DB::getInstance();
        $item = $db->single("SELECT * FROM test_report WHERE id=?", [$id], true);
        if (!$item) { header('Location: /report'); exit; }
        $materials = $db->query("SELECT id, material_name, material_no FROM material WHERE valid_status='Valid' ORDER BY material_name");
        $title = '编辑报告';
        $this->render('report/form', get_defined_vars());
    }
    private function doCreate() {
        $db = DB::getInstance();
        $no = $_POST['report_no'] ?? '';
        if (!$no) $no = 'R' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $materialId = (int)($_POST['material_id'] ?? 0);
        $testDate = $_POST['test_date'] ?? '';
        $effectiveDate = $_POST['effective_date'] ?? $testDate;
        $expiryDate = $_POST['expiry_date'] ?? ($testDate ? date('Y-m-d', strtotime($testDate . ' +1 year')) : '');
        $filePath = '';
        if (!empty($_FILES['report_file']['name'])) {
            $filePath = $this->handleUpload($_FILES['report_file'], $materialId);
        }
        $db->execute("INSERT INTO test_report (report_no, material_id, report_type, report_version, test_agency, test_date, issue_date, effective_date, expiry_date, file_path, file_size, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [$no, $materialId ?: null, $_POST['report_type'] ?? '', $_POST['report_version'] ?? 'V1.0',
             $_POST['test_agency'] ?? '', $testDate, $_POST['issue_date'] ?? '', $effectiveDate, $expiryDate,
             $filePath, (int)($_FILES['report_file']['size'] ?? 0), userId()]);
        $rid = $db->lastInsertId();
        if ($materialId) {
            $db->execute("UPDATE material SET audit_status='S', updated_at=datetime('now','localtime') WHERE id=?", [$materialId]);
        }
        logOp('create', 'report', $rid, '上传报告: ' . $no);
        flash('success', '报告上传成功');
        header('Location: /report'); exit;
    }
    private function doUpdate() {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) return;
        $db = DB::getInstance();
        $item = $db->single("SELECT * FROM test_report WHERE id=?", [$id], true);
        $materialId = (int)($_POST['material_id'] ?? 0);
        $filePath = $item['file_path'] ?? '';
        if (!empty($_FILES['report_file']['name'])) {
            if ($filePath && file_exists($filePath)) unlink($filePath);
            $filePath = $this->handleUpload($_FILES['report_file'], $materialId);
        }
        $testDate = $_POST['test_date'] ?? '';
        $expiryDate = $_POST['expiry_date'] ?? ($testDate ? date('Y-m-d', strtotime($testDate . ' +1 year')) : '');
        $db->execute("UPDATE test_report SET material_id=?, report_type=?, report_version=?, test_agency=?, test_date=?, issue_date=?, effective_date=?, expiry_date=?, file_path=?, file_size=?, updated_at=datetime('now','localtime') WHERE id=?",
            [$materialId ?: null, $_POST['report_type'] ?? '', $_POST['report_version'] ?? 'V1.0',
             $_POST['test_agency'] ?? '', $testDate, $_POST['issue_date'] ?? '',
             $_POST['effective_date'] ?? $testDate, $expiryDate, $filePath,
             (int)($_FILES['report_file']['size'] ?? 0), $id]);
        logOp('update', 'report', $id, '更新报告');
        flash('success', '报告更新成功');
        header('Location: /report'); exit;
    }
    private function doDelete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = DB::getInstance();
            $r = $db->single("SELECT * FROM test_report WHERE id=?", [$id], true);
            if ($r && $r['file_path'] && file_exists($r['file_path'])) unlink($r['file_path']);
            $db->execute("DELETE FROM element_content WHERE report_id=?", [$id]);
            $db->execute("DELETE FROM warning_record WHERE report_id=?", [$id]);
            $db->execute("DELETE FROM test_report WHERE id=?", [$id]);
            logOp('delete', 'report', $id, '删除报告');
            flash('success', '报告已删除');
        }
        header('Location: /report'); exit;
    }
    private function handleUpload($file, $materialId) {
        $dir = uploadPath($_SESSION['username'] ?? 'unknown');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $name = pathinfo($file['name'], PATHINFO_FILENAME) . '_' . date('Ymd') . '.' . $ext;
        $dest = $dir . '/' . $name;
        move_uploaded_file($file['tmp_name'], $dest);
        return $dest;
    }
    private function elementAdd() {
        $db = DB::getInstance();
        $reportId = (int)($_POST['report_id'] ?? 0);
        if ($reportId) {
            $value = (float)($_POST['content_value'] ?? 0);
            $limitVal = (float)($_POST['limit_value'] ?? 0);
            $isOver = ($limitVal > 0 && $value > $limitVal) ? 1 : 0;
            $db->execute("INSERT INTO element_content (report_id, substance_name, cas_no, content_value, unit, limit_value, limit_unit, is_over_limit, detection_method) VALUES (?,?,?,?,?,?,?,?,?)",
                [$reportId, $_POST['substance_name'] ?? '', $_POST['cas_no'] ?? '', $value,
                 $_POST['unit'] ?? 'mg/kg', $limitVal, $_POST['limit_unit'] ?? 'mg/kg', $isOver,
                 $_POST['detection_method'] ?? '']);
            if ($isOver) {
                $r = $db->single("SELECT material_id FROM test_report WHERE id=?", [$reportId], true);
                if ($r && $r['material_id']) {
                    $db->execute("INSERT INTO warning_record (material_id, report_id, warning_type, warning_level, warning_desc, created_at) VALUES (?,?,'over_limit','red',?,datetime('now','localtime'))",
                        [$r['material_id'], $reportId, $_POST['substance_name'] . ' 超标']);
                }
            }
            logOp('create', 'element_content', $reportId, '添加检测元素');
            flash('success', '元素已添加');
        }
        header('Location: /report/elements?report_id=' . $reportId); exit;
    }
    private function elementDelete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $e = DB::getInstance()->single("SELECT report_id FROM element_content WHERE id=?", [$id], true);
            DB::getInstance()->execute("DELETE FROM element_content WHERE id=?", [$id]);
            if ($e) { header('Location: /report/elements?report_id=' . $e['report_id']); exit; }
        }
        header('Location: /report/elements'); exit;
    }
    private function elementBatch() {
        $reportId = (int)($_POST['report_id'] ?? 0);
        if ($reportId && isset($_POST['substances'])) {
            $db = DB::getInstance();
            $db->execute("DELETE FROM element_content WHERE report_id=?", [$reportId]);
            foreach ($_POST['substances'] as $i => $name) {
                if (empty($name)) continue;
                $value = (float)($_POST['values'][$i] ?? 0);
                $limitVal = (float)($_POST['limits'][$i] ?? 0);
                $isOver = ($limitVal > 0 && $value > $limitVal) ? 1 : 0;
                $db->execute("INSERT INTO element_content (report_id, substance_name, cas_no, content_value, unit, limit_value, limit_unit, is_over_limit, detection_method) VALUES (?,?,?,?,?,?,?,?,?)",
                    [$reportId, $name, $_POST['cas_list'][$i] ?? '', $value, $_POST['units'][$i] ?? 'mg/kg',
                     $limitVal, $_POST['limit_units'][$i] ?? 'mg/kg', $isOver, $_POST['methods'][$i] ?? '']);
            }
            flash('success', '批量保存完毕');
        }
        header('Location: /report/elements?report_id=' . $reportId); exit;
    }
    private function syncMaterialStatus($materialId) {
        $db = DB::getInstance();
        $hasOverLimit = $db->single("SELECT COUNT(*) FROM element_content ec JOIN test_report tr ON ec.report_id=tr.id WHERE tr.material_id=? AND tr.audit_status='approved' AND ec.is_over_limit=1", [$materialId]);
        $db->execute("UPDATE material SET env_status=? WHERE id=?", [$hasOverLimit ? 'non_compliant' : 'compliant', $materialId]);
    }
}
