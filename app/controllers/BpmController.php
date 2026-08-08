<?php
class BpmController extends Controller {
    public function index() {
        $this->requirePermission('task.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'advance') {
                $instanceId = (int)($_POST['instance_id'] ?? 0);
                if (isSupplier() && !$this->isAssignee($instanceId)) { flash('error', '无权操作'); header('Location: /bpm'); exit; }
                $engine = new BpmEngine();
                $engine->advance($instanceId, userId(), $_POST['comment'] ?? '');
                flash('success', '已处理');
            }
            if ($action === 'reject') {
                $instanceId = (int)($_POST['instance_id'] ?? 0);
                if (isSupplier() && !$this->isAssignee($instanceId)) { flash('error', '无权操作'); header('Location: /bpm'); exit; }
                $engine = new BpmEngine();
                $engine->reject($instanceId, userId(), $_POST['comment'] ?? '');
                flash('success', '已驳回');
            }
            header('Location: /bpm'); exit;
        }
        $tasks = $db->query("SELECT bt.*, bi.title, bi.ref_type, bi.ref_id, bn.node_name, t.name AS template_name FROM bpm_task bt JOIN bpm_instance bi ON bt.instance_id=bi.id JOIN bpm_node bn ON bt.node_id=bn.id JOIN bpm_template t ON bi.template_id=t.id WHERE bt.assignee_id=? AND bt.status='pending' ORDER BY bt.created_at DESC", [userId()]);
        $instances = isSupplier()
            ? $db->query("SELECT bi.*, t.name AS template_name FROM bpm_instance bi JOIN bpm_template t ON bi.template_id=t.id WHERE bi.initiator_id=? ORDER BY bi.created_at DESC LIMIT 20", [userId()])
            : $db->query("SELECT bi.*, t.name AS template_name FROM bpm_instance bi JOIN bpm_template t ON bi.template_id=t.id ORDER BY bi.created_at DESC LIMIT 20");
        $title = '流程中心';
        $this->render('bpm/index', get_defined_vars());
    }
    public function config() {
        $this->requirePermission('bpm.config');
        if (!isAdmin()) { flash('error', '无权操作'); header('Location: /bpm'); exit; }
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'template_create') {
                $db->execute("INSERT INTO bpm_template (name, description, category, version) VALUES (?,?,?,?)",
                    [$_POST['name'] ?? '', $_POST['description'] ?? '', $_POST['category'] ?? '', 'V1.0']);
                flash('success', '模板创建成功');
            }
            if ($action === 'template_delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) { $db->execute("DELETE FROM bpm_template WHERE id=?", [$id]); flash('success', '模板已删除'); }
            }
            if ($action === 'node_create') {
                $db->execute("INSERT INTO bpm_node (template_id, node_name, node_type, sort_order, role_required, timeout_hours) VALUES (?,?,?,?,?,?)",
                    [(int)($_POST['template_id'] ?? 0), $_POST['node_name'] ?? '', $_POST['node_type'] ?? 'approval',
                     (int)($_POST['sort_order'] ?? 0), $_POST['role_required'] ?? '', (int)($_POST['timeout_hours'] ?? 0)]);
                flash('success', '节点已添加');
            }
            if ($action === 'node_delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) { $db->execute("DELETE FROM bpm_node WHERE id=?", [$id]); flash('success', '节点已删除'); }
            }
            header('Location: /bpm/config'); exit;
        }
        $templates = $db->query("SELECT * FROM bpm_template ORDER BY name");
        $nodes = $db->query("SELECT bn.*, t.name AS template_name FROM bpm_node bn JOIN bpm_template t ON bn.template_id=t.id ORDER BY t.name, bn.sort_order");
        $title = '流程配置';
        $this->render('bpm/config', get_defined_vars());
    }
    public function detail() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: /bpm'); exit; }
        $db = DB::getInstance();
        $instance = $db->single("SELECT bi.*, t.name AS template_name, u.realname AS initiator_name FROM bpm_instance bi JOIN bpm_template t ON bi.template_id=t.id LEFT JOIN users u ON bi.initiator_id=u.id WHERE bi.id=?", [$id], true);
        if (!$instance) { header('Location: /bpm'); exit; }
        if (isSupplier() && (int)$instance['initiator_id'] !== userId() && !$this->isAssignee($id)) { flash('error', '无权操作'); header('Location: /bpm'); exit; }
        $tasks = $db->query("SELECT bt.*, bn.node_name, bn.node_type, bn.sort_order, u.realname AS assignee_name FROM bpm_task bt JOIN bpm_node bn ON bt.node_id=bn.id LEFT JOIN users u ON bt.assignee_id=u.id WHERE bt.instance_id=? ORDER BY bt.id", [$id]);
        $title = '流程详情 - ' . $instance['title'];
        $this->render('bpm/detail', get_defined_vars());
    }
    public function agent() {
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $principalId = (int)($_POST['principal_id'] ?? 0);
                if (isSupplier() && $principalId !== userId()) { flash('error', '无权操作'); header('Location: /bpm/agent'); exit; }
                $db->execute("INSERT INTO bpm_agent (principal_id, agent_id, template_ids, start_date, end_date, remark) VALUES (?,?,?,?,?,?)",
                    [$principalId, (int)($_POST['agent_id'] ?? 0),
                     $_POST['template_ids'] ?? '*', $_POST['start_date'] ?? '', $_POST['end_date'] ?? '',
                     $_POST['remark'] ?? '']);
                flash('success', '代理设置已创建');
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    if (isSupplier()) {
                        $own = $db->single("SELECT principal_id FROM bpm_agent WHERE id=?", [$id], true);
                        if (!$own || (int)$own['principal_id'] !== userId()) { flash('error', '无权操作'); header('Location: /bpm/agent'); exit; }
                    }
                    $db->execute("DELETE FROM bpm_agent WHERE id=?", [$id]); flash('success', '代理已删除');
                }
            }
            header('Location: /bpm/agent'); exit;
        }
        $agents = isSupplier()
            ? $db->query("SELECT ba.*, u1.realname AS principal_name, u2.realname AS agent_name FROM bpm_agent ba LEFT JOIN users u1 ON ba.principal_id=u1.id LEFT JOIN users u2 ON ba.agent_id=u2.id WHERE ba.principal_id=? ORDER BY ba.created_at DESC", [userId()])
            : $db->query("SELECT ba.*, u1.realname AS principal_name, u2.realname AS agent_name FROM bpm_agent ba LEFT JOIN users u1 ON ba.principal_id=u1.id LEFT JOIN users u2 ON ba.agent_id=u2.id ORDER BY ba.created_at DESC");
        $users = $db->query("SELECT id, realname, username, role FROM users WHERE status='active' ORDER BY realname");
        $title = '流程代理';
        $this->render('bpm/agent', get_defined_vars());
    }
    private function isAssignee(int $instanceId): bool {
        $db = DB::getInstance();
        return (bool)$db->single("SELECT COUNT(*) FROM bpm_task WHERE instance_id=? AND assignee_id=? AND status='pending'", [$instanceId, userId()]);
    }
}
