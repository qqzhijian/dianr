<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    session_destroy();
}

redirect('/');
?>