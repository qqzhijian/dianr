<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$title = '用户管理';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="list-group">
            <a href="/admin/" class="list-group-item list-group-item-action">概览</a>
            <a href="/admin/users.php" class="list-group-item list-group-item-action active">用户管理</a>
            <a href="/admin/activities.php" class="list-group-item list-group-item-action">活动管理</a>
            <a href="/admin/blacklist.php" class="list-group-item list-group-item-action">黑名单管理</a>
            <a href="/admin/blackhouse.php" class="list-group-item list-group-item-action">小黑屋管理</a>
        </div>
    </div>

    <div class="col-md-9">
        <h2>用户管理</h2>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>昵称</th>
                    <th>手机号</th>
                    <th>角色</th>
                    <th>注册时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pdo = connectDB();
                $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>{$user['nickname']}</td>";
                    echo "<td>" . maskContact($user['contact'], $user['contact_type']) . "</td>";
                    echo "<td>" . ($user['role'] === 'user' ? '用户' : ($user['role'] === 'mediator' ? '媒人' : '商家')) . "</td>";
                    echo "<td>{$user['created_at']}</td>";
                    echo "<td>";
                    echo "<a href='/profile.php?id={$user['id']}' class='btn btn-sm btn-info'>查看</a> ";
                    if (!$user['is_blacklisted']) {
                        echo "<a href='/admin/ban_user.php?id={$user['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"确定要禁用此用户吗？\")'>禁用</a>";
                    } else {
                        echo "<a href='/admin/unban_user.php?id={$user['id']}' class='btn btn-sm btn-success'>解禁</a>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>