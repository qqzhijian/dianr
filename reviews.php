<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$reviewed_id = (int)($_GET['user'] ?? 0);

$title = '评价管理';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <?php if ($reviewed_id): ?>
            <?php
            $pdo = connectDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role IN ('mediator', 'merchant') AND is_blacklisted = 0");
            $stmt->execute([$reviewed_id]);
            $reviewed_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reviewed_user) {
                echo '<div class="alert alert-danger">用户不存在</div>';
            } else {
                // Check if already reviewed
                $stmt = $pdo->prepare("SELECT * FROM reviews WHERE reviewer_id = ? AND reviewed_id = ?");
                $stmt->execute([$user['id'], $reviewed_id]);
                $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="card">
                    <div class="card-header">评价 <?php echo $reviewed_user['nickname']; ?></div>
                    <div class="card-body">
                        <?php if ($existing_review): ?>
                            <div class="alert alert-info">您已经评价过此用户</div>
                            <p>评分: <?php echo str_repeat('★', $existing_review['rating']) . str_repeat('☆', 5 - $existing_review['rating']); ?></p>
                            <p>评价: <?php echo $existing_review['comment']; ?></p>
                        <?php else: ?>
                            <form method="post" action="/submit_review.php">
                                <input type="hidden" name="reviewed_id" value="<?php echo $reviewed_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">评分</label>
                                    <div class="rating">
                                        <input type="radio" id="star5" name="rating" value="5"><label for="star5">☆</label>
                                        <input type="radio" id="star4" name="rating" value="4"><label for="star4">☆</label>
                                        <input type="radio" id="star3" name="rating" value="3"><label for="star3">☆</label>
                                        <input type="radio" id="star2" name="rating" value="2"><label for="star2">☆</label>
                                        <input type="radio" id="star1" name="rating" value="1"><label for="star1">☆</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="comment" class="form-label">评价内容</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">提交评价</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php } ?>
        <?php else: ?>
            <div class="alert alert-info">选择要评价的用户</div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">可评价的用户</div>
            <div class="card-body">
                <?php
                $pdo = connectDB();
                $stmt = $pdo->query("SELECT u.id, u.nickname, u.role FROM users u WHERE u.role IN ('mediator', 'merchant') AND u.is_blacklisted = 0 ORDER BY u.nickname");
                $reviewable_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($reviewable_users as $ru) {
                    $role_text = $ru['role'] === 'mediator' ? '媒人' : '商家';
                    echo "<a href='/reviews.php?user={$ru['id']}' class='d-block mb-2'>{$ru['nickname']} ({$role_text})</a>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}
.rating input {
    display: none;
}
.rating label {
    cursor: pointer;
    font-size: 30px;
    color: #ddd;
}
.rating input:checked ~ label,
.rating label:hover,
.rating label:hover ~ label {
    color: #ffc107;
}
</style>

<?php include 'includes/footer.php'; ?>