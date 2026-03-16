<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();

if ($user['is_verified']) {
    redirect('/');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = sanitize($_POST['verification_code'] ?? '');

    // In a real implementation, you would check the code against a stored code
    // For demo purposes, we'll accept any 6-digit code
    if (preg_match('/^\d{6}$/', $verification_code)) {
        $pdo = connectDB();
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        if ($stmt->execute([$user['id']])) {
            $success = '验证成功！';
            // Refresh user data
            $_SESSION['user'] = null; // Clear cache
        } else {
            $errors[] = '验证失败，请重试';
        }
    } else {
        $errors[] = '请输入6位数字验证码';
    }
}

$title = '验证账号';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">验证您的<?php echo $user['contact_type'] === 'email' ? '邮箱' : '手机号'; ?></div>
            <div class="card-body">
                <p>我们已向 <strong><?php echo maskContact($user['contact'], $user['contact_type']); ?></strong> 发送了验证码。</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?> <a href="/">返回首页</a></div>
                <?php else: ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="verification_code" class="form-label">验证码</label>
                            <input type="text" class="form-control" id="verification_code" name="verification_code" required maxlength="6" pattern="\d{6}" placeholder="请输入6位数字验证码">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">验证</button>
                    </form>
                    <p class="mt-3 text-center"><a href="#" onclick="resendCode()">重新发送验证码</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function resendCode() {
    alert('验证码已重新发送，请查收');
    // In real implementation, trigger resend
}
</script>

<?php include 'includes/footer.php'; ?>