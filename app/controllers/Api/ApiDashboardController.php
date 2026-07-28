<?php
class ApiDashboardController extends ApiBaseController {
    public function stats() {
        $sid = $this->isSupplier() ? $this->supplierId() : 0;

        if ($sid) {
            $total = $this->db->single("SELECT COUNT(*) FROM material m WHERE m.supplier_id=?", [$sid]);
            $compliant = $this->db->single("SELECT COUNT(*) FROM material m WHERE m.supplier_id=? AND m.env_status='compliant'", [$sid]);
            $nonCompliant = $this->db->single("SELECT COUNT(*) FROM material m WHERE m.supplier_id=? AND m.env_status='non_compliant'", [$sid]);
            $pending = $this->db->single("SELECT COUNT(*) FROM material m WHERE m.supplier_id=? AND (m.env_status IS NULL OR m.env_status='pending')", [$sid]);
            $hmTotal = $this->db->single("SELECT COUNT(*) FROM homogeneous_material WHERE supplier_id=?", [$sid]);
        } else {
            $total = $this->db->single("SELECT COUNT(*) FROM material");
            $compliant = $this->db->single("SELECT COUNT(*) FROM material WHERE env_status='compliant'");
            $nonCompliant = $this->db->single("SELECT COUNT(*) FROM material WHERE env_status='non_compliant'");
            $pending = $this->db->single("SELECT COUNT(*) FROM material WHERE env_status IS NULL OR env_status='pending'");
            $hmTotal = $this->db->single("SELECT COUNT(*) FROM homogeneous_material");
        }

        $supplierCount = $this->db->single("SELECT COUNT(*) FROM supplier");
        $warnings = $this->db->query("SELECT wr.*, m.material_name FROM warning_record wr LEFT JOIN material m ON wr.material_id=m.id ORDER BY wr.created_at DESC LIMIT 10");

        ApiResponse::success([
            'material_total' => (int)$total,
            'material_compliant' => (int)$compliant,
            'material_non_compliant' => (int)$nonCompliant,
            'material_pending' => (int)$pending,
            'hm_total' => (int)$hmTotal,
            'supplier_total' => (int)$supplierCount,
            'recent_warnings' => $warnings,
        ]);
    }
}
