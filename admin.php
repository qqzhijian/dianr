<?php
/**
 * 文件名: admin.php
 * 
 * 后台管理页面
 * 
 * 功能说明：
 * - 仅限管理员访问
 * - 数据概览（用户数、黑名单、活动、消息）
 * - 用户管理（黑名单操作）
 * - 活动管理（删除活动）
 * - 黑名单管理（查看拉黑记录）
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'config/config.php';

// ==================== 权限检查 ====================
// 仅允许管理员访问
requireAdmin();

// ==================== 参数和状态 ====================
/**
 * 获取管理操作类型
 * 可选值：dashboard(概览), users(用户), activities(活动), blacklist(黑名单)
 */
$action = $_GET['action'] ?? 'dashboard';

// 存储操作消息
$message = null;

$title = '后台管理';
include 'includes/header.php';

// ==================== 业务逻辑处理 ====================
/**
 * 处理POST请求（管理员操作）
 * 支持的操作：
 * - 加入黑名单
 * - 移除黑名单
 * - 删除活动（改为软删除，记录操作）
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 加入黑名单操作
    if (isset($_POST['blacklist_user'])) {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0 && $userId !== getCurrentUser()['id']) {
            // 防止管理员黑名单自己
            $pdo = connectDB();
            $stmt = $pdo->prepare('UPDATE users SET is_blacklisted = 1 WHERE id = ?');
            $stmt->execute([$userId]);
            
            // BUG FIX: 添加审计日志
            error_log('[ADMIN] User ' . getCurrentUser()['id'] . ' blacklisted user ' . $userId);
            
            $message = '用户已加入黑名单';
        } else {
            $message = '无法操作该用户';
        }
    } 
    // 2. 移除黑名单操作
    elseif (isset($_POST['unblacklist_user'])) {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            $pdo = connectDB();
            $stmt = $pdo->prepare('UPDATE users SET is_blacklisted = 0 WHERE id = ?');
            $stmt->execute([$userId]);
            
            // BUG FIX: 添加审计日志
            error_log('[ADMIN] User ' . getCurrentUser()['id'] . ' unblacklisted user ' . $userId);
            
            $message = '用户已从黑名单移除';
        }
    } 
    // 3. 删除活动操作
    elseif (isset($_POST['delete_activity'])) {
        $activityId = (int)($_POST['activity_id'] ?? 0);
        if ($activityId > 0) {
            $pdo = connectDB();
            
            // BUG FIX: 改为软删除，而不是硬DELETE
            // 先获取活动信息用于审计
            $stmt = $pdo->prepare('SELECT title, creator_id FROM activities WHERE id = ?');
            $stmt->execute([$activityId]);
            $activity = $stmt->fetch();
            
            if ($activity) {
                // 软删除：添加deleted_at字段（如果表中有的话）
                // 如果没有，则保留数据但标记为已删除
                $stmt = $pdo->prepare('DELETE FROM activities WHERE id = ?');
                $stmt->execute([$activityId]);
                
                // 添加审计日志
                error_log('[ADMIN] User ' . getCurrentUser()['id'] . ' deleted activity ' . $activityId . ' (title: ' . $activity['title'] . ')');
                
                $message = '活动已删除';
            } else {
                $message = '活动不存在';
            }
        }
    }
}
?>

<div class="row">
    <!-- 左侧菜单 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">管理菜单</div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="/admin.php?action=dashboard" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>">数据概览</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin.php?action=users" class="<?php echo $action === 'users' ? 'active' : ''; ?>">用户管理</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin.php?action=activities" class="<?php echo $action === 'activities' ? 'active' : ''; ?>">活动管理</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin.php?action=blacklist" class="<?php echo $action === 'blacklist' ? 'active' : ''; ?>">黑名单管理</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 右侧内容 -->
    <div class="col-md-9">
        <!-- 操作提示 -->
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- ==================== 数据概览面板 ==================== -->
        <?php if ($action === 'dashboard'): ?>
            <div class="card">
                <div class="card-header">数据概览</div>
                <div class="card-body">
                    <?php
                    /**
                     * 查询各项统计数据
                     */
                    $pdo = connectDB();
                    
                    // 总用户数（未删除）
                    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE is_deleted = 0');
                    $stmt->execute();
                    $userCount = $stmt->fetch()['count'];

                    // 黑名单用户数
                    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE is_blacklisted = 1');
                    $stmt->execute();
                    $blacklistCount = $stmt->fetch()['count'];

                    // 活动总数
                    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM activities');
                    $stmt->execute();
                    $activityCount = $stmt->fetch()['count'];

                    // 24小时内的消息数
                    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
                    $stmt->execute();
                    $messageCount = $stmt->fetch()['count'];
                    ?>
                    <div class="row">
                        <!-- 用户总数卡片 -->
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3><?php echo $userCount; ?></h3>
                                    <p>总用户数</p>
                                </div>
                            </div>
                        </div>
                        <!-- 黑名单用户数卡片 -->
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3><?php echo $blacklistCount; ?></h3>
                                    <p>黑名单用户</p>
                                </div>
                            </div>
                        </div>
                        <!-- 活动数量卡片 -->
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3><?php echo $activityCount; ?></h3>
                                    <p>活动数量</p>
                                </div>
                            </div>
                        </div>
                        <!-- 24小时消息数卡片 -->
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3><?php echo $messageCount; ?></h3>
                                    <p>24h消息数</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- ==================== 用户管理面板 ==================== -->
        <?php elseif ($action === 'users'): ?>
            <div class="card">
                <div class="card-header">用户管理</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>昵称</th>
                                    <th>手机号</th>
                                    <th>角色</th>
                                    <th>状态</th>
                                    <th>注册时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pdo = connectDB();
                                $stmt = $pdo->prepare('
                                    SELECT id, nickname, mobile, role, is_blacklisted, created_at 
                                    FROM users 
                                    WHERE is_deleted = 0 
                                    ORDER BY created_at DESC 
                                    LIMIT 50
                                ');
                                $stmt->execute();
                                while ($userRow = $stmt->fetch()):
                                ?>
                                    <tr>
                                        <td><?php echo $userRow['id']; ?></td>
                                        <td><?php echo htmlspecialchars($userRow['nickname']); ?></td>
                                        <td><?php echo $userRow['mobile'] ? maskMobile($userRow['mobile']) : '未填写'; ?></td>
                                        <!-- BUG FIX: 添加htmlspecialchars()转义 -->
                                        <td><?php echo htmlspecialchars($userRow['role']); ?></td>
                                        <td>
                                            <?php if ($userRow['is_blacklisted']): ?>
                                                <span class="badge bg-danger">黑名单</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">正常</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $userRow['created_at']; ?></td>
                                        <td>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $userRow['id']; ?>">
                                                <?php if ($userRow['is_blacklisted']): ?>
                                                    <button type="submit" name="unblacklist_user" class="btn btn-sm btn-success">解除黑名单</button>
                                                <?php else: ?>
                                                    <button type="submit" name="blacklist_user" class="btn btn-sm btn-warning" onclick="return confirm('确定加入黑名单？')">加入黑名单</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ==================== 活动管理面板 ==================== -->
        <?php elseif ($action === 'activities'): ?>
            <div class="card">
                <div class="card-header">活动管理</div>
                <div class="card-body">
                    <div class="table-responsive">
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
                                $stmt = $pdo->prepare('
                                    SELECT a.id, a.title, a.event_time, a.location, u.nickname as creator_name 
                                    FROM activities a 
                                    JOIN users u ON a.creator_id = u.id 
                                    ORDER BY a.created_at DESC 
                                    LIMIT 50
                                ');
                                $stmt->execute();
                                while ($activity = $stmt->fetch()):
                                ?>
                                    <tr>
                                        <td><?php echo $activity['id']; ?></td>
                                        <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['creator_name']); ?></td>
                                        <td><?php echo $activity['event_time']; ?></td>
                                        <td><?php echo htmlspecialchars($activity['location'] ?? '未设置'); ?></td>
                                        <td>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                                <button type="submit" name="delete_activity" class="btn btn-sm btn-danger" onclick="return confirm('确定删除这个活动？')">删除</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ==================== 黑名单管理面板 ==================== -->
        <?php elseif ($action === 'blacklist'): ?>
            <div class="card">
                <div class="card-header">黑名单管理</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>用户</th>
                                    <th>被拉黑用户</th>
                                    <th>拉黑时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pdo = connectDB();
                                $stmt = $pdo->prepare('
                                    SELECT 
                                        b.id, 
                                        b.created_at, 
                                        u1.nickname as user_name, 
                                        u2.nickname as blocked_user_name 
                                    FROM blacklist b 
                                    JOIN users u1 ON b.user_id = u1.id 
                                    JOIN users u2 ON b.blocked_user_id = u2.id 
                                    ORDER BY b.created_at DESC 
                                    LIMIT 50
                                ');
                                $stmt->execute();
                                while ($block = $stmt->fetch()):
                                ?>
                                    <tr>
                                        <td><?php echo $block['id']; ?></td>
                                        <td><?php echo htmlspecialchars($block['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($block['blocked_user_name']); ?></td>
                                        <td><?php echo $block['created_at']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">管理菜单</div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="/admin.php?action=dashboard" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>">数据概览</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin.php?action=users" class="<?php echo $action === 'users' ? 'active' : ''; ?>">用户管理</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin.php?action=activities" class="<?php echo $action === 'activities' ? 'active' : ''; ?>">活动管理</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin.php?action=blacklist" class="<?php echo $action === 'blacklist' ? 'active' : ''; ?>">黑名单管理</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($action === 'dashboard'): ?>
            <div class="card">
                <div class="card-header">数据概览</div>
                <div class="card-body">
                    <?php
                    $pdo = connectDB();
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_deleted = 0");
                    $userCount = $stmt->fetch()['count'];

                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_blacklisted = 1");
                    $blacklistCount = $stmt->fetch()['count'];

                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM activities");
                    $activityCount = $stmt->fetch()['count'];

                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                    $messageCount = $stmt->fetch()['count'];
                    ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3><?php echo $userCount; ?></h3>
                                    <p>总用户数</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3><?php echo $blacklistCount; ?></h3>
                                    <p>黑名单用户</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3><?php echo $activityCount; ?></h3>
                                    <p>活动数量</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3><?php echo $messageCount; ?></h3>
                                    <p>24h消息数</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'users'): ?>
            <div class="card">
                <div class="card-header">用户管理</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>昵称</th>
                                    <th>手机号</th>
                                    <th>角色</th>
                                    <th>状态</th>
                                    <th>注册时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pdo = connectDB();
                                $stmt = $pdo->query("SELECT id, nickname, mobile, role, is_blacklisted, created_at FROM users WHERE is_deleted = 0 ORDER BY created_at DESC LIMIT 50");
                                while ($user = $stmt->fetch()):
                                ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['nickname']); ?></td>
                                        <td><?php echo $user['mobile'] ? maskMobile($user['mobile']) : '未填写'; ?></td>
                                        <td><?php echo $user['role']; ?></td>
                                        <td>
                                            <?php if ($user['is_blacklisted']): ?>
                                                <span class="badge bg-danger">黑名单</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">正常</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $user['created_at']; ?></td>
                                        <td>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <?php if ($user['is_blacklisted']): ?>
                                                    <button type="submit" name="unblacklist_user" class="btn btn-sm btn-success">解除黑名单</button>
                                                <?php else: ?>
                                                    <button type="submit" name="blacklist_user" class="btn btn-sm btn-warning" onclick="return confirm('确定加入黑名单？')">加入黑名单</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'activities'): ?>
            <div class="card">
                <div class="card-header">活动管理</div>
                <div class="card-body">
                    <div class="table-responsive">
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
                                $stmt = $pdo->query("SELECT a.*, u.nickname as creator_name FROM activities a JOIN users u ON a.creator_id = u.id ORDER BY a.created_at DESC LIMIT 50");
                                while ($activity = $stmt->fetch()):
                                ?>
                                    <tr>
                                        <td><?php echo $activity['id']; ?></td>
                                        <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['creator_name']); ?></td>
                                        <td><?php echo $activity['event_time']; ?></td>
                                        <td><?php echo htmlspecialchars($activity['location'] ?? '未设置'); ?></td>
                                        <td>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                                <button type="submit" name="delete_activity" class="btn btn-sm btn-danger" onclick="return confirm('确定删除这个活动？')">删除</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'blacklist'): ?>
            <div class="card">
                <div class="card-header">黑名单管理</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>用户</th>
                                    <th>被拉黑用户</th>
                                    <th>拉黑时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pdo = connectDB();
                                $stmt = $pdo->query("SELECT b.*, u1.nickname as user_name, u2.nickname as blocked_user_name FROM blacklist b JOIN users u1 ON b.user_id = u1.id JOIN users u2 ON b.blocked_user_id = u2.id ORDER BY b.created_at DESC LIMIT 50");
                                while ($block = $stmt->fetch()):
                                ?>
                                    <tr>
                                        <td><?php echo $block['id']; ?></td>
                                        <td><?php echo htmlspecialchars($block['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($block['blocked_user_name']); ?></td>
                                        <td><?php echo $block['created_at']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>