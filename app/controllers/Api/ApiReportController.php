<?php
class ApiReportController extends ApiBaseController {
    public function index() {
        $search = ApiRequest::get('search', '');
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($search) { $where[] = "(tr.report_no LIKE ? OR tr.report_type LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM test_report tr $w", $params);
        $items = $this->db->query("SELECT tr.*, m.material_name FROM test_report tr LEFT JOIN material m ON tr.material_id=m.id $w ORDER BY tr.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function detail() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT tr.*, m.material_name FROM test_report tr LEFT JOIN material m ON tr.material_id=m.id WHERE tr.id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $elements = $this->db->query("SELECT * FROM element_content WHERE report_id=? ORDER BY id", [$id]);
        ApiResponse::success(['item' => $item, 'elements' => $elements]);
    }
    public function audit() {
        $materialId = (int)ApiRequest::get('material_id', 0);
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($materialId) { $where[] = "mal.material_id=?"; $params[] = $materialId; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM material_audit_log mal $w", $params);
        $items = $this->db->query("SELECT mal.*, u.realname AS operator_name FROM material_audit_log mal LEFT JOIN users u ON mal.operator_id=u.id $w ORDER BY mal.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function elements() {
        $reportId = (int)ApiRequest::get('report_id', 0);
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($reportId) { $where[] = "ec.report_id=?"; $params[] = $reportId; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM element_content ec $w", $params);
        $items = $this->db->query("SELECT ec.*, tr.report_no FROM element_content ec LEFT JOIN test_report tr ON ec.report_id=tr.id $w ORDER BY ec.id LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function create() {
        $d = ApiRequest::post();
        $reportNo = $d['report_no'] ?? '';
        if (!$reportNo) ApiResponse::error('报告编号不能为空');

        $filePath = '';
        $fileSize = 0;
        $file = ApiRequest::file('file');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/reports/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName = date('Ymd') . '_' . uniqid() . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (!move_uploaded_file($file['tmp_name'], $dest)) ApiResponse::error('文件保存失败');
            $filePath = 'uploads/reports/' . $newName;
            $fileSize = $file['size'];
        }

        $this->db->execute(
            "INSERT INTO test_report (material_id, report_no, report_type, report_version, test_agency, test_date, issue_date, effective_date, expiry_date, file_path, file_size, audit_status, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending',?,datetime('now','localtime'),datetime('now','localtime'))",
            [
                (int)($d['material_id'] ?? 0),
                $reportNo,
                $d['report_type'] ?? '',
                $d['report_version'] ?? 'V1.0',
                $d['test_agency'] ?? '',
                $d['test_date'] ?? '',
                $d['issue_date'] ?? '',
                $d['effective_date'] ?? '',
                $d['expiry_date'] ?? '',
                $filePath,
                $fileSize,
                $this->userId()
            ]
        );
        $reportId = $this->db->lastInsertId();

        if (!empty($d['elements']) && is_array($d['elements'])) {
            foreach ($d['elements'] as $el) {
                $this->db->execute(
                    "INSERT INTO element_content (report_id, substance_name, cas_no, content_value, unit, limit_value, limit_unit, is_over_limit, detection_method) VALUES (?,?,?,?,?,?,?,?,?)",
                    [
                        $reportId,
                        $el['substance_name'] ?? '',
                        $el['cas_no'] ?? '',
                        (float)($el['content_value'] ?? 0),
                        $el['unit'] ?? 'mg/kg',
                        (float)($el['limit_value'] ?? 0),
                        $el['limit_unit'] ?? '',
                        (int)($el['is_over_limit'] ?? 0),
                        $el['detection_method'] ?? ''
                    ]
                );
            }
        }

        ApiResponse::created(['id' => $reportId]);
    }

    public function update() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM test_report WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();

        $d = ApiRequest::post();
        $filePath = $item['file_path'];
        $fileSize = $item['file_size'];
        $file = ApiRequest::file('file');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/reports/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName = date('Ymd') . '_' . uniqid() . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (!move_uploaded_file($file['tmp_name'], $dest)) ApiResponse::error('文件保存失败');
            $filePath = 'uploads/reports/' . $newName;
            $fileSize = $file['size'];
            if ($item['file_path']) {
                $oldFile = __DIR__ . '/../../' . $item['file_path'];
                if (file_exists($oldFile)) @unlink($oldFile);
            }
        }

        $this->db->execute(
            "UPDATE test_report SET material_id=?, report_no=?, report_type=?, report_version=?, test_agency=?, test_date=?, issue_date=?, effective_date=?, expiry_date=?, file_path=?, file_size=?, updated_at=datetime('now','localtime') WHERE id=?",
            [
                (int)($d['material_id'] ?? $item['material_id']),
                $d['report_no'] ?? $item['report_no'],
                $d['report_type'] ?? $item['report_type'],
                $d['report_version'] ?? $item['report_version'],
                $d['test_agency'] ?? $item['test_agency'],
                $d['test_date'] ?? $item['test_date'],
                $d['issue_date'] ?? $item['issue_date'],
                $d['effective_date'] ?? $item['effective_date'],
                $d['expiry_date'] ?? $item['expiry_date'],
                $filePath,
                $fileSize,
                $id
            ]
        );

        if (isset($d['elements']) && is_array($d['elements'])) {
            $this->db->execute("DELETE FROM element_content WHERE report_id=?", [$id]);
            foreach ($d['elements'] as $el) {
                $this->db->execute(
                    "INSERT INTO element_content (report_id, substance_name, cas_no, content_value, unit, limit_value, limit_unit, is_over_limit, detection_method) VALUES (?,?,?,?,?,?,?,?,?)",
                    [
                        $id,
                        $el['substance_name'] ?? '',
                        $el['cas_no'] ?? '',
                        (float)($el['content_value'] ?? 0),
                        $el['unit'] ?? 'mg/kg',
                        (float)($el['limit_value'] ?? 0),
                        $el['limit_unit'] ?? '',
                        (int)($el['is_over_limit'] ?? 0),
                        $el['detection_method'] ?? ''
                    ]
                );
            }
        }

        ApiResponse::success(['id' => $id]);
    }

    public function delete() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM test_report WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        if ($item['file_path']) {
            $f = __DIR__ . '/../../' . $item['file_path'];
            if (file_exists($f)) @unlink($f);
        }
        $this->db->execute("DELETE FROM element_content WHERE report_id=?", [$id]);
        $this->db->execute("DELETE FROM test_report WHERE id=?", [$id]);
        ApiResponse::success(null, '删除成功');
    }
}
