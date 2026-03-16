<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$user = getCurrentUser();
$data = json_decode(file_get_contents('php://input'), true);
$receiver_id = (int)($data['receiver_id'] ?? 0);
$message = trim($data['message'] ?? '');

if (!$receiver_id || empty($message)) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

$pdo = connectDB();

// Check if receiver exists and not blacklisted
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_blacklisted = 0");
$stmt->execute([$receiver_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => '用户不存在']);
    exit;
}

// Check if blocked
$stmt = $pdo->prepare("SELECT id FROM blacklist WHERE (user_id = ? AND blocked_user_id = ?) OR (user_id = ? AND blocked_user_id = ?)");
$stmt->execute([$user['id'], $receiver_id, $receiver_id, $user['id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => '无法发送消息']);
    exit;
}

// Insert message
$stmt = $pdo->prepare("INSERT INTO chats (sender_id, receiver_id, message) VALUES (?, ?, ?)");
if ($stmt->execute([$user['id'], $receiver_id, $message])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '发送失败']);
}
?>