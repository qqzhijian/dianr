<?php
require_once 'config/config.php';

$user = getCurrentUser();

$title = '用户';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">用户列表</div>
            <div class="card-body">
                <?php
                $pdo = connectDB();
                $stmt = $pdo->query("SELECT id, nickname, mobile, role, last_seen FROM users WHERE is_blacklisted = 0 AND is_deleted = 0 ORDER BY last_seen DESC");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($users)) {
                    echo '<p>暂无用户</p>';
                } else {
                    echo '<div class="row">';
                    foreach ($users as $u) {
                        $status = getOnlineStatus($u['last_seen']);
                        $mobile = $u['mobile'] ? maskMobile($u['mobile']) : '未填写';
                        echo "<div class='col-md-4 mb-3'>";
                        echo "<div class='card'>";
                        echo "<div class='card-body'>";
                        echo "<h5>{$u['nickname']}</h5>";
                        echo "<p>手机号: {$mobile}</p>";
                        echo "<p>角色: {$u['role']}</p>";
                        echo "<span class='online-status {$status}'></span>";
                        if ($user && $u['id'] != $user['id']) {
                            echo "<a href='/profile.php?id={$u['id']}' class='btn btn-sm btn-primary'>查看资料</a>";
                            echo "<a href='/chat.php?user={$u['id']}' class='btn btn-sm btn-secondary'>聊天</a>";
                        }
                        echo "</div></div></div>";
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>