<?php
class NoticeController extends Controller {
    public function index() {
        $this->requirePermission('notice.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'read') {
                $nid = (int)($_POST['id'] ?? 0);
                $sid = userSupplierId();
                if ($nid && $sid > 0) {
                    $exists = $db->single("SELECT COUNT(*) FROM notice_read_log WHERE notice_id=? AND supplier_id=? AND read_time IS NOT NULL", [$nid, $sid]);
                    if (!$exists) {
                        $db->execute("INSERT INTO notice_read_log (notice_id, supplier_id) VALUES (?,?)", [$nid, $sid]);
                    }
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true]); exit;
            }
            $this->requirePermission('notice.edit');
            if (!isAdmin()) { flash('error', '无权操作'); header('Location: /notice'); exit; }
            if ($action === 'create') {
                $db->execute("INSERT INTO notice (title, content, publisher_id, publisher_name, publish_time) VALUES (?,?,?,?,datetime('now','localtime'))",
                    [$_POST['title'] ?? '', $_POST['content'] ?? '', userId(), $_SESSION['realname'] ?? '']);
                $nid = $db->lastInsertId();
                if (!empty($_FILES['attachment']['name'])) {
                    $f = $_FILES['attachment'];
                    $dir = uploadPath('notice');
                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    $name = pathinfo($f['name'], PATHINFO_FILENAME) . '_' . date('Ymd') . '.' . $ext;
                    move_uploaded_file($f['tmp_name'], $dir . '/' . $name);
                    $db->execute("INSERT INTO notice_attachment (notice_id, file_name, file_path, file_size, upload_user) VALUES (?,?,?,?,?)",
                        [$nid, $f['name'], $dir . '/' . $name, $f['size'], $_SESSION['realname'] ?? '']);
                }
                logOp('create', 'notice', $nid, '发布通知: ' . ($_POST['title'] ?? ''));
                flash('success', '通知已发布');
            }
            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    $db->execute("UPDATE notice SET title=?, content=?, publish_time=datetime('now','localtime') WHERE id=?",
                        [$_POST['title'] ?? '', $_POST['content'] ?? '', $id]);
                    $db->execute("DELETE FROM notice_attachment WHERE notice_id=?", [$id]);
                    if (!empty($_FILES['attachment']['name'])) {
                        $f = $_FILES['attachment'];
                        $dir = uploadPath('notice');
                        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                        $name = pathinfo($f['name'], PATHINFO_FILENAME) . '_' . date('Ymd') . '.' . $ext;
                        move_uploaded_file($f['tmp_name'], $dir . '/' . $name);
                        $db->execute("INSERT INTO notice_attachment (notice_id, file_name, file_path, file_size, upload_user) VALUES (?,?,?,?,?)",
                            [$id, $f['name'], $dir . '/' . $name, $f['size'], $_SESSION['realname'] ?? '']);
                    }
                    logOp('update', 'notice', $id, '编辑通知: ' . ($_POST['title'] ?? ''));
                    flash('success', '通知已更新');
                }
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    $atts = $db->query("SELECT * FROM notice_attachment WHERE notice_id=?", [$id]);
                    foreach ($atts as $a) { if ($a['file_path'] && file_exists($a['file_path'])) unlink($a['file_path']); }
                    $db->execute("DELETE FROM notice_attachment WHERE notice_id=?", [$id]);
                    $db->execute("DELETE FROM notice WHERE id=?", [$id]);
                    $db->execute("DELETE FROM notice_read_log WHERE notice_id=?", [$id]);
                    flash('success', '通知已删除');
                }
            }
            header('Location: /notice'); exit;
        }
        $where = []; $params = [];
        $action = $_GET['action'] ?? '';
        if ($action === 'data') { $this->data(); return; }
        if (isSupplier()) {
            $where[] = "n.status='published'";
        }
        $title = '通知公告';
        $this->render('notice/index', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data）：返回 JSON { total, html } */
    private function data() {
        $this->requirePermission('notice.view');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $where = []; $params = [];
        if (isSupplier()) { $where[] = "n.status='published'"; }
        $whereSql = $where ? " WHERE " . implode(' AND ', $where) : '';
        $total = (int)$db->single("SELECT COUNT(*) FROM notice n" . $whereSql, $params);
        $offset = ($page - 1) * $perPage;
        $rows = $db->query(
            "SELECT n.*, (SELECT COUNT(*) FROM notice_read_log WHERE notice_id=n.id AND supplier_id=?) AS is_read FROM notice n" . $whereSql . " ORDER BY n.created_at DESC LIMIT ? OFFSET ?",
            array_merge([userSupplierId()], $params, [$perPage, $offset])
        );
        $ids = array_column($rows, 'id');
        $atts = !empty($ids) ? $db->query("SELECT * FROM notice_attachment WHERE notice_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")", $ids) : [];
        $attByNotice = [];
        foreach ($atts as $a) { $attByNotice[$a['notice_id']][] = $a; }
        $html = '';
        foreach ($rows as $n) { $html .= $this->noticeRowHtml($n, $attByNotice[$n['id']] ?? []); }
        $this->paginationResponse($total, $html);
    }

    private function noticeRowHtml($n, $attachments = []): string {
        $hasAttach = !empty($attachments);
        $hasImage = preg_match('/<img\b/i', (string)($n['content'] ?? '')) ? true : false;
        $titleJson = htmlspecialchars(json_encode($n['title'] ?? ''), ENT_QUOTES);
        $contentJson = htmlspecialchars(json_encode($n['content'] ?? ''), ENT_QUOTES);
        $attJson = htmlspecialchars(json_encode($attachments), ENT_QUOTES);
        $html = '<tr>';
        $html .= colId($n['id'] ?? '');
        $html .= '<td class="text-start"><a href="javascript:;" onclick="showNotice(' . (int)$n['id'] . ',' . $titleJson . ',' . $contentJson . ',\'' . h($n['publisher_name']) . '\',\'' . h($n['publish_time'] ?: $n['created_at']) . '\',' . $attJson . ')">' . h($n['title']) . '</a>';
        if ($hasAttach) $html .= '<span class="badge bg-secondary text-white ms-1" title="该通知包含附件"><i class="fas fa-paperclip"></i> 文件</span>';
        if ($hasImage) $html .= '<span class="badge bg-info text-white ms-1" title="该通知内容包含图片">图</span>';
        $html .= '</td>';
        $html .= '<td>' . h($n['publisher_name']) . '</td>';
        $html .= '<td><small>' . h($n['publish_time'] ?: $n['created_at']) . '</small></td>';
        if (isSupplier()) {
            $html .= '<td id="read-' . (int)$n['id'] . '">' . ($n['is_read'] ? '<span class="status-tag success">已读</span>' : '<span class="status-tag warning">未读</span>') . '</td>';
        }
        if (canManage('notice.delete')) {
            $html .= '<td><button class="btn-icon btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editNoticeModal" data-id="' . $n['id'] . '" data-title="' . h($n['title']) . '" data-content="' . h($n['content']) . '" data-has-attachment="' . (!empty($attachments) ? 1 : 0) . '"><i class="fas fa-edit"></i></button>';
            $html .= '<form method="post" class="d-inline" onsubmit="return confirm(\'确定删除？\')">' . csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . $n['id'] . '"><button class="btn-icon btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td>';
        }
        $html .= '</tr>';
        return $html;
    }
}
