<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
if (!$user) {
    redirect('/login.php');
}
$profileId = (int)($_GET['id'] ?? $user['id']);

$pdo = connectDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_deleted = 0');
$stmt->execute([$profileId]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    echo '用户不存在';
    exit;
}

$canViewDetails = $profileId == $user['id'] || $profile['role'] === 'admin';

if (!$canViewDetails) {
    // Check if approved
    $stmt = $pdo->prepare('SELECT status FROM profile_requests WHERE requester_id = ? AND owner_id = ?');
    $stmt->execute([$user['id'], $profileId]);
    $request = $stmt->fetch();
    if (!$request || $request['status'] !== 'approved') {
        $canViewDetails = false;
    } else {
        $canViewDetails = true;
    }
}

$title = $profile['nickname'] . '的资料';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><?php echo $profile['nickname']; ?>的资料</div>
            <div class="card-body">
                <p><strong>昵称:</strong> <?php echo htmlspecialchars($profile['nickname']); ?></p>
                <p><strong>手机号:</strong> <?php echo $profile['mobile'] ? maskMobile($profile['mobile']) : '未填写'; ?></p>
                <p><strong>角色:</strong> <?php echo $profile['role']; ?></p>
                <?php if ($canViewDetails): ?>
                    <p><strong>性别:</strong> <?php echo $profile['gender'] ?? '未填写'; ?></p>
                    <p><strong>年龄:</strong> <?php echo $profile['age'] ?? '未填写'; ?></p>
                    <p><strong>地区:</strong> <?php echo htmlspecialchars($profile['region'] ?? '未填写'); ?></p>
                    <p><strong>简介:</strong> <?php echo htmlspecialchars($profile['bio'] ?? '未填写'); ?></p>
                    <?php if ($profile['role'] === 'mediator'): ?>
                        <p><strong>从业简介:</strong> <?php echo htmlspecialchars($profile['profession'] ?? '未填写'); ?></p>
                        <p><strong>擅长领域:</strong> <?php echo htmlspecialchars($profile['expertise'] ?? '未填写'); ?></p>
                    <?php elseif ($profile['role'] === 'merchant'): ?>
                        <p><strong>商家名称:</strong> <?php echo htmlspecialchars($profile['business_name'] ?? '未填写'); ?></p>
                        <p><strong>地址:</strong> <?php echo htmlspecialchars($profile['business_address'] ?? '未填写'); ?></p>
                        <p><strong>经营范围:</strong> <?php echo htmlspecialchars($profile['business_scope'] ?? '未填写'); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>详细资料需对方同意查看。</p>
                    <?php if ($profileId != $user['id']): ?>
                        <a href="/request_profile.php?id=<?php echo $profileId; ?>" class="btn btn-primary">申请查看详细资料</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <?php if ($profileId == $user['id']): ?>
            <div class="card">
                <div class="card-header">编辑资料</div>
                <div class="card-body">
                    <form method="post" action="/update_profile.php">
                        <div class="mb-3">
                            <label for="gender" class="form-label">性别</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="">未选择</option>
                                <option value="male" <?php echo $profile['gender'] === 'male' ? 'selected' : ''; ?>>男</option>
                                <option value="female" <?php echo $profile['gender'] === 'female' ? 'selected' : ''; ?>>女</option>
                                <option value="other" <?php echo $profile['gender'] === 'other' ? 'selected' : ''; ?>>其他</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="age" class="form-label">年龄</label>
                            <input type="number" class="form-control" id="age" name="age" value="<?php echo $profile['age'] ?? ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="region" class="form-label">地区</label>
                            <input type="text" class="form-control" id="region" name="region" value="<?php echo htmlspecialchars($profile['region'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="bio" class="form-label">简介</label>
                            <textarea class="form-control" id="bio" name="bio"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                        </div>
                        <?php if ($profile['role'] === 'mediator'): ?>
                            <div class="mb-3">
                                <label for="profession" class="form-label">从业简介</label>
                                <textarea class="form-control" id="profession" name="profession"><?php echo htmlspecialchars($profile['profession'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="expertise" class="form-label">擅长领域</label>
                                <input type="text" class="form-control" id="expertise" name="expertise" value="<?php echo htmlspecialchars($profile['expertise'] ?? ''); ?>">
                            </div>
                        <?php elseif ($profile['role'] === 'merchant'): ?>
                            <div class="mb-3">
                                <label for="business_name" class="form-label">商家名称</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" value="<?php echo htmlspecialchars($profile['business_name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="business_address" class="form-label">地址</label>
                                <input type="text" class="form-control" id="business_address" name="business_address" value="<?php echo htmlspecialchars($profile['business_address'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="business_scope" class="form-label">经营范围</label>
                                <input type="text" class="form-control" id="business_scope" name="business_scope" value="<?php echo htmlspecialchars($profile['business_scope'] ?? ''); ?>">
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">更新</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>