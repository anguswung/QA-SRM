<?php
class BpmEngine {
    public function start($templateId, $title, $refType, $refId, $initiatorId) {
        $db = DB::getInstance();
        $db->execute(
            "INSERT INTO bpm_instance (template_id, title, ref_type, ref_id, initiator_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'active', datetime('now','localtime'))",
            [$templateId, $title, $refType, $refId, $initiatorId]
        );
        $instanceId = $db->lastInsertId();
        $firstNode = $db->single(
            "SELECT * FROM bpm_node WHERE template_id = ? AND sort_order = 1", [$templateId], true
        );
        if ($firstNode) {
            $db->execute("UPDATE bpm_instance SET current_node_id = ? WHERE id = ?", [$firstNode['id'], $instanceId]);
            $this->createTasks($instanceId, $firstNode);
        }
        return $instanceId;
    }
    public function advance($instanceId, $userId, $comment = '') {
        $db = DB::getInstance();
        $instance = $db->single("SELECT * FROM bpm_instance WHERE id = ?", [$instanceId], true);
        if (!$instance || $instance['status'] !== 'active') return false;
        $db->execute(
            "UPDATE bpm_task SET status = 'processed', comment = ?, processed_at = datetime('now','localtime')
             WHERE instance_id = ? AND assignee_id = ? AND status = 'pending'",
            [$comment, $instanceId, $userId]
        );
        $pendingCount = $db->single(
            "SELECT COUNT(*) FROM bpm_task WHERE instance_id = ? AND node_id = ? AND status = 'pending'",
            [$instanceId, $instance['current_node_id']]
        );
        if ($pendingCount > 0) return true;
        $currentNode = $db->single("SELECT * FROM bpm_node WHERE id = ?", [$instance['current_node_id']], true);
        $nextNode = $db->single(
            "SELECT * FROM bpm_node WHERE template_id = ? AND sort_order = ?",
            [$instance['template_id'], $currentNode['sort_order'] + 1], true
        );
        if ($nextNode) {
            if ($nextNode['node_type'] === 'end') {
                $db->execute(
                    "UPDATE bpm_instance SET status = 'completed', completed_at = datetime('now','localtime') WHERE id = ?",
                    [$instanceId]
                );
                $this->onComplete($instance);
            } else {
                $db->execute("UPDATE bpm_instance SET current_node_id = ? WHERE id = ?", [$nextNode['id'], $instanceId]);
                $this->createTasks($instanceId, $nextNode);
            }
        }
        return true;
    }
    public function reject($instanceId, $userId, $comment = '') {
        $db = DB::getInstance();
        $instance = $db->single("SELECT * FROM bpm_instance WHERE id = ?", [$instanceId], true);
        if (!$instance) return false;
        $db->execute(
            "UPDATE bpm_task SET status = 'skipped', processed_at = datetime('now','localtime')
             WHERE instance_id = ? AND status = 'pending'",
            [$instanceId]
        );
        $firstNode = $db->single(
            "SELECT * FROM bpm_node WHERE template_id = ? AND sort_order = 1",
            [$instance['template_id']], true
        );
        $db->execute("UPDATE bpm_instance SET current_node_id = ? WHERE id = ?", [$firstNode['id'], $instanceId]);
        $db->execute(
            "INSERT INTO bpm_task (instance_id, node_id, assignee_id, status, comment, created_at)
             VALUES (?, ?, ?, 'pending', ?, datetime('now','localtime'))",
            [$instanceId, $firstNode['id'], $instance['initiator_id'], '驳回: ' . $comment]
        );
        return true;
    }
    public function urge($instanceId) {
        $tasks = DB::getInstance()->query(
            "SELECT bt.*, bi.title FROM bpm_task bt
             JOIN bpm_instance bi ON bt.instance_id = bi.id
             WHERE bt.instance_id = ? AND bt.status = 'pending'",
            [$instanceId]
        );
        return count($tasks);
    }
    private function createTasks($instanceId, $node) {
        $db = DB::getInstance();
        $users = $db->query(
            "SELECT id FROM users WHERE role = ? AND status = 'active'",
            [$node['role_required']]
        );
        foreach ($users as $user) {
            $agent = $db->single(
                "SELECT * FROM bpm_agent
                 WHERE principal_id = ? AND status = 'active'
                   AND date('now') BETWEEN start_date AND end_date
                   AND (template_ids = '*' OR template_ids LIKE '%' || ? || '%')",
                [$user['id'], $instanceId], true
            );
            $assigneeId = $agent ? $agent['agent_id'] : $user['id'];
            $db->execute(
                "INSERT INTO bpm_task (instance_id, node_id, assignee_id, status, created_at)
                 VALUES (?, ?, ?, 'pending', datetime('now','localtime'))",
                [$instanceId, $node['id'], $assigneeId]
            );
        }
    }
    private function onComplete($instance) {
        $db = DB::getInstance();
        if ($instance['ref_type'] === 'material') {
            $db->execute("UPDATE material SET audit_status = 'A', env_status = 'compliant' WHERE id = ?",
                [$instance['ref_id']]);
        }
    }
}
