<?php
class ApiHomogeneousController extends ApiBaseController {
    public function index() {
        $sid = $this->isSupplier() ? $this->supplierId() : 0;
        $search = ApiRequest::get('search', '');
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $where = []; $params = [];
        if ($sid) { $where[] = "m.supplier_id=?"; $params[] = $sid; }
        if ($search) { $where[] = "m.material_name LIKE ?"; $params[] = "%$search%"; }
        $w = $where ? "WHERE " . implode(' AND ', $where) : '';
        $total = $this->db->single("SELECT COUNT(*) FROM homogeneous_material m $w", $params);
        $items = $this->db->query("SELECT m.*, s.name_short AS supplier_name FROM homogeneous_material m LEFT JOIN supplier s ON m.supplier_id=s.id $w ORDER BY m.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    public function detail() {
        $id = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM homogeneous_material WHERE id=?", [$id], true);
        if (!$item) ApiResponse::notFound();
        $composition = $this->db->query("SELECT * FROM hm_composition WHERE hm_id=?", [$id]);
        $reports = $this->db->query("SELECT * FROM hm_report WHERE hm_id=? ORDER BY version DESC", [$id]);
        ApiResponse::success(['item' => $item, 'composition' => $composition, 'reports' => $reports]);
    }
    public function reports() {
        $hmId = (int)ApiRequest::get('id', 0);
        $item = $this->db->single("SELECT * FROM homogeneous_material WHERE id=?", [$hmId], true);
        if (!$item) ApiResponse::notFound();
        $reports = $this->db->query("SELECT * FROM hm_report WHERE hm_id=? ORDER BY version_group, version DESC", [$hmId]);
        usort($reports, function($a, $b) {
            $order = ['RoHS' => 0, 'REACH' => 1, 'HF' => 2, 'MSDS' => 3];
            $oa = $order[$a['report_type']] ?? 9; $ob = $order[$b['report_type']] ?? 9;
            if ($oa !== $ob) return $oa - $ob;
            return ($b['version'] ?? 1) - ($a['version'] ?? 1);
        });
        $composition = $this->db->query("SELECT * FROM hm_composition WHERE hm_id=? ORDER BY id", [$hmId]);
        $reportElements = [];
        foreach ($reports as $r) {
            $elems = $this->db->query("SELECT * FROM hm_report_element WHERE hm_report_id=? ORDER BY id", [$r['id']]);
            if (empty($elems)) {
                $reg = $this->db->single("SELECT id FROM regulation_lib WHERE name LIKE ? AND status='published' ORDER BY id LIMIT 1", ['%' . $r['report_type'] . '%'], true);
                if ($reg) $elems = $this->db->query("SELECT id AS __orig, substance_name, cas_no, limit_value, limit_unit, article_no, title FROM regulation_content WHERE regulation_id=? ORDER BY sort_order, id", [$reg['id']]);
            }
            if ($r['report_type'] === 'RoHS') {
                $hasPBB = false;
                foreach ($elems as $e) {
                    if (strtoupper($e['substance_name']) === 'PBB' || mb_strpos($e['substance_name'] ?? '', '多溴联苯') !== false) {
                        $hasPBB = true;
                        break;
                    }
                }
                if (!$hasPBB) {
                    $elems[] = [
                        'substance_name' => 'PBB',
                        'cas_no' => '59536-65-1',
                        'limit_value' => 1000,
                        'limit_unit' => 'mg/kg',
                        'title' => '多溴联苯(PBB)',
                    ];
                }
            }
            if ($r['report_type'] === 'HF') {
                $hfDefaults = [
                    ['substance_name' => 'Fluorine', 'cas_no' => '7782-41-4', 'limit_value' => 900, 'title' => '氟(F)'],
                    ['substance_name' => 'Chlorine', 'cas_no' => '7782-50-5', 'limit_value' => 900, 'title' => '氯(Cl)'],
                    ['substance_name' => 'Bromine', 'cas_no' => '7726-95-6', 'limit_value' => 900, 'title' => '溴(Br)'],
                    ['substance_name' => 'Iodine', 'cas_no' => '7553-56-2', 'limit_value' => 900, 'title' => '碘(I)'],
                    ['substance_name' => 'Astatine', 'cas_no' => '7440-68-8', 'limit_value' => 0, 'title' => '砹(At)'],
                ];
                $existingCas = [];
                foreach ($elems as $e) {
                    if (!empty($e['cas_no'])) {
                        $existingCas[] = $e['cas_no'];
                    }
                }
                foreach ($hfDefaults as $def) {
                    if (!in_array($def['cas_no'], $existingCas)) {
                        $def['limit_unit'] = 'mg/kg';
                        $elems[] = $def;
                    }
                }
            }
            $reportElements[$r['id']] = $elems;
        }
        ApiResponse::success(['item' => $item, 'reports' => $reports, 'composition' => $composition, 'report_elements' => $reportElements]);
    }
    public function compliance() { $this->_notImpl(); }
    public function create() { $this->_notImpl(); }
    public function update() { $this->_notImpl(); }
    public function delete() { $this->_notImpl(); }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
