<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit;
}

$user = getCurrentUser();
$receiverId = (int)($_POST['receiver_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if (!$receiverId || empty($content)) {
    http_response_code(400);
    exit;
}

$pdo = connectDB();
// Check if receiver exists and not blacklisted
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND is_blacklisted = 0 AND is_deleted = 0');
$stmt->execute([$receiverId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    exit;
}

// Check blacklist
$stmt = $pdo->prepare("SELECT id FROM blacklist WHERE (user_id = ? AND blocked_user_id = ?) OR (user_id = ? AND blocked_user_id = ?)");
$stmt->execute([$user['id'], $receiverId, $receiverId, $user['id']]);
if ($stmt->fetch()) {
    http_response_code(403);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)');
$stmt->execute([$user['id'], $receiverId, $content]);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>