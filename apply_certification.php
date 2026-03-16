<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();

if ($user['role'] !== 'mediator' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$merchant_id = (int)($_POST['merchant_id'] ?? 0);

if (!$merchant_id) {
    redirect('/certifications.php');
}

$pdo = connectDB();

// Check if already applied
$stmt = $pdo->prepare("SELECT id FROM associations WHERE mediator_id = ? AND merchant_id = ?");
$stmt->execute([$user['id'], $merchant_id]);
if ($stmt->fetch()) {
    redirect('/certifications.php');
}

$stmt = $pdo->prepare("INSERT INTO associations (mediator_id, merchant_id) VALUES (?, ?)");
$stmt->execute([$user['id'], $merchant_id]);

redirect('/certifications.php');
?>