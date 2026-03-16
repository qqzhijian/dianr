<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
if (!in_array($user['role'], ['mediator', 'merchant'])) {
    http_response_code(403);
    echo '权限不足';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eventTime = $_POST['event_time'] ?? '';
    $location = trim($_POST['location'] ?? '');

    if (empty($title) || empty($eventTime)) {
        $error = '标题和时间不能为空';
    } else {
        $pdo = connectDB();
        $stmt = $pdo->prepare('INSERT INTO activities (creator_id, title, description, event_time, location) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user['id'], $title, $description, $eventTime, $location]);
        redirect('/activities.php');
    }
} else {
    redirect('/activities.php');
}
?>