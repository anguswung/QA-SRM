<?php
class AuthController extends Controller {
    public function login() {
        RateLimitMiddleware::checkLogin();
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $user = $db->single("SELECT * FROM users WHERE username=? AND status='active'", [$username], true);
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['realname'] = $user['realname'] ?? $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['supplier_id'] = $user['supplier_id'] ?? 0;
                loadUserPermissions($user['role']);
                $db->execute("INSERT INTO login_log (user_id, username, ip_address, result) VALUES (?,?,?,?)",
                    [$user['id'], $user['username'], $_SERVER['REMOTE_ADDR'] ?? '', 'success']);
                if ($user['role'] === 'supplier' && $user['supplier_id']) {
                    $sp = $db->single("SELECT blacklist, name FROM supplier WHERE id=?", [$user['supplier_id']], true);
                    if ($sp) {
                        $_SESSION['supplier_name'] = $sp['name'] ?? '';
                        if ($sp['blacklist']) {
                            $supplierLabel = $sp['name'] ?? '';
                            session_destroy();
                            setcookie('login_error', "$supplierLabel 已被列入黑名单，无法登录", time() + 30, '/');
                            header('Location: /login'); exit;
                        }
                    }
                }
                header('Location: /dashboard'); exit;
            }
            $db->execute("INSERT INTO login_log (user_id, username, ip_address, result, fail_reason) VALUES (0,?,?,?,?)",
                [$username, $_SERVER['REMOTE_ADDR'] ?? '', 'failed', '密码错误']);
            flash('error', '账号或密码错误');
        }
        $title = '登录';
        $this->render('auth/login', get_defined_vars());
    }
    public function logout() {
        session_destroy();
        header('Location: /login'); exit;
    }
    public function passwordChange() {
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old = $_POST['old_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $user = $db->single("SELECT * FROM users WHERE id=?", [userId()], true);
            if (!$user || !password_verify($old, $user['password'])) {
                flash('error', '原密码错误');
            } elseif ($new !== $confirm) {
                flash('error', '两次密码不一致');
            } elseif (strlen($new) < 6) {
                flash('error', '密码长度至少6位');
            } else {
                $db->execute("UPDATE users SET password=?, password_changed=1 WHERE id=?",
                    [password_hash($new, PASSWORD_DEFAULT), userId()]);
                flash('success', '密码修改成功');
                if (empty($user['password_changed'])) { header('Location: /dashboard'); exit; }
            }
        }
        $title = '修改密码';
        $this->render('auth/password_change', get_defined_vars());
    }
}
