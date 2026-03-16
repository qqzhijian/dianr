<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$user = getCurrentUser();
$data = json_decode(file_get_contents('php://input'), true);
$blocked_user_id = (int)($data['blocked_user_id'] ?? 0);

if (!$blocked_user_id || $blocked_user_id === $user['id']) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

$pdo = connectDB();

// Check if already blocked
$stmt = $pdo->prepare("SELECT id FROM blacklist WHERE user_id = ? AND blocked_user_id = ?");
$stmt->execute([$user['id'], $blocked_user_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => '已拉黑']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO blacklist (user_id, blocked_user_id) VALUES (?, ?)");
if ($stmt->execute([$user['id'], $blocked_user_id])) {
    echo json_encode(['success' => true, 'message' => '已拉黑']);
} else {
    echo json_encode(['success' => false, 'message' => '拉黑失败']);
}
?>