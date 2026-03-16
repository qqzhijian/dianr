<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/reviews.php');
}

$reviewed_id = (int)($_POST['reviewed_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = sanitize($_POST['comment'] ?? '');

if (!$reviewed_id || $rating < 1 || $rating > 5) {
    redirect('/reviews.php');
}

$pdo = connectDB();

// Check if reviewed user exists and is mediator/merchant
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role IN ('mediator', 'merchant') AND is_blacklisted = 0");
$stmt->execute([$reviewed_id]);
if (!$stmt->fetch()) {
    redirect('/reviews.php');
}

// Check if already reviewed
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE reviewer_id = ? AND reviewed_id = ?");
$stmt->execute([$user['id'], $reviewed_id]);
if ($stmt->fetch()) {
    redirect('/reviews.php');
}

$stmt = $pdo->prepare("INSERT INTO reviews (reviewer_id, reviewed_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->execute([$user['id'], $reviewed_id, $rating, $comment]);

redirect('/reviews.php');
?>