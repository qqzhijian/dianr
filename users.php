<?php
/**
 * 文件名: users.php
 * 
 * 用户列表显示页面
 * 
 * 功能说明：
 * - 显示所有未被删除且未被黑名单的用户列表
 * - 显示用户昵称、角色、认证状态、在线状态
 * - 支持查看用户详细资料
 * - 支持与用户发起聊天
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'config/config.php';

// ==================== 页面准备 ====================

// 获取当前登录用户
$user = isLoggedIn() ? getCurrentUser() : null;

// 页面标题
$title = '用户';

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">用户列表</div>
            <div class="card-body">
                <?php
                /**
                 * 查询所有有效用户
                 * 条件：未被删除（is_deleted=0）且未被黑名单（is_blacklisted=0）
                 * 排序：按最后活动时间倒序（最近活跃的用户优先）
                 */
                $pdo = connectDB();
                $stmt = $pdo->prepare('
                    SELECT 
                        id, 
                        nickname, 
                        mobile, 
                        role, 
                        last_seen, 
                        is_verified 
                    FROM users 
                    WHERE is_blacklisted = 0 AND is_deleted = 0 
                    ORDER BY last_seen DESC
                ');
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 判定是否有用户
                if (empty($users)) {
                    echo '<p>暂无用户</p>';
                } else {
                    // 以卡片网格形式显示用户
                    echo '<div class="row">';
                    
                    foreach ($users as $userData) {
                        // 判定用户在线状态
                        $status = getOnlineStatus($userData['last_seen']);
                        
                        // 格式化手机号（隐藏中间数字）
                        $maskedMobile = $userData['mobile'] ? maskMobile($userData['mobile']) : '未填写';
                        
                        // 转义HTML
                        $nickname = htmlspecialchars($userData['nickname']);
                        $role = htmlspecialchars($userData['role']);
                        
                        echo "<div class='col-md-4 mb-3'>";
                        echo "<div class='card'>";
                        echo "<div class='card-body'>";
                        
                        // 用户昵称和认证标志
                        echo "<h5>{$nickname}";
                        if ($userData['is_verified']) {
                            echo " <i class='fas fa-check-circle text-success' title='已认证'></i>";
                        }
                        echo "</h5>";
                        
                        // 手机号（掩码显示）
                        echo "<p>手机号: {$maskedMobile}</p>";
                        
                        // 用户角色
                        echo "<p>角色: {$role}</p>";
                        
                        // 在线状态指示器
                        echo "<span class='online-status {$status}'></span>";
                        
                        // 操作按钮（仅对其他用户显示）
                        if ($user && $userData['id'] != $user['id']) {
                            echo "<a href='/profile.php?id={$userData['id']}' class='btn btn-sm btn-primary'>查看资料</a> ";
                            echo "<a href='/chat.php?user={$userData['id']}' class='btn btn-sm btn-secondary'>聊天</a>";
                        }
                        
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>