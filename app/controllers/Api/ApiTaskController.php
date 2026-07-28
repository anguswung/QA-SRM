<?php
class ApiTaskController extends ApiBaseController {
    public function index() {
        $assigneeId = ApiRequest::get('assignee_id');
        $status = ApiRequest::get('status', '');
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($assigneeId !== null) { $where[] = "t.assignee_id=?"; $params[] = (int)$assigneeId; }
        if ($status) { $where[] = "t.status=?"; $params[] = $status; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM bpm_task t $w", $params);
        $items = $this->db->query("SELECT t.*, i.title AS instance_title, i.ref_type, i.ref_id, n.node_name FROM bpm_task t LEFT JOIN bpm_instance i ON t.instance_id=i.id LEFT JOIN bpm_node n ON t.node_id=n.id $w ORDER BY t.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function detail() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT t.*, i.title AS instance_title, i.ref_type, i.ref_id, n.node_name FROM bpm_task t LEFT JOIN bpm_instance i ON t.instance_id=i.id LEFT JOIN bpm_node n ON t.node_id=n.id WHERE t.id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        ApiResponse::success($item);
    }
    public function create() {
        $d = ApiRequest::post();
        $this->db->execute("INSERT INTO bpm_task (instance_id, node_id, assignee_id, status, created_at) VALUES (?,?,?,?,datetime('now','localtime'))",
            [$d['instance_id'] ?? 0, $d['node_id'] ?? 0, $d['assignee_id'] ?? 0, $d['status'] ?? 'pending']);
        ApiResponse::created(['id' => $this->db->lastInsertId()]);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
