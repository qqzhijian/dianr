<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('/');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($mobile) || empty($password)) {
        $errors[] = '手机号和密码不能为空';
    } else {
        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE mobile = ? AND is_deleted = 0 LIMIT 1');
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            updateLastSeen();
            redirect('/');
        } else {
            $errors[] = '手机号或密码错误';
        }
    }
}

$title = '登录';
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>欢迎回来</h2>
            <p>登录您的DianR账号</p>
        </div>

        <div class="auth-form">
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="mobile" class="form-label">手机号</label>
                    <input type="text" class="form-input" id="mobile" name="mobile"
                           placeholder="请输入手机号" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">密码</label>
                    <input type="password" class="form-input" id="password" name="password"
                           placeholder="请输入密码" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">登录</button>
            </form>
        </div>

        <div class="auth-footer">
            <p>还没有账号？<a href="/register.php" class="link">立即注册</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>