<?php
class ApiNoticeController extends ApiBaseController {
    public function index() {
        $page = max(1, (int)ApiRequest::get('page', 1));
        $perPage = max(1, min(100, (int)ApiRequest::get('per_page', 15)));
        $offset = ($page - 1) * $perPage;
        $total = $this->db->single("SELECT COUNT(*) FROM notice");
        $items = $this->db->query("SELECT n.*, u.realname AS publisher_name FROM notice n LEFT JOIN users u ON n.publisher_id=u.id ORDER BY n.id DESC LIMIT ? OFFSET ?", [$perPage, $offset]);
        ApiResponse::paginated($items, $total, $page, $perPage);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
