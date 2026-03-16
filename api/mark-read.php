<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$user = getCurrentUser();
$data = json_decode(file_get_contents('php://input'), true);
$sender_id = (int)($data['sender_id'] ?? 0);

if (!$sender_id) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

$pdo = connectDB();

// Mark messages as read
$stmt = $pdo->prepare("UPDATE chats SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$result = $stmt->execute([$sender_id, $user['id']]);

echo json_encode(['success' => $result]);
?>