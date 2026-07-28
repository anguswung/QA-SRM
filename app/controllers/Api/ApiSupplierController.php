<?php
class ApiSupplierController extends ApiBaseController {
    public function index() {
        $search = ApiRequest::get('search', '');
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($search) { $where[] = "(s.name LIKE ? OR s.code LIKE ? OR s.name_short LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM supplier s $w", $params);
        $items = $this->db->query("SELECT s.* FROM supplier s $w ORDER BY s.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function detail() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM supplier WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $contacts = $this->db->query("SELECT * FROM supplier_contact WHERE supplier_id=? ORDER BY is_primary DESC, id", [$id]);
        $ratings = $this->db->query("SELECT * FROM supplier_rating WHERE supplier_id=? ORDER BY rating_date DESC", [$id]);
        ApiResponse::success(['item' => $item, 'contacts' => $contacts, 'ratings' => $ratings]);
    }
    public function rating() {
        $supplierId = (int)ApiRequest::get('supplier_id', 0);
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($supplierId) { $where[] = "sr.supplier_id=?"; $params[] = $supplierId; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM supplier_rating sr $w", $params);
        $items = $this->db->query("SELECT sr.*, u.realname AS evaluator_name FROM supplier_rating sr LEFT JOIN users u ON sr.evaluator_id=u.id $w ORDER BY sr.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function create() {
        $d = ApiRequest::post();
        $this->db->execute("INSERT INTO supplier (code, name, name_short, credit_code, contact_person, contact_phone, address, level, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,datetime('now','localtime'),datetime('now','localtime'))",
            [$d['code'] ?? '', $d['name'] ?? '', $d['name_short'] ?? '', $d['credit_code'] ?? '', $d['contact_person'] ?? '', $d['contact_phone'] ?? '', $d['address'] ?? '', $d['level'] ?? 'C', $d['status'] ?? 'active']);
        ApiResponse::created(['id' => $this->db->lastInsertId()]);
    }
    public function update() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM supplier WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $d = ApiRequest::post();
        $this->db->execute("UPDATE supplier SET code=?, name=?, name_short=?, credit_code=?, contact_person=?, contact_phone=?, address=?, level=?, status=?, updated_at=datetime('now','localtime') WHERE id=?",
            [$d['code'] ?? $item['code'], $d['name'] ?? $item['name'], $d['name_short'] ?? $item['name_short'], $d['credit_code'] ?? $item['credit_code'], $d['contact_person'] ?? $item['contact_person'], $d['contact_phone'] ?? $item['contact_phone'], $d['address'] ?? $item['address'], $d['level'] ?? $item['level'], $d['status'] ?? $item['status'], $id]);
        ApiResponse::success(['id' => $id]);
    }
    public function delete() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM supplier WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $this->db->execute("DELETE FROM supplier_contact WHERE supplier_id=?", [$id]);
        $this->db->execute("DELETE FROM supplier_rating WHERE supplier_id=?", [$id]);
        $this->db->execute("DELETE FROM supplier WHERE id=?", [$id]);
        ApiResponse::success(null, '删除成功');
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
