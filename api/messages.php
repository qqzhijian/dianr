<?php
/**
 * 文件名: messages.php
 * 
 * API端点：获取聊天消息
 * 
 * 功能说明：
 * - 获取两个用户之间的聊天消息历史
 * - 仅返回最近30天的消息
 * - 返回JSON格式的消息列表
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once '../config/config.php';

// ==================== 权限检查 ====================
// 检查用户是否已登录
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
 * 获取聊天对方的用户ID
 * 必须是正整数
 */
$receiverId = (int)($_GET['receiver'] ?? 0);

// 参数不能为空
if (!$receiverId) {
    http_response_code(400);
    exit;
}

// ==================== 业务逻辑 ====================
$pdo = connectDB();

/**
 * 查询聊天消息
 * 
 * 条件说明：
 * 1. 两个方向都查询（A→B 或 B→A）
 * 2. 仅查询最近30天的消息
 * 3. 按创建时间升序排列（最早的优先）
 * 
 * 返回字段：
 * - 消息ID、内容、发送者ID、接收者ID、创建时间
 * - 发送者昵称（自动关联）
 */
$stmt = $pdo->prepare('
    SELECT 
        m.id,
        m.sender_id,
        m.receiver_id,
        m.content,
        m.created_at,
        u.nickname AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (
        (m.sender_id = ? AND m.receiver_id = ?) 
        OR 
        (m.sender_id = ? AND m.receiver_id = ?)
    )
    AND m.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY m.created_at ASC
');
$stmt->execute([$user['id'], $receiverId, $receiverId, $user['id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================== 返回结果 ====================
/**
 * 注意：json_encode() 会自动转义特殊字符，
 * 所以消息内容中的HTML和JavaScript代码都会被转义，
 * 在客户端JavaScript中使用 textContent 而不是 innerHTML 来显示
 */
header('Content-Type: application/json');
echo json_encode($messages);
?>