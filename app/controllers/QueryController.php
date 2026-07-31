<?php
class QueryController extends Controller {
    public function material() {
        $this->requirePermission('query.view');
        $db = DB::getInstance();
        $search = $_GET['search'] ?? '';
        $env = $_GET['env_status'] ?? '';
        $audit = $_GET['audit_status'] ?? '';
        $supplierId = (int)($_GET['supplier_id'] ?? 0);
        $where = []; $params = [];
        if (isSupplier()) { $where[] = "m.supplier_id=?"; $params[] = userSupplierId(); }
        if ($search) { $where[] = "(m.material_no LIKE ? OR m.material_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($env) { $where[] = "m.env_status=?"; $params[] = $env; }
        if ($audit) { $where[] = "m.audit_status=?"; $params[] = $audit; }
        if ($supplierId) { $where[] = "m.supplier_id=?"; $params[] = $supplierId; }
        $materials = $db->query("SELECT m.*, s.name AS supplier_name, (SELECT COUNT(*) FROM test_report WHERE material_id=m.id) AS report_count FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id" . ($where ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY m.id DESC LIMIT 200", $params);
        $suppliers = $db->query("SELECT id, name FROM supplier ORDER BY name");
        $title = '物料查询';
        $this->render('query/material', get_defined_vars());
    }
    public function expired() {
        $this->requirePermission('query.view');
        $db = DB::getInstance();
        $range = $_GET['range'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $where = ["tr.audit_status='approved'", "tr.expiry_date IS NOT NULL"];
        $params = [];
        if ($range === 'expired') { $where[] = "tr.expiry_date < date('now')"; }
        elseif ($range === '30days') { $where[] = "julianday(tr.expiry_date)-julianday('now') BETWEEN 0 AND 30"; }
        elseif ($range === '60days') { $where[] = "julianday(tr.expiry_date)-julianday('now') BETWEEN 0 AND 60"; }
        elseif ($range === 'valid') { $where[] = "tr.expiry_date >= date('now')"; }
        if ($startDate) { $where[] = "tr.expiry_date >= ?"; $params[] = $startDate; }
        if ($endDate) { $where[] = "tr.expiry_date <= ?"; $params[] = $endDate; }
        if (isSupplier()) { $where[] = "m.supplier_id=?"; $params[] = userSupplierId(); }
        $reports = $db->query("SELECT tr.*, m.material_name, m.material_no, s.name AS supplier_name, julianday(tr.expiry_date)-julianday('now') AS days_left FROM test_report tr JOIN material m ON tr.material_id=m.id LEFT JOIN supplier s ON m.supplier_id=s.id WHERE " . implode(' AND ', $where) . " ORDER BY tr.expiry_date", $params);
        $title = '到期查询';
        $this->render('query/expired', get_defined_vars());
    }
    public function history() {
        $this->requirePermission('query.view');
        $db = DB::getInstance();
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $module = $_GET['module'] ?? '';
        $action = $_GET['action'] ?? '';
        $where = []; $params = [];
        if (!isAdmin()) { $where[] = "ol.user_id=?"; $params[] = userId(); }
        if ($startDate) { $where[] = "ol.created_at >= ?"; $params[] = $startDate; }
        if ($endDate) { $where[] = "ol.created_at <= ?"; $params[] = $endDate . ' 23:59:59'; }
        if ($module) { $where[] = "ol.module=?"; $params[] = $module; }
        if ($action) { $where[] = "ol.action=?"; $params[] = $action; }
        $logs = $db->query("SELECT ol.*, u.realname FROM operation_log ol LEFT JOIN users u ON ol.user_id=u.id" . ($where ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY ol.created_at DESC LIMIT 200", $params);
        $mWhere = !isAdmin() ? " WHERE user_id=" . userId() . " AND module IS NOT NULL" : " WHERE module IS NOT NULL";
        $modules = $db->query("SELECT DISTINCT module FROM operation_log" . $mWhere . " ORDER BY module");
        $title = '变更历史';
        $this->render('query/history', get_defined_vars());
    }
}
