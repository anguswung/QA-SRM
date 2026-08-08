<?php
class ReportController extends Controller {
    public function index() {
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') { $this->doCreate(); return; }
            if ($action === 'update') { $this->doUpdate(); return; }
            if ($action === 'delete') { $this->doDelete(); return; }
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'edit') { $this->showForm((int)($_GET['id'] ?? 0)); return; }
        $this->showList();
    }
    public function audit() {
        $this->requirePermission('report.audit');
        if (!isAdmin()) { flash('error', '无权操作'); header('Location: /report'); exit; }
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['audit_status'] ?? '';
            $comment = $_POST['audit_comment'] ?? '';
            if ($id && $status) {
                $db->execute("UPDATE test_report SET audit_status=?, audit_user=?, audit_time=datetime('now','localtime'), audit_comment=? WHERE id=?", [$status, $_SESSION['realname'] ?? '', $comment, $id]);
                logOp('audit', 'report', $id, "报告审核 $status - $comment");
                flash('success', '审核完成');
            }
            header('Location: /report/audit'); exit;
        }
        $status = $_GET['status'] ?? 'pending';
        $action = $_GET['action'] ?? '';
        if ($action === 'data') {
            $this->requirePermission('report.audit');
            $db = DB::getInstance();
            [$page, $perPage] = $this->pageParams();
            $total = (int)$db->single("SELECT COUNT(*) FROM test_report WHERE audit_status=?", [$status]);
            $offset = ($page - 1) * $perPage;
            $rows = $db->query("SELECT tr.*, m.material_name, s.name AS supplier_name FROM test_report tr LEFT JOIN material m ON tr.material_id=m.id LEFT JOIN supplier s ON m.supplier_id=s.id WHERE tr.audit_status=? ORDER BY tr.created_at DESC LIMIT ? OFFSET ?", [$status, $perPage, $offset]);
            $html = '';
            foreach ($rows as $r) {
                $html .= '<tr>';
                $html .= colId($r['id'] ?? '') . '<td>' . h($r['report_no']) . '</td>';
                $html .= '<td>' . h($r['material_name'] ?? '-') . '</td>';
                $html .= '<td>' . h($r['supplier_name'] ?? '-') . '</td>';
                $html .= '<td>' . badge($r['report_type'], 'info') . '</td>';
                $html .= '<td>' . h($r['test_agency']) . '</td>';
                $html .= '<td>' . h($r['test_date']) . '</td>';
                $html .= '<td>' . statusBadge($r['audit_status']) . '</td>';
                if (can('report.audit')) {
                    $html .= '<button class="btn-icon btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#auditModal" data-id="' . $r['id'] . '" data-status="approved"><i class="fas fa-check"></i></button>';
                    $html .= '<button class="btn-icon btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#auditModal" data-id="' . $r['id'] . '" data-status="rejected"><i class="fas fa-times"></i></button>';
                }
                $html .= '</td></tr>';
            }
            $this->paginationResponse($total, $html);
        }
        $title = '报告审核';
        $this->render('report/audit', get_defined_vars());
    }
    private function showList() {
        $db = DB::getInstance();
        $search = $_GET['search'] ?? '';
        $type = $_GET['report_type'] ?? '';
        $audit = $_GET['audit_status'] ?? '';
        $isSupplier = isSupplier();
        $where = []; $params = [];
        if ($isSupplier) {
            $sid = userSupplierId();
            $where[] = "(((r.source_type='material' AND r.source_id IN (SELECT id FROM material WHERE supplier_id=$sid))
                OR (r.source_type='hm' AND r.source_id IN (SELECT hm.id FROM homogeneous_material hm WHERE hm.supplier_id=$sid OR hm.created_by IN (SELECT u.id FROM users u WHERE u.supplier_id=$sid)))))";
        }
        if ($search) { $where[] = "(r.report_no LIKE ? OR r.test_agency LIKE ? OR r.supplier_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($type) { $where[] = "r.report_type=?"; $params[] = $type; }
        if ($audit) { $where[] = "r.audit_status=?"; $params[] = $audit; }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $action = $_GET['action'] ?? '';
        if ($action === 'data') { $this->data($where, $params); return; }
        $title = '检测报告';
        $this->render('report/index', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data）：report/index 列表 */
    private function data($where = null, $params = null) {
        $this->requirePermission('report.view');
        $db = DB::getInstance();
        if ($where === null) {
            $search = $_GET['search'] ?? '';
            $type = $_GET['report_type'] ?? '';
            $audit = $_GET['audit_status'] ?? '';
            $where = []; $params = [];
            if (isSupplier()) {
                $sid = userSupplierId();
                $where[] = "(((r.source_type='material' AND r.source_id IN (SELECT id FROM material WHERE supplier_id=$sid))
                    OR (r.source_type='hm' AND r.source_id IN (SELECT hm.id FROM homogeneous_material hm WHERE hm.supplier_id=$sid OR hm.created_by IN (SELECT u.id FROM users u WHERE u.supplier_id=$sid)))))";
            }
            if ($search) { $where[] = "(r.report_no LIKE ? OR r.test_agency LIKE ? OR r.supplier_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($type) { $where[] = "r.report_type=?"; $params[] = $type; }
            if ($audit) { $where[] = "r.audit_status=?"; $params[] = $audit; }
        }
        [$page, $perPage] = $this->pageParams();
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $base = "
            SELECT r.id, r.report_no, r.report_type, r.test_agency, r.test_date, r.expiry_date,
                   r.file_path, r.file_size, r.audit_status, r.created_at,
                   r.source_type, r.source_id, r.supplier_name, r.supplier_name_short,
                   COALESCE(r.material_name, r.hm_name) AS item_name
            FROM (
                SELECT tr.id, tr.report_no, tr.report_type, tr.test_agency, tr.test_date, tr.expiry_date,
                       tr.file_path, tr.file_size, tr.audit_status, tr.created_at,
                       'material' AS source_type, tr.material_id AS source_id,
                       m.material_name, NULL AS hm_name, s.name AS supplier_name, s.name_short AS supplier_name_short
                FROM test_report tr
                LEFT JOIN material m ON tr.material_id=m.id
                LEFT JOIN supplier s ON m.supplier_id=s.id
                UNION ALL
                SELECT hr.id, hr.report_no, hr.report_type, hr.test_agency, hr.test_date, hr.expiry_date,
                       hr.file_path, hr.file_size, 'N' AS audit_status, hr.created_at,
                       'hm' AS source_type, hr.hm_id AS source_id,
                       NULL AS material_name, hm.material_name AS hm_name, s.name AS supplier_name, s.name_short AS supplier_name_short
                FROM hm_report hr
                LEFT JOIN homogeneous_material hm ON hr.hm_id=hm.id
                LEFT JOIN supplier s ON hm.supplier_id=s.id
            ) r
        ";
        $total = (int)$db->single("SELECT COUNT(*) FROM ($base) r $whereSql", $params);
        $offset = ($page - 1) * $perPage;
        $rows = $db->query($base . " $whereSql ORDER BY r.created_at DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        $html = '';
        foreach ($rows as $r) $html .= $this->reportRowHtml($r);
        $this->paginationResponse($total, $html);
    }

    private function reportRowHtml($r): string {
        $html = '<tr>';
        $html .= colId($r['id'] ?? '') . '<td>' . h($r['report_no']) . '</td>';
        if (!isSupplier()) { $html .= '<td>' . h($r['supplier_name_short'] ?: ($r['supplier_name'] ?? '-')) . '</td>'; }
        $html .= '<td><span class="badge badge-soft-' . ($r['source_type'] === 'material' ? 'primary' : 'success') . '">' . ($r['source_type'] === 'material' ? '物料' : '均质') . '</span> ' . h($r['item_name'] ?? '-') . '</td>';
        $html .= '<td>' . badge($r['report_type'], 'info') . '</td>';
        $html .= '<td>' . h($r['test_agency']) . '</td>';
        $html .= '<td>' . h($r['test_date']) . '</td>';
        $html .= '<td><small class="' . (strtotime($r['expiry_date'] ?? '') < time() ? 'text-danger' : '') . '">' . h($r['expiry_date'] ?? '-') . '</small></td>';
        $html .= '<td>' . statusBadge($r['audit_status']) . '</td>';
        $html .= '<td>';
        if ($r['source_type'] === 'material') {
            if (can('report.edit')) { $html .= '<a href="/report?action=edit&id=' . $r['id'] . '" class="btn-icon btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>'; }
            if (can('report.delete')) {
                $html .= '<form method="post" class="d-inline" onsubmit="return confirm(\'确定删除？\')">' . csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . $r['id'] . '"><button class="btn-icon btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>';
            }
        } else {
            $html .= '<a href="/homogeneous?action=detail&id=' . $r['source_id'] . '" class="btn-icon btn btn-sm btn-outline-success" title="查看均质材料"><i class="fas fa-eye"></i></a>';
        }
        if ($r['file_path']) { $html .= '<a href="' . h(fileUrl($r['file_path'])) . '" class="btn-icon btn btn-sm btn-outline-info" target="_blank"><i class="fas fa-download"></i></a>'; }
        $html .= '</td></tr>';
        return $html;
    }
    private function showForm($id) {
        $this->requirePermission('report.edit');
        $db = DB::getInstance();
        $item = $db->single("SELECT * FROM test_report WHERE id=?", [$id], true);
        if (!$item) { header('Location: /report'); exit; }
        if (isSupplier()) {
            $m = $db->single("SELECT supplier_id FROM material WHERE id=?", [(int)($item['material_id'] ?? 0)], true);
            if (!$m || (int)$m['supplier_id'] !== userSupplierId()) { flash('error', '无权操作'); header('Location: /report'); exit; }
        }
        $materials = $db->query("SELECT id, material_name, material_no FROM material WHERE valid_status='Valid' ORDER BY material_name");
        $title = '编辑报告';
        $this->render('report/form', get_defined_vars());
    }
    private function doCreate() {
        $this->requirePermission('report.create');
        $db = DB::getInstance();
        $no = $_POST['report_no'] ?? '';
        if (!$no) $no = 'R' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $hmId = (int)($_POST['material_id'] ?? 0);
        if (!$hmId) { flash('error', '请选择均质材料'); header('Location: /report'); exit; }
        $this->requireUserRecord('homogeneous_material', $hmId, 'created_by', '/report');
        $testDate = $_POST['test_date'] ?? '';
        $expiryDate = $_POST['expiry_date'] ?? ($testDate ? date('Y-m-d', strtotime($testDate . ' +1 year')) : '');
        $filePath = '';
        $fileSize = 0;
        if (!empty($_FILES['report_file']['name'])) {
            $dir = uploadPath($_SESSION['username'] ?? 'unknown');
            $ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
            $name = pathinfo($_FILES['report_file']['name'], PATHINFO_FILENAME) . '_' . date('Ymd') . '.' . $ext;
            $dest = $dir . '/' . $name;
            move_uploaded_file($_FILES['report_file']['tmp_name'], $dest);
            $filePath = $dest;
            $fileSize = (int)$_FILES['report_file']['size'];
        }
        $reportType = $_POST['report_type'] ?? '';
        $agency = $_POST['test_agency'] ?? '';
        $version = 1;
        $versionGroup = $hmId . '_' . $reportType;
        $db->execute("INSERT INTO hm_report (hm_id, report_no, report_type, test_agency, test_date, expiry_date, file_path, file_size, version, version_group, status) VALUES (?,?,?,?,?,?,?,?,?,?,'current')",
            [$hmId, $no, $reportType, $agency, $testDate, $expiryDate, $filePath, $fileSize, $version, $versionGroup]);
        logOp('create', 'hm_report', $db->lastInsertId(), '上传报告: ' . $no);
        flash('success', '报告上传成功');
        header('Location: /report'); exit;
    }
    private function doUpdate() {
        $this->requirePermission('report.edit');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) return;
        $db = DB::getInstance();
        $item = $db->single("SELECT * FROM test_report WHERE id=?", [$id], true);
        if (!$item) { flash('error', '记录不存在'); header('Location: /report'); exit; }
        if (isSupplier()) {
            $m = $db->single("SELECT supplier_id FROM material WHERE id=?", [(int)($item['material_id'] ?? 0)], true);
            if (!$m || (int)$m['supplier_id'] !== userSupplierId()) { flash('error', '无权操作'); header('Location: /report'); exit; }
        }
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
        $this->requirePermission('report.delete');
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = DB::getInstance();
            $r = $db->single("SELECT * FROM test_report WHERE id=?", [$id], true);
            if (!$r) { flash('error', '记录不存在'); header('Location: /report'); exit; }
            if (isSupplier()) {
                $m = $db->single("SELECT supplier_id FROM material WHERE id=?", [(int)($r['material_id'] ?? 0)], true);
                if (!$m || (int)$m['supplier_id'] !== userSupplierId()) { flash('error', '无权操作'); header('Location: /report'); exit; }
            }
            if ($r && $r['file_path'] && file_exists($r['file_path'])) unlink($r['file_path']);
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
}
