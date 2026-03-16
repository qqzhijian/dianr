<?php
require_once 'config/config.php';

requireAdmin();

$title = '后台管理';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">后台管理</div>
            <div class="card-body">
                <h5>用户管理</h5>
                <p>管理用户、黑名单、小黑屋等。</p>
                <h5>活动管理</h5>
                <p>审核和管理活动。</p>
                <h5>数据统计</h5>
                <p>查看平台统计数据。</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>