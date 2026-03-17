<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? '点燃 - 点燃生活，遇见未来'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-pM5Pd0hYhG0Tt3f+ExhYH8Bm2qP5L0MfW6T7QtQ1eZ4rXdQ6OGppoZ0EJx3NxP4iAzbN1XndqTY+S9V6/gh6kw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/">点燃</a>
            <span class="navbar-text">点燃生活，遇见未来</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/activities.php">活动</a></li>
                    <li class="nav-item"><a class="nav-link" href="/users.php">用户</a></li>
                    <?php 
                    if (isLoggedIn()):
                        $user = $user ?? getCurrentUser();
                    ?>
                        <li class="nav-item"><a class="nav-link" href="/chat.php">聊天</a></li>
                        <li class="nav-item"><a class="nav-link" href="/profile.php">个人资料</a></li>
                        <?php if ($user && ($user['role'] === 'mediator' || $user['role'] === 'merchant')): ?>
                            <li class="nav-item"><a class="nav-link" href="/certifications.php">认证</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="/reviews.php">评价</a></li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link" href="/admin.php">管理</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()):
                        $user = $user ?? getCurrentUser();
                        if ($user):
                    ?>
                        <li class="nav-item"><span class="nav-link">欢迎, <?php echo htmlspecialchars($user['nickname'] ?? '用户'); ?></span></li>
                        <li class="nav-item"><a class="nav-link" href="/logout.php">退出</a></li>
                    <?php 
                        endif;
                    else: ?>
                        <li class="nav-item"><a class="nav-link" href="/login.php">登录</a></li>
                        <li class="nav-item"><a class="nav-link" href="/register.php">注册</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">