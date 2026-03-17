<?php
/**
 * 文件名: register.php
 * 
 * 用户注册页面和表单处理
 * 
 * 功能说明：
 * - 显示用户注册表单
 * - 处理POST注册请求
 * - 验证用户输入（昵称、密码等）
 * - 检查昵称是否已被使用
 * - 加密密码并创建新用户账号
 * - 设置会话并重定向到首页
 * 
 * 注意：
 * - 新用户默认角色为'user'（普通用户）
 * - 密码最少6位
 * - 昵称必须唯一
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'config/config.php';

// ==================== 权限检查 ====================
/**
 * 已登录用户无需再注册，直接重定向到首页
 */
if (isLoggedIn()) {
    redirect('/');
}

// ==================== 表单处理 ====================

/**
 * 存储验证错误信息
 * @var array
 */
$errors = [];

// 处理POST请求（注册表单提交）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 获取并清理用户输入
    $nickname = trim($_POST['nickname'] ?? '');    // 昵称
    $password = $_POST['password'] ?? '';          // 密码
    $role = $_POST['role'] ?? 'user';             // 角色（默认为'user'）

    // 2. 输入验证 - 检查必填字段
    if (empty($nickname) || empty($password)) {
        $errors[] = '昵称和密码不能为空';
    }
    
    // 3. 检查密码长度
    if (strlen($password) < 6) {
        $errors[] = '密码至少需要6位';
    }

    // 4. 如果通过基础验证，进行唯一性检查和数据库操作
    if (empty($errors)) {
        try {
            $pdo = connectDB();
            
            // 检查昵称是否已被占用
            $stmt = $pdo->prepare('
                SELECT id 
                FROM users 
                WHERE nickname = ? AND is_deleted = 0 
                LIMIT 1
            ');
            $stmt->execute([$nickname]);
            
            if ($stmt->fetch()) {
                // 昵称已被使用
                $errors[] = '昵称已被使用';
            } else {
                // 5. 昵称未被使用，创建新用户
                
                // 加密密码
                $passwordHash = hashPassword($password);
                
                // 插入新用户记录
                $stmt = $pdo->prepare('
                    INSERT INTO users 
                    (password_hash, nickname, role) 
                    VALUES (?, ?, ?)
                ');
                $stmt->execute([$passwordHash, $nickname, $role]);
                
                // 获取新创建用户的ID
                $userId = $pdo->lastInsertId();
                
                // 6. 注册成功 - 自动登录
                /**
                 * $_SESSION['user_id'] 用于标识已登录的用户
                 * config.php中的 isLoggedIn() 函数检查此值
                 */
                $_SESSION['user_id'] = $userId;
                
                // 更新用户最后活动时间
                updateLastSeen();
                
                // 重定向到首页
                redirect('/');
            }
        } catch (PDOException $e) {
            // 数据库操作异常
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = '注册失败，请稍后重试';
        }
    }
}

// ==================== 页面准备 ====================

$title = '注册';
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- 注册卡片头部 -->
        <div class="auth-header">
            <h2>加入点燃</h2>
            <p>开启你的美好邂逅</p>
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

        <!-- 注册表单 -->
        <form method="post" class="auth-form">
            <!-- 昵称输入字段 -->
            <div class="form-group">
                <label for="nickname" class="form-label">昵称</label>
                <input 
                    type="text" 
                    class="form-input" 
                    id="nickname" 
                    name="nickname" 
                    placeholder="给自己起个好听的名字" 
                    value="<?php echo htmlspecialchars($_POST['nickname'] ?? ''); ?>" 
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
                    placeholder="设置登录密码（至少6位）" 
                    required
                >
            </div>

            <!-- 用户角色 -->
            <!-- 
            注意：新用户默认角色为'user'（普通用户）
            媒人(mediator)和商家(merchant)角色需要后续管理员审核或用户主动升级
            -->
            <input type="hidden" name="role" value="user">

            <!-- 注册按钮 -->
            <button type="submit" class="btn btn-primary btn-full">立即注册</button>
        </form>

        <!-- 页脚链接（跳转到登录页面） -->
        <div class="auth-footer">
            <p>已有账号？<a href="/login.php" class="link">去登录</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>