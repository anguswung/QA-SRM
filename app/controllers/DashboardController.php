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
            $recentNotices = $db->query("SELECT n.*, (SELECT COUNT(*) FROM notice_attachment WHERE notice_id=n.id) AS attach_count,
                (SELECT COUNT(*) FROM notice_read_log WHERE notice_id=n.id AND supplier_id=?) AS is_read
                FROM notice n WHERE n.status='published' ORDER BY n.publish_time DESC LIMIT 5", [$sid]);

            // Survey todo and list for supplier
            $todoUnfilledSurveys = $db->single("SELECT COUNT(*) FROM survey s WHERE s.status='active' AND (s.expiration_date IS NULL OR julianday(s.expiration_date) >= julianday('now')) AND NOT EXISTS (SELECT 1 FROM survey_response sr WHERE sr.survey_id=s.id AND sr.supplier_id=? AND sr.submit_status!='0')", [$sid]);
            $recentSurveys = $db->query("SELECT s.*, (SELECT COUNT(*) FROM survey_question WHERE survey_id=s.id) AS question_count,
                (SELECT submit_status FROM survey_response WHERE survey_id=s.id AND supplier_id=?) AS my_submit_status
                FROM survey s WHERE s.status='active' AND (s.expiration_date IS NULL OR julianday(s.expiration_date) >= julianday('now')) ORDER BY s.created_at DESC LIMIT 5", [$sid]);
        }
        $warnings = isSupplier()
            ? $db->query("SELECT wr.*, m.material_name, tr.report_no, tr.test_date FROM warning_record wr LEFT JOIN material m ON wr.material_id=m.id LEFT JOIN test_report tr ON wr.report_id=tr.id WHERE wr.solved=0 AND wr.material_id IN (SELECT id FROM material WHERE supplier_id=?) ORDER BY wr.created_at DESC LIMIT 10", [userSupplierId()])
            : $db->query("SELECT wr.*, m.material_name, tr.report_no, tr.test_date FROM warning_record wr LEFT JOIN material m ON wr.material_id=m.id LEFT JOIN test_report tr ON wr.report_id=tr.id WHERE wr.solved=0 ORDER BY wr.created_at DESC LIMIT 10");
        $title = '仪表盘';
        $this->render('dashboard/index', get_defined_vars());
    }
}
