<?php
/**
 * 文件名: signup_activity.php
 * 
 * 活动报名处理页面
 * 
 * 功能说明：
 * - 处理用户活动报名请求
 * - 检查活动是否存在
 * - 记录用户报名信息
 * - 防止重复报名（通过数据库唯一性约束）
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
 * 获取活动ID
 * 必须是有效的正整数
 */
$activityId = (int)($_GET['id'] ?? 0);

// 活动ID不能为空
if (!$activityId) {
    redirect('/activities.php');
}

// ==================== 业务逻辑 ====================
$pdo = connectDB();

/**
 * 检查活动是否存在
 */
$stmt = $pdo->prepare('SELECT id FROM activities WHERE id = ?');
$stmt->execute([$activityId]);
if (!$stmt->fetch()) {
    // 活动不存在，重定向回活动列表
    redirect('/activities.php');
}

/**
 * 添加用户报名记录
 * 
 * 使用 INSERT IGNORE 处理重复报名：
 * - 如果activity_signatures表中有唯一约束(activity_id, user_id)
 * - 则重复报名会被忽略，不会产生错误
 */
$stmt = $pdo->prepare('INSERT IGNORE INTO activity_signups (activity_id, user_id) VALUES (?, ?)');
$stmt->execute([$activityId, $user['id']]);

// ==================== 返回结果 ====================
// 报名成功，重定向回活动列表
redirect('/activities.php');
?>