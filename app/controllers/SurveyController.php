<?php
class SurveyController extends Controller {
    private const QUESTION_TYPES = ['text', 'textarea', 'radio', 'checkbox', 'select'];

    public function index() {
        $this->requirePermission('survey.view');
        $db = DB::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'submit') { $this->doSubmit(); return; }
            if ($action === 'review') { $this->doReview(); return; }
            if (!isAdmin()) { flash('error', '无权操作'); header('Location: /survey'); exit; }
            if ($action === 'create') { $this->doCreate($db); return; }
            if ($action === 'update') { $this->doUpdate($db); return; }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    $db->execute("DELETE FROM survey_answer WHERE response_id IN (SELECT id FROM survey_response WHERE survey_id=?)", [$id]);
                    $db->execute("DELETE FROM survey_response WHERE survey_id=?", [$id]);
                    $db->execute("DELETE FROM survey_question WHERE survey_id=?", [$id]);
                    $db->execute("DELETE FROM survey WHERE id=?", [$id]);
                    flash('success', '问卷已删除');
                }
            }
            header('Location: /survey'); exit;
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'data') { $this->data(); return; }
        if ($action === 'questions') { $this->questionsJson(); return; }
        if ($action === 'detail') { $this->detail(); return; }
        $surveys = $db->query("SELECT * FROM survey ORDER BY created_at DESC");
        $questionsBySurvey = [];
        foreach ($db->query("SELECT * FROM survey_question ORDER BY survey_id, sort_order, id") as $q) {
            $questionsBySurvey[$q['survey_id']][] = $q;
        }
        $myResponses = isSupplier() ? $db->query("SELECT sr.*, s.title, s.description FROM survey_response sr JOIN survey s ON sr.survey_id=s.id WHERE sr.supplier_id=? ORDER BY sr.created_at DESC", [userSupplierId()]) : [];
        $responses = isAdmin() ? $db->query("SELECT sr.*, s.title FROM survey_response sr JOIN survey s ON sr.survey_id=s.id ORDER BY sr.created_at DESC") : [];
        $title = '问卷管理';
        $this->render('survey/index', get_defined_vars());
    }

    private function saveQuestions($surveyId) {
        $db = DB::getInstance();
        $titles = $_POST['question_title'] ?? [];
        $types = $_POST['question_type'] ?? [];
        $requireds = $_POST['question_required'] ?? [];
        $optionsArr = $_POST['question_options'] ?? [];
        if (!is_array($titles)) return;
        $sort = 0;
        foreach ($titles as $i => $t) {
            $title = trim((string)$t);
            if ($title === '') continue;
            $type = isset($types[$i]) && in_array($types[$i], self::QUESTION_TYPES) ? $types[$i] : 'text';
            $required = (int)!empty($requireds[$i]);
            $opts = [];
            if (in_array($type, ['radio', 'checkbox', 'select'])) {
                if (isset($optionsArr[$i])) {
                    $raw = is_array($optionsArr[$i]) ? $optionsArr[$i] : (preg_split('/[\r\n,，]+/u', (string)$optionsArr[$i]) ?: [(string)$optionsArr[$i]]);
                    foreach ($raw as $o) { $o = trim((string)$o); if ($o !== '') $opts[] = $o; }
                }
                $optsJson = json_encode($opts, JSON_UNESCAPED_UNICODE);
            } else {
                // 单行/多行文本：将默认答案存入 options（原始字符串）
                 $optsJson = trim((string)($optionsArr[$i] ?? ''));
             }
             $db->execute("INSERT INTO survey_question (survey_id, question_type, question_title, is_required, sort_order, options) VALUES (?,?,?,?,?,?)",
                [$surveyId, $type, $title, $required, $sort, $optsJson]);
            $sort++;
        }
    }

    private function doCreate($db) {
        $db->execute("INSERT INTO survey (title, description, expiration_date, created_by) VALUES (?,?,?,?)",
            [$_POST['title'] ?? '', $_POST['description'] ?? '', $_POST['expiration_date'] ?? '', userId()]);
        $sid = $db->lastInsertId();
        $this->saveQuestions($sid);
        logOp('create', 'survey', $sid, '创建问卷: ' . ($_POST['title'] ?? ''));
        flash('success', '问卷已创建');
        header('Location: /survey'); exit;
    }

    private function doUpdate($db) {
        $id = (int)($_POST['id'] ?? 0);
        $db->execute("UPDATE survey SET title=?, description=?, expiration_date=?, status=? WHERE id=?",
            [$_POST['title'] ?? '', $_POST['description'] ?? '', $_POST['expiration_date'] ?? '',
             $_POST['status'] ?? 'active', $id]);
        $db->execute("DELETE FROM survey_answer WHERE question_id IN (SELECT id FROM survey_question WHERE survey_id=?)", [$id]);
        $db->execute("DELETE FROM survey_question WHERE survey_id=?", [$id]);
        $this->saveQuestions($id);
        flash('success', '问卷已更新');
        header('Location: /survey'); exit;
    }

    /** 服务端分页数据接口（action=data）：问卷列表 */
    private function data() {
        $this->requirePermission('survey.view');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $total = (int)$db->single("SELECT COUNT(*) FROM survey");
        $offset = ($page - 1) * $perPage;
        $sid = userSupplierId();
        $rows = $db->query("SELECT s.*,
            (SELECT COUNT(*) FROM survey_response WHERE survey_id=s.id) AS response_count,
            (SELECT COUNT(*) FROM survey_question WHERE survey_id=s.id) AS question_count,
            (SELECT COUNT(*) FROM survey_answer sa JOIN survey_response sr ON sa.response_id=sr.id WHERE sr.survey_id=s.id AND sr.supplier_id=?) AS my_answered,
            (SELECT COUNT(*) FROM survey_response WHERE survey_id=s.id AND supplier_id=?) AS my_submitted,
            (SELECT id FROM survey_response WHERE survey_id=s.id AND supplier_id=?) AS my_response_id
            FROM survey s ORDER BY s.created_at DESC LIMIT ? OFFSET ?", [$sid, $sid, $sid, $perPage, $offset]);
        $html = '';
        foreach ($rows as $s) {
            $html .= '<tr>';
            $html .= colId($s['id'] ?? '') . '<td>' . h($s['title']) . '</td>';
            $html .= '<td>' . h(mb_substr(strip_tags($s['description']), 0, 80)) . '</td>';
            $html .= '<td>' . statusBadge($s['status']) . '</td>';
            $html .= '<td><small>' . h($s['expiration_date'] ?? '-') . '</small></td>';
            if (isAdmin()) {
                $html .= '<td><span class="badge badge-soft-info">' . (int)$s['response_count'] . '</span> / ' . (int)$s['question_count'] . '题</td>';
                $html .= '<td><button class="btn-icon btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSurveyModal" data-id="' . $s['id'] . '" data-title="' . h($s['title']) . '" data-desc="' . h($s['description']) . '" data-expire="' . h($s['expiration_date']) . '" data-status="' . $s['status'] . '"><i class="fas fa-edit"></i></button>';
                $html .= '<form method="post" class="d-inline" onsubmit="return confirm(\'确定删除？\')">' . csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . $s['id'] . '"><button class="btn-icon btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td>';
            } else {
                $submitted = (int)($s['my_submitted'] ?? 0) > 0;
                $cell = $submitted ? '<span class="badge badge-soft-success">已提交</span> ' : '';
                $cell .= (int)($s['my_answered'] ?? 0) . ' / ' . (int)$s['question_count'] . '题';
                $html .= '<td>' . $cell . '</td>';
                if ($s['status'] === 'active') {
                    if ($submitted) {
                        $html .= '<td><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailModal" data-id="' . (int)$s['my_response_id'] . '"><i class="fas fa-eye"></i> 查看</button></td>';
                    } else {
                        $html .= '<td><button type="button" class="btn btn-sm btn-outline-primary" onclick="openReply(' . $s['id'] . ');return false;">填写</button></td>';
                    }
                } else {
                    $html .= '<td class="text-muted">已关闭</td>';
                }
            }
            $html .= '</tr>';
        }
        $this->paginationResponse($total, $html);
    }

    /** action=questions：问卷问题 JSON，供编辑弹窗回填 */
    private function questionsJson() {
        $this->requirePermission('survey.view');
        $id = (int)($_GET['id'] ?? 0);
        $rows = DB::getInstance()->query("SELECT id, question_type, question_title, is_required, options FROM survey_question WHERE survey_id=? ORDER BY sort_order, id", [$id]);
        header('Content-Type: application/json');
        echo json_encode($rows, JSON_UNESCAPED_UNICODE); exit;
    }

    /** action=detail：管理员查看某条回复的逐题答案 */
    private function detail() {
        $this->requirePermission('survey.view');
        $db = DB::getInstance();
        $rid = (int)($_GET['id'] ?? 0);
        if (isSupplier()) {
            $resp = $db->single("SELECT sr.*, s.title FROM survey_response sr JOIN survey s ON sr.survey_id=s.id WHERE sr.id=? AND sr.supplier_id=?", [$rid, userSupplierId()], true);
        } else {
            $resp = $db->single("SELECT sr.*, s.title FROM survey_response sr JOIN survey s ON sr.survey_id=s.id WHERE sr.id=?", [$rid], true);
        }
        if (!$resp) { flash('error', '回复不存在'); header('Location: /survey'); exit; }
        $answers = [];
        foreach ($db->query("SELECT sq.question_title, sq.question_type, sa.answer_value FROM survey_answer sa JOIN survey_question sq ON sa.question_id=sq.id WHERE sa.response_id=? ORDER BY sq.sort_order, sq.id", [$rid]) as $a) {
            $answers[] = $a;
        }
        header('Content-Type: application/json');
        echo json_encode(['resp' => [
            'id' => $resp['id'],
            'title' => $resp['title'],
            'supplier_name' => $resp['supplier_name'],
            'file_url' => $resp['file_path'] ? fileUrl($resp['file_path']) : '',
            'submit_status' => $resp['submit_status'],
        ], 'answers' => $answers], JSON_UNESCAPED_UNICODE); exit;
    }

    private function doSubmit() {
        $db = DB::getInstance();
        $surveyId = (int)($_POST['survey_id'] ?? 0);
        if (!$surveyId) { flash('error', '参数错误'); header('Location: /survey'); exit; }
        $supplierName = $db->single("SELECT name FROM supplier WHERE id=?", [userSupplierId()]);
        $filePath = '';
        $fileName = '';
        if (!empty($_FILES['response_file']['name']) && $_FILES['response_file']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['response_file'];
            $fileName = $f['name'];
            $dir = uploadPath('survey');
            $name = pathinfo($f['name'], PATHINFO_FILENAME) . '_' . date('Ymd') . '.' . strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            move_uploaded_file($f['tmp_name'], $dir . '/' . $name);
            $filePath = $dir . '/' . $name;
        }
        $existing = $db->single("SELECT id FROM survey_response WHERE survey_id=? AND supplier_id=?", [$surveyId, userSupplierId()], true);
        if ($existing) {
            $db->execute("UPDATE survey_response SET file_name=?, file_path=?, submit_status='1', updated_at=datetime('now','localtime') WHERE id=?", [$fileName, $filePath, $existing['id']]);
            $responseId = $existing['id'];
        } else {
            $db->execute("INSERT INTO survey_response (survey_id, supplier_id, supplier_name, file_name, file_path, submit_status) VALUES (?,?,?,?,?,'1')",
                [$surveyId, userSupplierId(), $supplierName, $fileName, $filePath]);
            $responseId = $db->lastInsertId();
        }
        // 保存逐题答案
        $answers = $_POST['answer'] ?? [];
        if (is_array($answers)) {
            foreach ($answers as $qid => $val) {
                $qid = (int)$qid;
                if (!$qid) continue;
                if (is_array($val)) $val = implode(',', array_filter($val, fn($v) => trim((string)$v) !== ''));
                $val = trim((string)$val);
                $db->execute("INSERT INTO survey_answer (response_id, question_id, answer_value) VALUES (?,?,?)
                    ON CONFLICT(response_id, question_id) DO UPDATE SET answer_value=excluded.answer_value",
                    [$responseId, $qid, $val]);
            }
        }
        flash('success', '问卷已提交');
        header('Location: /survey'); exit;
    }

    private function doReview() {
        $this->requirePermission('survey.view');
        $id = (int)($_POST['id'] ?? 0);
        DB::getInstance()->execute("UPDATE survey_response SET submit_status=?, reviewer=?, review_time=datetime('now','localtime'), review_comment=? WHERE id=?",
            [$_POST['submit_status'] ?? '2', $_SESSION['realname'] ?? '', $_POST['review_comment'] ?? '', $id]);
        flash('success', '已审核');
        header('Location: /survey'); exit;
    }
}