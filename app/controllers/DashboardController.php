<?php
class DashboardController extends Controller {
    public function index() {
        $this->requirePermission('dashboard.view');
        $db = DB::getInstance();
        $totalMaterials = $db->single("SELECT COUNT(*) FROM material WHERE valid_status='Valid'");
        $totalSuppliers = $db->single("SELECT COUNT(*) FROM supplier WHERE blacklist=0 AND status='active'");
        $compliant = $db->single("SELECT COUNT(*) FROM material WHERE env_status='compliant' AND valid_status='Valid'");
        $nonCompliant = $db->single("SELECT COUNT(*) FROM material WHERE env_status='non_compliant' AND valid_status='Valid'");
        $pending = $db->single("SELECT COUNT(*) FROM material WHERE env_status='pending' AND valid_status='Valid'");
        $overLimit = $db->single("SELECT COUNT(*) FROM element_content WHERE is_over_limit=1");
        $expiringSoon = $db->single("SELECT COUNT(*) FROM test_report WHERE audit_status='approved' AND expiry_date IS NOT NULL AND julianday(expiry_date)-julianday('now') BETWEEN 0 AND 30");
        $pendingReports = $db->single("SELECT COUNT(*) FROM test_report WHERE audit_status='pending'");
        $pendingMaterials = $db->single("SELECT COUNT(*) FROM material WHERE audit_status='S'");
        $activeWarnings = $db->single("SELECT COUNT(*) FROM warning_record WHERE solved=0");
        if (isSupplier()) {
            $sid = userSupplierId();
            $totalMaterials = $db->single("SELECT COUNT(*) FROM material WHERE supplier_id=? AND valid_status='Valid'", [$sid]);
            $compliant = $db->single("SELECT COUNT(*) FROM material WHERE supplier_id=? AND env_status='compliant' AND valid_status='Valid'", [$sid]);
            $nonCompliant = $db->single("SELECT COUNT(*) FROM material WHERE supplier_id=? AND env_status='non_compliant' AND valid_status='Valid'", [$sid]);
            $pending = $db->single("SELECT COUNT(*) FROM material WHERE supplier_id=? AND env_status='pending' AND valid_status='Valid'", [$sid]);
            $pendingReports = $db->single("SELECT COUNT(*) FROM test_report WHERE material_id IN (SELECT id FROM material WHERE supplier_id=?) AND audit_status='pending'", [$sid]);
            $pendingMaterials = 0;
            $activeWarnings = $db->single("SELECT COUNT(*) FROM warning_record WHERE material_id IN (SELECT id FROM material WHERE supplier_id=?) AND solved=0", [$sid]);
            $totalSuppliers = 0;

            // Supplier-specific todo items
            $todoPendingSubmit = $db->single("SELECT COUNT(*) FROM material WHERE supplier_id=? AND audit_status IN ('N','R') AND valid_status='Valid'", [$sid]);
            $todoExpiringReports = $db->single("SELECT COUNT(*) FROM test_report WHERE material_id IN (SELECT id FROM material WHERE supplier_id=?) AND audit_status='approved' AND expiry_date IS NOT NULL AND julianday(expiry_date)-julianday('now') BETWEEN 0 AND 30", [$sid]);
            $todoUnreadNotices = $db->single("SELECT COUNT(*) FROM notice n WHERE n.status='published' AND n.id NOT IN (SELECT notice_id FROM notice_read_log WHERE supplier_id=?)", [$sid]);
            $recentNotices = $db->query("SELECT n.*, (SELECT COUNT(*) FROM notice_read_log WHERE notice_id=n.id AND supplier_id=?) AS is_read FROM notice n WHERE n.status='published' ORDER BY n.publish_time DESC LIMIT 5", [$sid]);
        }
        $trends = $db->query("SELECT strftime('%Y-%m', created_at) AS month, SUM(CASE WHEN is_over_limit=1 THEN 1 ELSE 0 END) AS violations, COUNT(*) AS total FROM element_content WHERE created_at >= date('now','-6 months') GROUP BY month ORDER BY month");
        $months = []; $violations = []; $totals = [];
        foreach ($trends as $t) { $months[] = $t['month']; $violations[] = (int)$t['violations']; $totals[] = (int)$t['total']; }
        $warnings = $db->query("SELECT wr.*, m.material_name, ec.substance_name, ec.content_value, ec.limit_value, tr.report_no, tr.test_date FROM warning_record wr LEFT JOIN material m ON wr.material_id=m.id LEFT JOIN test_report tr ON wr.report_id=tr.id LEFT JOIN element_content ec ON ec.report_id=tr.id WHERE wr.solved=0 ORDER BY wr.created_at DESC LIMIT 10");
        $title = '仪表盘';
        $this->render('dashboard/index', get_defined_vars());
    }
}
