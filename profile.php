/**
 * 文件名: profile.php
 * 
 * 用户个人资料查看页面
 * 
 * 功能说明：
 * - 显示用户基本信息
 * - 权限控制（需要对方同意才能查看详细信息）
 * - 支持申请查看详细资料
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'config/config.php';

// ==================== 权限检查 ====================
// 确保用户已登录
if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
if (!$user) {
    redirect('/login.php');
}

// ==================== 业务逻辑 ====================
/**
 * 获取要查看的用户个人资料ID
 * 如果未指定，则查看当前用户自己的资料
 */
$profileId = (int)($_GET['id'] ?? $user['id']);

$pdo = connectDB();
// 查询用户信息
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_deleted = 0');
$stmt->execute([$profileId]);
$profile = $stmt->fetch();

// 用户不存在则返回404
if (!$profile) {
    http_response_code(404);
    echo '用户不存在';
    exit;
}

// ==================== 权限判定 ====================
/**
 * 权限判定规则：
 * 1. 当前用户查看自己的资料 → 可以看所有详细信息
 * 2. 当前用户是管理员 → 可以看所有详细信息
 * 3. 其他情况 → 只能看基本信息，除非已获得对方批准
 * 
 * BUG FIX: 第25行原来错误地检查了 $profile['role']（被查看用户的角色）
 * 修复为检查 $user['role']（当前用户的角色）
 */
// 初始状态：不能查看详细信息
$canViewDetails = false;

// 当前用户查看自己的资料
if ($profileId === $user['id']) {
    $canViewDetails = true;
}
// 当前用户是管理员（BUG FIX: 应该是 $user['role'] 而不是 $profile['role']）
elseif ($user['role'] === 'admin') {
    $canViewDetails = true;
}
// 其他情况：检查是否已获得对方批准
else {
    $stmt = $pdo->prepare('SELECT status FROM profile_requests WHERE requester_id = ? AND owner_id = ?');
    $stmt->execute([$user['id'], $profileId]);
    $request = $stmt->fetch();
    if ($request && $request['status'] === 'approved') {
        $canViewDetails = true;
    }
}

// ==================== 页面准备 ====================
$title = htmlspecialchars($profile['nickname']) . '的资料';
include 'includes/header.php';
?>

<div class="row">
    <!-- 左侧：用户资料卡片 -->
    <div class="col-md-8">
        <div class="card">
            <!-- 卡片头部 -->
            <div class="card-header"><?php echo htmlspecialchars($profile['nickname']); ?>的资料</div>
            <div class="card-body">
                <!-- 基本信息（所有人可见） -->
                <p><strong>昵称:</strong> <?php echo htmlspecialchars($profile['nickname']); ?></p>
                <p><strong>手机号:</strong> <?php echo $profile['mobile'] ? maskMobile($profile['mobile']) : '未填写'; ?></p>
                <!-- BUG FIX: 添加了htmlspecialchars()转义 -->
                <p><strong>角色:</strong> <?php echo htmlspecialchars($profile['role']); ?></p>
                
                <!-- 详细信息（需要权限才能看） -->
                <?php if ($canViewDetails): ?>
                    <!-- BUG FIX: 所有可能的用户输入都添加了htmlspecialchars()转义 -->
                    <p><strong>性别:</strong> <?php echo htmlspecialchars($profile['gender'] ?? '未填写'); ?></p>
                    <p><strong>年龄:</strong> <?php echo htmlspecialchars((string)($profile['age'] ?? '未填写')); ?></p>
                    <p><strong>地区:</strong> <?php echo htmlspecialchars($profile['region'] ?? '未填写'); ?></p>
                    <p><strong>简介:</strong> <?php echo htmlspecialchars($profile['bio'] ?? '未填写'); ?></p>
                    
                    <!-- 媒人信息 -->
                    <?php if ($profile['role'] === 'mediator'): ?>
                        <p><strong>从业简介:</strong> <?php echo htmlspecialchars($profile['profession'] ?? '未填写'); ?></p>
                        <p><strong>擅长领域:</strong> <?php echo htmlspecialchars($profile['expertise'] ?? '未填写'); ?></p>
                    <!-- 商家信息 -->
                    <?php elseif ($profile['role'] === 'merchant'): ?>
                        <p><strong>商家名称:</strong> <?php echo htmlspecialchars($profile['business_name'] ?? '未填写'); ?></p>
                        <p><strong>地址:</strong> <?php echo htmlspecialchars($profile['business_address'] ?? '未填写'); ?></p>
                        <p><strong>经营范围:</strong> <?php echo htmlspecialchars($profile['business_scope'] ?? '未填写'); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- 无权限提示 -->
                    <p>详细资料需对方同意查看。</p>
                    <?php if ($profileId !== $user['id']): ?>
                        <!-- BUG FIX: 在URL中也需要进行htmlspecialchars()转义 -->
                        <a href="/request_profile.php?id=<?php echo htmlspecialchars((string)$profileId); ?>" class="btn btn-primary">申请查看详细资料</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 右侧：编辑表单 -->
    <div class="col-md-4">
        <?php if ($profileId === $user['id']): ?>
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