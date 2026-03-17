<?php
/**
 * 文件名: activities.php
 * 
 * 活动管理和列表页面
 * 
 * 功能说明：
 * - 显示所有活动列表
 * - 登录用户可以报名活动
 * - 媒人和商家用户可以创建新活动
 * - 显示活动详细信息（标题、描述、时间、地点、创建者）
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'config/config.php';

// ==================== 权限检查 ====================

// 获取当前登录用户
$user = isLoggedIn() ? getCurrentUser() : null;

// 判定当前用户是否有权创建活动
// 仅媒人(mediator)和商家(merchant)可以创建活动
$canCreate = $user && isset($user['role']) && in_array($user['role'], ['mediator', 'merchant']);

// 页面标题
$title = '活动';

include 'includes/header.php';
?>

<div class="row">
    <!-- 左侧：活动列表 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">活动列表</div>
            <div class="card-body">
                <?php
                /**
                 * 查询所有活动，包含创建者信息
                 * 排序：按创建时间倒序（最新的活动优先）
                 */
                $pdo = connectDB();
                $stmt = $pdo->prepare('
                    SELECT 
                        a.id,
                        a.title,
                        a.description,
                        a.event_time,
                        a.location,
                        a.creator_id,
                        a.created_at,
                        u.id AS creator_id,
                        u.nickname AS creator_name
                    FROM activities a
                    JOIN users u ON a.creator_id = u.id
                    ORDER BY a.created_at DESC
                ');
                $stmt->execute();
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 判定是否有活动
                if (empty($activities)) {
                    echo '<p>暂无活动</p>';
                } else {
                    // 逐条显示活动
                    foreach ($activities as $activity) {
                        // 转义HTML防止XSS
                        $title = htmlspecialchars($activity['title']);
                        $description = htmlspecialchars($activity['description']);
                        $eventTime = htmlspecialchars($activity['event_time']);
                        $location = htmlspecialchars($activity['location']);
                        $creatorName = htmlspecialchars($activity['creator_name']);
                        
                        echo "<div class='mb-3 p-3 border rounded'>";
                        echo "<h5>{$title}</h5>";
                        echo "<p>{$description}</p>";
                        echo "<small>";
                        echo "时间: {$eventTime} | ";
                        echo "地点: {$location} | ";
                        echo "创建者: {$creatorName}";
                        echo "</small>";
                        
                        // 报名按钮和状态显示（仅登录用户）
                        if ($user) {
                            // 检查当前用户是否已报名此活动
                            $stmt = $pdo->prepare('
                                SELECT id 
                                FROM activity_signups 
                                WHERE activity_id = ? AND user_id = ?
                            ');
                            $stmt->execute([$activity['id'], $user['id']]);
                            $signedUp = $stmt->fetch();
                            
                            if ($signedUp) {
                                // 用户已报名，显示已报名标签
                                echo "<span class='badge bg-success'>已报名</span>";
                            } else {
                                // 用户未报名，显示报名按钮
                                echo "<a href='/signup_activity.php?id={$activity['id']}' class='btn btn-sm btn-primary'>报名</a>";
                            }
                        }
                        
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- 右侧：创建活动表单（仅媒人和商家可见） -->
    <div class="col-md-4">
        <?php if ($canCreate): ?>
            <div class="card">
                <div class="card-header">创建活动</div>
                <div class="card-body">
                    <form method="post" action="/create_activity.php">
                        <!-- 活动标题 -->
                        <div class="mb-3">
                            <label for="title" class="form-label">标题</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="title" 
                                name="title" 
                                placeholder="活动名称"
                                required
                            >
                        </div>

                        <!-- 活动描述 -->
                        <div class="mb-3">
                            <label for="description" class="form-label">描述</label>
                            <textarea 
                                class="form-control" 
                                id="description" 
                                name="description"
                                placeholder="活动详情说明"
                            ></textarea>
                        </div>

                        <!-- 活动时间 -->
                        <div class="mb-3">
                            <label for="event_time" class="form-label">时间</label>
                            <input 
                                type="datetime-local" 
                                class="form-control" 
                                id="event_time" 
                                name="event_time"
                                required
                            >
                        </div>

                        <!-- 活动地点 -->
                        <div class="mb-3">
                            <label for="location" class="form-label">地点</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="location" 
                                name="location"
                                placeholder="活动举办地点"
                            >
                        </div>

                        <!-- 提交按钮 -->
                        <button type="submit" class="btn btn-primary">创建</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>