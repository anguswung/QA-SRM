<?php
class ApiAuthController extends ApiBaseController {
    public function login() {
        RateLimitMiddleware::checkLogin();
        $username = trim(ApiRequest::post('username', ''));
        $password = ApiRequest::post('password', '');
        if (!$username || !$password) ApiResponse::error('用户名和密码不能为空');
        $user = $this->db->single("SELECT * FROM users WHERE username=? AND status='active'", [$username], true);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->db->execute("INSERT INTO login_log (user_id, username, ip_address, status, reason, login_time) VALUES (0,?,?,?,?,datetime('now','localtime'))",
                [$username, $_SERVER['REMOTE_ADDR'] ?? '', 'failed', '密码错误']);
            ApiResponse::error('账号或密码错误', 401, 401);
        }
        if ($user['role'] === 'supplier' && $user['supplier_id']) {
            $sp = $this->db->single("SELECT blacklist FROM supplier WHERE id=?", [$user['supplier_id']], true);
            if ($sp && $sp['blacklist']) ApiResponse::forbidden('该供应商已被列入黑名单');
        }
        $this->db->execute("INSERT INTO login_log (user_id, username, ip_address, status, login_time) VALUES (?,?,?,?,datetime('now','localtime'))",
            [$user['id'], $username, $_SERVER['REMOTE_ADDR'] ?? '', 'success']);
        $accessToken = ApiAuth::generateAccessToken($user);
        $refreshToken = ApiAuth::generateRefreshToken($user);
        ApiResponse::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token' => $accessToken,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'realname' => $user['realname'] ?? $user['username'],
                'role' => $user['role'],
                'supplier_id' => (int)($user['supplier_id'] ?? 0),
            ]
        ]);
    }

    public function refresh() {
        $refreshToken = ApiRequest::post('refresh_token', '');
        if (!$refreshToken) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) $refreshToken = $m[1];
        }
        if (!$refreshToken) ApiResponse::error('缺少 refresh_token');
        $tokens = ApiAuth::refreshAccessToken($refreshToken);
        if (!$tokens) ApiResponse::unauthorized('refresh_token 无效或已过期');
        ApiResponse::success($tokens);
    }

    public function me() {
        $uid = $this->userId();
        $user = $this->db->single("SELECT id, username, realname, role, supplier_id FROM users WHERE id=?", [$uid], true);
        if (!$user) ApiResponse::unauthorized();
        ApiResponse::success([
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'realname' => $user['realname'] ?? $user['username'],
            'role' => $user['role'],
            'supplier_id' => (int)($user['supplier_id'] ?? 0),
        ]);
    }

    public function logout() {
        ApiResponse::success(null, '已退出登录');
    }
}
