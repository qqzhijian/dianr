<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$ownerId = (int)($_GET['id'] ?? 0);

if (!$ownerId || $ownerId == $user['id']) {
    redirect('/users.php');
}

$pdo = connectDB();
$stmt = $pdo->prepare('INSERT IGNORE INTO profile_requests (requester_id, owner_id) VALUES (?, ?)');
$stmt->execute([$user['id'], $ownerId]);

redirect('/profile.php?id=' . $ownerId);
?>