<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$title = '黑名单管理';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="list-group">
            <a href="/admin/" class="list-group-item list-group-item-action">概览</a>
            <a href="/admin/users.php" class="list-group-item list-group-item-action">用户管理</a>
            <a href="/admin/activities.php" class="list-group-item list-group-item-action">活动管理</a>
            <a href="/admin/blacklist.php" class="list-group-item list-group-item-action active">黑名单管理</a>
            <a href="/admin/blackhouse.php" class="list-group-item list-group-item-action">小黑屋管理</a>
        </div>
    </div>

    <div class="col-md-9">
        <h2>用户黑名单</h2>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>用户</th>
                    <th>被拉黑用户</th>
                    <th>拉黑时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pdo = connectDB();
                $stmt = $pdo->query("SELECT b.*, u1.nickname as blocker_name, u2.nickname as blocked_name FROM blacklist b JOIN users u1 ON b.user_id = u1.id JOIN users u2 ON b.blocked_user_id = u2.id ORDER BY b.blocked_at DESC");
                $blacklist = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($blacklist as $entry) {
                    echo "<tr>";
                    echo "<td>{$entry['blocker_name']}</td>";
                    echo "<td>{$entry['blocked_name']}</td>";
                    echo "<td>{$entry['blocked_at']}</td>";
                    echo "<td><a href='/admin/unblock.php?id={$entry['id']}' class='btn btn-sm btn-warning' onclick='return confirm(\"确定要解除拉黑吗？\")'>解除</a></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>