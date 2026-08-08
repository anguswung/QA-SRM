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
        if (($_GET['action'] ?? '') === 'data') { $this->usersData(); return; }
        $suppliers = QueryRegistry::supplierDropdownActive();
        $roles = $db->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL ORDER BY role");
        $title = '用户管理';
        $this->render('system/users', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data）：用户管理 */
    private function usersData() {
        $this->requirePermission('system.users');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $total = (int)$db->single("SELECT COUNT(*) FROM users");
        $offset = ($page - 1) * $perPage;
        $rows = $db->query("SELECT u.*, s.name AS supplier_name FROM users u LEFT JOIN supplier s ON u.supplier_id=s.id ORDER BY u.id LIMIT ? OFFSET ?", [$perPage, $offset]);
        $html = '';
        foreach ($rows as $u) {
            $nameShow = ($u['supplier_id'] && $u['supplier_name']) ? $u['supplier_name'] : ($u['realname'] ?? '');
            $html .= '<tr style="cursor:pointer" data-id="' . $u['id'] . '" data-username="' . h($u['username']) . '" data-realname="' . h($u['realname']) . '" data-role="' . h($u['role']) . '" data-email="' . h($u['email']) . '" data-supplier="' . (int)$u['supplier_id'] . '" data-status="' . h($u['status']) . '">';
            $html .= colId($u['id'] ?? '') . '<td>' . h($u['username']) . '</td>';
            $html .= '<td class="col-realname">' . h($nameShow) . '</td>';
            $html .= '<td>' . h($u['role']) . '</td>';
            $html .= '<td>' . h($u['email'] ?: '-') . '</td>';
            $html .= '<td>' . ($u['supplier_id'] ? '<a href="/supplier/' . (int)$u['supplier_id'] . '/detail">' . h($u['supplier_name'] ?? '-') . '</a>' : '-') . '</td>';
            $html .= '<td><span class="status-tag ' . ($u['status'] === 'active' ? 'success' : 'secondary') . '">' . ($u['status'] === 'active' ? '启用' : '禁用') . '</span></td>';
            $html .= '</tr>';
        }
        $this->paginationResponse($total, $html);
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
        if (($_GET['action'] ?? '') === 'data') { $this->rolesData(); return; }
        $title = '角色管理';
        $this->render('system/roles', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data）：角色管理 */
    private function rolesData() {
        $this->requirePermission('system.roles');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $total = (int)$db->single("SELECT COUNT(*) FROM roles");
        $offset = ($page - 1) * $perPage;
        $rows = $db->query("SELECT * FROM roles ORDER BY name LIMIT ? OFFSET ?", [$perPage, $offset]);
        $html = '';
        foreach ($rows as $r) {
            $p = json_decode($r['permissions'] ?: '[]', true);
            $isSuper = in_array('*', $p);
            $html .= '<tr>';
            $html .= colId($r['id'] ?? '') . '<td>' . h($r['name']) . ($isSuper ? ' <span class="badge bg-primary text-white">超级</span>' : '') . '</td>';
            $html .= '<td>' . h($r['description'] ?: '-') . '</td>';
            $html .= '<td><span class="badge badge-soft-info">' . count($p) . '</span></td>';
            $html .= '<td><button type="button" class="btn-icon btn btn-sm btn-outline-primary" title="编辑" data-bs-toggle="modal" data-bs-target="#editRoleModal" data-id="' . $r['id'] . '" data-name="' . h($r['name']) . '" data-desc="' . h($r['description']) . '" data-perms="' . h($r['permissions'] ?: '[]') . '"><i class="fas fa-edit"></i></button></td>';
            $html .= '</tr>';
        }
        $this->paginationResponse($total, $html);
    }
    public function config() {
        $this->requirePermission('system.config');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if (in_array($action, ['test_smtp', 'send_test', 'send_reminders'], true)) {
                $mailer = new Mailer();
                if ($action === 'test_smtp') {
                    $ok = $mailer->testSmtp($err);
                    flash($ok ? 'success' : 'error', $ok ? 'SMTP 连接与认证成功' : "SMTP 测试失败: {$err}");
                } elseif ($action === 'send_test') {
                    $to = trim((string)($_POST['mail_test_to'] ?? ''));
                    $ok = $mailer->sendTest($to, $err);
                    flash($ok ? 'success' : 'error', $ok ? "测试邮件已发送至 {$to}" : "发送测试邮件失败: {$err}");
                } elseif ($action === 'send_reminders') {
                    $res = $mailer->sendExpiryReminders(true);
                    $msg = "到期提醒发送完成：表格 {$res['reports']} 份，成功 {$res['sent']}，跳过 {$res['skipped']}，失败 {$res['failed']}";
                    if ($res['errors']) $msg .= '；错误：' . implode(' | ', array_slice($res['errors'], 0, 5));
                    flash($res['failed'] ? 'warning' : 'success', $msg);
                }
                header('Location: /system/config'); exit;
            }
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
        $action = $_GET['action'] ?? '';
        if ($action === 'data') { $this->data(); return; }
        $title = '操作日志';
        $this->render('system/logs', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data） */
    private function data() {
        $this->requirePermission('system.logs');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $total = (int)$db->single("SELECT COUNT(*) FROM operation_log");
        $offset = ($page - 1) * $perPage;
        $rows = $db->query("SELECT ol.*, u.realname FROM operation_log ol LEFT JOIN users u ON ol.user_id=u.id ORDER BY ol.created_at DESC LIMIT ? OFFSET ?", [$perPage, $offset]);
        $html = '';
        foreach ($rows as $l) {
            $html .= '<tr>';
            $html .= colId($l['id'] ?? '') . '<td><small>' . h($l['created_at']) . '</small></td>';
            $html .= '<td>' . h($l['realname'] ?? $l['user_id']) . '</td>';
            $html .= '<td>' . badge($l['action'], 'info') . '</td>';
            $html .= '<td>' . h($l['module']) . '</td>';
            $html .= '<td>' . h($l['target_type'] ?? '-') . '</td>';
            $html .= '<td>' . h($l['target_id'] ?? '-') . '</td>';
            $html .= '<td><small>' . h($l['detail']) . '</small></td>';
            $html .= '<td><small>' . h($l['ip_address']) . '</small></td>';
            $html .= '</tr>';
        }
        $this->paginationResponse($total, $html);
    }

    /** 内部标准限值：管理员维护己方内部标准，对照官方限值 */
    public function internalStandard() {
        $this->requirePermission('system.std_limit');
        $db = DB::getInstance();
        $types = ['RoHS', 'REACH', 'HF'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['report_type'] ?? '';
            $names = $_POST['substance_name'] ?? [];
            $cases = $_POST['cas_no'] ?? [];
            $vals = $_POST['limit_value'] ?? [];
            if (in_array($type, $types) && is_array($names) && is_array($cases) && is_array($vals)) {
                $cnt = 0;
                foreach ($names as $i => $nm) {
                    $cas = trim((string)($cases[$i] ?? ''));
                    if ($cas === '' || !isset($vals[$i]) || trim((string)$vals[$i]) === '') continue;
                    $db->execute(
                        "INSERT INTO internal_standard (report_type, substance_name, cas_no, limit_value, limit_unit, updated_by)
                         VALUES (?,?,?,?,'mg/kg',?)
                         ON CONFLICT(report_type, cas_no) DO UPDATE SET
                           substance_name=excluded.substance_name, limit_value=excluded.limit_value,
                           updated_by=excluded.updated_by, updated_at=datetime('now','localtime')",
                        [$type, $nm, $cas, (float)$vals[$i], userId()]
                    );
                    $cnt++;
                }
                logOp('update', 'internal_standard', 0, "{$type} 内部标准保存 {$cnt} 项");
                flash('success', "{$type} 内部标准已保存（{$cnt} 项）");
            }
            header('Location: /system/internal-standard?type=' . urlencode($type)); exit;
        }

        $activeType = $_GET['type'] ?? 'RoHS';
        if (!in_array($activeType, $types)) $activeType = 'RoHS';

        $sections = [];
        foreach ($types as $t) {
            $stored = [];
            foreach ($db->query("SELECT * FROM internal_standard WHERE report_type=?", [$t]) as $s) {
                $stored[$s['cas_no']] = $s;
            }
            $rows = [];
            foreach (stdOfficialLimits($t) as $def) {
                $rows[] = [
                    'substance_name' => $def['substance_name'],
                    'cas_no'         => $def['cas_no'],
                    'official'       => $def['limit_value'],
                    'internal'       => $stored[$def['cas_no']]['limit_value'] ?? null,
                ];
            }
            $sections[$t] = $rows;
        }

        $title = '内部标准限值';
        $this->render('system/internal_standard', get_defined_vars());
    }
}
