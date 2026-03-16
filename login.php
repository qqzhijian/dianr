<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact = sanitize($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($contact) || empty($password)) {
        $errors[] = '请输入邮箱/手机号和密码';
    } else {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE contact = ?");
        $stmt->execute([$contact]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_blacklisted']) {
                $errors[] = '您的账户已被禁用';
            } else {
                $_SESSION['user_id'] = $user['id'];
                // Update online status
                $stmt = $pdo->prepare("UPDATE users SET is_online = 1 WHERE id = ?");
                $stmt->execute([$user['id']]);
                redirect('/');
            }
        } else {
            $errors[] = '邮箱/手机号或密码错误';
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
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="contact" class="form-label">邮箱/手机号</label>
                        <input type="text" class="form-control" id="contact" name="contact" required placeholder="请输入邮箱或手机号">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">登录</button>
                </form>

                <p class="mt-3 text-center">还没有账户？ <a href="/register.php" class="text-decoration-none">立即注册</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>