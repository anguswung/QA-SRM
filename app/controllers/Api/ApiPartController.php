<?php
class ApiPartController extends ApiBaseController {
    public function index() {
        $search = ApiRequest::get('search', '');
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($search) { $where[] = "(p.part_no LIKE ? OR p.part_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM part p $w", $params);
        $items = $this->db->query("SELECT p.* FROM part p $w ORDER BY p.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function detail() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM part WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $materials = $this->db->query("SELECT pm.*, m.material_name, m.material_no, m.env_status FROM part_material pm LEFT JOIN material m ON pm.material_id=m.id WHERE pm.part_id=?", [$id]);
        ApiResponse::success(['item' => $item, 'materials' => $materials]);
    }
    public function bom() {
        $partId = (int)ApiRequest::get('part_id', 0);
        if (!$partId) {
            $search = ApiRequest::get('search', '');
            $page = max(1, (int)ApiRequest::get('page', 1));
            $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
            $offset = ($page - 1) * $perPage;
            $where = []; $params = [];
            if ($search) { $where[] = "(p.part_no LIKE ? OR p.part_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
            $w = $where ? "WHERE " . implode(' AND ', $where) : '';
            $total = $this->db->single("SELECT COUNT(*) FROM part p $w", $params);
            $items = $this->db->query("SELECT p.* FROM part p $w ORDER BY p.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
            ApiResponse::paginated($items, $total, $page, $perPage);
        } else {
            $items = $this->db->query(
                "SELECT pm.*, m.material_name, m.material_no, m.env_status, m.spec AS material_spec FROM part_material pm LEFT JOIN material m ON pm.material_id=m.id WHERE pm.part_id=? ORDER BY pm.id",
                [$partId]
            );
            ApiResponse::success($items);
        }
    }
    public function bomTree() {
        $parts = $this->db->query("SELECT * FROM part ORDER BY sort_order, id");
        $materials = $this->db->query("SELECT pm.*, m.material_name, m.material_no, m.env_status, m.spec AS material_spec FROM part_material pm LEFT JOIN material m ON pm.material_id=m.id ORDER BY pm.id");
        $matByPart = [];
        foreach ($materials as $m) {
            $matByPart[$m['part_id']][] = $m;
        }
        $tree = $this->_buildTree($parts, $matByPart, 0);
        ApiResponse::success($tree);
    }
    public function bomExport() { $this->_notImpl(); }
    private function _buildTree(&$parts, &$matByPart, $parentId) {
        $branch = [];
        foreach ($parts as $p) {
            if ((int)$p['parent_id'] === $parentId) {
                $node = $p;
                $node['materials'] = $matByPart[$p['id']] ?? [];
                $children = $this->_buildTree($parts, $matByPart, $p['id']);
                if ($children) $node['children'] = $children;
                $branch[] = $node;
            }
        }
        return $branch;
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
