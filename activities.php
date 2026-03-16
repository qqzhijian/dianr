<?php
require_once 'config/config.php';

$user = getCurrentUser();
$can_create = isLoggedIn() && in_array($user['role'], ['mediator', 'merchant']);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_create) {
    if (!$user['is_verified']) {
        $errors[] = '需要验证邮箱/手机号后才能创建活动';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $event_time = $_POST['event_time'] ?? '';
        $location = sanitize($_POST['location'] ?? '');
        $associated_roles = $_POST['associated_roles'] ?? [];

        if (empty($title) || empty($event_time) || empty($location)) {
            $errors[] = '请填写所有必填字段';
        }

        if (empty($errors)) {
            $pdo = connectDB();
            $stmt = $pdo->prepare("INSERT INTO activities (creator_id, title, event_time, location, associated_roles) VALUES (?, ?, ?, ?, ?)");
            $roles_str = implode(',', $associated_roles);
            if ($stmt->execute([$user['id'], $title, $event_time, $location, $roles_str])) {
                $success = '活动创建成功';
            } else {
                $errors[] = '创建失败';
            }
        }
    }
}

$title = '活动';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <h2>活动列表</h2>
        <p>发现有趣的交友活动，扩大社交圈。</p>

        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info">
                <strong>提示：</strong>注册后可以报名参加活动。
                <a href="/register.php" class="btn btn-primary btn-sm">立即注册</a>
            </div>
        <?php endif; ?>

        <?php
        $pdo = connectDB();
        $stmt = $pdo->query("SELECT a.*, u.nickname as creator_name FROM activities a JOIN users u ON a.creator_id = u.id ORDER BY a.event_time DESC");
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($activities)) {
            echo '<p>暂无活动</p>';
        } else {
            foreach ($activities as $activity) {
                $can_join = isLoggedIn() && ($user['role'] === 'user' || in_array($user['role'], explode(',', $activity['associated_roles'])));
                $joined = false;
                if ($can_join) {
                    $stmt = $pdo->prepare("SELECT id FROM activity_participants WHERE activity_id = ? AND user_id = ?");
                    $stmt->execute([$activity['id'], $user['id']]);
                    $joined = $stmt->fetch();
                }
                echo "<div class='card mb-3'>";
                echo "<div class='card-body'>";
                echo "<h5 class='card-title'>{$activity['title']}</h5>";
                echo "<p class='card-text'>创建者: {$activity['creator_name']}<br>时间: {$activity['event_time']}<br>地点: {$activity['location']}</p>";
                if ($can_join && !$joined) {
                    echo "<a href='/join_activity.php?id={$activity['id']}' class='btn btn-primary'>报名</a>";
                } elseif ($joined) {
                    echo "<span class='text-success'>已报名</span>";
                } elseif (!isLoggedIn()) {
                    echo "<a href='/register.php' class='btn btn-outline-primary'>注册报名</a>";
                }
                echo "</div></div>";
            }
        }
        ?>
    </div>

    <?php if ($can_create): ?>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">创建活动</div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="title" class="form-label">标题</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="event_time" class="form-label">时间</label>
                        <input type="datetime-local" class="form-control" id="event_time" name="event_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">地点</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">关联角色</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="associated_roles[]" value="user" id="role_user">
                            <label class="form-check-label" for="role_user">用户</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="associated_roles[]" value="mediator" id="role_mediator">
                            <label class="form-check-label" for="role_mediator">媒人</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="associated_roles[]" value="merchant" id="role_merchant">
                            <label class="form-check-label" for="role_merchant">商家</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">创建</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>