<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$user_id = (int)($_GET['id'] ?? 0);

if (!$user_id) {
    redirect('/admin/users.php');
}

$pdo = connectDB();
$stmt = $pdo->prepare("UPDATE users SET is_blacklisted = 1 WHERE id = ?");
$stmt->execute([$user_id]);

redirect('/admin/users.php');
?>