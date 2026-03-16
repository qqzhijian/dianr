<?php
require_once 'config/config.php';

$title = '用户列表';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>用户列表</h2>
        <p>浏览平台用户，找到合适的交友对象或合作伙伴。</p>

        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info">
                <strong>提示：</strong>注册后可以查看详细资料、发送消息和参与更多互动。
                <a href="/register.php" class="btn btn-primary btn-sm">立即注册</a>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php
            $pdo = connectDB();
            $stmt = $pdo->query("SELECT u.id, u.nickname, u.role, u.gender, u.age, u.region, u.last_seen FROM users u WHERE u.is_blacklisted = 0 ORDER BY u.last_seen DESC LIMIT 50");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($users as $user) {
                $status = getOnlineStatus($user['last_seen']);
                $role_text = $user['role'] === 'user' ? '用户' : ($user['role'] === 'mediator' ? '媒人' : '商家');
                echo "<div class='col-md-4 mb-3'>";
                echo "<div class='card'>";
                echo "<div class='card-body'>";
                echo "<h5 class='card-title'>{$user['nickname']}</h5>";
                echo "<p class='card-text'>";
                echo "<span class='online-status {$status}'></span> {$role_text}<br>";
                if ($user['gender']) echo "性别: " . ($user['gender'] === 'male' ? '男' : ($user['gender'] === 'female' ? '女' : '其他')) . "<br>";
                if ($user['age']) echo "年龄: {$user['age']}<br>";
                if ($user['region']) echo "地区: {$user['region']}<br>";
                echo "</p>";
                if (isLoggedIn()) {
                    echo "<a href='/profile.php?id={$user['id']}' class='btn btn-primary'>查看资料</a>";
                    echo " <a href='/chat.php?user={$user['id']}' class='btn btn-success'>发送消息</a>";
                } else {
                    echo "<a href='/register.php' class='btn btn-outline-primary'>注册查看详情</a>";
                }
                echo "</div></div></div>";
            }
            ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>