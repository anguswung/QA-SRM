<?php
class ApiQueryController extends ApiBaseController {
    public function material() {
        $search = ApiRequest::get('search', '');
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($search) { $where[] = "(m.material_name LIKE ? OR m.material_no LIKE ? OR m.mpn LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM material m $w", $params);
        $items = $this->db->query("SELECT m.*, s.name_short AS supplier_name FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id $w ORDER BY m.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function expired() {
        $days = (int)ApiRequest::get('days', 30);
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        $where[] = "tr.expiry_date IS NOT NULL AND tr.expiry_date <= date('now','+$days days')";
        $w = "WHERE " . implode(' AND ', $where);
        $total = $this->db->single("SELECT COUNT(*) FROM test_report tr $w", $params);
        $items = $this->db->query("SELECT tr.*, m.material_name, m.material_no FROM test_report tr LEFT JOIN material m ON tr.material_id=m.id $w ORDER BY tr.expiry_date ASC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function history() {
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $total = $this->db->single("SELECT COUNT(*) FROM operation_log");
        $items = $this->db->query("SELECT o.*, u.realname AS user_name FROM operation_log o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.id DESC LIMIT ? OFFSET ?", [$perPage, $offset]);
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
