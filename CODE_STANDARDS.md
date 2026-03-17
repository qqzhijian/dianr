# DianR 项目编码规范和最佳实践

## 快速开始 - 编码规范检查清单

在提交代码前，请检查以下内容：

### 注释规范检查
- [ ] 文件顶部有文件说明注释
- [ ] 所有函数都有PHPDoc注释
- [ ] 所有类都有PHPDoc注释
- [ ] 复杂逻辑有行注释说明

### 代码质量检查
- [ ] 没有使用全局变量（除了$_SESSION和$_SERVER）
- [ ] 所有用户输入都使用了$_POST、$_GET等超全局变量
- [ ] 所有数据库查询都使用了预编译语句
- [ ] 所有HTML输出都使用了htmlspecialchars()进行转义
- [ ] 没有SQL注入风险的代码

### 格式规范检查
- [ ] 使用4个空格作为缩进
- [ ] 没有混用制表符和空格
- [ ] 行尾没有多余空格
- [ ] 文件以换行符结尾

---

## 完整的优化代码示例

### 示例1：优化 config/config.php

```php
<?php
/**
 * 文件名: config.php
 * 
 * 项目配置和核心函数库
 * 
 * 功能说明：
 * - 数据库连接配置
 * - 应用常量定义
 * - 会话初始化
 * - 核心助手函数（认证、加密、验证）
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 * @package DianR
 */

// ==================== 数据库配置 ====================
/**
 * 数据库主机地址
 * @const string
 */
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');

/**
 * 数据库服务端口
 * @const int
 */
define('DB_PORT', getenv('DB_PORT') ?: '3306');

/**
 * 数据库名称
 * @const string
 */
define('DB_NAME', getenv('DB_NAME') ?: 'dianr');

/**
 * 数据库用户名
 * @const string
 */
define('DB_USER', getenv('DB_USER') ?: 'root');

/**
 * 数据库密码
 * @const string
 */
define('DB_PASS', getenv('DB_PASS') ?: '');

// ==================== 应用配置 ====================
/**
 * 应用名称（用于显示）
 * @const string
 */
define('SITE_NAME', '点燃');

/**
 * 应用标语
 * @const string
 */
define('SITE_SLOGAN', '点燃生活，遇见未来');

/**
 * 聊天消息轮询间隔（毫秒）
 * 用于判断是否需要获取新消息
 * @const int
 */
define('CHAT_POLL_INTERVAL', 5000);

/**
 * 在线判定阈值（秒）
 * 用户最近活动距离现在小于此值时，判定为在线
 * @const int
 */
define('ONLINE_THRESHOLD', 120);

/**
 * 离线判定阈值（秒）
 * 用户最近活动距离现在大于此值时，判定为离线
 * @const int
 */
define('AWAY_THRESHOLD', 600);

// ==================== 会话初始化 ====================
/**
 * 启动会话（如果还未启动）
 * 必须在任何输出之前调用
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== 数据库连接 ====================
/**
 * 获取数据库连接（单例模式）
 * 
 * 使用PDO进行数据库连接，配置选项：
 * - ERRMODE_EXCEPTION: 将错误作为异常抛出
 * - FETCH_ASSOC: 默认返回关联数组
 * - EMULATE_PREPARES: 禁用模拟预编译语句，使用真正的参数绑定
 * 
 * @return PDO 数据库连接对象
 * @throws PDOException 当数据库连接失败时
 * 
 * @example
 * $pdo = connectDB();
 * $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
 * $stmt->execute([$userId]);
 * $user = $stmt->fetch();
 */
function connectDB(): PDO
{
    // 使用静态变量实现单例，避免重复连接
    static $pdo;
    if ($pdo) {
        return $pdo;
    }

    // 构建数据库连接字符串
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );
    
    // PDO配置选项
    $options = [
        // 遇到错误时抛出异常
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // 默认获取关联数组
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // 使用真正的参数绑定，防止SQL注入
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO(DB_USER, DB_PASS, $options);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // 记录错误日志（生产环境）
        error_log('Database connection failed: ' . $e->getMessage());
        // 显示用户友好的错误提示
        die('数据库连接失败，请稍后重试。');
    }

    return $pdo;
}

// ==================== 页面重定向 ====================
/**
 * 重定向到指定URL
 * 
 * @param string $url 目标URL路径，如 '/login.php'
 * @return void 函数执行后终止脚本
 * 
 * @example
 * if (!isLoggedIn()) {
 *     redirect('/login.php');
 * }
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ==================== 认证相关函数 ====================
/**
 * 判断用户是否已登录
 * 
 * @return bool true如果用户已登录，false反之
 * 
 * @example
 * if (isLoggedIn()) {
 *     $user = getCurrentUser();
 * }
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * 获取当前登录用户的信息
 * 
 * 首先尝试从会话缓存中获取，如果不存在则从数据库查询
 * 查询结果缓存在会话中，减少数据库查询
 * 
 * @return array|null 用户数据数组，或null如果用户未登录
 * 
 * @example
 * if (isLoggedIn()) {
 *     $user = getCurrentUser();
 *     echo $user['nickname'];
 * }
 */
function getCurrentUser(): ?array
{
    // 未登录情况
    if (!isLoggedIn()) {
        return null;
    }
    
    // 优先使用会话缓存，减少数据库查询
    if (!empty($_SESSION['current_user'])) {
        return $_SESSION['current_user'];
    }
    
    // 从数据库查询用户信息
    $pdo = connectDB();
    $stmt = $pdo->prepare('
        SELECT * 
        FROM users 
        WHERE id = ? AND is_deleted = 0 
        LIMIT 1
    ');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // 缓存用户信息到会话
    if ($user) {
        $_SESSION['current_user'] = $user;
    }
    
    return $user;
}

/**
 * 重新加载当前用户信息
 * 
 * 清除会话缓存并重新从数据库查询
 * 用于用户信息更新后刷新缓存
 * 
 * @return array|null 最新的用户数据，或null如果用户不存在
 * 
 * @example
 * // 用户更新了个人资料后
 * reloadCurrentUser();
 */
function reloadCurrentUser(): ?array
{
    // 清除会话缓存
    unset($_SESSION['current_user']);
    // 重新查询用户信息
    return getCurrentUser();
}

/**
 * 检查用户是否已登录，否则重定向到登录页面
 * 
 * @return void 如果未登录则终止脚本并重定向
 * 
 * @example
 * // 在页面开头调用
 * ensureLoggedIn();
 * // 后续代码保证用户已登录
 */
function ensureLoggedIn(): void
{
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

// ==================== 密码和认证 ====================
/**
 * 使用PHP内置函数加密密码
 * 
 * 使用password_hash()函数，默认算法为bcrypt
 * 每次调用产生不同的哈希值（包含随机盐）
 * 
 * @param string $password 原始密码（明文）
 * @return string 加密后的密码哈希值
 * 
 * @example
 * $passwordHash = hashPassword('myPassword123');
 * // 存储$passwordHash到数据库
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 验证密码是否匹配加密的哈希值
 * 
 * 使用password_verify()函数进行安全的密码比对
 * 自动对比密码和哈希值中的盐
 * 
 * @param string $password 用户输入的密码（明文）
 * @param string $hash 数据库中存储的密码哈希值
 * @return bool true如果密码匹配，false反之
 * 
 * @example
 * if (verifyPassword($inputPassword, $user['password_hash'])) {
 *     // 密码正确，允许登录
 * }
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

// ==================== 格式化和掩码 ====================
/**
 * 隐藏手机号的中间数字，保护用户隐私
 * 
 * 规则：
 * - 11位数字格式：保留前3位和后4位，中间显示****
 * - 其他格式：尝试通过正则表达式处理
 * 
 * 示例：
 * - 输入：13812345678
 * - 输出：138****5678
 * 
 * @param string $mobile 原始手机号
 * @return string 掩码后的手机号
 * 
 * @example
 * echo maskMobile('13812345678'); // 输出: 138****5678
 */
function maskMobile(string $mobile): string
{
    // 处理标准11位中国手机号
    if (preg_match('/^\d{11}$/', $mobile)) {
        return substr($mobile, 0, 3) . '****' . substr($mobile, 7);
    }
    
    // 通用的正则表达式处理其他格式
    // 保留前3位数字，后4位数字，中间替换为****
    return preg_replace('/(\d{3})(\d+)(\d{4})/', '$1****$3', $mobile);
}

// ==================== 用户状态判定 ====================
/**
 * 判定用户在线状态
 * 
 * 逻辑：
 * - 最后活动时间为空 → 离线
 * - 距离现在 <= ONLINE_THRESHOLD → 在线
 * - 距离现在 > ONLINE_THRESHOLD 且 <= AWAY_THRESHOLD → 离线
 * - 距离现在 > AWAY_THRESHOLD → 离线
 * 
 * @param string|null $lastSeen 用户最后活动时间戳（SQL格式：YYYY-MM-DD HH:MM:SS）
 * @return string 在线状态: 'online', 'away', 'offline'
 * 
 * @example
 * $user = getCurrentUser();
 * $status = getOnlineStatus($user['last_seen']);
 * echo $status; // 输出: online 或 away 或 offline
 */
function getOnlineStatus(?string $lastSeen): string
{
    // 没有活动记录，视为离线
    if (!$lastSeen) {
        return 'offline';
    }

    // 计算距离现在的时间差（秒）
    $diff = time() - strtotime($lastSeen);
    
    // 判定在线状态
    if ($diff <= ONLINE_THRESHOLD) {
        return 'online';
    } elseif ($diff <= AWAY_THRESHOLD) {
        return 'away';
    }
    
    return 'offline';
}

/**
 * 更新当前用户的最后活动时间
 * 
 * 用于记录用户活跃时间，判定用户在线状态
 * 通常在每个页面加载时调用
 * 
 * @return void
 * 
 * @example
 * // 在每个具有身份验证的页面顶部调用
 * updateLastSeen();
 */
function updateLastSeen(): void
{
    // 只有登录用户才更新
    if (!isLoggedIn()) {
        return;
    }
    
    $pdo = connectDB();
    $stmt = $pdo->prepare('
        UPDATE users 
        SET last_seen = NOW() 
        WHERE id = ?
    ');
    $stmt->execute([$_SESSION['user_id']]);
}

// ==================== 权限检查 ====================
/**
 * 判定当前用户是否为管理员
 * 
 * @return bool true如果是管理员，false反之
 * 
 * @example
 * if (isAdmin()) {
 *     // 显示管理功能
 * }
 */
function isAdmin(): bool
{
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * 判定当前用户是否被黑名单限制
 * 
 * @return bool true如果用户被黑名单限制，false反之
 * 
 * @example
 * if (isBlacklisted()) {
 *     // 提示用户已被限制
 * }
 */
function isBlacklisted(): bool
{
    $user = getCurrentUser();
    return $user && (int)$user['is_blacklisted'] === 1;
}

/**
 * 验证用户权限，如果不是管理员则返回403错误
 * 
 * @return void 如果无权限则终止脚本
 * 
 * @example
 * // 在管理员页面顶部调用
 * requireAdmin();
 */
function requireAdmin(): void
{
    if (!isAdmin()) {
        http_response_code(403);
        echo '<h2>权限不足</h2><p>此页面仅限管理员访问。</p>';
        exit;
    }
}

/**
 * 验证用户是否被黑名单限制，如果是则提示并终止访问
 * 
 * @return void 如果用户被黑名单限制则显示提示并终止脚本
 * 
 * @example
 * // 在需要检查黑名单的页面调用
 * requireNotBlacklisted();
 */
function requireNotBlacklisted(): void
{
    if (isBlacklisted()) {
        echo '<div class="alert alert-danger">您的账号已被管理限制访问，请联系管理员。</div>';
        exit;
    }
}

// 每个页面加载时更新用户活动时间
// 这确保了用户在线状态的准确性
// 注意：这会在includeheader.php前调用以确保及时更新
updateLastSeen();
```

### 示例2：优化 login.php

```php
<?php
/**
 * 文件名: login.php
 * 
 * 用户登录页面和处理
 * 
 * 功能说明：
 * - 显示登录表单
 * - 处理POST登录请求
 * - 验证用户凭证
 * - 设置会话并重定向到首页
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'config/config.php';

// ==================== 权限检查 ====================
// 已登录用户无需再登录，直接重定向到首页
if (isLoggedIn()) {
    redirect('/');
}

// ==================== 表单处理 ====================
$errors = [];  // 存储验证错误信息

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取用户输入
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. 输入验证
    if (empty($mobile) || empty($password)) {
        $errors[] = '手机号和密码不能为空';
    }
    
    // 2. 如果没有验证错误，查询数据库
    if (empty($errors)) {
        try {
            $pdo = connectDB();
            
            // 使用预编译语句防止SQL注入
            $stmt = $pdo->prepare('
                SELECT id, nickname, password_hash, role, is_deleted, is_blacklisted
                FROM users 
                WHERE mobile = ? 
                AND is_deleted = 0 
                LIMIT 1
            ');
            $stmt->execute([$mobile]);
            $user = $stmt->fetch();

            // 3. 验证用户是否存在且密码正确
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // 检查用户是否被黑名单限制
                if ($user['is_blacklisted']) {
                    $errors[] = '您的账号已被限制，请联系管理员';
                } else {
                    // 登录成功，设置会话
                    $_SESSION['user_id'] = $user['id'];
                    
                    // 更新最后活动时间
                    updateLastSeen();
                    
                    // 重定向到首页
                    redirect('/');
                }
            } else {
                // 登录失败：用户不存在或密码错误
                $errors[] = '手机号或密码错误';
            }
        } catch (PDOException $e) {
            // 记录数据库错误日志
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
        <div class="auth-header">
            <h2>欢迎回来</h2>
            <p>登录您的DianR账号</p>
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

            <button type="submit" class="btn btn-primary btn-full">登录</button>
        </form>

        <!-- 页脚链接 -->
        <div class="auth-footer">
            <p>还没有账号？<a href="/register.php" class="link">去注册</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
```

---

## 优化前后对比

### 数据库查询优化对比

```php
// ❌ 不规范：没有注释，字段混乱
$stmt = $pdo->query("SELECT a.*, u.nickname as creator_name FROM activities a JOIN users u ON a.creator_id = u.id ORDER BY a.created_at DESC");

// ✅ 规范：清晰的格式和注释
/**
 * 查询所有活动，包含创建者信息
 * 按创建时间倒序排列（最新的优先）
 */
$stmt = $pdo->prepare('
    SELECT 
        a.id,
        a.title,
        a.description,
        a.event_time,
        a.location,
        a.creator_id,
        a.created_at,
        u.id AS creator_id,
        u.nickname AS creator_name
    FROM activities a
    JOIN users u ON a.creator_id = u.id
    ORDER BY a.created_at DESC
');
$stmt->execute();
$activities = $stmt->fetchAll();
```

### HTML输出优化对比

```php
// ❌ 不规范：直接拼接HTML，缺少转义
foreach ($users as $u) {
    echo "<div class='col-md-4 mb-3'>";
    echo "<div class='card'>";
    echo "<h5>{$u['nickname']}";
    if ($u['is_verified']) {
        echo " <i class='fas fa-check-circle'></i>";
    }
    echo "</h5>";
    echo "<p>手机号: {$u['mobile']}</p>";
    echo "</div></div>";
}

// ✅ 规范：使用htmlspecialchars转义，格式清晰
/**
 * 显示用户卡片
 * 包含：昵称、认证状态、手机号（掩码）
 */
foreach ($users as $userData) {
    echo '<div class="col-md-4 mb-3">';
    echo '<div class="card">';
    
    // 用户昵称和认证状态
    echo '<h5>';
    echo htmlspecialchars($userData['nickname']);
    if ($userData['is_verified']) {
        echo ' <i class="fas fa-check-circle text-success" title="已认证"></i>';
    }
    echo '</h5>';
    
    // 掩码后的手机号
    $maskedMobile = $userData['mobile'] ? maskMobile($userData['mobile']) : '未填写';
    echo '<p>手机号: ' . htmlspecialchars($maskedMobile) . '</p>';
    
    echo '</div>';
    echo '</div>';
}
```

---

## 持续改进建议

1. **建立代码审查机制**：每个Pull Request都需要至少一个人的代码审查
2. **使用静态分析工具**：如 PHPStan 或 Psalm 进行静态代码分析
3. **编写单元测试**：至少为核心函数编写测试
4. **版本控制规范**：遵循语义化版本号（Semantic Versioning）
5. **自动化部署**：使用CI/CD流程自动化测试和部署

