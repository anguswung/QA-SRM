<?php
class ComplianceEngine {
    public function analyze($materialId, $regulationId = null) {
        $db = DB::getInstance();
        $elements = $db->query(
            "SELECT ec.*, tr.material_id FROM element_content ec
             JOIN test_report tr ON ec.report_id = tr.id
             WHERE tr.material_id = ? AND tr.audit_status IN ('approved','pending')",
            [$materialId]
        );
        // If no material-level detected elements, descend into composition HMs
        if (empty($elements)) {
            $composition = $db->query("SELECT mc.substance_id FROM material_composition mc WHERE mc.material_id=?", [$materialId]);
            foreach ($composition as $c) {
                $hmId = (int)$c['substance_id'];
                if (!$hmId) continue;
                // Run HM analysis if not already done
                $existing = $db->single("SELECT COUNT(*) FROM analysis_record WHERE hm_id=?", [$hmId]);
                if (!$existing) {
                    $this->analyzeHm($hmId, $regulationId);
                }
                // Copy HM analysis records as material-level records
                $hmRecords = $db->query("SELECT * FROM analysis_record WHERE hm_id=?", [$hmId]);
                foreach ($hmRecords as $hr) {
                    $dup = $db->single("SELECT COUNT(*) FROM analysis_record WHERE material_id=? AND regulation_id=?", [$materialId, $hr['regulation_id']]);
                    if (!$dup) {
                        $db->execute(
                            "INSERT INTO analysis_record (material_id, regulation_id, analyze_result, substance_name, cas_no, detected_value, limit_value, analyzed_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now','localtime'))",
                            [$materialId, $hr['regulation_id'], $hr['analyze_result'],
                             $hr['substance_name'], $hr['cas_no'], $hr['detected_value'], $hr['limit_value']]
                        );
                    }
                }
            }
            // Re-fetch material-level analysis after HM copy
            $sql = "SELECT rc.* FROM regulation_content rc
                    JOIN regulation_lib rl ON rc.regulation_id = rl.id
                    WHERE rl.status = 'published'";
            $params = [];
            if ($regulationId) { $sql .= " AND rl.id = ?"; $params[] = $regulationId; }
            $limits = $db->query($sql, $params);
            // Calculate material-level results from HM-derived analysis records
            $matRecords = $db->query("SELECT * FROM analysis_record WHERE material_id=?", [$materialId]);
            $results = [];
            foreach ($matRecords as $ar) {
                $results[] = [
                    'substance' => $ar['substance_name'],
                    'cas' => $ar['cas_no'],
                    'detected' => $ar['detected_value'],
                    'limit' => $ar['limit_value'],
                    'result' => $ar['analyze_result'],
                    'result_text' => $this->resultText($ar['analyze_result']),
                ];
            }
            $hasViolation = !empty(array_filter($results, fn($r) => $r['result'] == 2));
            $db->execute("UPDATE material SET env_status = ? WHERE id = ?",
                [$hasViolation ? 'non_compliant' : 'compliant', $materialId]);
            return $results;
        }
        $sql = "SELECT rc.* FROM regulation_content rc
                JOIN regulation_lib rl ON rc.regulation_id = rl.id
                WHERE rl.status = 'published'";
        $params = [];
        if ($regulationId) { $sql .= " AND rl.id = ?"; $params[] = $regulationId; }
        $limits = $db->query($sql, $params);
        $db->execute("DELETE FROM analysis_record WHERE material_id=? AND (?=0 OR regulation_id=?)", [$materialId, $regulationId ?? 0, $regulationId ?? 0]);
        $results = [];
        $exemptions = $db->query(
            "SELECT er.*, s.cas_no AS ex_cas_no FROM exemption_record er
             LEFT JOIN substance s ON er.substance_id=s.id
             WHERE er.material_id=?", [$materialId]
        );
        $exemptionMap = [];
        foreach ($exemptions as $ex) {
            $k = strtoupper($ex['ex_cas_no'] ?? '') . '_' . $ex['regulation_id'];
            $exemptionMap[$k] = $ex;
        }
        foreach ($elements as $elem) {
            foreach ($limits as $limit) {
                if (!$this->matchSubstance($elem, $limit)) continue;
                $exKey = strtoupper($elem['cas_no'] ?? '') . '_' . $limit['regulation_id'];
                $exemption = $exemptionMap[$exKey] ?? null;
                $result = $this->judge($elem, $limit, $exemption);
                $db->execute(
                    "INSERT INTO analysis_record (material_id, regulation_id, analyze_result, substance_name, cas_no, detected_value, limit_value, analyzed_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now','localtime'))",
                    [$materialId, $limit['regulation_id'], $result, $elem['substance_name'], $elem['cas_no'], $elem['content_value'], $limit['limit_value']]
                );
                $results[] = [
                    'substance' => $elem['substance_name'],
                    'cas' => $elem['cas_no'],
                    'detected' => $elem['content_value'],
                    'limit' => $limit['limit_value'],
                    'result' => $result,
                    'result_text' => $this->resultText($result),
                ];
            }
        }
        $hasViolation = !empty(array_filter($results, fn($r) => $r['result'] === 2));
        $db->execute("UPDATE material SET env_status = ? WHERE id = ?",
            [$hasViolation ? 'non_compliant' : 'compliant', $materialId]);
        return $results;
    }
    public function analyzeHm($hmId, $regulationId = null) {
        $db = DB::getInstance();
        // Get actual test results from reports (hm_report_element), fallback to composition
        $elements = $db->query(
            "SELECT hre.substance_name, hre.cas_no,
                    MAX(hre.content_value) AS content_value,
                    MAX(hre.limit_value) AS limit_value,
                    hre.unit, hr.hm_id AS material_id
             FROM hm_report_element hre
             JOIN hm_report hr ON hre.hm_report_id = hr.id
             WHERE hr.hm_id = ? AND hr.status = 'current'
             GROUP BY hre.substance_name, hre.cas_no",
            [$hmId]
        );
        if (empty($elements)) {
            $elements = $db->query(
                "SELECT hc.id, hc.substance_name, hc.cas_no, hc.percentage AS content_value,
                        NULL AS limit_value, 'mg/kg' AS unit, hc.hm_id AS material_id
                 FROM hm_composition hc
                 WHERE hc.hm_id = ?",
                [$hmId]
            );
        }
        $sql = "SELECT rc.* FROM regulation_content rc
                JOIN regulation_lib rl ON rc.regulation_id = rl.id
                WHERE rl.status = 'published'";
        $params = [];
        if ($regulationId) { $sql .= " AND rl.id = ?"; $params[] = $regulationId; }
        $limits = $db->query($sql, $params);
        $db->execute("DELETE FROM analysis_record WHERE hm_id=? AND (?=0 OR regulation_id=?)", [$hmId, $regulationId ?? 0, $regulationId ?? 0]);
        $results = [];
        foreach ($elements as $elem) {
            $matched = false;
            // 优先使用手工录入限值（hm_report_element.limit_value）
            if (isset($elem['limit_value']) && (float)$elem['limit_value'] > 0) {
                $result = $this->judge($elem, ['limit_value' => $elem['limit_value']], null);
                $db->execute(
                    "INSERT INTO analysis_record (hm_id, regulation_id, analyze_result, substance_name, cas_no, detected_value, limit_value, analyzed_at)
                     VALUES (?, 0, ?, ?, ?, ?, ?, datetime('now','localtime'))",
                    [$hmId, $result, $elem['substance_name'], $elem['cas_no'], $elem['content_value'], $elem['limit_value']]
                );
                $results[] = [
                    'substance' => $elem['substance_name'],
                    'cas' => $elem['cas_no'],
                    'detected' => $elem['content_value'],
                    'limit' => $elem['limit_value'],
                    'result' => $result,
                    'result_text' => $this->resultText($result),
                    'analyzed_at' => date('Y-m-d H:i:s'),
                ];
                $matched = true;
            }
            if ($matched) continue;
            // 回退：法规库限值匹配
            foreach ($limits as $limit) {
                if (!$this->matchSubstance($elem, $limit)) continue;
                $result = $this->judge($elem, $limit, null);
                $db->execute(
                    "INSERT INTO analysis_record (hm_id, regulation_id, analyze_result, substance_name, cas_no, detected_value, limit_value, analyzed_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now','localtime'))",
                    [$hmId, $limit['regulation_id'], $result, $elem['substance_name'], $elem['cas_no'], $elem['content_value'], $limit['limit_value']]
                );
                $results[] = [
                    'substance' => $elem['substance_name'],
                    'cas' => $elem['cas_no'],
                    'detected' => $elem['content_value'],
                    'limit' => $limit['limit_value'],
                    'result' => $result,
                    'result_text' => $this->resultText($result),
                    'analyzed_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
        $hasViolation = !empty(array_filter($results, fn($r) => $r['result'] === 2));
        $db->execute("UPDATE homogeneous_material SET env_status = ? WHERE id = ?",
            [$hasViolation ? 'non_compliant' : 'compliant', $hmId]);
        return $results;
    }
    private function matchSubstance($element, $limit) {
        if (!empty($element['cas_no']) && !empty($limit['cas_no'])) {
            return strtoupper($element['cas_no']) === strtoupper($limit['cas_no']);
        }
        if (!empty($element['substance_name']) && !empty($limit['substance_name'])) {
            return trim($element['substance_name']) === trim($limit['substance_name']);
        }
        return false;
    }
    private function judge($elem, $limit, $exemption) {
        if ($exemption && $exemption['is_limit']) return 1;
        $detected = (float)$elem['content_value'];
        $limitVal = (float)$limit['limit_value'];
        if ($detected <= 0 && $limitVal <= 0) return 0;
        if ($limitVal <= 0) return 0;
        if ($detected <= $limitVal) return 1;
        return 2;
    }
    public function resultText($code) {
        $map = [1 => '合规', 2 => '违规', 0 => '不确定', 3 => '错误', 9 => '定性-合规', 10 => '定性-违规'];
        return $map[$code] ?? '未知';
    }
    public function setManualResult($materialId, $regulationId, $manualResult, $remark = '') {
        $db = DB::getInstance();
        $resultCode = $manualResult === 'compliant' ? 9 : 10;
        $db->execute(
            "INSERT INTO analysis_record (material_id, regulation_id, analyze_result, is_manual, manual_result, remark, analyzed_at)
             VALUES (?, ?, ?, 1, ?, ?, datetime('now','localtime'))",
            [$materialId, $regulationId, $resultCode, $resultCode, $remark]
        );
    }
}
