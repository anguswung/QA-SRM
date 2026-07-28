<?php
class ApiMaterialController extends ApiBaseController {
    public function index() {
        $sid = $this->isSupplier() ? $this->supplierId() : 0;
        $search = ApiRequest::get('search', '');
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($sid) { $where[] = "m.supplier_id=?"; $params[] = $sid; }
        if ($search) { $where[] = "(m.material_name LIKE ? OR m.material_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM material m $w", $params);
        $items = $this->db->query("SELECT m.*, s.name_short AS supplier_name FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id $w ORDER BY m.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function detail() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT m.*, s.name_short AS supplier_name FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id WHERE m.id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $reports = $this->db->query("SELECT * FROM test_report WHERE material_id=? ORDER BY created_at DESC", [$id]);
        $composition = $this->db->query("SELECT mc.*, hm.material_name AS hm_name FROM material_composition mc JOIN homogeneous_material hm ON mc.substance_id=hm.id WHERE mc.material_id=? ORDER BY mc.id", [$id]);
        ApiResponse::success(['item' => $item, 'reports' => $reports, 'composition' => $composition]);
    }
    public function create() { $this->_notImpl(); }
    public function update() { $this->_notImpl(); }
    public function delete() { $this->_notImpl(); }
    public function composition() { $this->_notImpl(); }
    public function hmData() {
        $hmId = (int)ApiRequest::get('hm_id', 0);
        $item = $this->db->single("SELECT * FROM homogeneous_material WHERE id=?", [$hmId], true);
        if (!$item) ApiResponse::notFound();
        $reports = $this->db->query("SELECT * FROM hm_report WHERE hm_id=? ORDER BY version DESC", [$hmId]);
        $analysis = $this->db->query("SELECT ar.*, rl.name AS regulation_name, rl.regulation_no FROM analysis_record ar LEFT JOIN regulation_lib rl ON ar.regulation_id=rl.id WHERE ar.hm_id=? ORDER BY ar.analyzed_at DESC LIMIT 50", [$hmId]);
        ApiResponse::success(['hm' => $item, 'reports' => $reports, 'analysis' => $analysis]);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
