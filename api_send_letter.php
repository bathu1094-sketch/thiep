<?php
/**
 * API NHẬN THƯ / LỜI NHẮN TỪ NGƯỜI XEM THIỆP
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

$sender = trim($_POST['sender_name'] ?? '');
$content = trim($_POST['content'] ?? '');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung thư!']);
    exit;
}

if (empty($sender)) {
    $sender = 'Người gửi ẩn danh 🌸';
}

$success = saveLetter($sender, $content);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Thư của bạn đã được gửi thành công đến người nhận! Cảm ơn bạn rất nhiều 💖' : 'Không thể lưu thư vào cơ sở dữ liệu.'
]);
