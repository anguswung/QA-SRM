<?php
class ApiSystemController extends ApiBaseController {
    public function users() {
        if (!$this->isAdmin()) ApiResponse::forbidden();
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $total = $this->db->single("SELECT COUNT(*) FROM users");
        $items = $this->db->query("SELECT id, username, realname, role, email, phone, supplier_id, status, created_at FROM users ORDER BY id ASC LIMIT ? OFFSET ?", [$perPage, $offset]);
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function logs() {
        if (!$this->isAdmin()) ApiResponse::forbidden();
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $total = $this->db->single("SELECT COUNT(*) FROM login_log");
        $items = $this->db->query("SELECT * FROM login_log ORDER BY id DESC LIMIT ? OFFSET ?", [$perPage, $offset]);
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function config() {
        if (!$this->isAdmin()) ApiResponse::forbidden();
        $items = $this->db->query("SELECT config_key, config_value, description FROM system_config ORDER BY id");
        $config = [];
        foreach ($items as $row) { $config[$row['config_key']] = $row['config_value']; }
        ApiResponse::success($config);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
