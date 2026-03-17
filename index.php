<?php
/**
 * 文件名: index.php
 * 
 * 平台首页
 * 
 * 功能说明：
 * - 显示平台介绍和特色功能
 * - 显示最新的5条活动
 * - 显示在线用户列表（仅登录用户可见）
 * - 显示平台统计信息（用户数、活动数）
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'config/config.php';

// ==================== 页面准备 ====================

// 页面标题
$title = '首页';

// 获取当前登录用户信息（如果已登录）
$user = isLoggedIn() ? getCurrentUser() : null;

// 获取数据库连接
$pdo = connectDB();

// 加载页面头部模板
include 'includes/header.php';
?>

<!-- ==================== 英雄区域 ==================== -->
<div class="hero-section">
    <h1>点燃生活，遇见未来</h1>
    <a href="/register.php" class="cta-button">立即加入</a>
</div>

<!-- ==================== 平台特色展示 ==================== -->
<div class="row">
    <!-- 特色1：多样化角色 -->
    <div class="col-md-4">
        <div class="feature-card">
            <i class="fas fa-users"></i>
            <h3>多样化角色</h3>
            <p>用户、媒人、商家三方互动，满足不同需求</p>
        </div>
    </div>

    <!-- 特色2：安全可信 -->
    <div class="col-md-4">
        <div class="feature-card">
            <i class="fas fa-shield-alt"></i>
            <h3>安全可信</h3>
            <p>实名认证体系，保护用户隐私安全</p>
        </div>
    </div>

    <!-- 特色3：优质服务 -->
    <div class="col-md-4">
        <div class="feature-card">
            <i class="fas fa-star"></i>
            <h3>优质服务</h3>
            <p>评价系统确保服务质量</p>
        </div>
    </div>
</div>

<!-- ==================== 主要内容区域 ==================== -->
<div class="row">
    <!-- 左侧：最新活动列表 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">最新活动</div>
            <div class="card-body">
                <?php
                /**
                 * 查询最新的5条活动
                 * 包含活动创建者的昵称
                 * 按创建时间倒序排列（最新的优先）
                 */
                $stmt = $pdo->prepare('
                    SELECT 
                        a.id,
                        a.title,
                        a.description,
                        a.event_time,
                        a.created_at,
                        u.id AS creator_id,
                        u.nickname AS creator_name
                    FROM activities a
                    JOIN users u ON a.creator_id = u.id
                    ORDER BY a.created_at DESC
                    LIMIT 5
                ');
                $stmt->execute();
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 显示活动列表或提示信息
                if (empty($activities)) {
                    echo '<p>暂无活动，<a href="/register.php">注册</a>成为媒人或商家发布活动</p>';
                } else {
                    // 逐条显示活动
                    foreach ($activities as $activity) {
                        // 转义输出以防止XSS攻击
                        $title = htmlspecialchars($activity['title']);
                        $creatorName = htmlspecialchars($activity['creator_name']);
                        $eventTime = htmlspecialchars($activity['event_time']);
                        
                        echo "<div class='mb-2'>";
                        echo "<strong>{$title}</strong> ";
                        echo "- {$creatorName} ";
                        echo "- {$eventTime}";
                        echo "</div>";
                    }
                }
                ?>
                <a href="/activities.php" class="btn btn-primary">查看所有活动</a>
            </div>
        </div>
    </div>

    <!-- 右侧栏 -->
    <div class="col-md-4">
        <!-- 在线用户显示 -->
        <div class="card">
            <div class="card-header">在线用户</div>
            <div class="card-body">
                <?php
                if (isLoggedIn()) {
                    // 只有登录用户才能查看在线用户列表
                    
                    // 计算在线阈值时间戳
                    $threshold = ONLINE_THRESHOLD;
                    $sinceTimestamp = time() - $threshold;
                    $since = date('Y-m-d H:i:s', $sinceTimestamp);
                    
                    // 查询在线用户（排除当前用户）
                    // 在线用户定义：最后活动时间在指定阈值内
                    $stmt = $pdo->prepare('
                        SELECT id, nickname, last_seen 
                        FROM users 
                        WHERE last_seen >= ? 
                        AND id != ? 
                        AND is_deleted = 0
                        ORDER BY last_seen DESC 
                        LIMIT 10
                    ');
                    $stmt->execute([$since, $_SESSION['user_id']]);
                    $onlineUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($onlineUsers)) {
                        // 没有在线用户
                        echo '<p>暂无在线用户</p>';
                    } else {
                        // 显示在线用户列表
                        foreach ($onlineUsers as $onlineUser) {
                            // 判定用户在线状态（在线/离线）
                            $status = getOnlineStatus($onlineUser['last_seen']);
                            // 转义用户昵称
                            $nickname = htmlspecialchars($onlineUser['nickname']);
                            
                            echo "<div>";
                            echo "<span class='online-status {$status}'></span> ";
                            echo "{$nickname}";
                            echo "</div>";
                        }
                    }
                } else {
                    // 未登录用户提示
                    echo '<p>登录后查看在线用户</p>';
                }
                ?>
            </div>
        </div>

        <!-- 平台统计信息 -->
        <div class="card mt-3">
            <div class="card-header">平台统计</div>
            <div class="card-body">
                <?php
                /**
                 * 统计：注册用户总数（排除黑名单用户）
                 */
                $stmt = $pdo->query('
                    SELECT COUNT(*) as count 
                    FROM users 
                    WHERE is_blacklisted = 0 AND is_deleted = 0
                ');
                $userCount = $stmt->fetch()['count'];
                
                /**
                 * 统计：创建的活动总数
                 */
                $stmt = $pdo->query('
                    SELECT COUNT(*) as count 
                    FROM activities
                ');
                $activityCount = $stmt->fetch()['count'];
                ?>
                
                <!-- 用户数统计 -->
                <p>
                    <i class="fas fa-users"></i> 
                    注册用户: <strong><?php echo $userCount; ?></strong>
                </p>
                
                <!-- 活动数统计 -->
                <p>
                    <i class="fas fa-calendar"></i> 
                    活动数量: <strong><?php echo $activityCount; ?></strong>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>