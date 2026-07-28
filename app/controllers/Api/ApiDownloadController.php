<?php
class ApiDownloadController extends ApiBaseController {
    private static function isPathAllowed($path) {
        $normalized = str_replace('\\', '/', realpath($path) ?: $path);
        $uploadsBase = str_replace('\\', '/', realpath(__DIR__ . '/../../uploads'));
        if (!$uploadsBase) return false;
        return strpos($normalized, $uploadsBase) === 0;
    }

    public function token() {
        $path = ApiRequest::get('path', '');
        if (!$path) ApiResponse::error('路径不能为空');
        if (!file_exists($path)) ApiResponse::notFound('文件不存在');
        if (!self::isPathAllowed($path)) ApiResponse::forbidden('无权访问该文件');
        $data = ['path' => $path, 'time' => time(), 'uid' => $this->userId()];
        $token = base64_encode(json_encode($data)) . '.' . substr(md5(json_encode($data) . 'GPM_DOWNLOAD_SECRET'), 0, 16);
        ApiResponse::success(['token' => $token]);
    }

    public function file() {
        $token = ApiRequest::get('token', '');
        if (!$token) ApiResponse::error('token 不能为空');
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) ApiResponse::error('无效的令牌');
        $payload = json_decode(base64_decode($parts[0]), true);
        if (!$payload || !isset($payload['path'])) ApiResponse::error('无效的令牌');
        $check = substr(md5(json_encode($payload) . 'GPM_DOWNLOAD_SECRET'), 0, 16);
        if ($parts[1] !== $check) ApiResponse::error('令牌验证失败');
        if (!self::isPathAllowed($payload['path'])) ApiResponse::forbidden('无权访问该文件');
        if (!file_exists($payload['path'])) ApiResponse::notFound('文件不存在');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($payload['path']) . '"');
        header('Content-Length: ' . filesize($payload['path']));
        readfile($payload['path']);
        exit;
    }

    private function _notImpl() { ApiResponse::error('接口未实现', 501, 501); }
}
