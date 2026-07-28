<?php
class RuleEngine {
    private $db;
    public function __construct() { $this->db = DB::getInstance(); }
    
    public function checkMaterial($materialId) {
        $material = $this->db->single("SELECT m.*, s.name AS supplier_name FROM material m LEFT JOIN supplier s ON m.supplier_id=s.id WHERE m.id=?", [$materialId], true);
        if (!$material) return ['pass'=>true, 'mandatory'=>[], 'warnings'=>[]];
        return $this->runChecks($material, 'material');
    }
    
    public function checkHm($hmId) {
        $hm = $this->db->single("SELECT * FROM homogeneous_material WHERE id=?", [$hmId], true);
        if (!$hm) return ['pass'=>true, 'mandatory'=>[], 'warnings'=>[]];
        return $this->runChecks($hm, 'hm');
    }
    
    public function checkSupplierAll($supplierId) {
        $results = ['pass'=>true, 'mandatory'=>[], 'warnings'=>[], 'overall'=>'ok'];
        $materials = $this->db->query("SELECT * FROM material WHERE supplier_id=?", [$supplierId]);
        foreach ($materials as $m) {
            $r = $this->runChecks($m, 'material');
            foreach ($r['mandatory'] as $v) $results['mandatory'][] = $v;
            foreach ($r['warnings'] as $v) $results['warnings'][] = $v;
            if (!empty($r['mandatory'])) $results['pass'] = false;
        }
        $hms = $this->db->query("SELECT * FROM homogeneous_material WHERE supplier_id=?", [$supplierId]);
        foreach ($hms as $h) {
            $r = $this->runChecks($h, 'hm');
            foreach ($r['mandatory'] as $v) $results['mandatory'][] = $v;
            foreach ($r['warnings'] as $v) $results['warnings'][] = $v;
            if (!empty($r['mandatory'])) $results['pass'] = false;
        }
        if (!$results['pass']) $results['overall'] = 'blocked';
        elseif (!empty($results['warnings'])) $results['overall'] = 'warning';
        return $results;
    }
    
    private function runChecks($item, $type) {
        $rules = $this->db->query("SELECT * FROM compliance_rule WHERE enabled=1 ORDER BY sort_order");
        $mandatory = []; $warnings = [];
        foreach ($rules as $rule) {
            if (!$this->matchesRule($item, $type, $rule)) continue;
            // Check if required document is uploaded
            $hasDoc = $this->checkDocument($item, $type, $rule['doc_category_code']);
            if (!$hasDoc) {
                $msg = $rule['warning_message'];
                if ($rule['rule_type'] === 'mandatory') $mandatory[] = $msg;
                else $warnings[] = $msg;
            }
        }
        return ['pass'=>empty($mandatory), 'mandatory'=>$mandatory, 'warnings'=>$warnings];
    }
    
    private function matchesRule($item, $type, $rule) {
        $mt = $rule['match_type'];
        if ($mt === 'all') return true;
        if ($mt === 'hm' && $type === 'hm') return true;
        if ($mt === 'hm' && isset($item['spec']) && $item['spec'] === '均质原材料') return true;
        if ($mt === 'hf') {
            $env = $item['env_status'] ?? '';
            return $env === 'halogen_free'; // Simplified
        }
        if ($mt === 'glue') {
            $cat = $item['category'] ?? '';
            return mb_stripos($cat, '胶') !== false || mb_stripos($cat, '油墨') !== false || mb_stripos($cat, '清洗') !== false;
        }
        if ($mt === 'battery') {
            $cat = $item['category'] ?? '';
            return mb_stripos($cat, '电池') !== false || mb_stripos($cat, '电芯') !== false;
        }
        if ($mt === 'packaging') {
            $cat = $item['category'] ?? '';
            return mb_stripos($cat, '包装') !== false;
        }
        if ($mt === 'confidential') return false; // Would need a flag on composition
        if ($mt === 'f_over_50') return false; // Would need F content value
        return false;
    }
    
    private function checkDocument($item, $type, $docCode) {
        $id = $item['id'];
        if ($type === 'hm') {
            return (bool)$this->db->single("SELECT COUNT(*) FROM hm_report hr JOIN document_category dc ON 1=1 WHERE hr.hm_id=? AND dc.code=? LIMIT 1", [$id, $docCode]);
        } else {
            return (bool)$this->db->single("SELECT COUNT(*) FROM test_report tr WHERE tr.material_id=?", [$id]);
        }
    }
}
