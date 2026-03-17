<?php
/**
 * 文件名: request_profile.php
 * 
 * 申请查看详细资料处理页面
 * 
 * 功能说明：
 * - 处理用户申请查看他人资料的请求
 * - 验证目标用户的有效性
 * - 防止申请查看自己的资料
 * - 创建资料访问请求记录
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

// ==================== 参数验证 ====================
/**
 * 获取被申请用户的ID
 * 必须是有效的正整数
 */
$ownerId = (int)($_GET['id'] ?? 0);

// 参数验证
if (!$ownerId || $ownerId === $user['id']) {
    // 无效的ID或申请查看自己的资料
    redirect('/users.php');
}

// ==================== 业务逻辑 ====================
$pdo = connectDB();

/**
 * 创建资料访问申请
 * 
 * 使用 INSERT IGNORE 处理重复申请：
 * - 如果profile_requests表中有唯一约束(requester_id, owner_id)
 * - 则重复申请会被忽略，不会产生错误
 */
$stmt = $pdo->prepare('
    INSERT IGNORE INTO profile_requests (requester_id, owner_id) 
    VALUES (?, ?)
');
$stmt->execute([$user['id'], $ownerId]);

// ==================== 返回结果 ====================
/**
 * 重定向回被申请用户的资料页面
 * BUG FIX: URL中使用了htmlspecialchars()进行转义
 */
redirect('/profile.php?id=' . htmlspecialchars((string)$ownerId));
?>