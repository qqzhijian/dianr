<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$user_id = (int)($_GET['id'] ?? $user['id']);
$is_own = $user_id === $user['id'];

$pdo = connectDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    redirect('/');
}

$can_view_details = $is_own;

if (!$can_view_details) {
    // Check if approved request
    $stmt = $pdo->prepare("SELECT status FROM profile_requests WHERE requester_id = ? AND target_id = ? AND status = 'approved'");
    $stmt->execute([$user['id'], $user_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    $can_view_details = $request ? true : false;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own) {
    $gender = $_POST['gender'] ?? '';
    $age = (int)($_POST['age'] ?? 0);
    $region = sanitize($_POST['region'] ?? '');
    $intro = sanitize($_POST['intro'] ?? '');

    $stmt = $pdo->prepare("UPDATE users SET gender = ?, age = ?, region = ?, intro = ? WHERE id = ?");
    if ($stmt->execute([$gender, $age, $region, $intro, $user['id']])) {
        $success = '资料更新成功';
        $user = getCurrentUser(); // Refresh
    } else {
        $errors[] = '更新失败';
    }
}

$title = $is_own ? '我的资料' : $profile_user['nickname'] . '的资料';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><?php echo $title; ?></div>
            <div class="card-body">
                <?php if ($is_own && !empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                    </div>
                <?php endif; ?>
                <?php if ($is_own && $success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="mb-3">
                    <strong>昵称:</strong> <?php echo $profile_user['nickname']; ?>
                </div>
                <div class="mb-3">
                    <strong><?php echo $profile_user['contact_type'] === 'email' ? '邮箱' : '手机号'; ?>:</strong> <?php echo maskContact($profile_user['contact'], $profile_user['contact_type']); ?>
                </div>
                <div class="mb-3">
                    <strong>角色:</strong> <?php echo $profile_user['role'] === 'user' ? '用户' : ($profile_user['role'] === 'mediator' ? '媒人' : '商家'); ?>
                    <?php if ($profile_user['role'] === 'mediator' || $profile_user['role'] === 'merchant'): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE reviewed_id = ?");
                        $stmt->execute([$user_id]);
                        $review_stats = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($review_stats['review_count'] > 0) {
                            $stars = str_repeat('★', round($review_stats['avg_rating'])) . str_repeat('☆', 5 - round($review_stats['avg_rating']));
                            echo " - 评分: {$stars} ({$review_stats['avg_rating']}/5, {$review_stats['review_count']}条评价)";
                        }
                        ?>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <strong>在线状态:</strong> <span class="online-status <?php echo getOnlineStatus($profile_user['last_seen']); ?>"></span>
                    <?php echo getOnlineStatus($profile_user['last_seen']) === 'online' ? '在线' : (getOnlineStatus($profile_user['last_seen']) === 'away' ? '离开' : '离线'); ?>
                </div>

                <?php if ($can_view_details): ?>
                    <div class="mb-3">
                        <strong>性别:</strong> <?php echo $profile_user['gender'] === 'male' ? '男' : ($profile_user['gender'] === 'female' ? '女' : '其他'); ?>
                    </div>
                    <div class="mb-3">
                        <strong>年龄:</strong> <?php echo $profile_user['age'] ?: '未设置'; ?>
                    </div>
                    <div class="mb-3">
                        <strong>地区:</strong> <?php echo $profile_user['region'] ?: '未设置'; ?>
                    </div>
                    <div class="mb-3">
                        <strong>简介:</strong> <?php echo $profile_user['intro'] ?: '未设置'; ?>
                    </div>

                    <?php if ($profile_user['role'] === 'mediator'): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM mediators WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $mediator = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($mediator): ?>
                            <div class="mb-3">
                                <strong>从业简介:</strong> <?php echo $mediator['intro'] ?: '未设置'; ?>
                            </div>
                            <div class="mb-3">
                                <strong>擅长领域:</strong> <?php echo $mediator['expertise'] ?: '未设置'; ?>
                            </div>
                            <div class="mb-3">
                                <strong>认证状态:</strong> <?php echo $mediator['is_verified'] ? '已认证' : '未认证'; ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($profile_user['role'] === 'merchant'): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $merchant = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($merchant): ?>
                            <div class="mb-3">
                                <strong>商家名称:</strong> <?php echo $merchant['name']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>地址:</strong> <?php echo $merchant['address'] ?: '未设置'; ?>
                            </div>
                            <div class="mb-3">
                                <strong>经营范围:</strong> <?php echo $merchant['business'] ?: '未设置'; ?>
                            </div>
                            <div class="mb-3">
                                <strong>认证状态:</strong> <?php echo $merchant['is_verified'] ? '已认证' : '未认证'; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($is_own): ?>
                        <h4>编辑资料</h4>
                        <form method="post">
                            <div class="mb-3">
                                <label for="gender" class="form-label">性别</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">未设置</option>
                                    <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>>男</option>
                                    <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>>女</option>
                                    <option value="other" <?php echo $user['gender'] === 'other' ? 'selected' : ''; ?>>其他</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="age" class="form-label">年龄</label>
                                <input type="number" class="form-control" id="age" name="age" value="<?php echo $user['age']; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="region" class="form-label">地区</label>
                                <input type="text" class="form-control" id="region" name="region" value="<?php echo $user['region']; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="intro" class="form-label">简介</label>
                                <textarea class="form-control" id="intro" name="intro" rows="3"><?php echo $user['intro']; ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">更新</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        详细资料需对方同意查看。
                        <?php if (!$is_own): ?>
                            <button class="btn btn-primary request-profile" data-user-id="<?php echo $user_id; ?>">申请查看</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$is_own): ?>
                    <div class="mt-3">
                        <a href="/chat.php?user=<?php echo $user_id; ?>" class="btn btn-success">发送消息</a>
                        <button class="btn btn-danger" onclick="blockUser(<?php echo $user_id; ?>)">拉黑</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function blockUser(userId) {
    if (confirm('确定要拉黑此用户吗？')) {
        fetch('/api/block-user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ blocked_user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>