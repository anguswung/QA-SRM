<?php
class NoticeController extends Controller {
    public function index() {
        $this->requirePermission('notice.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
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
        if (isSupplier()) {
            $where[] = "n.status='published'";
        }
        $notices = $db->query("SELECT n.*, (SELECT COUNT(*) FROM notice_read_log WHERE notice_id=n.id AND supplier_id=?) AS is_read FROM notice n" . ($where ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY n.created_at DESC", [userSupplierId()]);
        $title = '通知公告';
        $this->render('notice/index', get_defined_vars());
    }
}
