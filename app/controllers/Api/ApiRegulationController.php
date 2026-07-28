<?php
class ApiRegulationController extends ApiBaseController {
    public function index() {
        $search = ApiRequest::get('search', '');
        $category = ApiRequest::get('category', '');
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($search) { $where[] = "(rl.name LIKE ? OR rl.regulation_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($category) { $where[] = "rl.category=?"; $params[] = $category; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM regulation_lib rl $w", $params);
        $items = $this->db->query("SELECT rl.* FROM regulation_lib rl $w ORDER BY rl.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function detail() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM regulation_lib WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $contents = $this->db->query("SELECT * FROM regulation_content WHERE regulation_id=? ORDER BY sort_order, id", [$id]);
        ApiResponse::success(['item' => $item, 'contents' => $contents]);
    }
    public function focus() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM regulation_lib WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $newFocus = $item['is_focus'] ? 0 : 1;
        $this->db->execute("UPDATE regulation_lib SET is_focus=?, updated_at=datetime('now','localtime') WHERE id=?", [$newFocus, $id]);
        ApiResponse::success(['is_focus' => (bool)$newFocus]);
    }
    public function create() {
        $d = ApiRequest::post();
        $this->db->execute("INSERT INTO regulation_lib (regulation_no, name, name_en, issuing_body, publish_date, effective_date, category, status, description, content_text, version, is_focus, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,0,datetime('now','localtime'),datetime('now','localtime'))",
            [$d['regulation_no'] ?? '', $d['name'] ?? '', $d['name_en'] ?? '', $d['issuing_body'] ?? '', $d['publish_date'] ?? '', $d['effective_date'] ?? '', $d['category'] ?? '', $d['status'] ?? 'draft', $d['description'] ?? '', $d['content_text'] ?? '', $d['version'] ?? 'V1.0']);
        ApiResponse::created(['id' => $this->db->lastInsertId()]);
    }
    public function update() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM regulation_lib WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $d = ApiRequest::post();
        $this->db->execute("UPDATE regulation_lib SET regulation_no=?, name=?, name_en=?, issuing_body=?, publish_date=?, effective_date=?, category=?, status=?, description=?, content_text=?, version=?, updated_at=datetime('now','localtime') WHERE id=?",
            [$d['regulation_no'] ?? $item['regulation_no'], $d['name'] ?? $item['name'], $d['name_en'] ?? $item['name_en'], $d['issuing_body'] ?? $item['issuing_body'], $d['publish_date'] ?? $item['publish_date'], $d['effective_date'] ?? $item['effective_date'], $d['category'] ?? $item['category'], $d['status'] ?? $item['status'], $d['description'] ?? $item['description'], $d['content_text'] ?? $item['content_text'], $d['version'] ?? $item['version'], $id]);
        ApiResponse::success(['id' => $id]);
    }
    public function delete() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM regulation_lib WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $this->db->execute("DELETE FROM regulation_content WHERE regulation_id=?", [$id]);
        $this->db->execute("DELETE FROM regulation_lib WHERE id=?", [$id]);
        ApiResponse::success(null, '删除成功');
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
