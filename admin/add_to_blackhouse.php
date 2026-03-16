<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $reason = sanitize($_POST['reason'] ?? '');

    if ($user_id && $reason) {
        $pdo = connectDB();
        $stmt = $pdo->prepare("INSERT INTO blackhouse (user_id, banned_by, reason) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $_SESSION['admin_id'], $reason]);
    }
}

redirect('/admin/blackhouse.php');
?>