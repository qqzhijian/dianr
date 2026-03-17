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
    $code = trim($_POST['code'] ?? '');
    // Simple verification, in real app use SMS/email
    if ($code === '123456') { // dummy code
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
        $stmt->execute([$user['id']]);
        reloadCurrentUser();
        redirect('/');
    } else {
        $error = '验证码错误';
    }
}

redirect('/verify.php');
?>