<?php
class ApiBomController extends ApiBaseController {
    public function index() {
        $partId = (int)ApiRequest::get('part_id', 0);
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($partId) { $where[] = "pm.part_id=?"; $params[] = $partId; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM part_material pm $w", $params);
        $items = $this->db->query("SELECT pm.*, p.part_no, p.part_name, m.material_name, m.material_no, m.env_status FROM part_material pm LEFT JOIN part p ON pm.part_id=p.id LEFT JOIN material m ON pm.material_id=m.id $w ORDER BY pm.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
