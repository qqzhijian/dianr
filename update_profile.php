<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
if (!$user) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gender = $_POST['gender'] ?? '';
    $age = (int)($_POST['age'] ?? 0);
    $region = trim($_POST['region'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $profession = trim($_POST['profession'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $businessAddress = trim($_POST['business_address'] ?? '');
    $businessScope = trim($_POST['business_scope'] ?? '');

    $pdo = connectDB();
    $stmt = $pdo->prepare('UPDATE users SET gender = ?, age = ?, region = ?, bio = ?, profession = ?, expertise = ?, business_name = ?, business_address = ?, business_scope = ? WHERE id = ?');
    $stmt->execute([$gender ?: null, $age ?: null, $region ?: null, $bio ?: null, $profession ?: null, $expertise ?: null, $businessName ?: null, $businessAddress ?: null, $businessScope ?: null, $user['id']]);

    reloadCurrentUser();
    redirect('/profile.php');
} else {
    redirect('/profile.php');
}
?>