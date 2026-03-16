<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit;
}

$user = getCurrentUser();
$data = json_decode(file_get_contents('php://input'), true);

$userId = (int)($data['user_id'] ?? 0);
$action = $data['action'] ?? '';

if (!$userId || !in_array($action, ['verify', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

$pdo = connectDB();

// Check if user exists and is not already verified
$stmt = $pdo->prepare('SELECT id, is_verified FROM users WHERE id = ? AND is_deleted = 0');
$stmt->execute([$userId]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    echo json_encode(['success' => false, 'message' => '用户不存在']);
    exit;
}

if ($action === 'verify') {
    $stmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
    $stmt->execute([$userId]);
    $message = '认证已通过';
} else {
    $stmt = $pdo->prepare('UPDATE users SET is_verified = 0 WHERE id = ?');
    $stmt->execute([$userId]);
    $message = '认证已拒绝';
}

echo json_encode(['success' => true, 'message' => $message]);
?>