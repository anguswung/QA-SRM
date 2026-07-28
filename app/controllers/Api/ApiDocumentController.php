<?php
class ApiDocumentController extends ApiBaseController {
    public function categories() {
        $items = $this->db->query("SELECT * FROM document_category ORDER BY sort_order, id");
        ApiResponse::success($items);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
