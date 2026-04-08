<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin();
if (!admin_can('manage_content') && !admin_can('manage_menu')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload failed']);
    exit;
}

$tmp = (string)$file['tmp_name'];
$size = (int)($file['size'] ?? 0);
if ($size <= 0 || $size > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file size']);
    exit;
}

$imageInfo = @getimagesize($tmp);
if ($imageInfo === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image']);
    exit;
}

$mime = strtolower((string)($imageInfo['mime'] ?? ''));
$extMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];
if (!isset($extMap[$mime])) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported image type']);
    exit;
}

$year = date('Y');
$month = date('m');
$relativeDir = "/uploads/editor/{$year}/{$month}";
$absoluteDir = __DIR__ . '/../uploads/editor/' . $year . '/' . $month;
if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create directory']);
    exit;
}

$filename = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $extMap[$mime];
$destination = $absoluteDir . '/' . $filename;
if (!move_uploaded_file($tmp, $destination)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save image']);
    exit;
}

echo json_encode(['location' => $relativeDir . '/' . $filename]);

