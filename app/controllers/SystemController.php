<?php
class SystemController extends Controller {
    public function users() {
        $this->requirePermission('system.users');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $pwd = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
                $db->execute("INSERT INTO users (username, password, realname, role, email, phone, supplier_id, status) VALUES (?,?,?,?,?,?,?,?)",
                    [$_POST['username'] ?? '', $pwd, $_POST['realname'] ?? '', $_POST['role'] ?? 'supplier',
                     $_POST['email'] ?? '', $_POST['phone'] ?? '', (int)($_POST['supplier_id'] ?? 0) ?: null,
                     $_POST['status'] ?? 'active']);
                logOp('create', 'user', $db->lastInsertId(), '创建用户: ' . ($_POST['username'] ?? ''));
                flash('success', '用户创建成功');
            }
            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $sql = "UPDATE users SET realname=?, role=?, email=?, phone=?, supplier_id=?, status=?";
                $params = [$_POST['realname'] ?? '', $_POST['role'] ?? 'supplier', $_POST['email'] ?? '',
                           $_POST['phone'] ?? '', (int)($_POST['supplier_id'] ?? 0) ?: null, $_POST['status'] ?? 'active'];
                if (!empty($_POST['password'])) {
                    $sql .= ", password=?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                $sql .= " WHERE id=?";
                $params[] = $id;
                $db->execute($sql, $params);
                logOp('update', 'user', $id, '更新用户');
                flash('success', '用户已更新');
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id && $id !== userId()) { $db->execute("DELETE FROM users WHERE id=?", [$id]); flash('success', '用户已删除'); }
            }
            header('Location: /system/users'); exit;
        }
        $users = $db->query("SELECT u.*, s.name AS supplier_name FROM users u LEFT JOIN supplier s ON u.supplier_id=s.id ORDER BY u.id");
        $suppliers = $db->query("SELECT id, code, name FROM supplier WHERE status='active' ORDER BY code");
        $roles = $db->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL ORDER BY role");
        $title = '用户管理';
        $this->render('system/users', get_defined_vars());
    }
    public function roles() {
        $this->requirePermission('system.roles');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $permsJson = json_encode($_POST['perms'] ?? [], JSON_UNESCAPED_UNICODE);
            if ($action === 'create') {
                $db->execute("INSERT INTO roles (name, description, permissions) VALUES (?,?,?)",
                    [$_POST['name'] ?? '', $_POST['description'] ?? '', $permsJson]);
                flash('success', '角色已创建');
            }
            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $db->execute("UPDATE roles SET name=?, description=?, permissions=? WHERE id=?",
                    [$_POST['name'] ?? '', $_POST['description'] ?? '', $permsJson, $id]);
                flash('success', '角色已更新');
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $used = $db->single("SELECT COUNT(*) FROM users WHERE role=(SELECT name FROM roles WHERE id=?)", [$id]);
                if ($used > 0) { flash('error', '该角色下有用户，无法删除'); }
                elseif ($id) { $db->execute("DELETE FROM roles WHERE id=?", [$id]); flash('success', '角色已删除'); }
            }
            header('Location: /system/roles'); exit;
        }
        $roles = $db->query("SELECT * FROM roles ORDER BY name");
        $title = '角色管理';
        $this->render('system/roles', get_defined_vars());
    }
    public function config() {
        $this->requirePermission('system.config');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_FILES['company_logo']['name'])) {
                $file = $_FILES['company_logo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (!in_array($ext, $allowed)) {
                    flash('error', 'Logo仅支持jpg/png/gif/webp格式');
                    header('Location: /system/config'); exit;
                }
                $dir = uploadPath('logo');
                $dest = $dir . '/logo.' . $ext;
                $old = $db->single("SELECT config_value FROM system_config WHERE config_key='company_logo'", [], false);
                if ($old && file_exists($old)) @unlink($old);
                move_uploaded_file($file['tmp_name'], $dest);
                $existing = $db->single("SELECT id FROM system_config WHERE config_key='company_logo'", [], true);
                if ($existing) {
                    $db->execute("UPDATE system_config SET config_value=?, updated_at=datetime('now','localtime') WHERE id=?", [$dest, $existing['id']]);
                } else {
                    $db->execute("INSERT INTO system_config (config_key, config_value, description) VALUES ('company_logo', ?, '公司Logo')", [$dest]);
                }
            }
            $keys = $_POST['config_key'] ?? [];
            $values = $_POST['config_value'] ?? [];
            foreach ($keys as $i => $key) {
                if (empty($key)) continue;
                $existing = $db->single("SELECT id FROM system_config WHERE config_key=?", [$key], true);
                if ($existing) {
                    $db->execute("UPDATE system_config SET config_value=?, updated_at=datetime('now','localtime') WHERE id=?", [$values[$i] ?? '', $existing['id']]);
                } else {
                    $db->execute("INSERT INTO system_config (config_key, config_value) VALUES (?,?)", [$key, $values[$i] ?? '']);
                }
            }
            flash('success', '配置已保存');
            header('Location: /system/config'); exit;
        }
        $configs = $db->query("SELECT * FROM system_config ORDER BY config_key");
        $title = '系统设置';
        $this->render('system/config', get_defined_vars());
    }
    public function logs() {
        $this->requirePermission('system.logs');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'clean_logs') {
                $keepDays = max(7, (int)($_POST['keep_days'] ?? 30));
                $cutoff = date('Y-m-d H:i:s', strtotime("-{$keepDays} days"));
                $opDeleted = $db->single("SELECT COUNT(*) FROM operation_log WHERE created_at < ?", [$cutoff]);
                $db->execute("DELETE FROM operation_log WHERE created_at < ?", [$cutoff]);
                $loginDeleted = $db->single("SELECT COUNT(*) FROM login_log WHERE login_time < ?", [$cutoff]);
                $db->execute("DELETE FROM login_log WHERE login_time < ?", [$cutoff]);
                logOp('clean', 'logs', 0, "清理 {$keepDays} 天前日志: 操作日志 {$opDeleted} 条, 登录日志 {$loginDeleted} 条");
                flash('success', "已清理 {$keepDays} 天前的日志（操作日志 {$opDeleted} 条，登录日志 {$loginDeleted} 条）");
                header('Location: /system/logs'); exit;
            }
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        $total = $db->single("SELECT COUNT(*) FROM operation_log");
        $logs = $db->query("SELECT ol.*, u.realname FROM operation_log ol LEFT JOIN users u ON ol.user_id=u.id ORDER BY ol.created_at DESC LIMIT ? OFFSET ?", [$perPage, $offset]);
        $totalPages = ceil($total / $perPage);
        $title = '操作日志';
        $this->render('system/logs', get_defined_vars());
    }
}
