<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
if (!$user) {
    redirect('/login.php');
}

if (isset($user['is_verified']) && $user['is_verified']) {
    redirect('/');
}

$title = '验证';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">账号验证</div>
            <div class="card-body">
                <p>为了使用聊天等功能，请验证您的账号。</p>
                <form method="post" action="/do_verify.php">
                    <div class="mb-3">
                        <label for="code" class="form-label">验证码</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <button type="submit" class="btn btn-primary">验证</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>