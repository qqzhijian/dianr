<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('/');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($mobile) && empty($email)) {
        $errors[] = '手机号或邮箱至少填写一个';
    }
    if (empty($nickname) || empty($password)) {
        $errors[] = '昵称和密码不能为空';
    }
    if (!in_array($role, ['user', 'mediator', 'merchant'])) {
        $errors[] = '角色无效';
    }

    if (empty($errors)) {
        $pdo = connectDB();
        // Check if mobile or email exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE (mobile = ? OR email = ?) AND is_deleted = 0 LIMIT 1');
        $stmt->execute([$mobile, $email]);
        if ($stmt->fetch()) {
            $errors[] = '手机号或邮箱已被注册';
        } else {
            $passwordHash = hashPassword($password);
            $stmt = $pdo->prepare('INSERT INTO users (mobile, email, password_hash, nickname, role) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$mobile ?: null, $email ?: null, $passwordHash, $nickname, $role]);
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

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">注册</div>
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
                        <label for="mobile" class="form-label">手机号（可选）</label>
                        <input type="text" class="form-control" id="mobile" name="mobile">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">邮箱（可选）</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="nickname" class="form-label">昵称</label>
                        <input type="text" class="form-control" id="nickname" name="nickname" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">角色</label>
                        <select class="form-control" id="role" name="role">
                            <option value="user">用户</option>
                            <option value="mediator">媒人</option>
                            <option value="merchant">商家</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">注册</button>
                </form>
                <p class="mt-3">已有账号？<a href="/login.php">登录</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>