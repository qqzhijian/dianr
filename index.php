<?php
require_once 'config/config.php';
$title = '首页';
$user = isLoggedIn() ? getCurrentUser() : null;
$pdo = connectDB();
include 'includes/header.php';
?>

<div class="hero-section">
    <h1>点燃生活，遇见未来</h1>
    <a href="/register.php" class="cta-button">立即加入</a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="feature-card">
            <i class="fas fa-users"></i>
            <h3>多样化角色</h3>
            <p>用户、媒人、商家三方互动，满足不同需求</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-card">
            <i class="fas fa-shield-alt"></i>
            <h3>安全可信</h3>
            <p>实名认证体系，保护用户隐私安全</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-card">
            <i class="fas fa-star"></i>
            <h3>优质服务</h3>
            <p>评价系统确保服务质量</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">最新活动</div>
            <div class="card-body">
                <?php
                $stmt = $pdo->query("SELECT a.*, u.nickname as creator_name FROM activities a JOIN users u ON a.creator_id = u.id ORDER BY a.created_at DESC LIMIT 5");
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($activities)) {
                    echo '<p>暂无活动，<a href="/register.php">注册</a>成为媒人或商家发布活动</p>';
                } else {
                    foreach ($activities as $activity) {
                        echo "<div class='mb-2'><strong>{$activity['title']}</strong> - {$activity['creator_name']} - {$activity['event_time']}</div>";
                    }
                }
                ?>
                <a href="/activities.php" class="btn btn-primary">查看所有活动</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">在线用户</div>
            <div class="card-body">
                <?php
                if (isLoggedIn()) {
                    // Fallback to last_seen. `is_online` is not in schema.
                    $threshold = ONLINE_THRESHOLD;
                    $since = date('Y-m-d H:i:s', time() - $threshold);
                    $stmt = $pdo->prepare('SELECT id, nickname, last_seen FROM users WHERE last_seen >= ? AND id != ? ORDER BY last_seen DESC LIMIT 10');
                    $stmt->execute([$since, $_SESSION['user_id']]);
                    $onlineUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($onlineUsers)) {
                        echo '<p>暂无在线用户</p>';
                    } else {
                        foreach ($onlineUsers as $onlineUser) {
                            $status = getOnlineStatus($onlineUser['last_seen']);
                            echo "<div><span class='online-status {$status}'></span> " . htmlspecialchars($onlineUser['nickname']) . "</div>";
                        }
                    }
                } else {
                    echo '<p>登录后查看在线用户</p>';
                }
                ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">平台统计</div>
            <div class="card-body">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_blacklisted = 0");
                $userCount = $stmt->fetch()['count'];
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM activities");
                $activityCount = $stmt->fetch()['count'];
                ?>
                <p><i class="fas fa-users"></i> 注册用户: <?php echo $userCount; ?></p>
                <p><i class="fas fa-calendar"></i> 活动数量: <?php echo $activityCount; ?></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>