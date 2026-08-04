<?php
require_once __DIR__ . '/../app/helpers.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['code' => 401, 'msg' => '未登录']);
    exit;
}
if (($_POST['_csrf_token'] ?? '') === '' || empty($_SESSION['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'], $_POST['_csrf_token'] ?? '')) {
    http_response_code(419);
    header('Content-Type: application/json');
    echo json_encode(['code' => 419, 'msg' => 'CSRF token validation failed']);
    exit;
}
$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['code' => 400, 'msg' => '文件上传失败']);
    exit;
}
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
if (!in_array($ext, $allowed)) {
    header('Content-Type: application/json');
    echo json_encode(['code' => 400, 'msg' => '不支持的图片格式']);
    exit;
}
$dir = __DIR__ . '/../uploads/quill/';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$newName = date('Ymd') . '_' . uniqid() . '.' . $ext;
$dest = $dir . $newName;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    header('Content-Type: application/json');
    echo json_encode(['code' => 500, 'msg' => '文件保存失败']);
    exit;
}
$url = '/download.php?path=quill/' . $newName;
header('Content-Type: application/json');
echo json_encode(['code' => 0, 'msg' => 'success', 'data' => ['url' => $url]]);
