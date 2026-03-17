<?php
/**
 * 文件名: verify.php
 * 
 * 账号验证页面
 * 
 * 功能说明：
 * - 显示账号验证表单
 * - 要求用户输入验证码
 * - 验证后可使用聊天等高级功能
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

// ==================== 权限判定 ====================
/**
 * 如果用户已验证，直接重定向到首页
 * 防止已验证的用户再次看到验证页面
 */
if (isset($user['is_verified']) && $user['is_verified']) {
    redirect('/');
}

// ==================== 页面准备 ====================
// 获取错误信息（如果有）
$error = null;
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

$title = '账号验证';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <!-- 验证卡片 -->
        <div class="card">
            <!-- 卡片头部 -->
            <div class="card-header">账号验证</div>
            
            <!-- 卡片内容 -->
            <div class="card-body">
                <p>为了使用聊天等功能，请验证您的账号。</p>
                
                <!-- 错误提示 -->
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- 验证表单 -->
                <form method="post" action="/do_verify.php">
                    <!-- 验证码输入字段 -->
                    <div class="mb-3">
                        <label for="code" class="form-label">验证码</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="code" 
                            name="code" 
                            placeholder="请输入验证码" 
                            autocomplete="off"
                            required
                        >
                        <small class="form-text text-muted">
                            <!-- 演示提示：实际应该通过SMS获取 -->
                            示例验证码：123456
                        </small>
                    </div>
                    
                    <!-- 提交按钮 -->
                    <button type="submit" class="btn btn-primary btn-block">验证</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>