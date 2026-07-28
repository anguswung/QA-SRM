<?php
class SubstanceController extends Controller {
    public function index() {
        $this->requirePermission('substance.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') { $this->doCreate(); return; }
            if ($action === 'update') { $this->doUpdate(); return; }
            if ($action === 'delete') { $this->doDelete(); return; }
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'create') { $this->showForm(); return; }
        if ($action === 'edit') { $this->showForm((int)($_GET['id'] ?? 0)); return; }
        $this->showList();
    }
    public function groups() {
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $db->execute("INSERT INTO substance_group (name, parent_id, sort_order) VALUES (?,?,?)",
                    [$_POST['name'] ?? '', (int)($_POST['parent_id'] ?? 0) ?: null, (int)($_POST['sort_order'] ?? 0)]);
                flash('success', '分组创建成功');
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) { $db->execute("DELETE FROM substance_group WHERE id=?", [$id]); flash('success', '分组已删除'); }
            }
            header('Location: /substance/groups'); exit;
        }
        $groups = $db->query("WITH RECURSIVE gt AS (SELECT id,name,parent_id,0 AS depth,CAST(printf('%04d',sort_order) AS TEXT) AS path FROM substance_group WHERE parent_id IS NULL UNION ALL SELECT sg.id,sg.name,sg.parent_id,gt.depth+1,gt.path||'/'||CAST(printf('%04d',sg.sort_order) AS TEXT) FROM substance_group sg JOIN gt ON sg.parent_id=gt.id) SELECT * FROM gt ORDER BY path");
        $allGroups = $db->query("SELECT id, name FROM substance_group ORDER BY name");
        $title = '物质分组';
        $this->render('substance/groups', get_defined_vars());
    }
    public function uses() {
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['action'] ?? '') === 'create') {
                $db->execute("INSERT INTO maybe_use (name, description) VALUES (?,?)",
                    [$_POST['name'] ?? '', $_POST['description'] ?? '']);
                flash('success', '用途创建成功');
            }
            if (($_POST['action'] ?? '') === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) { $db->execute("DELETE FROM maybe_use WHERE id=?", [$id]); flash('success', '用途已删除'); }
            }
            header('Location: /substance/uses'); exit;
        }
        $uses = $db->query("SELECT * FROM maybe_use ORDER BY name");
        $title = '物质用途';
        $this->render('substance/uses', get_defined_vars());
    }
    private function showList() {
        $db = DB::getInstance();
        $search = $_GET['search'] ?? '';
        $group = (int)($_GET['group_id'] ?? 0);
        $controlled = $_GET['controlled'] ?? '';
        $where = []; $params = [];
        if ($search) {
            $where[] = "(s.name LIKE ? OR s.name_en LIKE ? OR s.cas_no=?)";
            $params[] = "%$search%"; $params[] = "%$search%"; $params[] = $search;
        }
        if ($group) { $where[] = "s.substance_group_id=?"; $params[] = $group; }
        if ($controlled !== '') { $where[] = "s.is_controlled=?"; $params[] = (int)$controlled; }
        $subs = $db->query("SELECT s.*, sg.name AS group_name FROM substance s LEFT JOIN substance_group sg ON s.substance_group_id=sg.id" . ($where ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY s.name LIMIT 2000", $params);
        $groups = $db->query("SELECT id, name FROM substance_group ORDER BY name");
        $title = '物质库';
        $this->render('substance/index', get_defined_vars());
    }
    private function showForm($id = 0) {
        $db = DB::getInstance();
        $item = $id ? $db->single("SELECT * FROM substance WHERE id=?", [$id], true) : [];
        if ($id && !$item) { header('Location: /substance'); exit; }
        $groups = $db->query("SELECT id, name FROM substance_group ORDER BY name");
        $uses = $db->query("SELECT * FROM maybe_use ORDER BY name");
        $selectedUses = $id ? $db->query("SELECT maybe_use_id FROM substance_maybe_use WHERE substance_id=?", [$id]) : [];
        $selectedIds = array_map(fn($u) => $u['maybe_use_id'], $selectedUses);
        $title = $id ? '编辑物质' : '新增物质';
        $this->render('substance/form', get_defined_vars());
    }
    private function doCreate() {
        $db = DB::getInstance();
        try {
            $db->execute("INSERT INTO substance (cas_no, ec_no, index_no, name, name_en, molecular_formula, alias, substance_group_id, is_controlled) VALUES (?,?,?,?,?,?,?,?,?)",
                [$_POST['cas_no'] ?? '', $_POST['ec_no'] ?? '', $_POST['index_no'] ?? '', $_POST['name'] ?? '',
                 $_POST['name_en'] ?? '', $_POST['molecular_formula'] ?? '', $_POST['alias'] ?? '',
                 (int)($_POST['substance_group_id'] ?? 0) ?: null, (int)($_POST['is_controlled'] ?? 0)]);
            $sid = $db->lastInsertId();
            foreach ((array)($_POST['maybe_use_ids'] ?? []) as $uid) {
                $db->execute("INSERT INTO substance_maybe_use (substance_id, maybe_use_id) VALUES (?,?)", [$sid, (int)$uid]);
            }
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
        $db->execute("UPDATE substance SET cas_no=?, ec_no=?, index_no=?, name=?, name_en=?, molecular_formula=?, alias=?, substance_group_id=?, is_controlled=? WHERE id=?",
            [$_POST['cas_no'] ?? '', $_POST['ec_no'] ?? '', $_POST['index_no'] ?? '', $_POST['name'] ?? '',
             $_POST['name_en'] ?? '', $_POST['molecular_formula'] ?? '', $_POST['alias'] ?? '',
             (int)($_POST['substance_group_id'] ?? 0) ?: null, (int)($_POST['is_controlled'] ?? 0), $id]);
        $db->execute("DELETE FROM substance_maybe_use WHERE substance_id=?", [$id]);
        foreach ((array)($_POST['maybe_use_ids'] ?? []) as $uid) {
            $db->execute("INSERT INTO substance_maybe_use (substance_id, maybe_use_id) VALUES (?,?)", [$id, (int)$uid]);
        }
        logOp('update', 'substance', $id, '更新物质: ' . ($_POST['name'] ?? ''));
        flash('success', '物质更新成功');
        header('Location: /substance'); exit;
    }
    private function doDelete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = DB::getInstance();
            $db->execute("DELETE FROM substance_maybe_use WHERE substance_id=?", [$id]);
            $db->execute("DELETE FROM substance WHERE id=?", [$id]);
            logOp('delete', 'substance', $id, '删除物质');
            flash('success', '物质已删除');
        }
        header('Location: /substance'); exit;
    }
}
