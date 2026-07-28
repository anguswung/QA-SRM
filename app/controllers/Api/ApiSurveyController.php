<?php
class ApiSurveyController extends ApiBaseController {
    public function index() {
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $total = $this->db->single("SELECT COUNT(*) FROM survey");
        $items = $this->db->query("SELECT s.*, u.realname AS creator_name FROM survey s LEFT JOIN users u ON s.created_by=u.id ORDER BY s.id DESC LIMIT ? OFFSET ?", [$perPage, $offset]);
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
