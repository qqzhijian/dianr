<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$user = getCurrentUser();
$pdo = connectDB();

// Get unread message counts by sender
$stmt = $pdo->prepare("
    SELECT
        c.sender_id,
        u.nickname as sender_nickname,
        COUNT(*) as unread_count,
        MAX(c.sent_at) as latest_message_time
    FROM chats c
    JOIN users u ON c.sender_id = u.id
    LEFT JOIN blacklist b ON (b.user_id = ? AND b.blocked_user_id = c.sender_id) OR (b.user_id = c.sender_id AND b.blocked_user_id = ?)
    WHERE c.receiver_id = ?
    AND c.is_read = 0
    AND u.is_blacklisted = 0
    AND b.id IS NULL
    GROUP BY c.sender_id, u.nickname
    ORDER BY latest_message_time DESC
");

$stmt->execute([$user['id'], $user['id'], $user['id']]);
$unread_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert to associative array for easier lookup
$counts = [];
foreach ($unread_counts as $count) {
    $counts[$count['sender_id']] = $count['unread_count'];
}

// Get latest unread messages for notifications (limit to 1 for simplicity)
$notifications = [];
if (!empty($unread_counts)) {
    $latest_sender = $unread_counts[0]['sender_id'];

    $stmt = $pdo->prepare("
        SELECT c.message, u.nickname as sender_nickname, c.sent_at
        FROM chats c
        JOIN users u ON c.sender_id = u.id
        WHERE c.sender_id = ? AND c.receiver_id = ? AND c.is_read = 0
        ORDER BY c.sent_at DESC
        LIMIT 1
    ");

    $stmt->execute([$latest_sender, $user['id']]);
    $latest_msg = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($latest_msg) {
        $notifications[] = $latest_msg;
    }
}

echo json_encode([
    'success' => true,
    'unread_counts' => $counts,
    'notifications' => $notifications
]);
?>