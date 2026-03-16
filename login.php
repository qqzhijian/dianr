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

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">登录</div>
            <div class="card-body">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="mobile" class="form-label">手机号</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">登录</button>
                </form>
                <p class="mt-3">还没有账号？<a href="/register.php">注册</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>