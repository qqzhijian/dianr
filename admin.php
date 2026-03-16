<?php
require_once 'config/config.php';

requireAdmin();

$action = $_GET['action'] ?? 'dashboard';
$title = '后台管理';
include 'includes/header.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['blacklist_user'])) {
        $userId = (int)$_POST['user_id'];
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE users SET is_blacklisted = 1 WHERE id = ?');
        $stmt->execute([$userId]);
        $message = '用户已加入黑名单';
    } elseif (isset($_POST['unblacklist_user'])) {
        $userId = (int)$_POST['user_id'];
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE users SET is_blacklisted = 0 WHERE id = ?');
        $stmt->execute([$userId]);
        $message = '用户已从黑名单移除';
    } elseif (isset($_POST['delete_activity'])) {
        $activityId = (int)$_POST['activity_id'];
        $pdo = connectDB();
        $stmt = $pdo->prepare('DELETE FROM activities WHERE id = ?');
        $stmt->execute([$activityId]);
        $message = '活动已删除';
    }
}
?>

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