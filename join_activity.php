<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$activity_id = (int)($_GET['id'] ?? 0);

if (!$activity_id) {
    redirect('/activities.php');
}

$pdo = connectDB();
$stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
$stmt->execute([$activity_id]);
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    redirect('/activities.php');
}

$can_join = $user['role'] === 'user' || in_array($user['role'], explode(',', $activity['associated_roles']));

if (!$can_join) {
    redirect('/activities.php');
}

// Check if already joined
$stmt = $pdo->prepare("SELECT id FROM activity_participants WHERE activity_id = ? AND user_id = ?");
$stmt->execute([$activity_id, $user['id']]);
if ($stmt->fetch()) {
    redirect('/activities.php');
}

// Join
$stmt = $pdo->prepare("INSERT INTO activity_participants (activity_id, user_id) VALUES (?, ?)");
$stmt->execute([$activity_id, $user['id']]);

redirect('/activities.php');
?>