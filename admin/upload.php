<?php
/**
 * Image upload endpoint for the featured-image picker and the Quill editor's
 * inline image button. Returns JSON. Validates real image content (not just
 * the extension/declared MIME) and writes with a randomized filename.
 */
require_once __DIR__ . '/includes/admin-auth.php';

header('Content-Type: application/json; charset=UTF-8');

function upload_fail(string $message): never
{
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    upload_fail('Буруу хүсэлт.');
}
if (!csrf_verify()) {
    upload_fail('Хүчингүй хүсэлт (CSRF). Хуудсаа шинэчилж дахин оролдоно уу.');
}
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    upload_fail('Зураг байршуулахад алдаа гарлаа.');
}

$file = $_FILES['image'];

if ($file['size'] > MAX_UPLOAD_BYTES) {
    upload_fail('Зургийн хэмжээ хэтэрхий том байна (дээд тал нь ' . (int) (MAX_UPLOAD_BYTES / 1024 / 1024) . ' МБ).');
}

$imageInfo = @getimagesize($file['tmp_name']);
if (!$imageInfo) {
    upload_fail('Файл зураг биш байна.');
}

$allowedTypes = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG => 'png',
    IMAGETYPE_GIF => 'gif',
    IMAGETYPE_WEBP => 'webp',
];
$type = $imageInfo[2];
if (!isset($allowedTypes[$type])) {
    upload_fail('Зөвхөн JPG, PNG, GIF, WEBP зураг зөвшөөрнө.');
}
$ext = $allowedTypes[$type];

$subDir = date('Y/m');
$targetDir = UPLOAD_DIR . '/' . $subDir;
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    upload_fail('Серверт зураг хадгалах хавтас үүсгэж чадсангүй. Uploads хавтасны эрхийг шалгана уу.');
}

$filename = bin2hex(random_bytes(8)) . '.' . $ext;
$targetPath = $targetDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    upload_fail('Зураг хадгалахад алдаа гарлаа.');
}

$url = rtrim(UPLOAD_URL, '/') . '/' . $subDir . '/' . $filename;
echo json_encode(['ok' => true, 'url' => $url]);
