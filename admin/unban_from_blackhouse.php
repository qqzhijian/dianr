<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$ban_id = (int)($_GET['id'] ?? 0);

if (!$ban_id) {
    redirect('/admin/blackhouse.php');
}

$pdo = connectDB();
$stmt = $pdo->prepare("DELETE FROM blackhouse WHERE id = ?");
$stmt->execute([$ban_id]);

redirect('/admin/blackhouse.php');
?>