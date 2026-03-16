<?php
// config/config.php
// Basic configuration for 点燃平台 (DianR)
// Update these constants to match your MySQL setup.

// === DATABASE ===
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'dianr');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('SITE_NAME', '点燃');

define('SITE_SLOGAN', '点燃生活，遇见未来');

define('CHAT_POLL_INTERVAL', 5000); // ms

define('ONLINE_THRESHOLD', 120); // seconds to consider online

define('AWAY_THRESHOLD', 600); // seconds to consider away

// === SESSION ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === DATABASE CONNECTION ===
function connectDB()
{
    static $pdo;
    if ($pdo) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    } catch (PDOException $e) {
        die('数据库连接失败: ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
}

// === HELPERS ===
function redirect(string $url)
{
    header('Location: ' . $url);
    exit;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    if (!empty($_SESSION['current_user'])) {
        return $_SESSION['current_user'];
    }
    $pdo = connectDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_deleted = 0 LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['current_user'] = $user;
    }
    return $user;
}

function reloadCurrentUser()
{
    unset($_SESSION['current_user']);
    return getCurrentUser();
}

function ensureLoggedIn()
{
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function maskMobile(string $mobile): string
{
    // 13812345678 -> 138****5678
    if (preg_match('/^\d{11}$/', $mobile)) {
        return substr($mobile, 0, 3) . '****' . substr($mobile, 7);
    }
    // fallback
    return preg_replace('/(\d{3})(\d+)(\d{4})/', '$1****$3', $mobile);
}

function getOnlineStatus(?string $lastSeen): string
{
    if (!$lastSeen) {
        return 'offline';
    }

    $diff = time() - strtotime($lastSeen);
    if ($diff <= ONLINE_THRESHOLD) {
        return 'online';
    }
    if ($diff <= AWAY_THRESHOLD) {
        return 'away';
    }
    return 'offline';
}

function updateLastSeen()
{
    if (!isLoggedIn()) {
        return;
    }
    $pdo = connectDB();
    $stmt = $pdo->prepare('UPDATE users SET last_seen = NOW() WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
}

// Call this on every page view by including this file and calling updateLastSeen();

function isAdmin(): bool
{
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function isBlacklisted(): bool
{
    $user = getCurrentUser();
    return $user && (int)$user['is_blacklisted'] === 1;
}

function requireAdmin()
{
    if (!isAdmin()) {
        http_response_code(403);
        echo '<h2>权限不足</h2><p>此页面仅限管理员访问。</p>';
        exit;
    }
}

function requireNotBlacklisted()
{
    if (isBlacklisted()) {
        echo '<div class="alert alert-danger">您的账号已被管理限制访问，请联系管理员。</div>';
        exit;
    }
}
