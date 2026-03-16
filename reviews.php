<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$pdo = connectDB();

$action = $_GET['action'] ?? 'list';
$targetId = (int)($_GET['target'] ?? 0);

$title = '评价';
include 'includes/header.php';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $targetId = (int)$_POST['target_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment'] ?? '');

    if ($rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare('INSERT INTO reviews (reviewer_id, target_id, rating, comment) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = NOW()');
        $stmt->execute([$user['id'], $targetId, $rating, $comment]);
        $message = '评价提交成功';
    } else {
        $error = '评分必须在1-5之间';
    }
}
?>

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">评价中心</div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="/reviews.php?action=list" class="<?php echo $action === 'list' ? 'active' : ''; ?>">我的评价</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/reviews.php?action=received" class="<?php echo $action === 'received' ? 'active' : ''; ?>">收到的评价</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/reviews.php?action=users" class="<?php echo $action === 'users' ? 'active' : ''; ?>">评价用户</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="card">
                <div class="card-header">我发布的评价</div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT r.*, u.nickname as target_name, u.role as target_role FROM reviews r JOIN users u ON r.target_id = u.id WHERE r.reviewer_id = ? ORDER BY r.created_at DESC");
                    $stmt->execute([$user['id']]);
                    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($reviews)) {
                        echo '<p>您还没有发布过评价</p>';
                    } else {
                        foreach ($reviews as $review) {
                            echo "<div class='mb-3 p-3 border rounded'>";
                            echo "<h6>对 " . htmlspecialchars($review['target_name']) . " ({$review['target_role']}) 的评价</h6>";
                            echo "<div class='mb-2'>";
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '⭐' : '☆';
                            }
                            echo " ({$review['rating']}/5)</div>";
                            if ($review['comment']) {
                                echo "<p>" . htmlspecialchars($review['comment']) . "</p>";
                            }
                            echo "<small class='text-muted'>评价时间: {$review['created_at']}</small>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>

        <?php elseif ($action === 'received'): ?>
            <div class="card">
                <div class="card-header">收到的评价</div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT r.*, u.nickname as reviewer_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.target_id = ? ORDER BY r.created_at DESC");
                    $stmt->execute([$user['id']]);
                    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($reviews)) {
                        echo '<p>还没有人评价您</p>';
                    } else {
                        // Calculate average rating
                        $totalRating = 0;
                        foreach ($reviews as $review) {
                            $totalRating += $review['rating'];
                        }
                        $avgRating = count($reviews) > 0 ? round($totalRating / count($reviews), 1) : 0;

                        echo "<div class='mb-3'><h5>综合评分: ";
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $avgRating ? '⭐' : '☆';
                        }
                        echo " ({$avgRating}/5, 共" . count($reviews) . "个评价)</h5></div>";

                        foreach ($reviews as $review) {
                            echo "<div class='mb-3 p-3 border rounded'>";
                            echo "<h6>来自 " . htmlspecialchars($review['reviewer_name']) . " 的评价</h6>";
                            echo "<div class='mb-2'>";
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '⭐' : '☆';
                            }
                            echo " ({$review['rating']}/5)</div>";
                            if ($review['comment']) {
                                echo "<p>" . htmlspecialchars($review['comment']) . "</p>";
                            }
                            echo "<small class='text-muted'>评价时间: {$review['created_at']}</small>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>

        <?php elseif ($action === 'users'): ?>
            <div class="card">
                <div class="card-header">选择要评价的用户</div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // Show mediators and merchants
                        $stmt = $pdo->query("SELECT id, nickname, role, profession, business_name FROM users WHERE role IN ('mediator', 'merchant') AND is_blacklisted = 0 AND is_deleted = 0 ORDER BY last_seen DESC LIMIT 20");
                        while ($targetUser = $stmt->fetch()):
                        ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6><?php echo htmlspecialchars($targetUser['nickname']); ?></h6>
                                        <p class="text-muted"><?php echo $targetUser['role'] === 'mediator' ? '媒人' : '商家'; ?></p>
                                        <?php if ($targetUser['role'] === 'mediator' && $targetUser['profession']): ?>
                                            <p><?php echo htmlspecialchars($targetUser['profession']); ?></p>
                                        <?php elseif ($targetUser['role'] === 'merchant' && $targetUser['business_name']): ?>
                                            <p><?php echo htmlspecialchars($targetUser['business_name']); ?></p>
                                        <?php endif; ?>
                                        <a href="/reviews.php?action=review&target=<?php echo $targetUser['id']; ?>" class="btn btn-sm btn-primary">评价</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'review' && $targetId): ?>
            <?php
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_deleted = 0');
            $stmt->execute([$targetId]);
            $targetUser = $stmt->fetch();

            if (!$targetUser || !in_array($targetUser['role'], ['mediator', 'merchant'])) {
                echo '<div class="alert alert-danger">用户不存在或无法评价</div>';
            } else {
            ?>
                <div class="card">
                    <div class="card-header">评价 <?php echo htmlspecialchars($targetUser['nickname']); ?></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="target_id" value="<?php echo $targetId; ?>">

                            <div class="mb-3">
                                <label class="form-label">评分 (1-5星)</label>
                                <select name="rating" class="form-control" required>
                                    <option value="">请选择评分</option>
                                    <option value="5">⭐⭐⭐⭐⭐ 5星 - 非常满意</option>
                                    <option value="4">⭐⭐⭐⭐☆ 4星 - 满意</option>
                                    <option value="3">⭐⭐⭐☆☆ 3星 - 一般</option>
                                    <option value="2">⭐⭐☆☆☆ 2星 - 不满意</option>
                                    <option value="1">⭐☆☆☆☆ 1星 - 非常不满意</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">评价内容 (可选)</label>
                                <textarea name="comment" class="form-control" rows="4" placeholder="请分享您的使用体验..."></textarea>
                            </div>

                            <button type="submit" name="submit_review" class="btn btn-primary">提交评价</button>
                            <a href="/reviews.php?action=users" class="btn btn-secondary">取消</a>
                        </form>
                    </div>
                </div>
            <?php } ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>