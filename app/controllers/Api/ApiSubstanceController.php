<?php
class ApiSubstanceController extends ApiBaseController {
    public function index() {
        $search = ApiRequest::get('search', '');
        $groupId = (int)ApiRequest::get('group_id', 0);
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($search) { $where[] = "(s.name LIKE ? OR s.cas_no LIKE ? OR s.alias LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($groupId) { $where[] = "s.substance_group_id=?"; $params[] = $groupId; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM substance s $w", $params);
        $items = $this->db->query("SELECT s.*, sg.name AS group_name FROM substance s LEFT JOIN substance_group sg ON s.substance_group_id=sg.id $w ORDER BY s.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function detail() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT s.*, sg.name AS group_name FROM substance s LEFT JOIN substance_group sg ON s.substance_group_id=sg.id WHERE s.id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        ApiResponse::success($item);
    }
    public function lookup() {
        $keyword = ApiRequest::get('keyword', '');
        if (!$keyword) ApiResponse::error('keyword 不能为空');
        $items = $this->db->query("SELECT * FROM substance WHERE name LIKE ? OR cas_no LIKE ? ORDER BY name LIMIT 50", ["%$keyword%", "%$keyword%"]);
        ApiResponse::success($items);
    }
    public function create() {
        $d = ApiRequest::post();
        $this->db->execute("INSERT INTO substance (cas_no, ec_no, index_no, name, name_en, molecular_formula, alias, substance_group_id, is_controlled, created_at) VALUES (?,?,?,?,?,?,?,?,?,datetime('now','localtime'))",
            [$d['cas_no'] ?? '', $d['ec_no'] ?? '', $d['index_no'] ?? '', $d['name'] ?? '', $d['name_en'] ?? '', $d['molecular_formula'] ?? '', $d['alias'] ?? '', $d['substance_group_id'] ?? 0, $d['is_controlled'] ?? 0]);
        ApiResponse::created(['id' => $this->db->lastInsertId()]);
    }
    public function update() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM substance WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $d = ApiRequest::post();
        $this->db->execute("UPDATE substance SET cas_no=?, ec_no=?, index_no=?, name=?, name_en=?, molecular_formula=?, alias=?, substance_group_id=?, is_controlled=? WHERE id=?",
            [$d['cas_no'] ?? $item['cas_no'], $d['ec_no'] ?? $item['ec_no'], $d['index_no'] ?? $item['index_no'], $d['name'] ?? $item['name'], $d['name_en'] ?? $item['name_en'], $d['molecular_formula'] ?? $item['molecular_formula'], $d['alias'] ?? $item['alias'], $d['substance_group_id'] ?? $item['substance_group_id'], $d['is_controlled'] ?? $item['is_controlled'], $id]);
        ApiResponse::success(['id' => $id]);
    }
    public function delete() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM substance WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $this->db->execute("DELETE FROM substance_maybe_use WHERE substance_id=?", [$id]);
        $this->db->execute("DELETE FROM substance WHERE id=?", [$id]);
        ApiResponse::success(null, '删除成功');
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
