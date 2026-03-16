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

$title = '认证';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">认证管理</div>
            <div class="card-body">
                <p>认证功能开发中...</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>