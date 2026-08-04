<?php
class SurveyController extends Controller {
    public function index() {
        $this->requirePermission('survey.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'submit') {
                $this->doSubmit();
                return;
            }
            if (!isAdmin()) { flash('error', '无权操作'); header('Location: /survey'); exit; }
            if ($action === 'create') {
                $db->execute("INSERT INTO survey (title, description, expiration_date, created_by) VALUES (?,?,?,?)",
                    [$_POST['title'] ?? '', $_POST['description'] ?? '', $_POST['expiration_date'] ?? '', userId()]);
                logOp('create', 'survey', $db->lastInsertId(), '创建问卷: ' . ($_POST['title'] ?? ''));
                flash('success', '问卷已创建');
            }
            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $db->execute("UPDATE survey SET title=?, description=?, expiration_date=?, status=? WHERE id=?",
                    [$_POST['title'] ?? '', $_POST['description'] ?? '', $_POST['expiration_date'] ?? '',
                     $_POST['status'] ?? 'active', $id]);
                flash('success', '问卷已更新');
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) { $db->execute("DELETE FROM survey WHERE id=?", [$id]); $db->execute("DELETE FROM survey_response WHERE survey_id=?", [$id]); flash('success', '问卷已删除'); }
            }
            if ($action === 'submit') {
                return;
            }
            if ($action === 'review') {
                $id = (int)($_POST['id'] ?? 0);
                $db->execute("UPDATE survey_response SET submit_status=?, reviewer=?, review_time=datetime('now','localtime'), review_comment=? WHERE id=?",
                    [$_POST['submit_status'] ?? '2', $_SESSION['realname'] ?? '', $_POST['review_comment'] ?? '', $id]);
                flash('success', '已审核');
            }
            header('Location: /survey'); exit;
        }
        $surveys = $db->query("SELECT s.*, (SELECT COUNT(*) FROM survey_response WHERE survey_id=s.id) AS response_count FROM survey s ORDER BY s.created_at DESC");
        $myResponses = isSupplier() ? $db->query("SELECT sr.*, s.title, s.description FROM survey_response sr JOIN survey s ON sr.survey_id=s.id WHERE sr.supplier_id=? ORDER BY sr.created_at DESC", [userSupplierId()]) : [];
        $responses = isAdmin() ? $db->query("SELECT sr.*, s.title FROM survey_response sr JOIN survey s ON sr.survey_id=s.id ORDER BY sr.created_at DESC") : [];
        $title = '问卷管理';
        $this->render('survey/index', get_defined_vars());
    }
    private function doSubmit() {
        $db = DB::getInstance();
        $surveyId = (int)($_POST['survey_id'] ?? 0);
        if (!$surveyId) { flash('error', '参数错误'); header('Location: /survey'); exit; }
        $supplierName = $db->single("SELECT name FROM supplier WHERE id=?", [userSupplierId()]);
        $filePath = '';
        $fileName = '';
        if (!empty($_FILES['response_file']['name'])) {
            $f = $_FILES['response_file'];
            $fileName = $f['name'];
            $dir = uploadPath('survey');
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $name = pathinfo($f['name'], PATHINFO_FILENAME) . '_' . date('Ymd') . '.' . $ext;
            move_uploaded_file($f['tmp_name'], $dir . '/' . $name);
            $filePath = $dir . '/' . $name;
        }
        $existing = $db->single("SELECT id FROM survey_response WHERE survey_id=? AND supplier_id=?", [$surveyId, userSupplierId()], true);
        if ($existing) {
            $db->execute("UPDATE survey_response SET file_name=?, file_path=?, submit_status='1', updated_at=datetime('now','localtime') WHERE id=?", [$fileName, $filePath, $existing['id']]);
        } else {
            $db->execute("INSERT INTO survey_response (survey_id, supplier_id, supplier_name, file_name, file_path, submit_status) VALUES (?,?,?,?,?,'1')",
                [$surveyId, userSupplierId(), $supplierName, $fileName, $filePath]);
        }
        flash('success', '问卷已提交');
        header('Location: /survey'); exit;
    }
}
