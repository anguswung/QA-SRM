<?php
class ApiWarningController extends ApiBaseController {
    public function index() {
        $status = ApiRequest::get('status', '');
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($status) { $where[] = "wr.status=?"; $params[] = $status; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM warning_record wr $w", $params);
        $items = $this->db->query("SELECT wr.*, m.material_name, m.material_no FROM warning_record wr LEFT JOIN material m ON wr.material_id=m.id $w ORDER BY wr.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
