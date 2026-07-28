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

if (!file_exists($normalized)) {
    http_response_code(404);
    exit('File not found');
}

$realPath = str_replace('\\', '/', realpath($normalized));
if (strpos($realPath, $uploadsBase) !== 0) {
    http_response_code(403);
    exit('Forbidden');
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
