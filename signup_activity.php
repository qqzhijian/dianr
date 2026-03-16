<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$activityId = (int)($_GET['id'] ?? 0);

if (!$activityId) {
    redirect('/activities.php');
}

$pdo = connectDB();
$stmt = $pdo->prepare('SELECT id FROM activities WHERE id = ?');
$stmt->execute([$activityId]);
if (!$stmt->fetch()) {
    redirect('/activities.php');
}

$stmt = $pdo->prepare('INSERT IGNORE INTO activity_signups (activity_id, user_id) VALUES (?, ?)');
$stmt->execute([$activityId, $user['id']]);

redirect('/activities.php');
?>