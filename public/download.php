<?php
require_once __DIR__ . '/../app/helpers.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$path = $_GET['path'] ?? '';
if (!$path) { http_response_code(400); exit('Missing path'); }

$normalized = str_replace('\\', '/', $path);
$parts = explode('/', $normalized);
$resolved = [];
foreach ($parts as $p) {
    if ($p === '..' && $resolved) {
        array_pop($resolved);
    } elseif ($p !== '.' && $p !== '') {
        $resolved[] = $p;
    }
}
$normalized = implode('/', $resolved);

$uploadsBase = str_replace('\\', '/', realpath(__DIR__ . '/../uploads'));
if (!$uploadsBase) {
    http_response_code(500);
    exit('Server configuration error');
}

// Resolve path relative to uploads base (prevent path traversal)
$candidate = realpath($uploadsBase . '/' . ltrim($normalized, '/'));
if ($candidate && strpos(str_replace('\\', '/', $candidate), $uploadsBase) === 0) {
    $normalized = $candidate;
}

if (!is_file($normalized)) {
    http_response_code(404);
    exit('File not found');
}

$realPath = str_replace('\\', '/', realpath($normalized));
if (strpos($realPath, $uploadsBase) !== 0) {
    http_response_code(403);
    exit('Forbidden');
}

// Ownership check for supplier accounts: only allow files owned by the current supplier
if (isSupplier()) {
    require_once __DIR__ . '/../app/core/Controller.php';
    if (!class_exists('DB')) {
        require_once __DIR__ . '/../app/models/DB.php';
    }
    $db = DB::getInstance();
    $sid = userSupplierId();
    $uid = userId();
    $allowed = (bool)$db->single("SELECT COUNT(*) FROM test_report tr JOIN material m ON tr.material_id=m.id WHERE REPLACE(tr.file_path,'\\','/')=? AND m.supplier_id=?", [$realPath, $sid])
        || (bool)$db->single("SELECT COUNT(*) FROM hm_report hr JOIN homogeneous_material hm ON hr.hm_id=hm.id WHERE REPLACE(hr.file_path,'\\','/')=? AND hm.created_by=?", [$realPath, $uid])
        || (bool)$db->single("SELECT COUNT(*) FROM survey_response WHERE REPLACE(file_path,'\\','/')=? AND supplier_id=?", [$realPath, $sid])
        || (bool)$db->single("SELECT COUNT(*) FROM task_attachment ta JOIN bpm_instance bi ON ta.instance_id=bi.id WHERE REPLACE(ta.file_path,'\\','/')=? AND (bi.initiator_id=? OR bi.id IN (SELECT bt.instance_id FROM bpm_task bt WHERE bt.assignee_id=?))", [$realPath, $uid, $uid])
        || (bool)$db->single("SELECT COUNT(*) FROM notice_attachment na JOIN notice n ON na.notice_id=n.id WHERE REPLACE(na.file_path,'\\','/')=? AND n.status='published'", [$realPath])
        || (bool)$db->single("SELECT COUNT(*) FROM material WHERE REPLACE(mcd_file_path,'\\','/')=? AND supplier_id=?", [$realPath, $sid])
        || strpos($realPath, $uploadsBase . '/quill/') === 0;
    if (!$allowed) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'zip' => 'application/zip',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($normalized));
header('Content-Disposition: inline; filename="' . basename($normalized) . '"');
readfile($normalized);
