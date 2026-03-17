<?php
/**
 * 文件名: config.php
 * 
 * 项目配置和核心函数库
 * 
 * 功能说明：
 * - 数据库连接配置和初始化
 * - 应用常量定义
 * - 会话初始化管理
 * - 核心助手函数（认证、加密、密码、用户状态等）
 * - 权限控制函数
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 * @package DianR
 */

// ==================== 数据库配置 ====================

/**
 * 数据库主机地址
 * 默认值：127.0.0.1（本地）
 * 可通过环境变量 DB_HOST 覆盖
 * @const string
 */
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');

/**
 * 数据库服务端口
 * 默认值：3306（MySQL标准端口）
 * 可通过环境变量 DB_PORT 覆盖
 * @const string
 */
define('DB_PORT', getenv('DB_PORT') ?: '3306');

/**
 * 数据库名称
 * 默认值：dianr
 * 可通过环境变量 DB_NAME 覆盖
 * @const string
 */
define('DB_NAME', getenv('DB_NAME') ?: 'dianr');

/**
 * 数据库用户名
 * 默认值：root
 * 可通过环境变量 DB_USER 覆盖
 * 注意：生产环境应该使用更安全的凭证
 * @const string
 */
define('DB_USER', getenv('DB_USER') ?: 'root');

/**
 * 数据库密码
 * 默认值：空字符串
 * 可通过环境变量 DB_PASS 覆盖
 * 注意：不要在代码中硬编码密码，使用环境变量
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
 * 用于客户端判断是否需要获取新消息
 * @const int
 */
define('CHAT_POLL_INTERVAL', 5000);

/**
 * 在线判定阈值（秒）
 * 用户最近活动距离现在小于此值时，判定用户为在线
 * @const int
 */
define('ONLINE_THRESHOLD', 120);

/**
 * 离线判定阈值（秒）
 * 用户最近活动距离现在大于此值时，判定用户为离线
 * @const int
 */
define('AWAY_THRESHOLD', 600);

// ==================== 会话初始化 ====================

/**
 * 启动PHP会话（如果还未启动）
 * 必须在任何输出之前调用
 * 会话用于存储用户登录状态和临时数据
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== 数据库连接函数 ====================

/**
 * 获取数据库连接（单例模式）
 * 
 * 使用PDO进行数据库连接，使用单例模式避免重复连接
 * PDO配置选项说明：
 * - ERRMODE_EXCEPTION: 将数据库错误作为异常抛出，便于捕获处理
 * - FETCH_ASSOC: 所有查询默认返回关联数组（key-value）而非对象
 * - EMULATE_PREPARES: false表示禁用客户端模拟预编译，使用真正的服务器端参数绑定
 * 
 * 字符集设置为utf8mb4，支持emoji和其他高位Unicode字符
 * 
 * @return PDO 数据库连接对象
 * @throws PDOException 当数据库连接失败时抛出异常
 * 
 * @example
 * $pdo = connectDB();
 * $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
 * $stmt->execute([$userId]);
 * $user = $stmt->fetch();
 * 
 * @see https://www.php.net/manual/en/class.pdo.php
 */
function connectDB(): PDO
{
    // 使用静态变量实现单例，如果已连接则直接返回
    static $pdo;
    if ($pdo) {
        return $pdo;
    }

    // 构建PDO数据源名称（DSN）
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );
    
    // PDO配置选项数组
    $opts = [
        // 错误模式：抛出异常
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // 默认获取模式：关联数组
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // 禁用预处理语句的客户端模拟，使用服务器端真正的参数绑定
        // 这样能更有效地防止SQL注入
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    } catch (PDOException $e) {
        // 记录错误到日志（生产环境）
        error_log('Database connection failed: ' . $e->getMessage());
        // 显示用户友好的错误提示（不透露详细技术信息）
        die('数据库连接失败，请稍后重试。');
    }

    return $pdo;
}

// ==================== 页面导航和重定向 ====================

/**
 * 重定向到指定URL
 * 
 * 设置HTTP Location头，将浏览器重定向到新URL
 * 执行后立即终止脚本
 * 
 * @param string $url 目标URL路径，如 '/login.php' 或 'https://example.com'
 * @return void 函数执行后终止脚本，不返回
 * 
 * @example
 * if (!isLoggedIn()) {
 *     redirect('/login.php');
 * }
 * // 此后的代码不会执行
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
 * 通过检查 $_SESSION['user_id'] 是否存在来判定
 * 
 * @return bool true 如果用户已登录，false 反之
 * 
 * @example
 * if (isLoggedIn()) {
 *     $user = getCurrentUser();
 *     echo "欢迎, " . $user['nickname'];
 * } else {
 *     redirect('/login.php');
 * }
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * 获取当前登录用户的完整信息
 * 
 * 首先尝试从会话缓存中获取，如果缓存不存在则从数据库查询
 * 查询结果缓存在 $_SESSION['current_user'] 中，减少数据库查询
 * 
 * @return array|null 用户数据数组（包含所有用户表字段），或 null 如果用户未登录或不存在
 * 
 * @example
 * if (isLoggedIn()) {
 *     $user = getCurrentUser();
 *     echo "用户ID: " . $user['id'];
 *     echo "昵称: " . $user['nickname'];
 *     echo "角色: " . $user['role'];
 * }
 */
function getCurrentUser(): ?array
{
    // 未登录，直接返回null
    if (!isLoggedIn()) {
        return null;
    }
    
    // 优先使用会话缓存，减少数据库查询
    if (!empty($_SESSION['current_user'])) {
        return $_SESSION['current_user'];
    }
    
    // 缓存不存在，从数据库查询
    $pdo = connectDB();
    $stmt = $pdo->prepare('
        SELECT * 
        FROM users 
        WHERE id = ? AND is_deleted = 0 
        LIMIT 1
    ');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // 将用户信息缓存到会话
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
 * @return array|null 最新的用户数据数组，或 null 如果用户未登录或已被删除
 * 
 * @example
 * // 用户更新了个人资料后调用此函数
 * $updatedUser = reloadCurrentUser();
 * echo "更新后的昵称: " . $updatedUser['nickname'];
 */
function reloadCurrentUser(): ?array
{
    // 清除会话中的用户缓存
    unset($_SESSION['current_user']);
    // 重新查询并返回用户信息
    return getCurrentUser();
}

/**
 * 检查用户是否已登录，否则重定向到登录页面
 * 
 * 这是一个便利函数，在需要确保用户已登录的页面开头调用
 * 如果用户未登录，将被重定向到登录页面
 * 
 * @return void 如果未登录则重定向并终止脚本；如果已登录则正常返回
 * 
 * @example
 * // 在页面开头调用
 * ensureLoggedIn();
 * // 后续代码保证用户已登录，可以安全地使用 getCurrentUser()
 * $user = getCurrentUser();
 */
function ensureLoggedIn(): void
{
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

// ==================== 密码管理函数 ====================

/**
 * 使用PHP内置函数加密密码
 * 
 * 使用 password_hash() 函数，底层支持bcrypt算法
 * 每次调用生成不同的哈希值（包含随机盐），安全性高
 * 
 * @param string $password 原始密码（用户输入的明文）
 * @return string 加密后的密码哈希值，可直接存储到数据库
 * 
 * @example
 * $plainPassword = 'myPassword123';
 * $hashedPassword = hashPassword($plainPassword);
 * // 存储 $hashedPassword 到数据库
 * 
 * @see https://www.php.net/manual/en/function.password-hash.php
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 验证密码是否与哈希值匹配
 * 
 * 使用 password_verify() 函数进行安全的密码比对
 * 自动从哈希值中提取盐并进行对比
 * 
 * @param string $password 用户输入的密码（明文）
 * @param string $hash 数据库中存储的密码哈希值
 * @return bool true 如果密码匹配，false 反之
 * 
 * @example
 * $user = getCurrentUser();
 * $inputPassword = $_POST['password'];
 * if (verifyPassword($inputPassword, $user['password_hash'])) {
 *     // 密码正确
 * } else {
 *     // 密码错误
 * }
 * 
 * @see https://www.php.net/manual/en/function.password-verify.php
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

// ==================== 数据格式化函数 ====================

/**
 * 隐藏手机号的中间数字，保护用户隐私
 * 
 * 规则：
 * - 将11位手机号格式化为 XXX****XXXX （保留前3位和后4位）
 * - 其他格式通过正则表达式尝试处理
 * 
 * 示例:
 * - 输入：13812345678
 * - 输出：138****5678
 * 
 * @param string $mobile 原始手机号（11位数字）
 * @return string 隐藏中间数字的手机号
 * 
 * @example
 * $mobile = '13812345678';
 * echo maskMobile($mobile); // 输出: 138****5678
 */
function maskMobile(string $mobile): string
{
    // 处理标准11位中国手机号
    if (preg_match('/^\d{11}$/', $mobile)) {
        // 保留前3位 + **** + 后4位
        return substr($mobile, 0, 3) . '****' . substr($mobile, 7);
    }
    
    // 通用的正则表达式处理其他格式
    // 匹配任何格式，保留前3位和后4位，中间替换为****
    return preg_replace('/(\d{3})(\d+)(\d{4})/', '$1****$3', $mobile);
}

// ==================== 用户状态判定函数 ====================

/**
 * 根据最后活动时间判定用户的在线状态
 * 
 * 逻辑判定：
 * - 没有活动记录 → offline（离线）
 * - 距离现在 <= ONLINE_THRESHOLD → online（在线）
 * - ONLINE_THRESHOLD < 距离现在 <= AWAY_THRESHOLD → away（离线）
 * - 距离现在 > AWAY_THRESHOLD → offline（离线）
 * 
 * @param string|null $lastSeen 用户最后活动时间戳（SQL格式：YYYY-MM-DD HH:MM:SS）
 * @return string 在线状态字符串：'online'（在线）、'away'（离线）、'offline'（离线）
 * 
 * @example
 * $user = getCurrentUser();
 * $status = getOnlineStatus($user['last_seen']);
 * if ($status === 'online') {
 *     echo '<span class="badge badge-success">在线</span>';
 * }
 */
function getOnlineStatus(?string $lastSeen): string
{
    // 没有活动记录，视为离线
    if (!$lastSeen) {
        return 'offline';
    }

    // 计算距离现在的时间差（秒）
    $diff = time() - strtotime($lastSeen);
    
    // 根据阈值判定状态
    if ($diff <= ONLINE_THRESHOLD) {
        return 'online';
    } elseif ($diff <= AWAY_THRESHOLD) {
        return 'away';
    }
    
    return 'offline';
}

/**
 * 更新当前用户的最后活动时间为当前时刻
 * 
 * 用于记录用户最近活跃时间，可用于判定用户在线状态
 * 通常在每个页面加载时调用，但仅对已登录用户生效
 * 
 * @return void
 * 
 * @example
 * // 在需要跟踪用户活动的页面中调用
 * updateLastSeen();
 * // 此后用户的 last_seen 字段会被更新为当前时间
 * 
 * @note 是在config.php底部自动调用，无需手动调用
 */
function updateLastSeen(): void
{
    // 只有登录用户才更新 last_seen
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

// ==================== 权限检查函数 ====================

/**
 * 判定当前用户是否为管理员
 * 
 * 通过检查用户角色(role)是否为'admin'来判定
 * 
 * @return bool true 如果当前用户是管理员，false 反之（包括未登录）
 * 
 * @example
 * if (isAdmin()) {
 *     echo '<a href="/admin.php">管理后台</a>';
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
 * 黑名单用户无法正常使用平台功能
 * 
 * @return bool true 如果用户被黑名单限制，false 反之
 * 
 * @example
 * if (isBlacklisted()) {
 *     echo '<div class="alert">您的账号已被限制</div>';
 * }
 */
function isBlacklisted(): bool
{
    $user = getCurrentUser();
    return $user && (int)$user['is_blacklisted'] === 1;
}

/**
 * 验证用户是否为管理员，否则返回403错误
 * 
 * 这是一个便利函数，在管理员专属页面开头调用
 * 如果当前用户不是管理员，显示权限错误并终止脚本
 * 
 * @return void 如果无权限则显示错误并终止脚本；如果有权限则正常返回
 * 
 * @example
 * // 在管理员页面开头调用
 * requireAdmin();
 * // 后续代码只在用户是管理员时才会执行
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

// ==================== 自动执行 ====================

/**
 * 每个页面加载时更新用户的最后活动时间
 * 这确保了用户在线状态的准确性
 */
updateLastSeen();
