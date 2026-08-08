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
        if (($_GET['action'] ?? '') === 'data') { $this->materialData($where, $params); return; }
        $suppliers = $db->query("SELECT id, name FROM supplier ORDER BY name");
        $title = '物料查询';
        $this->render('query/material', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data）：物料查询 */
    private function materialData($where, $params) {
        $this->requirePermission('query.view');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $whereSql = $where ? " WHERE " . implode(' AND ', $where) : '';
        $total = (int)$db->single("SELECT COUNT(*) FROM material m" . $whereSql, $params);
        $offset = ($page - 1) * $perPage;
        $rows = $db->query("SELECT m.*, s.name AS supplier_name, (SELECT COUNT(*) FROM test_report WHERE material_id=m.id) AS report_count FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id" . $whereSql . " ORDER BY m.id DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        $html = '';
        foreach ($rows as $m) {
            $html .= '<tr>';
            $html .= colId($m['id'] ?? '') . '<td>' . h($m['material_no']) . '</td>';
            $html .= '<td class="text-start"><a href="/material?action=detail&id=' . $m['id'] . '" class="fw-bold">' . h($m['material_name']) . '</a></td>';
            $html .= '<td>' . h($m['supplier_name'] ?? '-') . '</td>';
            $html .= '<td>' . statusBadge($m['env_status']) . '</td>';
            $html .= '<td>' . statusBadge($m['audit_status']) . '</td>';
            $html .= '<td><span class="badge badge-soft-info">' . (int)$m['report_count'] . '</span></td>';
            $html .= '</tr>';
        }
        $this->paginationResponse($total, $html);
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
        if (($_GET['action'] ?? '') === 'data') { $this->expiredData($where, $params); return; }
        $title = '到期查询';
        $this->render('query/expired', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data）：到期查询 */
    private function expiredData($where, $params) {
        $this->requirePermission('query.view');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $whereSql = " WHERE " . implode(' AND ', $where);
        $total = (int)$db->single("SELECT COUNT(*) FROM test_report tr JOIN material m ON tr.material_id=m.id" . $whereSql, $params);
        $offset = ($page - 1) * $perPage;
        $rows = $db->query("SELECT tr.*, m.material_name, m.material_no, s.name AS supplier_name, julianday(tr.expiry_date)-julianday('now') AS days_left FROM test_report tr JOIN material m ON tr.material_id=m.id LEFT JOIN supplier s ON m.supplier_id=s.id " . $whereSql . " ORDER BY tr.expiry_date LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        $html = '';
        foreach ($rows as $r) {
            $days = (int)round($r['days_left'] ?? 0);
            $badge = $days < 0 ? 'danger' : ($days <= 30 ? 'warning' : 'success');
            $html .= '<tr>';
            $html .= colId($r['id'] ?? '') . '<td>' . h($r['report_no']) . '</td>';
            $html .= '<td>' . h($r['material_no'] ?? '-') . '</td>';
            $html .= '<td class="text-start">' . h($r['material_name'] ?? '-') . '</td>';
            $html .= '<td>' . h($r['supplier_name'] ?? '-') . '</td>';
            $html .= '<td>' . h($r['expiry_date']) . '</td>';
            $html .= '<td><span class="badge bg-' . $badge . '">' . ($days < 0 ? '已过期' : ($days === 0 ? '今天' : $days . ' 天')) . '</span></td>';
            $html .= '</tr>';
        }
        $this->paginationResponse($total, $html);
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
        if (($_GET['action'] ?? '') === 'data') { $this->historyData($where, $params); return; }
        $mWhere = !isAdmin() ? " WHERE user_id=" . userId() . " AND module IS NOT NULL" : " WHERE module IS NOT NULL";
        $modules = $db->query("SELECT DISTINCT module FROM operation_log" . $mWhere . " ORDER BY module");
        $title = '变更历史';
        $this->render('query/history', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data）：变更历史 */
    private function historyData($where, $params) {
        $this->requirePermission('query.view');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $whereSql = $where ? " WHERE " . implode(' AND ', $where) : '';
        $total = (int)$db->single("SELECT COUNT(*) FROM operation_log ol" . $whereSql, $params);
        $offset = ($page - 1) * $perPage;
        $rows = $db->query("SELECT ol.*, u.realname FROM operation_log ol LEFT JOIN users u ON ol.user_id=u.id" . $whereSql . " ORDER BY ol.created_at DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
        $html = '';
        foreach ($rows as $l) {
            $html .= '<tr>';
            $html .= colId($l['id'] ?? '') . '<td><td><small>' . h($l['created_at']) . '</small></td>';
            $html .= '<td>' . h($l['realname'] ?? $l['user_id']) . '</td>';
            $html .= '<td>' . h($l['module']) . '</td>';
            $html .= '<td>' . h($l['action']) . '</td>';
            $html .= '<td>' . h($l['target_type'] ?? '-') . ' ' . h($l['target_id'] ?? '') . '</td>';
            $html .= '<td><small>' . h($l['detail']) . '</small></td>';
            $html .= '<td><small>' . h($l['ip_address']) . '</small></td>';
            $html .= '</tr>';
        }
        $this->paginationResponse($total, $html);
    }
}
