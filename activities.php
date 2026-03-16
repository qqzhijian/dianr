<?php
require_once 'config/config.php';

$user = getCurrentUser();
$canCreate = $user && in_array($user['role'], ['mediator', 'merchant']);

$title = '活动';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">活动列表</div>
            <div class="card-body">
                <?php
                $pdo = connectDB();
                $stmt = $pdo->query("SELECT a.*, u.nickname as creator_name FROM activities a JOIN users u ON a.creator_id = u.id ORDER BY a.created_at DESC");
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($activities)) {
                    echo '<p>暂无活动</p>';
                } else {
                    foreach ($activities as $activity) {
                        echo "<div class='mb-3 p-3 border rounded'>";
                        echo "<h5>{$activity['title']}</h5>";
                        echo "<p>{$activity['description']}</p>";
                        echo "<small>时间: {$activity['event_time']} | 地点: {$activity['location']} | 创建者: {$activity['creator_name']}</small>";
                        if ($user) {
                            $stmt = $pdo->prepare("SELECT id FROM activity_signups WHERE activity_id = ? AND user_id = ?");
                            $stmt->execute([$activity['id'], $user['id']]);
                            $signedUp = $stmt->fetch();
                            if ($signedUp) {
                                echo "<span class='badge bg-success'>已报名</span>";
                            } else {
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

    <div class="col-md-4">
        <?php if ($canCreate): ?>
            <div class="card">
                <div class="card-header">创建活动</div>
                <div class="card-body">
                    <form method="post" action="/create_activity.php">
                        <div class="mb-3">
                            <label for="title" class="form-label">标题</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">描述</label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="event_time" class="form-label">时间</label>
                            <input type="datetime-local" class="form-control" id="event_time" name="event_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">地点</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                        <button type="submit" class="btn btn-primary">创建</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>