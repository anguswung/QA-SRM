<?php
class TaskController extends Controller {
    public function index() {
        $this->requirePermission('task.view');
        $this->ensureSchema();
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') { $this->doCreate(); return; }
            if ($action === 'submit') { $this->doSubmit(); return; }
            if ($action === 'upload_attachment') { $this->doUploadAttachment(); return; }
            if ($action === 'delete_attachment') { $this->doDeleteAttachment(); return; }
            header('Location: /task'); exit;
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'create') { $this->showForm(); return; }
        if ($action === 'detail') { $this->showDetail((int)($_GET['id'] ?? 0)); return; }
        $this->showList();
    }
    private function ensureSchema() {
        $db = DB::getInstance();
        try { $db->execute("ALTER TABLE bpm_instance ADD COLUMN priority INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $db->execute("ALTER TABLE bpm_instance ADD COLUMN due_date TEXT"); } catch (Exception $e) {}
        try { $db->execute("CREATE TABLE IF NOT EXISTS task_attachment (id INTEGER PRIMARY KEY AUTOINCREMENT, instance_id INTEGER REFERENCES bpm_instance(id), file_name TEXT, file_path TEXT, uploaded_by INTEGER REFERENCES users(id), created_at TEXT DEFAULT (datetime('now','localtime')))"); } catch (Exception $e) {}
    }
    private function showList() {
        $db = DB::getInstance();
        $tab = $_GET['tab'] ?? 'pending';
        $search = $_GET['search'] ?? '';
        $priority = (int)($_GET['priority'] ?? 0);
        if (isSupplier()) {
            $sid = userSupplierId();
            $instances = $db->query("SELECT bi.*, t.name AS template_name FROM bpm_instance bi JOIN bpm_template t ON bi.template_id=t.id WHERE bi.initiator_id=? ORDER BY bi.created_at DESC", [userId()]);
            $pendingTasks = $db->query("SELECT bt.*, bi.title, bi.ref_type, bi.priority, bi.due_date, bn.node_name, t.name AS template_name FROM bpm_task bt JOIN bpm_instance bi ON bt.instance_id=bi.id JOIN bpm_node bn ON bt.node_id=bn.id JOIN bpm_template t ON bi.template_id=t.id WHERE bt.assignee_id=? AND bt.status='pending' ORDER BY bi.priority DESC, bt.created_at DESC", [userId()]);
        } else {
            $where = []; $params = [];
            if ($tab === 'pending') $where[] = "bi.status='active'";
            elseif ($tab === 'completed') $where[] = "bi.status='completed'";
            if ($search) { $where[] = "(bi.title LIKE ? OR bi.id LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($priority) { $where[] = "bi.priority=?"; $params[] = $priority; }
            $whereClause = $where ? "WHERE " . implode(' AND ', $where) : '';
            $instances = $db->query("SELECT bi.*, t.name AS template_name, u.realname AS initiator_name FROM bpm_instance bi JOIN bpm_template t ON bi.template_id=t.id LEFT JOIN users u ON bi.initiator_id=u.id $whereClause ORDER BY bi.priority DESC, bi.created_at DESC" . ($search ? " LIMIT 50" : ""), $params);
            $pendingTasks = [];
        }
        $title = '任务管理';
        $this->render('task/index', get_defined_vars());
    }
    private function showForm() {
        $db = DB::getInstance();
        $suppliers = QueryRegistry::supplierDropdownActive();
        $templates = $db->query("SELECT * FROM bpm_template WHERE status='published' ORDER BY name");
        $title = '新建任务';
        $this->render('task/form', get_defined_vars());
    }
    private function showDetail($id) {
        $db = DB::getInstance();
        $instance = $db->single("SELECT bi.*, t.name AS template_name, u.realname AS initiator_name FROM bpm_instance bi JOIN bpm_template t ON bi.template_id=t.id LEFT JOIN users u ON bi.initiator_id=u.id WHERE bi.id=?", [$id], true);
        if (!$instance) { header('Location: /task'); exit; }
        $tasks = $db->query("SELECT bt.*, bn.node_name, bn.node_type, bn.sort_order, u.realname AS assignee_name FROM bpm_task bt JOIN bpm_node bn ON bt.node_id=bn.id LEFT JOIN users u ON bt.assignee_id=u.id WHERE bt.instance_id=? ORDER BY bt.id", [$id]);
        $attachments = $db->query("SELECT ta.*, u.realname AS uploader_name FROM task_attachment ta LEFT JOIN users u ON ta.uploaded_by=u.id WHERE ta.instance_id=? ORDER BY ta.created_at DESC", [$id]);
        $title = '任务详情';
        $this->render('task/detail', get_defined_vars());
    }
    private function doCreate() {
        $db = DB::getInstance();
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $templateId = (int)($_POST['template_id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $priority = (int)($_POST['priority'] ?? 0);
        $dueDate = $_POST['due_date'] ?? '';
        $description = $_POST['description'] ?? '';
        if (!$supplierId || !$templateId || !$title) { flash('error', '请填写完整信息'); header('Location: /task?action=create'); exit; }
        $supplier = $db->single("SELECT id, name FROM supplier WHERE id=?", [$supplierId], true);
        $user = $db->single("SELECT id FROM users WHERE supplier_id=? AND role='supplier'", [$supplierId], true);
        if (!$user) { flash('error', '该供应商无系统账号'); header('Location: /task?action=create'); exit; }
        // Store description in ref_type_extra or use a separate field - reuse ref_type as 'task'
        $engine = new BpmEngine();
        $instanceId = $engine->start($templateId, $title, 'task', 0, $user['id']);
        // Update priority, due_date, description
        $db->execute("UPDATE bpm_instance SET ref_id=?, priority=?, due_date=? WHERE id=?", [$supplierId, $priority, $dueDate ?: null, $instanceId]);
        // Add a first task comment with the description as admin instructions
        if ($description) {
            $db->execute("UPDATE bpm_task SET comment=? WHERE instance_id=? AND status='pending' AND id=(SELECT MIN(id) FROM bpm_task WHERE instance_id=? AND status='pending')", ["管理员说明 $description", $instanceId, $instanceId]);
        }
        logOp('create', 'task', $instanceId, "新建任务-{$supplier['name']}: $title");
        flash('success', "已成功下发给{$supplier['name']}");
        header('Location: /task'); exit;
    }
    private function doSubmit() {
        $instanceId = (int)($_POST['instance_id'] ?? 0);
        $comment = $_POST['comment'] ?? '';
        if ($instanceId) {
            $db = DB::getInstance();
            // Handle file upload
            if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $this->saveAttachment($instanceId, $_FILES['attachment']);
            }
            $engine = new BpmEngine();
            $engine->advance($instanceId, userId(), $comment);
            logOp('update', 'task', $instanceId, '提交任务');
            flash('success', '已提交');
        }
        header('Location: /task'); exit;
    }
    private function doUploadAttachment() {
        $instanceId = (int)($_POST['instance_id'] ?? 0);
        if ($instanceId && !empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $this->saveAttachment($instanceId, $_FILES['attachment']);
            flash('success', '文件已上传');
        }
        header('Location: /task?action=detail&id=' . $instanceId); exit;
    }
    private function doDeleteAttachment() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = DB::getInstance();
            $att = $db->single("SELECT * FROM task_attachment WHERE id=?", [$id], true);
            if ($att) {
                if (!empty($att['file_path']) && file_exists($att['file_path'])) unlink($att['file_path']);
                $db->execute("DELETE FROM task_attachment WHERE id=?", [$id]);
                flash('success', '文件已删除');
            }
        }
        header('Location: /task?action=detail&id=' . (int)($_POST['instance_id'] ?? 0)); exit;
    }
    private function saveAttachment($instanceId, $file) {
        $config = require __DIR__ . '/../../config/app.php';
        $uploadDir = rtrim($config['upload']['path'] ?? 'uploads', '/') . '/task/' . $instanceId;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fname = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadDir . '/' . $fname;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            DB::getInstance()->execute(
                "INSERT INTO task_attachment (instance_id, file_name, file_path, uploaded_by) VALUES (?,?,?,?)",
                [$instanceId, $file['name'], $dest, userId()]
            );
        }
    }
}
