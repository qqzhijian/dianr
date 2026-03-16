<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$user = getCurrentUser();
$pdo = connectDB();

$stmt = $pdo->prepare("SELECT id, last_seen FROM users WHERE id != ? AND is_blacklisted = 0");
$stmt->execute([$user['id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statuses = [];
foreach ($users as $u) {
    $statuses[] = [
        'id' => $u['id'],
        'status' => getOnlineStatus($u['last_seen'])
    ];
}

echo json_encode(['success' => true, 'statuses' => $statuses]);
?>