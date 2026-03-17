<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    exit;
}
$receiverId = (int)($_GET['receiver'] ?? 0);

if (!$receiverId) {
    http_response_code(400);
    exit;
}

$pdo = connectDB();
$stmt = $pdo->prepare("
    SELECT m.*, u.nickname as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
    AND m.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY m.created_at ASC
");
$stmt->execute([$user['id'], $receiverId, $receiverId, $user['id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($messages);
?>