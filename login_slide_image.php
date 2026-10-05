<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php
require_once __DIR__ . '/config/database.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) { http_response_code(404); exit; }
try {
    $stmt = $conn->prepare("SELECT mime_type, image_data FROM login_slides WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    if (!$row || empty($row['image_data'])) { http_response_code(404); exit; }
    $data = (string)$row['image_data'];
    $mime = (string)($row['mime_type'] ?: 'image/jpeg');
    if (str_starts_with($data, 'data:')) {
        if (preg_match('/^data:([^;]+);base64,(.*)$/s', $data, $m)) {
            $mime = $m[1] ?: $mime;
            $data = $m[2];
        }
    }
    $binary = base64_decode($data, true);
    if ($binary === false) { http_response_code(500); exit; }
    $etag='\"'.sha1($binary).'\"';
    header('ETag: '.$etag);
    header('Cache-Control: public, max-age=31536000, immutable');
    if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag){
        http_response_code(304); exit;
    }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . strlen($binary));
    echo $binary;
} catch (Throwable $e) {
    http_response_code(404);
}
