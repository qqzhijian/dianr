<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();

$title = '评价';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">评价系统</div>
            <div class="card-body">
                <p>评价功能开发中...</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>