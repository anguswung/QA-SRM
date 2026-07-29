<?php
class MaterialController extends Controller {
    public function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') { $this->doCreate(); return; }
            if ($action === 'update') { $this->doUpdate(); return; }
            if ($action === 'delete') { $this->doDelete(); return; }
            if ($action === 'audit') { $this->doAudit(); return; }
            if ($action === 'composition_add') { $this->compositionAdd(); return; }
            if ($action === 'composition_delete') { $this->compositionDelete(); return; }
            if ($action === 'composition_update') { $this->compositionUpdate(); return; }
            if ($action === 'composition_batch_update') { $this->compositionBatchUpdate(); return; }
            if ($action === 'import') { $this->doImport(); return; }
            if ($action === 'submit_audit') { $this->submitAudit(); return; }
            if ($action === 'batch_submit_audit') { $this->batchSubmitAudit(); return; }
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'create') { $this->showForm(); return; }
        if ($action === 'edit') { $this->showForm((int)($_GET['id'] ?? 0)); return; }
        if ($action === 'detail') { $this->detail((int)($_GET['id'] ?? 0)); return; }
        if ($action === 'hm_data') { $this->hmData(); return; }
        if ($action === 'mcd_template') { $this->mcdTemplate(); return; }
        if ($action === 'hm_template') { $this->hmTemplate(); return; }
        if ($action === 'import_template') { $this->importTemplate(); return; }
        $this->showList();
    }
    private function showList() {
        $this->requirePermission('material.view');
        $db = DB::getInstance();
        $search = $_GET['search'] ?? '';
        $env = $_GET['env_status'] ?? '';
        $audit = $_GET['audit_status'] ?? '';
        $where = ["m.spec='零部件'"]; $params = [];
        if (isSupplier()) { $where[] = "m.supplier_id=?"; $params[] = userSupplierId(); }
        if ($search) { $where[] = "(m.material_no LIKE ? OR m.material_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($env) { $where[] = "m.env_status=?"; $params[] = $env; }
        if ($audit) { $where[] = "m.audit_status=?"; $params[] = $audit; }
        $materials = $db->query("SELECT m.*, s.name AS supplier_name, s.name_short AS supplier_name_short FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id WHERE " . implode(' AND ', $where) . " ORDER BY m.id DESC", $params);
        $countSql = "SELECT COUNT(*) FROM material m WHERE " . implode(' AND ', $where);
        $total = $db->single($countSql, $params);
        $compliantCount = $db->single($countSql . " AND m.env_status='compliant'", $params);
        $nonCompliantCount = $db->single($countSql . " AND m.env_status='non_compliant'", $params);
        $pendingCount = $db->single($countSql . " AND m.env_status='pending'", $params);
        $configs = $db->query("SELECT config_key, config_value FROM system_config");
        $title = '物料管理';
        $this->render('material/index', get_defined_vars());
    }
    private function showForm($id = 0) {
        $this->requirePermission($id ? 'material.edit' : 'material.create');
        $db = DB::getInstance();
        $item = $id ? $db->single("SELECT * FROM material WHERE id=?", [$id], true) : [];
        if ($id && !$item) { header('Location: /material'); exit; }
        if ($id && isSupplier() && (int)($item['supplier_id'] ?? 0) !== userSupplierId()) { header('Location: /material?error=no_permission'); exit; }
        $suppliers = $db->query("SELECT id, code, name FROM supplier WHERE blacklist=0 ORDER BY code");
        $hmMaterials = $db->query("SELECT id, material_name, supplier_id, supplier_name, weight_mg FROM homogeneous_material ORDER BY material_name");
        $composition = $id ? $db->query("SELECT * FROM material_composition WHERE material_id=?", [$id]) : [];
        $configs = $db->query("SELECT config_key, config_value FROM system_config");
        $supplierName = '-';
        if (!empty($item['supplier_id'])) {
            $s = $db->single("SELECT name FROM supplier WHERE id=?", [$item['supplier_id']], true);
            if ($s) $supplierName = $s['name'];
        }
        $isImported = $id && !empty($item['created_by']) && (int)$item['created_by'] !== userId();
        $currentStep = (int)($_GET['step'] ?? 1);
        $title = $id ? '编辑物料' : '新增物料';
        $this->render('material/form', get_defined_vars());
    }
    private function detail($id) {
        $this->requirePermission('material.view');
        $db = DB::getInstance();
        $item = $db->single("SELECT m.*, s.name AS supplier_name FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id WHERE m.id=?", [$id], true);
        if (!$item) { header('Location: /material'); exit; }
        if (isSupplier() && (int)$item['supplier_id'] !== userSupplierId()) { header('Location: /material?error=no_permission'); exit; }
        $composition = $db->query("SELECT mc.*,
          (SELECT id FROM hm_report WHERE hm_id=mc.substance_id AND report_type='RoHS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS rohs_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=mc.substance_id AND report_type='RoHS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS rohs_expiry,
          (SELECT id FROM hm_report WHERE hm_id=mc.substance_id AND report_type='REACH' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS reach_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=mc.substance_id AND report_type='REACH' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS reach_expiry,
          (SELECT id FROM hm_report WHERE hm_id=mc.substance_id AND report_type='HF' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS hf_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=mc.substance_id AND report_type='HF' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS hf_expiry,
          (SELECT id FROM hm_report WHERE hm_id=mc.substance_id AND report_type='MSDS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS msds_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=mc.substance_id AND report_type='MSDS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS msds_expiry
          FROM material_composition mc WHERE mc.material_id=?", [$id]);
        $reports = $db->query("SELECT * FROM test_report WHERE material_id=? ORDER BY created_at DESC", [$id]);
        $auditLogs = $db->query("SELECT * FROM material_audit_log WHERE material_id=? ORDER BY operated_at DESC", [$id]);
        $elements = $db->query("SELECT ec.*, tr.report_no FROM element_content ec JOIN test_report tr ON ec.report_id=tr.id WHERE tr.material_id=? ORDER BY ec.id", [$id]);
        // Aggregate HM reports & analysis counts from composition substances
        $hmIds = array_column($composition, 'substance_id');
        $hmIds = array_filter(array_map('intval', $hmIds));
        $totalReports = count($reports);
        $totalElements = count($elements);
        if ($hmIds) {
            $hmList = implode(',', $hmIds);
            $totalReports += (int)$db->single("SELECT COUNT(*) FROM hm_report WHERE hm_id IN ($hmList)");
            $totalElements += (int)$db->single("SELECT COUNT(*) FROM analysis_record WHERE hm_id IN ($hmList)");
        }
        $hmMaterials = $db->query("SELECT id, material_name, supplier_id, supplier_name, weight_mg FROM homogeneous_material ORDER BY material_name");
        $additives = $db->query("SELECT a.* FROM additive a WHERE a.material_category_id=(SELECT id FROM material_category WHERE name=(SELECT category FROM material WHERE id=?))", [$id]);
        $activeTab = $_GET['tab'] ?? 'composition';
        $title = '物料详情 - ' . $item['material_name'];
        $this->render('material/detail', get_defined_vars());
    }
    public function hmData() {
        $this->requirePermission('homogeneous.view');
        $hmId = (int)($_GET['hm_id'] ?? 0);
        if (!$hmId) { echo json_encode(['error' => 'no hm_id']); exit; }
        $db = DB::getInstance();
        $hm = $db->single("SELECT * FROM homogeneous_material WHERE id=?", [$hmId], true);
        if (!$hm) { echo json_encode(['error' => 'not found']); exit; }
        $reports = $db->query("SELECT * FROM hm_report WHERE hm_id=? ORDER BY version_group, version DESC", [$hmId]);
        $analysis = $db->query("SELECT ar.*, rl.name AS regulation_name, rl.regulation_no FROM analysis_record ar LEFT JOIN regulation_lib rl ON ar.regulation_id=rl.id WHERE ar.hm_id=? ORDER BY ar.analyzed_at DESC LIMIT 50", [$hmId]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['hm' => $hm, 'reports' => $reports, 'analysis' => $analysis], JSON_UNESCAPED_UNICODE);
        exit;
    }
    private function doCreate() {
        $this->requirePermission('material.create');
        $db = DB::getInstance();
        $step = (int)($_POST['step'] ?? 1);
        $spec = $_POST['spec'] ?? '';
        $isHM = ($spec === '均质原材料');
        $no = $isHM ? null : (trim($_POST['material_no'] ?? '') ?: null);
        if (!$isHM && !$no) { flash('error', '物料编号不能为空'); header('Location: ' . $_SERVER['HTTP_REFERER']); exit; }
        $supplierId = isSupplier() ? userSupplierId() : ((int)($_POST['supplier_id'] ?? 0) ?: ($isHM ? 2 : 0));
        // step=2: submit from step1 ??save basic info only, show step2 next
        // step=3: submit from step2 ??save composition + report, show step3 (confirm) next
        // step=4: submit from step3 ??final save, redirect to list
        if ($step == 2) {
            $db->execute("INSERT INTO material (material_no, material_name, spec, mpn, supplier_id, unit_weight, weight_mg, env_status) VALUES (?,?,?,?,?,?,?,?)",
                [$no, $_POST['material_name'] ?? '', $spec, $_POST['mpn'] ?? '',
                 $supplierId, (float)($_POST['unit_weight'] ?? 0), (float)($_POST['weight_mg'] ?? 0), 'non_compliant']);
            $mid = $db->lastInsertId();
            logOp('create', 'material', $mid, '新增物料(步骤2): ' . ($_POST['material_name'] ?? ''));
            header('Location: /material?action=edit&id=' . $mid . '&step=2'); exit;
        }
        $mid = (int)($_POST['id'] ?? 0);
        if ($step == 3) { $this->saveComposition($mid); $this->saveReport($mid); logOp('update', 'material', $mid, '保存成分与报告(步骤3)'); header('Location: /material?action=edit&id=' . $mid . '&step=3'); exit; }
        // step 4: final save all + redirect
        $this->saveComposition($mid); $this->saveReport($mid);
        $db->execute("UPDATE material SET material_no=?, material_name=?, spec=?, mpn=?, unit_weight=?, weight_mg=?, updated_at=datetime('now','localtime') WHERE id=?",
            [$_POST['material_no'] ?? '', $_POST['material_name'] ?? '', $spec, $_POST['mpn'] ?? '',
             (float)($_POST['unit_weight'] ?? 0), (float)($_POST['weight_mg'] ?? 0), $mid]);
        logOp('create', 'material', $mid, '完成物料创建 ' . ($_POST['material_name'] ?? ''));
        flash('success', '物料创建成功'); header('Location: /material'); exit;
    }
    private function doUpdate() {
        $this->requirePermission('material.edit');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) return;
        $step = (int)($_POST['step'] ?? 4);
        $db = DB::getInstance();
        if (isSupplier()) {
            $m = $db->single("SELECT supplier_id FROM material WHERE id=?", [$id], true);
            if (!$m || (int)$m['supplier_id'] !== userSupplierId()) { header('Location: /material'); exit; }
        }
        $spec = $_POST['spec'] ?? '';
        if ($step == 2) {
            $db->execute("UPDATE material SET material_no=?, material_name=?, spec=?, mpn=?, supplier_id=?, unit_weight=?, updated_at=datetime('now','localtime') WHERE id=?",
                [$_POST['material_no'] ?? '', $_POST['material_name'] ?? '', $spec, $_POST['mpn'] ?? '',
                 (int)($_POST['supplier_id'] ?? 0), (float)($_POST['unit_weight'] ?? 0), $id]);
            logOp('update', 'material', $id, '更新基本信息(步骤2)');
            header('Location: /material?action=edit&id=' . $id . '&step=2'); exit;
        }
        if ($step == 3) {
            $this->handleMcdUpload($id);
            $db->execute("DELETE FROM material_composition WHERE material_id=?", [$id]); $this->saveComposition($id);
            $this->saveReport($id);
            logOp('update', 'material', $id, '更新成分与报告(步骤3)'); header('Location: /material?action=edit&id=' . $id . '&step=3'); exit;
        }
        $db->execute("DELETE FROM material_composition WHERE material_id=?", [$id]); $this->saveComposition($id); $this->saveReport($id);
        $db->execute("UPDATE material SET material_no=?, material_name=?, spec=?, mpn=?, supplier_id=?, unit_weight=?, updated_at=datetime('now','localtime') WHERE id=?",
            [$_POST['material_no'] ?? '', $_POST['material_name'] ?? '', $spec, $_POST['mpn'] ?? '',
             (int)($_POST['supplier_id'] ?? 0), (float)($_POST['unit_weight'] ?? 0), $id]);
        logOp('update', 'material', $id, '完成物料更新');
        flash('success', '物料更新成功'); header('Location: /material'); exit;
    }
    private function doDelete() {
        $this->requirePermission('material.delete');
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = DB::getInstance();
            if (isSupplier()) {
                $m = $db->single("SELECT supplier_id FROM material WHERE id=?", [$id], true);
                if (!$m || (int)$m['supplier_id'] !== userSupplierId()) { header('Location: /material'); exit; }
            }
            $db->execute("DELETE FROM material_composition WHERE material_id=?", [$id]);
            $db->execute("DELETE FROM material_audit_log WHERE material_id=?", [$id]);
            $reports = $db->query("SELECT * FROM test_report WHERE material_id=?", [$id]);
            foreach ($reports as $r) {
                if ($r['file_path'] && file_exists($r['file_path'])) unlink($r['file_path']);
                $db->execute("DELETE FROM element_content WHERE report_id=?", [$r['id']]);
                $db->execute("DELETE FROM warning_record WHERE report_id=?", [$r['id']]);
                $db->execute("DELETE FROM test_report WHERE id=?", [$r['id']]);
            }
            $db->execute("DELETE FROM analysis_record WHERE material_id=?", [$id]);
            $db->execute("DELETE FROM exemption_record WHERE material_id=?", [$id]);
            $db->execute("DELETE FROM material WHERE id=?", [$id]);
            logOp('delete', 'material', $id, '删除物料');
            flash('success', '物料已删除');
        }
        header('Location: /material'); exit;
    }
    private function submitAudit() {
        $this->requirePermission('material.edit');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { header('Location: /material'); exit; }
        $db = DB::getInstance();
        if (isSupplier()) {
            $m = $db->single("SELECT supplier_id FROM material WHERE id=?", [$id], true);
            if (!$m || (int)$m['supplier_id'] !== userSupplierId()) { header('Location: /material'); exit; }
        }
        $db->execute("UPDATE material SET audit_status='S', updated_at=datetime('now','localtime') WHERE id=?", [$id]);
        logOp('update', 'material', $id, '提交审核');
        flash('success', '已提交审核');
        header('Location: /material'); exit;
    }
    private function batchSubmitAudit() {
        $this->requirePermission('material.edit');
        $ids = $_POST['ids'] ?? '';
        if (!$ids) { header('Location: /material'); exit; }
        $idArr = array_map('intval', explode(',', $ids));
        $idArr = array_filter($idArr);
        if (!$idArr) { header('Location: /material'); exit; }
        $db = DB::getInstance();
        $placeholders = implode(',', array_fill(0, count($idArr), '?'));
        $params = $idArr;
        if (isSupplier()) {
            $params[] = userSupplierId();
            $db->execute("UPDATE material SET audit_status='S', updated_at=datetime('now','localtime') WHERE id IN ($placeholders) AND supplier_id=?", $params);
        } else {
            $db->execute("UPDATE material SET audit_status='S', updated_at=datetime('now','localtime') WHERE id IN ($placeholders)", $params);
        }
        logOp('update', 'material', 0, '批量提交审核 ' . count($idArr) . ' 项');
        flash('success', '已提交 ' . count($idArr) . ' 项物料审核');
        header('Location: /material'); exit;
    }
    private function doAudit() {
        $this->requirePermission('material.audit');
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['audit_status'] ?? '';
        $comment = $_POST['audit_comment'] ?? '';
        if ($id && $status) {
            $db = DB::getInstance();
            $db->execute("UPDATE material SET audit_status=?, audit_user=?, audit_time=datetime('now','localtime'), audit_comment=? WHERE id=?",
                [$status, $_SESSION['realname'] ?? '', $comment, $id]);
            $db->execute("INSERT INTO material_audit_log (material_id, action, comment, operator_id) VALUES (?,?,?,?)",
                [$id, $status === 'A' ? 'approve' : 'reject', $comment, userId()]);
            if ($status === 'A') {
                $db->execute("UPDATE material SET env_status='compliant' WHERE id=?", [$id]);
            } elseif ($status === 'R') {
                $db->execute("UPDATE material SET env_status='non_compliant' WHERE id=?", [$id]);
            }
            logOp('audit', 'material', $id, "物料审核 $status - $comment");
            flash('success', '审核完成');
        }
        header('Location: /material'); exit;
    }
    private function saveComposition($materialId) {
        $db = DB::getInstance();
        $db->execute("DELETE FROM material_composition WHERE material_id=?", [$materialId]);
        $hmIds = $_POST['hm_id'] ?? [];
        $quantities = $_POST['min_percentage'] ?? [];
        foreach ($hmIds as $i => $hid) {
            $hid = (int)$hid;
            if (!$hid) continue;
            $hm = $db->single("SELECT id, material_name FROM homogeneous_material WHERE id=?", [$hid], true);
            if ($hm) {
                $db->execute("INSERT INTO material_composition (material_id, substance_id, substance_name, cas_no, min_percentage, max_percentage) VALUES (?,?,?,?,?,?)",
                    [$materialId, $hm['id'], $hm['material_name'] ?? '', '',
                     (float)($quantities[$i] ?? 0), 0]);
            }
        }
    }
    private function mcdTemplate() {
        $this->requirePermission('material.view');
        require_once __DIR__ . '/../../vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MCD模板');
        $db = DB::getInstance();
        $sysName = $db->single("SELECT config_value FROM system_config WHERE config_key='system_name'", [], false) ?: '绿色产品管理系统GPM';
        $sheet->setCellValue('A1', $sysName . ' - MCD材料成分表');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A3', '物料编号');
        $sheet->setCellValue('B3', '物料名称');
        $sheet->setCellValue('C3', '均质材料ID');
        $sheet->setCellValue('D3', '均质材料名称');
        $sheet->setCellValue('E3', '用量');
        $sheet->getStyle('A3:E3')->getFont()->setBold(true);
        $sheet->setCellValue('A4', '示例: MAT20250101');
        $sheet->setCellValue('B4', '示例: 连接器');
        $sheet->setCellValue('C4', '示例: 1');
        $sheet->setCellValue('D4', '示例: 锡膏');
        $sheet->setCellValue('E4', '示例: 50');
        foreach (range('A','E') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="MCD模板.xlsx"');
        ob_clean();
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }
    private function hmTemplate() {
        $this->requirePermission('material.view');
        require_once __DIR__ . '/../../vendor/autoload.php';
        $db = DB::getInstance();
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('均质材料元素明细');
        $header = ['均质材料ID','均质材料名称','均质材料供应商','重量(mg)','均质材料名称','中文名称','CAS号','含量(%)','最小(%)','最大(%)','单位'];
        foreach ($header as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col.'1', $h);
        }
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->getRGB('DBEAFE');
        $hms = $db->query("SELECT id, material_name, supplier_name AS hm_supplier_name, weight_mg FROM homogeneous_material ORDER BY id");
        $allComps = $db->query("SELECT hc.*, hm.material_name FROM hm_composition hc JOIN homogeneous_material hm ON hc.hm_id=hm.id ORDER BY hm.id, hc.id");
        $compsByHm = [];
        foreach ($allComps as $c) { $compsByHm[$c['hm_id']][] = $c; }
        $row = 2;
        foreach ($hms as $hm) {
            $hmComps = $compsByHm[$hm['id']] ?? [null];
            foreach ($hmComps as $c) {
                $sheet->setCellValue('A'.$row, $hm['id']);
                $sheet->setCellValue('B'.$row, $hm['material_name']);
                $sheet->setCellValue('C'.$row, $hm['hm_supplier_name'] ?? '');
                $sheet->setCellValue('D'.$row, $hm['weight_mg'] ?? '');
                $sheet->setCellValue('E'.$row, $c['substance_name'] ?? '');
                $sheet->setCellValue('F'.$row, $c['name_cn'] ?? '');
                $sheet->setCellValue('G'.$row, $c['cas_no'] ?? '');
                $sheet->setCellValue('H'.$row, $c['percentage'] ?? '');
                $sheet->setCellValue('I'.$row, $c['min_pct'] ?? '');
                $sheet->setCellValue('J'.$row, $c['max_pct'] ?? '');
                $sheet->setCellValue('K'.$row, 'mg/kg');
                $row++;
            }
        }
        foreach (range('A','K') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="物料均质模板.xlsx"');
        ob_clean();
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }
    private function importTemplate() {
        $this->requirePermission('material.create');
        require_once __DIR__ . '/../../vendor/autoload.php';
        $db = DB::getInstance();
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('物料列表');
        $sheet->setCellValue('A1', '物料导入模板');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $headers = [
            'A' => "*物料编号\nPart No.",
            'B' => "*物料名称\nPart Name",
            'C' => "*物料供应商\nSupplier Name",
            'D' => "制造商料号\nMPN",
            'E' => "单位重量(g)\nUnit Weight",
            'F' => "单位\nUnit",
        ];
        foreach ($headers as $col => $title) {
            $sheet->setCellValue($col.'2', $title);
        }
        $sheet->getStyle('A2:F2')->getFont()->setBold(true);
        $sheet->getStyle('A2:F2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->getRGB('E2E8F0');
        $sheet->getStyle('A2:F2')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(2)->setRowHeight(45);
        $materials = $db->query("SELECT m.*, s.name AS supplier_name FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id ORDER BY m.id DESC LIMIT 3");
        if (empty($materials)) {
            $materials = [[
                'material_no' => 'MAT202507001', 'material_name' => '示例物料-连接器',
                'supplier_name' => '深圳供应商', 'mpn' => 'MPN-001', 'unit_weight' => 150, 'weight_unit' => 'g',
            ]];
        }
        $row = 3;
        foreach ($materials as $m) {
            $sheet->setCellValue('A'.$row, $m['material_no']);
            $sheet->setCellValue('B'.$row, $m['material_name']);
            $sheet->setCellValue('C'.$row, $m['supplier_name'] ?? '');
            $sheet->setCellValue('D'.$row, $m['mpn'] ?? '');
            $sheet->setCellValue('E'.$row, $m['unit_weight'] ?? '');
            $sheet->setCellValue('F'.$row, $m['weight_unit'] ?? 'g');
            $row++;
        }
        $endRow = $row - 1;
        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ];
        if ($endRow >= 2) {
            $sheet->getStyle('A2:F'.$endRow)->applyFromArray($thinBorder);
        }
        foreach (range('A','F') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(20);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="物料列表模板.xlsx"');
        ob_clean();
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }
    private function doImport() {
        $this->requirePermission('material.create');
        if (empty($_FILES['import_file']['tmp_name'])) { flash('error', '请选择文件'); header('Location: /material'); exit; }
        $f = $_FILES['import_file'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xls','xlsx'])) { flash('error', '仅支持Excel文件'); header('Location: /material'); exit; }
        require_once __DIR__ . '/../../vendor/autoload.php';
        try {
            $reader = $ext === 'xlsx' ? new \PhpOffice\PhpSpreadsheet\Reader\Xlsx() : new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            $rows = $reader->load($f['tmp_name'])->getActiveSheet()->toArray();
        } catch (Exception $e) { flash('error', '文件解析失败'); header('Location: /material'); exit; }
        if (empty($rows)) { flash('error', '模板为空'); header('Location: /material'); exit; }
        $headerRow = -1;
        foreach ($rows as $idx => $row) {
            $rowStr = implode(',', array_map('strval', $row));
            if (str_contains($rowStr, '物料编号') && str_contains($rowStr, '物料名称')) {
                $headerRow = $idx; break;
            }
        }
        if ($headerRow < 0) { flash('error', '模板不匹配，请使用物料导入模板'); header('Location: /material'); exit; }
        $db = DB::getInstance();
        $imported = 0; $updated = 0; $errors = [];
        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $no = trim($row[0] ?? ''); $name = trim($row[1] ?? '');
            $supplierName = trim($row[2] ?? ''); $mpn = trim($row[3] ?? '');
            $unitWeight = (float)($row[4] ?? 0); $unit = trim($row[5] ?? 'g');
            if (!in_array($unit, ['g', 'M', 'Pcs'])) $unit = 'g';
            if (empty($no) && empty($name)) continue;
            $existing = $db->single("SELECT id FROM material WHERE material_no=? AND material_name=?", [$no, $name], true);
            if ($existing) {
                $supplierId = 0;
                if ($supplierName) { $s = $db->single("SELECT id FROM supplier WHERE name=? OR name_short=?", [$supplierName, $supplierName], true); if ($s) $supplierId = $s['id']; }
                if (isSupplier()) $supplierId = userSupplierId();
                try {
                    $db->execute("UPDATE material SET mpn=?, supplier_id=?, unit_weight=?, weight_unit=?, updated_at=datetime('now','localtime') WHERE id=?",
                        [$mpn, $supplierId, $unitWeight, $unit, $existing['id']]);
                    $updated++;
                } catch (Exception $e) { $errors[] = "第".($i+1)."行 ".$e->getMessage(); }
                continue;
            }
            if (!$no) {
                $maxNo = $db->single("SELECT material_no FROM material WHERE material_no LIKE 'MAT%' ORDER BY id DESC LIMIT 1");
                $no = $maxNo ? 'MAT' . date('Ymd') . str_pad(((int)substr($maxNo, -3) + 1), 3, '0', STR_PAD_LEFT) : 'MAT' . date('Ymd') . '001';
            }
            $supplierId = 0;
            if ($supplierName) { $s = $db->single("SELECT id FROM supplier WHERE name=? OR name_short=?", [$supplierName, $supplierName], true); if ($s) $supplierId = $s['id']; }
            if (isSupplier()) $supplierId = userSupplierId();
            try {
                $db->execute("INSERT INTO material (material_no, material_name, spec, mpn, supplier_id, unit_weight, weight_unit, created_by) VALUES (?,?,?,?,?,?,?,?)",
                    [$no, $name, '零部件', $mpn, $supplierId, $unitWeight, $unit, userId()]);
                $imported++;
            } catch (Exception $e) { $errors[] = "第".($i+1)."行 ".$e->getMessage(); }
        }
        logOp('import', 'material', 0, "批量导入$imported 个物料, 更新$updated 个物料");
        $msg = "成功导入$imported 个物料";
        if ($updated > 0) $msg .= ", 更新$updated 个物料";
        if (!empty($errors)) $msg .= '。' . implode('; ', array_slice($errors, 0, 5));
        flash($imported > 0 || $updated > 0 ? 'success' : 'error', $msg);
        header('Location: /material'); exit;
    }
    private function handleMcdUpload($materialId) {
        if (empty($_FILES['mcd_file']['name'])) return;
        $f = $_FILES['mcd_file'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xls','xlsx'])) return;
        try {
            require_once __DIR__ . '/../../vendor/autoload.php';
            $reader = $ext === 'xlsx' ? new \PhpOffice\PhpSpreadsheet\Reader\Xlsx() : new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            $spreadsheet = $reader->load($f['tmp_name']);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            // Find data rows (skip headers)
            $startRow = null;
            foreach ($rows as $idx => $row) {
                if (!empty($row[0]) && !empty($row[1]) && !empty($row[2])) {
                    $startRow = $idx; break;
                }
            }
            if ($startRow === null) return;
            $dir = uploadPath($_SESSION['username'] ?? 'unknown');
            $fileName = pathinfo($f['name'], PATHINFO_FILENAME) . '_' . date('Ymd') . '.' . $ext;
            move_uploaded_file($f['tmp_name'], $dir . '/' . $fileName);
            // Parse rows as HM composition
            $hmIds = $_POST['hm_id'] ?? [];
            $quantities = $_POST['min_percentage'] ?? [];
            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                $hmId = (int)($row[2] ?? 0);
                $qty = (float)($row[4] ?? 0);
                if (!$hmId || !$qty) continue;
                // Check if this HM is already in the list
                $found = false;
                foreach ($hmIds as $j => $hid) {
                    if ((int)$hid === $hmId) { $quantities[$j] = $qty; $found = true; break; }
                }
                if (!$found) {
                    $hmIds[] = $hmId;
                    $quantities[] = $qty;
                }
            }
            $_POST['hm_id'] = $hmIds;
            $_POST['min_percentage'] = $quantities;
            $db = DB::getInstance();
            $db->execute("UPDATE material SET mcd_file_path=? WHERE id=?", [$dir . '/' . $fileName, $materialId]);
        } catch (Exception $e) {}
    }
    private function compositionAdd() {
        $this->requirePermission('material.edit');
        $mid = (int)($_POST['material_id'] ?? 0);
        if (!$mid) { header('Location: /material'); exit; }
        $db = DB::getInstance();
        $hmId = (int)($_POST['hm_id'] ?? 0);
        if ($hmId) {
            $hm = $db->single("SELECT id, material_name, supplier_name FROM homogeneous_material WHERE id=?", [$hmId], true);
            if ($hm) {
                $db->execute("INSERT INTO material_composition (material_id, substance_id, substance_name, min_percentage, max_percentage) VALUES (?,?,?,?,?)",
                    [$mid, $hm['id'], $hm['material_name'],
                     (float)($_POST['min_percentage'] ?? 0), (float)($_POST['max_percentage'] ?? 0)]);
                flash('success', '成分已添加 ' . $hm['material_name']);
            }
        } else {
            $sub = $db->single("SELECT id, name, cas_no FROM substance WHERE id=?", [(int)($_POST['substance_id'] ?? 0)], true);
            if ($sub) {
                $db->execute("INSERT INTO material_composition (material_id, substance_id, substance_name, cas_no, min_percentage, max_percentage, is_main_component, remark) VALUES (?,?,?,?,?,?,?,?)",
                    [$mid, $sub['id'], $sub['name'], $sub['cas_no'],
                     (float)($_POST['min_percentage'] ?? 0), (float)($_POST['max_percentage'] ?? 0),
                     (int)($_POST['is_main_component'] ?? 0), $_POST['remark'] ?? '']);
                flash('success', '成分已添加');
            }
        }
        $this->updateMainComponent($mid);
        header('Location: /material?action=detail&id=' . $mid . '&tab=composition'); exit;
    }
    private function compositionDelete() {
        $this->requirePermission('material.edit');
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $c = DB::getInstance()->single("SELECT material_id FROM material_composition WHERE id=?", [$id], true);
            DB::getInstance()->execute("DELETE FROM material_composition WHERE id=?", [$id]);
            if ($c) {
                $this->updateMainComponent($c['material_id']);
                header('Location: /material?action=detail&id=' . $c['material_id'] . '&tab=composition'); exit;
            }
        }
        header('Location: /material'); exit;
    }
    private function compositionUpdate() {
        $this->requirePermission('material.edit');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => '无效ID']); exit; }
        $pct = (float)($_POST['min_percentage'] ?? 0);
        $db = DB::getInstance();
        $db->execute("UPDATE material_composition SET min_percentage=?, max_percentage=? WHERE id=?", [$pct, $pct, $id]);
        echo json_encode(['success' => true]);
        exit;
    }
    private function compositionBatchUpdate() {
        $this->requirePermission('material.edit');
        $mid = (int)($_POST['material_id'] ?? 0);
        if (!$mid) { header('Location: /material'); exit; }
        $db = DB::getInstance();
        $ids = $_POST['comp_id'] ?? [];
        $pcts = $_POST['comp_pct'] ?? [];
        foreach ($ids as $i => $id) {
            $id = (int)$id;
            $pct = (float)($pcts[$i] ?? 0);
            if ($id > 0) {
                $db->execute("UPDATE material_composition SET min_percentage=?, max_percentage=? WHERE id=? AND material_id=?", [$pct, $pct, $id, $mid]);
            }
        }
        $this->updateMainComponent($mid);
        flash('success', '成分已保存');
        header('Location: /material?action=detail&id=' . $mid);
        exit;
    }
    private function updateMainComponent(int $materialId): void {
        $db = DB::getInstance();
        $db->execute("UPDATE material_composition SET is_main_component=0 WHERE material_id=?", [$materialId]);
        $top = $db->single("SELECT id FROM material_composition WHERE material_id=? ORDER BY min_percentage DESC LIMIT 1", [$materialId]);
        if ($top) {
            $db->execute("UPDATE material_composition SET is_main_component=1 WHERE id=?", [$top]);
        }
    }
    private function saveReport($materialId) {
        $db = DB::getInstance();
        $username = $_SESSION['username'] ?? 'unknown';
        $baseDir = uploadPath($username);

        // Check for multiple report files (report_file_0, report_file_1, ...)
        $saved = 0;
        foreach ($_FILES as $key => $file) {
            if (strpos($key, 'report_file_') !== 0) continue;
            if (empty($file['name'])) continue;
            $idx = substr($key, strlen('report_file_'));
            $no = trim($_POST['report_no_' . $idx] ?? '') ?: ('R' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT));
            // Skip if already saved (prevents duplicate on step4→step5 final submit)
            $existing = $db->single("SELECT id FROM test_report WHERE report_no=? AND material_id=?", [$no, $materialId], true);
            if ($existing) { $saved++; continue; }
            $testDate = $_POST['test_date_' . $idx] ?? '';
            $expiryDate = $_POST['expiry_date_' . $idx] ?? ($testDate ? date('Y-m-d', strtotime($testDate . ' +1 year')) : '');
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $origName = pathinfo($file['name'], PATHINFO_FILENAME);
            $baseName = $origName . '_' . date('Ymd');
            $fileName = $baseName . '.' . $ext;
            $filePath = $baseDir . '/' . $fileName;
            $n = 1;
            while (file_exists($filePath)) {
                $fileName = $baseName . '_' . ($n++) . '.' . $ext;
                $filePath = $baseDir . '/' . $fileName;
            }
            move_uploaded_file($file['tmp_name'], $filePath);
            $db->execute("INSERT INTO test_report (report_no, material_id, report_type, test_agency, test_date, expiry_date, file_path, file_size, created_by) VALUES (?,?,?,?,?,?,?,?,?)",
                [$no, $materialId, $_POST['report_type_' . $idx] ?? '', $_POST['test_agency_' . $idx] ?? '', $testDate, $expiryDate, $filePath, $file['size'], userId()]);
            $saved++;
        }
        if ($saved > 0) {
            $db->execute("UPDATE material SET audit_status='S', updated_at=datetime('now','localtime') WHERE id=?", [$materialId]);
        }
    }
}
