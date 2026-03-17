<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('/');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($nickname) || empty($password)) {
        $errors[] = '昵称和密码不能为空';
    }
    if (strlen($password) < 6) {
        $errors[] = '密码至少需要6位';
    }

    if (empty($errors)) {
        $pdo = connectDB();
        // Check if nickname exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE nickname = ? AND is_deleted = 0 LIMIT 1');
        $stmt->execute([$nickname]);
        if ($stmt->fetch()) {
            $errors[] = '昵称已被使用';
        } else {
            $passwordHash = hashPassword($password);
            $stmt = $pdo->prepare('INSERT INTO users (password_hash, nickname, role) VALUES (?, ?, ?)');
            $stmt->execute([$passwordHash, $nickname, $role]);
            $userId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;
            updateLastSeen();
            redirect('/');
        }
    }
}

$title = '注册';
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>加入点燃</h2>
            <p>开启你的美好邂逅</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="nickname" class="form-label">昵称</label>
                <input type="text" class="form-input" id="nickname" name="nickname" placeholder="给自己起个好听的名字" value="<?php echo htmlspecialchars($_POST['nickname'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">密码</label>
                <input type="password" class="form-input" id="password" name="password" placeholder="设置登录密码（至少6位）" required>
            </div>

            <input type="hidden" name="role" value="user">

            <button type="submit" class="btn btn-primary btn-full">立即注册</button>
        </form>

        <div class="auth-footer">
            <p>已有账号？<a href="/login.php" class="link">去登录</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>