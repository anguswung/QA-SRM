<?php
class SupplierController extends Controller {
    public function index() {
        $this->requirePermission('supplier.view');
        $blockedActions = ['create', 'edit', 'delete', 'blacklist', 'contact_create', 'contact_delete', 'import'];
        if (isSupplier() && (in_array($_GET['action'] ?? '', $blockedActions) || $_SERVER['REQUEST_METHOD'] === 'POST')) { header('Location: /supplier/rating'); exit; }
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') { $this->doCreate(); return; }
            if ($action === 'update') { $this->doUpdate(); return; }
            if ($action === 'delete') { $this->doDelete(); return; }
            if ($action === 'blacklist') { $this->toggleBlacklist(); return; }
            if ($action === 'contact_create') { $this->contactCreate(); return; }
            if ($action === 'contact_delete') { $this->contactDelete(); return; }
            if ($action === 'rating_create') { $this->ratingCreate(); return; }
            if ($action === 'import') { $this->doImport(); return; }
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'create') { $this->showForm(); return; }
        if ($action === 'edit') { $this->showForm((int)($_GET['id'] ?? 0)); return; }
        if ($action === 'detail') { $this->detail((int)($_GET['id'] ?? 0)); return; }
        if ($action === 'import_template') { $this->importTemplate(); return; }
        $this->showList();
    }
    public function rating() {
        $db = DB::getInstance();
        if (isSupplier()) {
            $sid = userSupplierId();
            $suppliers = $db->query("SELECT s.*, COUNT(m.id) AS material_count, SUM(CASE WHEN m.env_status='compliant' THEN 1 ELSE 0 END) AS compliant_count, SUM(CASE WHEN m.env_status='non_compliant' THEN 1 ELSE 0 END) AS non_compliant_count FROM supplier s LEFT JOIN material m ON s.id=m.supplier_id AND m.valid_status='Valid' WHERE s.id=? GROUP BY s.id ORDER BY s.name", [$sid]);
        } else {
            $suppliers = $db->query("SELECT s.*, COUNT(m.id) AS material_count, SUM(CASE WHEN m.env_status='compliant' THEN 1 ELSE 0 END) AS compliant_count, SUM(CASE WHEN m.env_status='non_compliant' THEN 1 ELSE 0 END) AS non_compliant_count FROM supplier s LEFT JOIN material m ON s.id=m.supplier_id AND m.valid_status='Valid' GROUP BY s.id ORDER BY s.name");
        }
        $title = '供应商评价';
        $this->render('supplier/rating', get_defined_vars());
    }
    private function showList() {
        if (isSupplier()) { header('Location: /supplier/rating'); exit; }
        $db = DB::getInstance();
        $search = $_GET['search'] ?? '';
        $level = $_GET['level'] ?? '';
        $blacklist = $_GET['blacklist'] ?? '';
        $where = []; $params = [];
        if ($search) { $where[] = "(s.code LIKE ? OR s.name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($level) { $where[] = "s.level=?"; $params[] = $level; }
        if ($blacklist !== '') { $where[] = "s.blacklist=?"; $params[] = (int)$blacklist; }
        $suppliers = $db->query("SELECT s.*, (SELECT COUNT(*) FROM material WHERE supplier_id=s.id) AS material_count FROM supplier s" . ($where ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY s.code", $params);
        $title = '供应商管理';
        $this->render('supplier/index', get_defined_vars());
    }
    private function showForm($id = 0) {
        $db = DB::getInstance();
        $item = $id ? $db->single("SELECT * FROM supplier WHERE id=?", [$id], true) : [];
        if ($id && !$item) { header('Location: /supplier'); exit; }
        $contacts = $id ? $db->query("SELECT * FROM supplier_contact WHERE supplier_id=?", [$id]) : [];
        $title = $id ? '编辑供应商' : '新增供应商';
        $this->render('supplier/form', get_defined_vars());
    }
    private function detail($id) {
        $this->requirePermission('supplier.view');
        $db = DB::getInstance();
        if (isSupplier() && (int)$id !== userSupplierId()) { header('Location: /supplier/rating'); exit; }
        $item = $db->single("SELECT * FROM supplier WHERE id=?", [$id], true);
        if (!$item) { header('Location: /supplier'); exit; }
        $contacts = $db->query("SELECT * FROM supplier_contact WHERE supplier_id=?", [$id]);
        $ratings = $db->query("SELECT * FROM supplier_rating WHERE supplier_id=? ORDER BY rating_date DESC", [$id]);
        $materials = $db->query("SELECT id, material_name, material_no, env_status, audit_status FROM material WHERE supplier_id=? ORDER BY material_name", [$id]);
        // 按月分组       $ratingGroups = [];
        foreach ($ratings as $r) {
            $monthKey = substr($r['rating_date'], 0, 7);
            if (!isset($ratingGroups[$monthKey])) $ratingGroups[$monthKey] = ['date' => $r['rating_date'], 'items' => []];
            $ratingGroups[$monthKey]['items'][$r['dimension']] = $r;
        }
        $currentMonthRatings = $ratingGroups[date('Y-m')]['items'] ?? [];
        $title = '供应商详情 ' . $item['name'];
        $this->render('supplier/detail', get_defined_vars());
    }
    private function importTemplate() {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('供应商导入');
        $sheet->setCellValue('A1', '编码(将自动生成)');
        $sheet->setCellValue('B1', '供应商名称');
        $sheet->setCellValue('C1', '简称');
        $sheet->setCellValue('D1', '联系人');
        $sheet->setCellValue('E1', '联系电话');
        $sheet->setCellValue('F1', '地址');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->setCellValue('A2', '001');
        $sheet->setCellValue('B2', '示例供应商');
        foreach (range('A','F') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="供应商导入模板.xlsx"');
        ob_clean();
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }
    private function doImport() {
        if (empty($_FILES['import_file']['tmp_name'])) { flash('error', '请选择文件'); header('Location: /supplier'); exit; }
        $f = $_FILES['import_file'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xls','xlsx'])) { flash('error', '仅支持Excel文件'); header('Location: /supplier'); exit; }
        require_once __DIR__ . '/../../vendor/autoload.php';
        try {
            $reader = $ext === 'xlsx' ? new \PhpOffice\PhpSpreadsheet\Reader\Xlsx() : new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            $rows = $reader->load($f['tmp_name'])->getActiveSheet()->toArray();
        } catch (Exception $e) { flash('error', '文件解析失败'); header('Location: /supplier'); exit; }
        if (empty($rows) || !str_contains($rows[0][0] ?? '', '编码') || !str_contains($rows[0][1] ?? '', '供应商名称')) {
            flash('error', '模板不匹配，请使用供应商导入模板'); header('Location: /supplier'); exit;
        }
        $db = DB::getInstance();
        $imported = 0; $errors = [];
        foreach ($rows as $i => $row) {
            if ($i == 0) continue; // skip header
            $codeInput = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            if (empty($name)) continue;
            $short = trim($row[2] ?? '');
            $contact = trim($row[3] ?? '');
            $phone = trim($row[4] ?? '');
            $address = trim($row[5] ?? '');
            $existing = $db->single("SELECT id, code FROM supplier WHERE name=?", [$name], true);
            if ($existing) { $errors[] = "第".($i+1)."行 供应商名称".$name."'已存在"; continue; }
            if ($codeInput && preg_match('/^\d+$/', $codeInput)) {
                $code = str_pad($codeInput, 3, '0', STR_PAD_LEFT);
                $existing = $db->single("SELECT id FROM supplier WHERE code=?", [$code]);
                if ($existing) { $errors[] = "第".($i+1)."行 编码".$code."已存在"; continue; }
            } else {
                $maxCode = $db->single("SELECT MAX(CAST(code AS INTEGER)) FROM supplier WHERE code GLOB '[0-9][0-9][0-9]'");
                $code = str_pad(($maxCode ?: 0) + 1, 3, '0', STR_PAD_LEFT);
            }
            try {
                $db->execute("INSERT INTO supplier (code, name, name_short, contact_person, contact_phone, address) VALUES (?,?,?,?,?,?)",
                    [$code, $name, $short, $contact, $phone, $address]);
                $sid = $db->lastInsertId();
                $existingUser = $db->single("SELECT id FROM users WHERE username=?", [$code]);
                if ($existingUser) {
                    $errors[] = "第".($i+1)."行 供应商".$name." 已导入，用户账号".$code."已存在，未覆盖";
                } else {
                    $pwd = password_hash($code, PASSWORD_DEFAULT);
                    $db->execute("INSERT INTO users (username, password, realname, role, supplier_id, status, password_changed) VALUES (?,?,?,?,?,?,?)",
                        [$code, $pwd, $name, 'supplier', $sid, 'active', 0]);
                }
                $imported++;
            } catch (Exception $e) { $errors[] = "第".($i+1)."行 ".$e->getMessage(); }
        }
        logOp('import', 'supplier', 0, "批量导入$imported 个供应商");
        $msg = "成功导入$imported 个供应商";
        if (!empty($errors)) $msg .= '。' . implode('; ', array_slice($errors, 0, 3));
        flash('success', $msg);
        header('Location: /supplier'); exit;
    }
    private function doCreate() {
        $db = DB::getInstance();
        $code = trim($_POST['code'] ?? '');
        if (!$code) { flash('error', '供应商编码不能为空'); header('Location: /supplier?action=create'); exit; }
        $existing = $db->single("SELECT id FROM supplier WHERE code=?", [$code]);
        if ($existing) { flash('error', '编码已存在'); header('Location: /supplier?action=create'); exit; }
        $db->execute("INSERT INTO supplier (code, name, name_short, credit_code, contact_person, contact_phone, address, level) VALUES (?,?,?,?,?,?,?,?)",
            [$code, $_POST['name'] ?? '', $_POST['name_short'] ?? '', $_POST['credit_code'] ?? '',
             $_POST['contact_person'] ?? '', $_POST['contact_phone'] ?? '', $_POST['address'] ?? '',
             $_POST['level'] ?? 'C']);
        $sid = $db->lastInsertId();
        $existingUser = $db->single("SELECT id FROM users WHERE username=?", [$code]);
        if ($existingUser) {
            flash('info', "供应商创建成功，用户账号$code 已存在，未覆盖");
        } else {
            $pwd = password_hash($code, PASSWORD_DEFAULT);
            $db->execute("INSERT INTO users (username, password, realname, role, supplier_id, status, password_changed) VALUES (?,?,?,?,?,?,?)",
                [$code, $pwd, $_POST['name'] ?? '', 'supplier', $sid, 'active', 0]);
            flash('success', "供应商创建成功，编码 $code，初始密码$code");
        }
        logOp('create', 'supplier', $sid, '新增供应商' . ($_POST['name'] ?? '') . " [code=$code]");
        header('Location: /supplier'); exit;
    }
    private function doUpdate() {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { header('Location: /supplier'); exit; }
        $db = DB::getInstance();
        $db->execute("UPDATE supplier SET code=?, name=?, name_short=?, credit_code=?, contact_person=?, contact_phone=?, address=?, level=?, updated_at=datetime('now','localtime') WHERE id=?",
            [$_POST['code'] ?? '', $_POST['name'] ?? '', $_POST['name_short'] ?? '', $_POST['credit_code'] ?? '',
             $_POST['contact_person'] ?? '', $_POST['contact_phone'] ?? '', $_POST['address'] ?? '',
             $_POST['level'] ?? 'C', $id]);
        logOp('update', 'supplier', $id, '更新供应商' . ($_POST['name'] ?? ''));
        flash('success', '供应商更新成功');
        header('Location: /supplier'); exit;
    }
    private function doDelete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            DB::getInstance()->execute("DELETE FROM supplier WHERE id=?", [$id]);
            logOp('delete', 'supplier', $id, '删除供应商');
            flash('success', '供应商已删除');
        }
        header('Location: /supplier'); exit;
    }
    private function toggleBlacklist() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = DB::getInstance();
            $s = $db->single("SELECT blacklist FROM supplier WHERE id=?", [$id], true);
            $newVal = $s ? ($s['blacklist'] ? 0 : 1) : 0;
            $db->execute("UPDATE supplier SET blacklist=? WHERE id=?", [$newVal, $id]);
            logOp('update', 'supplier', $id, $newVal ? '已加入黑名单' : '已移出黑名单');
            flash('success', $newVal ? '已加入黑名单' : '已移出黑名单');
        }
        header('Location: /supplier'); exit;
    }
    private function contactCreate() {
        $db = DB::getInstance();
        $db->execute("INSERT INTO supplier_contact (supplier_id, name, position, phone, mobile, email, is_primary) VALUES (?,?,?,?,?,?,?)",
            [(int)($_POST['supplier_id'] ?? 0), $_POST['name'] ?? '', $_POST['position'] ?? '',
             $_POST['phone'] ?? '', $_POST['mobile'] ?? '', $_POST['email'] ?? '',
             (int)($_POST['is_primary'] ?? 0)]);
        flash('success', '联系人已添加');
        header('Location: /supplier?action=detail&id=' . (int)($_POST['supplier_id'] ?? 0)); exit;
    }
    private function contactDelete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $c = DB::getInstance()->single("SELECT supplier_id FROM supplier_contact WHERE id=?", [$id], true);
            DB::getInstance()->execute("DELETE FROM supplier_contact WHERE id=?", [$id]);
            if ($c) { header('Location: /supplier?action=detail&id=' . $c['supplier_id']); exit; }
        }
        header('Location: /supplier'); exit;
    }
    private function ratingCreate() {
        $db = DB::getInstance();
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $ratingDate = $_POST['rating_date'] ?? date('Y-m-d');
        $scores = $_POST['score'] ?? [];
        $comments = $_POST['comment'] ?? [];
        $inserted = 0;
        // 删除当月已有评价，实现覆盖修改       $db->execute("DELETE FROM supplier_rating WHERE supplier_id=? AND strftime('%Y-%m', rating_date)=strftime('%Y-%m', ?)", [$supplierId, $ratingDate]);
        foreach ($scores as $dim => $score) {
            $score = (float)$score;
            if ($score <= 0) continue;
            $db->execute("INSERT INTO supplier_rating (supplier_id, rating_date, score, dimension, comment, evaluator_id) VALUES (?,?,?,?,?,?)",
                [$supplierId, $ratingDate, $score, $dim, trim($comments[$dim] ?? ''), userId()]);
            $inserted++;
        }
        if ($inserted === 0) { flash('error', '请至少输入一个维度的评分'); header('Location: /supplier?action=detail&id=' . $supplierId); exit; }
        $totalScore = $db->single("SELECT AVG(score) FROM supplier_rating WHERE supplier_id=?", [$supplierId]);
        $level = $totalScore >= 90 ? 'A' : ($totalScore >= 70 ? 'B' : 'C');
        $db->execute("UPDATE supplier SET level=? WHERE id=?", [$level, $supplierId]);
        logOp('create', 'supplier_rating', $supplierId, "评分$inserted 个维度，均分 $totalScore -> $level");
        flash('success', "已完成$inserted 个维度评分");
        header('Location: /supplier?action=detail&id=' . $supplierId); exit;
    }
}
