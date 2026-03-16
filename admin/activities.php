<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$title = '活动管理';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="list-group">
            <a href="/admin/" class="list-group-item list-group-item-action">概览</a>
            <a href="/admin/users.php" class="list-group-item list-group-item-action">用户管理</a>
            <a href="/admin/activities.php" class="list-group-item list-group-item-action active">活动管理</a>
            <a href="/admin/blacklist.php" class="list-group-item list-group-item-action">黑名单管理</a>
            <a href="/admin/blackhouse.php" class="list-group-item list-group-item-action">小黑屋管理</a>
        </div>
    </div>

    <div class="col-md-9">
        <h2>活动管理</h2>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>标题</th>
                    <th>创建者</th>
                    <th>时间</th>
                    <th>地点</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pdo = connectDB();
                $stmt = $pdo->query("SELECT a.*, u.nickname as creator_name FROM activities a JOIN users u ON a.creator_id = u.id ORDER BY a.created_at DESC");
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($activities as $activity) {
                    echo "<tr>";
                    echo "<td>{$activity['id']}</td>";
                    echo "<td>{$activity['title']}</td>";
                    echo "<td>{$activity['creator_name']}</td>";
                    echo "<td>{$activity['event_time']}</td>";
                    echo "<td>{$activity['location']}</td>";
                    echo "<td><a href='/admin/delete_activity.php?id={$activity['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"确定要删除此活动吗？\")'>删除</a></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>