<?php
class DocumentController extends Controller {
    public function index() {
        $this->requirePermission('document.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $db->execute("INSERT INTO document_category (code, name, parent_id, validity_days, is_required, sort_order) VALUES (?,?,?,?,?,?)",
                    [$_POST['code'] ?? '', $_POST['name'] ?? '', (int)($_POST['parent_id'] ?? 0) ?: null, (int)($_POST['validity_days'] ?? 365), (int)($_POST['is_required'] ?? 1), (int)($_POST['sort_order'] ?? 0)]);
                flash('success', '分类已创建');
            }
            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $db->execute("UPDATE document_category SET code=?, name=?, parent_id=?, validity_days=?, is_required=?, sort_order=? WHERE id=?",
                    [$_POST['code'] ?? '', $_POST['name'] ?? '', (int)($_POST['parent_id'] ?? 0) ?: null, (int)($_POST['validity_days'] ?? 365), (int)($_POST['is_required'] ?? 1), (int)($_POST['sort_order'] ?? 0), $id]);
                flash('success', '分类已更新');
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) { $db->execute("DELETE FROM document_category WHERE id=?", [$id]); flash('success', '已删除'); }
            }
            header('Location: /document/categories'); exit;
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'create') { $this->showForm(); return; }
        if ($action === 'edit') { $this->showForm((int)($_GET['id'] ?? 0)); return; }
        $this->showList();
    }
    public function categories() { $this->index(); }
    private function showList() {
        $db = DB::getInstance();
        $cats = $db->query("SELECT * FROM document_category ORDER BY sort_order, code");
        $title = '文档分类管理';
        $this->render('document/categories', get_defined_vars());
    }
    private function showForm($id = 0) {
        $db = DB::getInstance();
        $item = $id ? $db->single("SELECT * FROM document_category WHERE id=?", [$id], true) : [];
        $allCats = $db->query("SELECT * FROM document_category ORDER BY sort_order, code");
        $title = $id ? '编辑分类' : '新增分类';
        $this->render('document/category_form', get_defined_vars());
    }
}
