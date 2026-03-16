<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$user = getCurrentUser();

// Check if verified
if (!$user['is_verified']) {
    echo json_encode(['success' => false, 'message' => '需要验证邮箱/手机号后才能发送申请']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$target_id = (int)($data['target_id'] ?? 0);

if (!$target_id || $target_id === $user['id']) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

$pdo = connectDB();

// Check if already requested
$stmt = $pdo->prepare("SELECT id, status FROM profile_requests WHERE requester_id = ? AND target_id = ?");
$stmt->execute([$user['id'], $target_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    if ($existing['status'] === 'pending') {
        echo json_encode(['success' => false, 'message' => '已申请，等待回复']);
    } elseif ($existing['status'] === 'approved') {
        echo json_encode(['success' => false, 'message' => '已批准']);
    } else {
        // Resend request
        $stmt = $pdo->prepare("UPDATE profile_requests SET status = 'pending', requested_at = NOW() WHERE id = ?");
        $stmt->execute([$existing['id']]);
        echo json_encode(['success' => true, 'message' => '申请已重新发送']);
    }
} else {
    $stmt = $pdo->prepare("INSERT INTO profile_requests (requester_id, target_id) VALUES (?, ?)");
    if ($stmt->execute([$user['id'], $target_id])) {
        echo json_encode(['success' => true, 'message' => '申请已发送']);
    } else {
        echo json_encode(['success' => false, 'message' => '申请失败']);
    }
}
?>