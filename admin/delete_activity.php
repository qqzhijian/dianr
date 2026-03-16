<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$activity_id = (int)($_GET['id'] ?? 0);

if (!$activity_id) {
    redirect('/admin/activities.php');
}

$pdo = connectDB();
$stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
$stmt->execute([$activity_id]);

redirect('/admin/activities.php');
?>