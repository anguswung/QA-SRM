<?php
class WarningEngine {
    public function generateWarnings() {
        $db = DB::getInstance();
        $config = require __DIR__ . '/../../config/app.php';
        $thresholds = $config['warning'];
        $warnings = [];
        $expiringReports = $db->query(
            "SELECT tr.*, m.material_name, s.name AS supplier_name
             FROM test_report tr
             JOIN material m ON tr.material_id = m.id
             JOIN supplier s ON m.supplier_id = s.id
             WHERE tr.audit_status = 'approved'
               AND tr.expiry_date IS NOT NULL
               AND julianday(tr.expiry_date) - julianday('now') <= ?",
            [$thresholds['yellow']]
        );
        foreach ($expiringReports as $r) {
            $daysLeft = $this->daysUntil($r['expiry_date']);
            $level = $daysLeft <= $thresholds['red'] ? 'red'
                   : ($daysLeft <= $thresholds['orange'] ? 'orange' : 'yellow');
            $existing = $db->single(
                "SELECT id FROM warning_record WHERE report_id=? AND warning_type='report_expiring' AND solved=0",
                [$r['id']], true
            );
            if (!$existing) {
                $db->execute(
                    "INSERT INTO warning_record (material_id, report_id, warning_type, warning_level, warning_desc, days_left, created_at)
                     VALUES (?, ?, 'report_expiring', ?, ?, ?, datetime('now','localtime'))",
                    [$r['material_id'], $r['id'], $level,
                     "{$r['material_name']} 的检测报告 {$r['report_no']} 将于 {$r['expiry_date']} 到期 ({$daysLeft}天)",
                     $daysLeft]
                );
                $warnings[] = $r['id'];
            }
        }
        return $warnings;
    }
    private function daysUntil($date) {
        return (int)((strtotime($date) - time()) / 86400);
    }
    public function resolve($id) {
        $db = DB::getInstance();
        $db->execute("UPDATE warning_record SET solved=1, status='resolved', closed_at=datetime('now','localtime') WHERE id=?", [$id]);
    }
}
