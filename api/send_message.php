<?php
/**
 * 文件名: send_message.php
 * 
 * API端点：发送聊天消息
 * 
 * 功能说明：
 * - 接收用户发送的聊天消息
 * - 验证接收方的有效性和黑名单状态
 * - 保存消息到数据库
 * - 返回成功/失败的JSON响应
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once '../config/config.php';

// ==================== 权限检查 ====================
// 确保用户已登录
if (!isLoggedIn()) {
    http_response_code(401);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    exit;
}

// ==================== 参数验证 ====================
/**
 * 获取并验证消息参数
 * 
 * 参数说明：
 * - receiver_id: 接收方用户ID（必须是正整数）
 * - content: 消息内容（必须非空）
 */
$receiverId = (int)($_POST['receiver_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

// 参数验证
if (!$receiverId || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// ==================== 业务逻辑 ====================
$pdo = connectDB();

// 第1步：检查接收方是否存在且未被黑名单
/**
 * 验证接收方用户的存在性和可用性
 * 条件：用户必须存在、未被黑名单、未被删除
 */
$stmt = $pdo->prepare('
    SELECT id 
    FROM users 
    WHERE id = ? 
    AND is_blacklisted = 0 
    AND is_deleted = 0
');
$stmt->execute([$receiverId]);
if (!$stmt->fetch()) {
    // 接收方不存在或被限制
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Receiver not found']);
    exit;
}

// 第2步：检查双向黑名单
/**
 * 处理黑名单逻辑
 * 如果发送方拉黑了接收方，或接收方拉黑了发送方，都不允许发送消息
 */
$stmt = $pdo->prepare('
    SELECT id 
    FROM blacklist 
    WHERE (user_id = ? AND blocked_user_id = ?) 
    OR (user_id = ? AND blocked_user_id = ?)
');
$stmt->execute([$user['id'], $receiverId, $receiverId, $user['id']]);
if ($stmt->fetch()) {
    // 被黑名单限制
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Cannot send message']);
    exit;
}

// 第3步：保存消息到数据库
/**
 * 插入新消息记录
 * 字段说明：
 * - sender_id: 发送方用户ID
 * - receiver_id: 接收方用户ID
 * - content: 消息内容
 * - created_at: 创建时间（由数据库自动记录）
 */
$stmt = $pdo->prepare('
    INSERT INTO messages (sender_id, receiver_id, content) 
    VALUES (?, ?, ?)
');

try {
    $stmt->execute([$user['id'], $receiverId, $content]);
    
    // ==================== 返回结果 ====================
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} catch (PDOException $e) {
    // 数据库错误
    error_log('Message insert error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
?>