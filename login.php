<?php
/**
 * 文件名: login.php
 * 
 * 用户登录页面和表单处理
 * 
 * 功能说明：
 * - 处理用户登录表单提交（POST请求）
 * - 验证用户输入（手机号和密码）
 * - 查询数据库验证用户凭证
 * - 校验用户账号状态（是否被黑名单限制）
 * - 设置会话并重定向到首页
 * - 显示登录失败的错误信息
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'config/config.php';

// ==================== 权限检查 ====================
/**
 * 已登录用户无需再登录，直接重定向到首页
 */
if (isLoggedIn()) {
    redirect('/');
}

// ==================== 表单处理 ====================

/**
 * 存储验证错误信息
 * 格式：字符串数组，每个元素为一条错误信息
 * @var array
 */
$errors = [];

// 处理POST请求（登录表单提交）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 获取并清理用户输入
    $mobile = trim($_POST['mobile'] ?? '');     // 手机号
    $password = $_POST['password'] ?? '';       // 密码

    // 2. 输入验证 - 检查必填字段
    if (empty($mobile) || empty($password)) {
        $errors[] = '手机号和密码不能为空';
    }
    
    // 3. 如果通过输入验证，进行数据库查询和身份验证
    if (empty($errors)) {
        try {
            $pdo = connectDB();
            
            // 使用预编译语句防止SQL注入
            // 查询指定手机号的用户记录（包括密码哈希值）
            $stmt = $pdo->prepare('
                SELECT 
                    id, 
                    nickname, 
                    password_hash, 
                    role, 
                    is_deleted, 
                    is_blacklisted
                FROM users 
                WHERE mobile = ? AND is_deleted = 0 
                LIMIT 1
            ');
            $stmt->execute([$mobile]);
            $user = $stmt->fetch();

            // 4. 验证用户是否存在且密码是否正确
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // 5. 检查用户账号状态
                if ($user['is_blacklisted']) {
                    // 用户被黑名单限制
                    $errors[] = '您的账号已被限制，请联系管理员';
                } else {
                    // 6. 登录成功 - 设置会话信息
                    /**
                     * $_SESSION['user_id'] 用于判定用户是否登录
                     * config.php中的 isLoggedIn() 函数检查此值
                     */
                    $_SESSION['user_id'] = $user['id'];
                    
                    // 更新用户的最后活动时间
                    updateLastSeen();
                    
                    // 重定向到首页或原来的页面
                    redirect('/');
                }
            } else {
                // 7. 登录失败：用户不存在或密码错误
                $errors[] = '手机号或密码错误';
            }
        } catch (PDOException $e) {
            // 数据库查询异常
            error_log('Login error: ' . $e->getMessage());
            $errors[] = '登录失败，请稍后重试';
        }
    }
}

// ==================== 页面准备 ====================

$title = '登录';
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- 登录卡片头部 -->
        <div class="auth-header">
            <h2>欢迎回来</h2>
            <p>登录您的 DianR 账号</p>
        </div>

        <!-- 错误提示 -->
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- 登录表单 -->
        <form method="post" class="auth-form">
            <!-- 手机号输入字段 -->
            <div class="form-group">
                <label for="mobile" class="form-label">手机号</label>
                <input 
                    type="tel" 
                    class="form-input" 
                    id="mobile" 
                    name="mobile" 
                    placeholder="请输入手机号" 
                    value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>" 
                    required
                >
            </div>

            <!-- 密码输入字段 -->
            <div class="form-group">
                <label for="password" class="form-label">密码</label>
                <input 
                    type="password" 
                    class="form-input" 
                    id="password" 
                    name="password" 
                    placeholder="请输入密码" 
                    required
                >
            </div>

            <!-- 登录按钮 -->
            <button type="submit" class="btn btn-primary btn-full">登录</button>
        </form>

        <!-- 页脚链接（跳转到注册页面） -->
        <div class="auth-footer">
            <p>还没有账号？<a href="/register.php" class="link">去注册</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="mobile" class="form-label">手机号</label>
                    <input type="text" class="form-input" id="mobile" name="mobile"
                           placeholder="请输入手机号" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">密码</label>
                    <input type="password" class="form-input" id="password" name="password"
                           placeholder="请输入密码" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">登录</button>
            </form>
        </div>

        <div class="auth-footer">
            <p>还没有账号？<a href="/register.php" class="link">立即注册</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>