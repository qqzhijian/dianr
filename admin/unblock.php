<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$block_id = (int)($_GET['id'] ?? 0);

if (!$block_id) {
    redirect('/admin/blacklist.php');
}

$pdo = connectDB();
$stmt = $pdo->prepare("DELETE FROM blacklist WHERE id = ?");
$stmt->execute([$block_id]);

redirect('/admin/blacklist.php');
?>