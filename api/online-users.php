<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$user = getCurrentUser();
$pdo = connectDB();

// Get online users (active in last 10 minutes)
$ten_minutes_ago = date('Y-m-d H:i:s', strtotime('-10 minutes'));
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.nickname,
        u.role,
        u.last_seen,
        COUNT(CASE WHEN c.is_read = 0 AND c.receiver_id = ? THEN 1 END) as unread_count
    FROM users u
    LEFT JOIN chats c ON c.sender_id = u.id AND c.receiver_id = ?
    LEFT JOIN blacklist b ON (b.user_id = ? AND b.blocked_user_id = u.id) OR (b.user_id = u.id AND b.blocked_user_id = ?)
    WHERE u.id != ?
    AND u.is_blacklisted = 0
    AND u.last_seen > ?
    AND b.id IS NULL
    GROUP BY u.id, u.nickname, u.role, u.last_seen
    ORDER BY u.last_seen DESC
");

$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $ten_minutes_ago]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add status and role text
foreach ($users as &$u) {
    $u['status'] = getOnlineStatus($u['last_seen']);
    $u['role_text'] = $u['role'] === 'user' ? '用户' : ($u['role'] === 'mediator' ? '媒人' : '商家');
}

echo json_encode(['success' => true, 'users' => $users]);
?>