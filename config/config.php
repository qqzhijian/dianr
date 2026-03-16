<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dianr_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Other configs
define('SITE_URL', 'http://yourdomain.com');
define('UPLOAD_DIR', '/workspaces/dianr/public/uploads/');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Shanghai');

// Helper functions
function connectDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $user;
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function maskContact($contact, $type) {
    if ($type === 'email') {
        $parts = explode('@', $contact);
        if (count($parts) === 2) {
            $name = $parts[0];
            $domain = $parts[1];
            $masked_name = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 4)) . substr($name, -2);
            return $masked_name . '@' . $domain;
        }
        return $contact;
    } else { // phone
        return substr($contact, 0, 3) . '****' . substr($contact, -4);
    }
}

function getOnlineStatus($last_seen) {
    $now = time();
    $last = strtotime($last_seen);
    if ($now - $last < 300) { // 5 minutes
        return 'online';
    } elseif ($now - $last < 3600) { // 1 hour
        return 'away';
    } else {
        return 'offline';
    }
}
?>