<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();

if ($user['role'] !== 'merchant') {
    redirect('/');
}

$cert_id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$cert_id || !in_array($action, ['approve', 'reject'])) {
    redirect('/certifications.php');
}

$pdo = connectDB();

// Check if the certification belongs to this merchant
$stmt = $pdo->prepare("SELECT id FROM associations WHERE id = ? AND merchant_id = ?");
$stmt->execute([$cert_id, $user['id']]);
if (!$stmt->fetch()) {
    redirect('/certifications.php');
}

$status = $action === 'approve' ? 'approved' : 'rejected';
$stmt = $pdo->prepare("UPDATE associations SET status = ? WHERE id = ?");
$stmt->execute([$status, $cert_id]);

redirect('/certifications.php');
?>