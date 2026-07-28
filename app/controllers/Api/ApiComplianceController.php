<?php
class ApiComplianceController extends ApiBaseController {
    public function index() {
        $complianceRate = $this->db->single("SELECT ROUND(SUM(CASE WHEN env_status='compliant' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) FROM material WHERE env_status IS NOT NULL");
        $total = (int)$this->db->single("SELECT COUNT(*) FROM material");
        $compliant = (int)$this->db->single("SELECT COUNT(*) FROM material WHERE env_status='compliant'");
        $nonCompliant = (int)$this->db->single("SELECT COUNT(*) FROM material WHERE env_status='non_compliant'");
        $pending = (int)$this->db->single("SELECT COUNT(*) FROM material WHERE env_status IS NULL OR env_status='pending'");
        $bySupplier = $this->db->query("SELECT s.id, s.name_short, COUNT(m.id) AS total, SUM(CASE WHEN m.env_status='compliant' THEN 1 ELSE 0 END) AS compliant, SUM(CASE WHEN m.env_status='non_compliant' THEN 1 ELSE 0 END) AS non_compliant FROM supplier s LEFT JOIN material m ON m.supplier_id=s.id GROUP BY s.id ORDER BY total DESC");
        ApiResponse::success(['total' => $total, 'compliant' => $compliant, 'non_compliant' => $nonCompliant, 'pending' => $pending, 'compliance_rate' => $complianceRate ?: 0, 'by_supplier' => $bySupplier]);
    }
    public function detail() {
        $materialId = (int)ApiRequest::get('material_id', 0);
        if (!$materialId) ApiResponse::error('material_id 不能为空');
        $material = $this->db->single("SELECT m.*, s.name_short AS supplier_name FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id WHERE m.id=?", [$materialId], true);
        if (!$material) ApiResponse::notFound();
        $records = $this->db->query("SELECT ar.*, rl.name AS regulation_name, rl.regulation_no FROM analysis_record ar LEFT JOIN regulation_lib rl ON ar.regulation_id=rl.id WHERE ar.material_id=? ORDER BY ar.analyzed_at DESC", [$materialId]);
        ApiResponse::success(['material' => $material, 'records' => $records]);
    }
    public function analyze() {
        $materialId = (int)ApiRequest::get('material_id', 0);
        $regulationId = (int)ApiRequest::get('regulation_id', 0) ?: null;
        if (!$materialId) ApiResponse::error('material_id 不能为空');
        $engine = new ComplianceEngine();
        $result = $engine->analyze($materialId, $regulationId);
        ApiResponse::success($result ?: []);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
