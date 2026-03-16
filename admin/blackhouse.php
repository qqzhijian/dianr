<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$title = '小黑屋管理';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="list-group">
            <a href="/admin/" class="list-group-item list-group-item-action">概览</a>
            <a href="/admin/users.php" class="list-group-item list-group-item-action">用户管理</a>
            <a href="/admin/activities.php" class="list-group-item list-group-item-action">活动管理</a>
            <a href="/admin/blacklist.php" class="list-group-item list-group-item-action">黑名单管理</a>
            <a href="/admin/blackhouse.php" class="list-group-item list-group-item-action active">小黑屋管理</a>
        </div>
    </div>

    <div class="col-md-9">
        <h2>小黑屋用户</h2>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>用户</th>
                    <th>原因</th>
                    <th>禁入时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pdo = connectDB();
                $stmt = $pdo->query("SELECT b.*, u.nickname as user_name FROM blackhouse b JOIN users u ON b.user_id = u.id ORDER BY b.banned_at DESC");
                $banned = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($banned as $entry) {
                    echo "<tr>";
                    echo "<td>{$entry['user_name']}</td>";
                    echo "<td>{$entry['reason']}</td>";
                    echo "<td>{$entry['banned_at']}</td>";
                    echo "<td><a href='/admin/unban_from_blackhouse.php?id={$entry['id']}' class='btn btn-sm btn-success' onclick='return confirm(\"确定要释放此用户吗？\")'>释放</a></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <h3>添加用户到小黑屋</h3>
        <form method="post" action="/admin/add_to_blackhouse.php">
            <div class="mb-3">
                <label for="user_id" class="form-label">用户ID</label>
                <input type="number" class="form-control" id="user_id" name="user_id" required>
            </div>
            <div class="mb-3">
                <label for="reason" class="form-label">原因</label>
                <textarea class="form-control" id="reason" name="reason" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger">添加</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>