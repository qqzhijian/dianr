<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$user = getCurrentUser();
$receiver_id = (int)($_GET['receiver_id'] ?? 0);

if (!$receiver_id) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

$pdo = connectDB();

// Get messages from last 30 days
$thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
$stmt = $pdo->prepare("SELECT c.*, u.nickname as sender_nickname FROM chats c JOIN users u ON c.sender_id = u.id WHERE ((c.sender_id = ? AND c.receiver_id = ?) OR (c.sender_id = ? AND c.receiver_id = ?)) AND c.sent_at > ? ORDER BY c.sent_at ASC");
$stmt->execute([$user['id'], $receiver_id, $receiver_id, $user['id'], $thirty_days_ago]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark as read
$stmt = $pdo->prepare("UPDATE chats SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$stmt->execute([$receiver_id, $user['id']]);

echo json_encode(['success' => true, 'messages' => $messages]);
?>