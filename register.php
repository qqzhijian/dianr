<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('/');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact = sanitize($_POST['contact'] ?? '');
    $contact_type = $_POST['contact_type'] ?? 'phone';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $nickname = sanitize($_POST['nickname'] ?? '');
    $role = $_POST['role'] ?? 'user';

    // Validation
    if (empty($contact)) {
        $errors[] = '请输入邮箱或手机号';
    } elseif ($contact_type === 'phone' && !preg_match('/^1[3-9]\d{9}$/', $contact)) {
        $errors[] = '请输入有效的手机号';
    } elseif ($contact_type === 'email' && !filter_var($contact, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '请输入有效的邮箱地址';
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = '密码至少6位';
    }
    if ($password !== $confirm_password) {
        $errors[] = '密码确认不匹配';
    }
    if (empty($nickname)) {
        $errors[] = '请输入昵称';
    }
    if (!in_array($role, ['user', 'mediator', 'merchant'])) {
        $errors[] = '无效角色';
    }

    if (empty($errors)) {
        $pdo = connectDB();
        // Check if contact exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE contact = ?");
        $stmt->execute([$contact]);
        if ($stmt->fetch()) {
            $errors[] = '该邮箱或手机号已注册';
        } else {
            // Insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (contact, contact_type, password, nickname, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$contact, $contact_type, $hashed_password, $nickname, $role])) {
                // Insert role-specific data
                $user_id = $pdo->lastInsertId();
                if ($role === 'mediator') {
                    $stmt = $pdo->prepare("INSERT INTO mediators (user_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                } elseif ($role === 'merchant') {
                    $stmt = $pdo->prepare("INSERT INTO merchants (user_id, name) VALUES (?, ?)");
                    $stmt->execute([$user_id, sanitize($_POST['merchant_name'] ?? '')]);
                }
                $success = '注册成功，请登录';
            } else {
                $errors[] = '注册失败，请重试';
            }
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
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="contact_type" class="form-label">注册方式</label>
                        <select class="form-select" id="contact_type" name="contact_type" required>
                            <option value="phone">手机号</option>
                            <option value="email">邮箱</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="contact" class="form-label" id="contact_label">手机号</label>
                        <input type="text" class="form-control" id="contact" name="contact" required>
                        <div class="form-text">注册后可通过此方式接收重要通知</div>
                    </div>

                    <div class="mb-3">
                        <label for="nickname" class="form-label">昵称</label>
                        <input type="text" class="form-control" id="nickname" name="nickname" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">确认密码</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">角色</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user">用户 - 寻找交友机会</option>
                            <option value="mediator">媒人 - 专业撮合服务</option>
                            <option value="merchant">商家 - 提供活动场地</option>
                        </select>
                    </div>

                    <div id="merchant-fields" style="display: none;">
                        <div class="mb-3">
                            <label for="merchant_name" class="form-label">商家名称</label>
                            <input type="text" class="form-control" id="merchant_name" name="merchant_name">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">立即注册</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('contact_type').addEventListener('change', function() {
    const label = document.getElementById('contact_label');
    const input = document.getElementById('contact');
    if (this.value === 'email') {
        label.textContent = '邮箱地址';
        input.type = 'email';
        input.placeholder = '请输入邮箱地址';
    } else {
        label.textContent = '手机号';
        input.type = 'tel';
        input.placeholder = '请输入手机号';
    }
});

document.getElementById('role').addEventListener('change', function() {
    const merchantFields = document.getElementById('merchant-fields');
    if (this.value === 'merchant') {
        merchantFields.style.display = 'block';
    } else {
        merchantFields.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>