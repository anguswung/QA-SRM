<?php
class RegulationController extends Controller {
    public function index() {
        $this->requirePermission('regulation.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isAdmin()) { flash('error', '无权操作'); header('Location: /regulation'); exit; }
            $action = $_POST['action'] ?? '';
            if ($action === 'create') { $this->doCreate(); return; }
            if ($action === 'update') { $this->doUpdate(); return; }
            if ($action === 'delete') { $this->doDelete(); return; }
            if ($action === 'publish') { $this->doPublish(); return; }
            if ($action === 'content_create') { $this->contentCreate(); return; }
            if ($action === 'content_delete') { $this->contentDelete(); return; }
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'create') { $this->requirePermission('regulation.edit'); $this->showForm(); return; }
        if ($action === 'edit') { $this->requirePermission('regulation.edit'); $this->showForm((int)($_GET['id'] ?? 0)); return; }
        if ($action === 'detail') { $this->detail((int)($_GET['id'] ?? 0)); return; }
        $this->showList();
    }
    public function focus() {
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $focus = (int)($_POST['is_focus'] ?? 0);
            $db->execute("UPDATE regulation_lib SET is_focus=? WHERE id=?", [$focus, $id]);
            logOp('update', 'regulation', $id, $focus ? '标记重点关注' : '取消重点关注');
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]); exit;
        }
    }
    private function showList() {
        $db = DB::getInstance();
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $status = $_GET['status'] ?? '';
        $where = []; $params = [];
        if ($search) { $where[] = "(r.name LIKE ? OR r.regulation_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($category) { $where[] = "r.category=?"; $params[] = $category; }
        if ($status) { $where[] = "r.status=?"; $params[] = $status; }
        $action = $_GET['action'] ?? '';
        if ($action === 'data') { $this->data($where, $params); return; }
        $cats = $db->query("SELECT DISTINCT category FROM regulation_lib WHERE category IS NOT NULL AND category!='' ORDER BY category");
        $title = '法规管理';
        $this->render('regulation/index', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data） */
    private function data($where = null, $params = null) {
        $this->requirePermission('regulation.view');
        $db = DB::getInstance();
        if ($where === null) {
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $status = $_GET['status'] ?? '';
            $where = []; $params = [];
            if ($search) { $where[] = "(r.name LIKE ? OR r.regulation_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($category) { $where[] = "r.category=?"; $params[] = $category; }
            if ($status) { $where[] = "r.status=?"; $params[] = $status; }
        }
        [$page, $perPage] = $this->pageParams();
        $whereSql = $where ? " WHERE " . implode(' AND ', $where) : '';
        $total = (int)$db->single("SELECT COUNT(*) FROM regulation_lib r" . $whereSql, $params);
        $offset = ($page - 1) * $perPage;
        $rows = $db->query("SELECT r.*, (SELECT COUNT(*) FROM regulation_content WHERE regulation_id=r.id) AS article_count FROM regulation_lib r" . $whereSql . " ORDER BY r.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        $html = '';
        foreach ($rows as $r) {
            $html .= '<tr>';
            $html .= colId($r['id'] ?? '') . '<td>' . h($r['regulation_no']) . '</td>';
            $html .= '<td class="text-start"><a href="/regulation/' . $r['id'] . '/detail">' . h($r['name']) . '</a></td>';
            $html .= '<td>' . badge($r['category'], 'info') . '</td>';
            $html .= '<td>' . h($r['issuing_body']) . '</td>';
            $html .= '<td>' . h($r['effective_date']) . '</td>';
            $html .= '<td>' . statusBadge($r['status']) . '</td>';
            $html .= '<td><a href="javascript:;" class="toggle-focus" data-id="' . $r['id'] . '" data-focus="' . $r['is_focus'] . '"><i class="fas fa-star ' . ($r['is_focus'] ? 'text-warning' : 'text-muted') . '"></i></a></td>';
            $html .= '<td>';
            if (can('regulation.edit')) { $html .= '<a href="/regulation?action=edit&id=' . $r['id'] . '" class="btn-icon btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>'; }
            if ($r['status'] === 'draft') {
                $html .= '<form method="post" class="d-inline inline-form">' . csrf_field() . '<input type="hidden" name="action" value="publish"><input type="hidden" name="id" value="' . $r['id'] . '"><input type="hidden" name="status" value="published"><button class="btn-icon btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button></form>';
            }
            $html .= '</td></tr>';
        }
        $this->paginationResponse($total, $html);
    }
    private function showForm($id = 0) {
        $db = DB::getInstance();
        $item = $id ? $db->single("SELECT * FROM regulation_lib WHERE id=?", [$id], true) : [];
        if ($id && !$item) { header('Location: /regulation'); exit; }
        $title = $id ? '编辑法规' : '新增法规';
        $this->render('regulation/form', get_defined_vars());
    }
    private function doCreate() {
        $db = DB::getInstance();
        $no = $_POST['regulation_no'] ?? '';
        if (!$no) $no = 'REG' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $db->execute("INSERT INTO regulation_lib (regulation_no, name, name_en, issuing_body, publish_date, effective_date, category, description, status, version) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$no, $_POST['name'] ?? '', $_POST['name_en'] ?? '', $_POST['issuing_body'] ?? '',
             $_POST['publish_date'] ?? '', $_POST['effective_date'] ?? '', $_POST['category'] ?? '',
             $_POST['description'] ?? '', 'draft', $_POST['version'] ?? 'V1.0']);
        logOp('create', 'regulation', $db->lastInsertId(), '新增法规: ' . ($_POST['name'] ?? ''));
        flash('success', '法规创建成功');
        header('Location: /regulation'); exit;
    }
    private function doUpdate() {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) return;
        $db = DB::getInstance();
        $db->execute("UPDATE regulation_lib SET regulation_no=?, name=?, name_en=?, issuing_body=?, publish_date=?, effective_date=?, category=?, description=?, version=?, updated_at=datetime('now','localtime') WHERE id=?",
            [$_POST['regulation_no'] ?? '', $_POST['name'] ?? '', $_POST['name_en'] ?? '',
             $_POST['issuing_body'] ?? '', $_POST['publish_date'] ?? '', $_POST['effective_date'] ?? '',
             $_POST['category'] ?? '', $_POST['description'] ?? '', $_POST['version'] ?? 'V1.0', $id]);
        logOp('update', 'regulation', $id, '更新法规: ' . ($_POST['name'] ?? ''));
        flash('success', '法规更新成功');
        header('Location: /regulation'); exit;
    }
    private function doDelete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = DB::getInstance();
            $db->execute("DELETE FROM regulation_content WHERE regulation_id=?", [$id]);
            $db->execute("DELETE FROM regulation_lib WHERE id=?", [$id]);
            logOp('delete', 'regulation', $id, '删除法规');
            flash('success', '法规已删除');
        }
        header('Location: /regulation'); exit;
    }
    private function doPublish() {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'published';
        if ($id) {
            DB::getInstance()->execute("UPDATE regulation_lib SET status=?, updated_at=datetime('now','localtime') WHERE id=?", [$status, $id]);
            logOp('update', 'regulation', $id, "法规状态变更为 $status");
            flash('success', '状态更新成功');
        }
        header('Location: /regulation'); exit;
    }
    private function detail($id) {
        $db = DB::getInstance();
        $item = $db->single("SELECT * FROM regulation_lib WHERE id=?", [$id], true);
        if (!$item) { header('Location: /regulation'); exit; }
        $contents = $db->query("SELECT * FROM regulation_content WHERE regulation_id=? ORDER BY sort_order, id", [$id]);
        $title = $item['name'];
        $this->render('regulation/detail', get_defined_vars());
    }
    private function contentCreate() {
        $regId = (int)($_POST['regulation_id'] ?? 0);
        if ($regId) {
            DB::getInstance()->execute("INSERT INTO regulation_content (regulation_id, article_no, title, content_text, substance_name, cas_no, limit_value, limit_unit, exemption_desc, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)",
                [$regId, $_POST['article_no'] ?? '', $_POST['title'] ?? '', $_POST['content_text'] ?? '',
                 $_POST['substance_name'] ?? '', $_POST['cas_no'] ?? '', (float)($_POST['limit_value'] ?? 0),
                 $_POST['limit_unit'] ?? 'mg/kg', $_POST['exemption_desc'] ?? '', (int)($_POST['sort_order'] ?? 0)]);
            logOp('create', 'regulation_content', $regId, '新增管控条款');
        }
        header('Location: /regulation/' . $regId . '/detail'); exit;
    }
    private function contentDelete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $c = DB::getInstance()->single("SELECT regulation_id FROM regulation_content WHERE id=?", [$id], true);
            DB::getInstance()->execute("DELETE FROM regulation_content WHERE id=?", [$id]);
            logOp('delete', 'regulation_content', $id, '删除管控条款');
            if ($c) { header('Location: /regulation/' . $c['regulation_id'] . '/detail'); exit; }
        }
        header('Location: /regulation'); exit;
    }
}
