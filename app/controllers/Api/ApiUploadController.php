<?php
class ApiUploadController extends ApiBaseController {
    public function upload() {
        $file = ApiRequest::file('file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) ApiResponse::error('文件上传失败');
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','zip'];
        if (!in_array($ext, $allowed)) ApiResponse::error('不支持的文件类型');
        $newName = date('Ymd') . '_' . uniqid() . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) ApiResponse::error('文件保存失败');
        ApiResponse::success(['url' => '/uploads/' . $newName, 'path' => $dest, 'name' => $file['name'], 'size' => $file['size']]);
    }
    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
