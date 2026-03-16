<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$title = '后台管理';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="list-group">
            <a href="/admin/" class="list-group-item list-group-item-action active">概览</a>
            <a href="/admin/users.php" class="list-group-item list-group-item-action">用户管理</a>
            <a href="/admin/activities.php" class="list-group-item list-group-item-action">活动管理</a>
            <a href="/admin/blacklist.php" class="list-group-item list-group-item-action">黑名单管理</a>
            <a href="/admin/blackhouse.php" class="list-group-item list-group-item-action">小黑屋管理</a>
        </div>
    </div>

    <div class="col-md-9">
        <h2>后台概览</h2>

        <div class="row">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">总用户数</h5>
                        <?php
                        $pdo = connectDB();
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                        $count = $stmt->fetch()['count'];
                        ?>
                        <p class="card-text"><?php echo $count; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">活动数</h5>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM activities");
                        $count = $stmt->fetch()['count'];
                        ?>
                        <p class="card-text"><?php echo $count; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">黑名单用户</h5>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM blacklist");
                        $count = $stmt->fetch()['count'];
                        ?>
                        <p class="card-text"><?php echo $count; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">小黑屋用户</h5>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM blackhouse");
                        $count = $stmt->fetch()['count'];
                        ?>
                        <p class="card-text"><?php echo $count; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>