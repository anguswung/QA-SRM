<?php
class SubstanceController extends Controller {
    public function index() {
        $this->requirePermission('substance.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isAdmin()) { flash('error', '无权操作'); header('Location: /substance'); exit; }
            $action = $_POST['action'] ?? '';
            if ($action === 'create') { $this->doCreate(); return; }
            if ($action === 'update') { $this->doUpdate(); return; }
            if ($action === 'delete') { $this->doDelete(); return; }
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'create') { $this->requirePermission('substance.edit'); $this->showForm(); return; }
        if ($action === 'edit') { $this->requirePermission('substance.edit'); $this->showForm((int)($_GET['id'] ?? 0)); return; }
        $this->showList();
    }
    private function showList() {
        $db = DB::getInstance();
        $search = $_GET['search'] ?? '';
        $controlled = $_GET['controlled'] ?? '';
        $where = []; $params = [];
        if ($search) {
            $where[] = "(s.name LIKE ? OR s.name_en LIKE ? OR s.cas_no=?)";
            $params[] = "%$search%"; $params[] = "%$search%"; $params[] = $search;
        }
        if ($controlled !== '') { $where[] = "s.is_controlled=?"; $params[] = (int)$controlled; }
        $action = $_GET['action'] ?? '';
        if ($action === 'data') { $this->data($where, $params); return; }
        $title = '物质库';
        $this->render('substance/index', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data） */
    private function data($where = null, $params = null) {
        $this->requirePermission('substance.view');
        $db = DB::getInstance();
        if ($where === null) {
            $search = $_GET['search'] ?? '';
            $controlled = $_GET['controlled'] ?? '';
            $where = []; $params = [];
            if ($search) { $where[] = "(s.name LIKE ? OR s.name_en LIKE ? OR s.cas_no=?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = $search; }
            if ($controlled !== '') { $where[] = "s.is_controlled=?"; $params[] = (int)$controlled; }
        }
        [$page, $perPage] = $this->pageParams();
        $whereSql = $where ? " WHERE " . implode(' AND ', $where) : '';
        $total = (int)$db->single("SELECT COUNT(*) FROM substance s" . $whereSql, $params);
        $offset = ($page - 1) * $perPage;
        $rows = $db->query("SELECT s.* FROM substance s" . $whereSql . " ORDER BY s.name LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        $html = '';
        foreach ($rows as $s) {
            $html .= '<tr>';
            $html .= colId($s['id'] ?? '') . '<td>' . h($s['cas_no']) . '</td>';
            $html .= '<td class="text-start">' . h($s['name']) . '</td>';
            $html .= '<td>' . h($s['name_en']) . '</td>';
            $html .= '<td>' . h($s['molecular_formula']) . '</td>';
            $html .= '<td>' . ($s['is_controlled'] ? '<span class="status-tag danger">受控</span>' : '<span class="status-tag secondary">非受控</span>') . '</td>';
            $html .= '<td>';
            if (can('substance.edit')) { $html .= '<a href="/substance?action=edit&id=' . $s['id'] . '" class="btn-icon btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>'; }
            if (can('substance.delete')) {
                $html .= '<form method="post" class="d-inline" onsubmit="return confirm(\'确定删除？\')">' . csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . $s['id'] . '"><button class="btn-icon btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>';
            }
            $html .= '</td></tr>';
        }
        $this->paginationResponse($total, $html);
    }
    private function showForm($id = 0) {
        $db = DB::getInstance();
        $item = $id ? $db->single("SELECT * FROM substance WHERE id=?", [$id], true) : [];
        if ($id && !$item) { header('Location: /substance'); exit; }
        $title = $id ? '编辑物质' : '新增物质';
        $this->render('substance/form', get_defined_vars());
    }
    private function doCreate() {
        $db = DB::getInstance();
        try {
            $db->execute("INSERT INTO substance (cas_no, ec_no, index_no, name, name_en, molecular_formula, alias, is_controlled) VALUES (?,?,?,?,?,?,?,?)",
                [$_POST['cas_no'] ?? '', $_POST['ec_no'] ?? '', $_POST['index_no'] ?? '', $_POST['name'] ?? '',
                 $_POST['name_en'] ?? '', $_POST['molecular_formula'] ?? '', $_POST['alias'] ?? '',
                 (int)($_POST['is_controlled'] ?? 0)]);
            $sid = $db->lastInsertId();
            logOp('create', 'substance', $sid, '新增物质: ' . ($_POST['name'] ?? ''));
            flash('success', '物质创建成功');
        } catch (Exception $e) {
            flash('error', '创建失败: ' . $e->getMessage());
        }
        header('Location: /substance'); exit;
    }
    private function doUpdate() {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) return;
        $db = DB::getInstance();
        $db->execute("UPDATE substance SET cas_no=?, ec_no=?, index_no=?, name=?, name_en=?, molecular_formula=?, alias=?, is_controlled=? WHERE id=?",
            [$_POST['cas_no'] ?? '', $_POST['ec_no'] ?? '', $_POST['index_no'] ?? '', $_POST['name'] ?? '',
             $_POST['name_en'] ?? '', $_POST['molecular_formula'] ?? '', $_POST['alias'] ?? '',
             (int)($_POST['is_controlled'] ?? 0), $id]);
        logOp('update', 'substance', $id, '更新物质: ' . ($_POST['name'] ?? ''));
        flash('success', '物质更新成功');
        header('Location: /substance'); exit;
    }
    private function doDelete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = DB::getInstance();
            $db->execute("DELETE FROM substance WHERE id=?", [$id]);
            logOp('delete', 'substance', $id, '删除物质');
            flash('success', '物质已删除');
        }
        header('Location: /substance'); exit;
    }
}
