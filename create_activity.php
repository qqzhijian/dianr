<?php
/**
 * 文件名: create_activity.php
 * 
 * 创建活动处理页面
 * 
 * 功能说明：
 * - 处理新活动创建请求
 * - 仅允许媒人和商家用户创建
 * - 验证活动信息的有效性
 * - 保存活动到数据库
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

// ==================== 权限验证 ====================
/**
 * 仅允许媒人(mediator)和商家(merchant)创建活动
 * 普通用户会返回403权限错误
 */
if (!in_array($user['role'], ['mediator', 'merchant'])) {
    http_response_code(403);
    echo '权限不足，只有媒人和商家才能创建活动';
    exit;
}

// ==================== 表单处理 ====================
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 获取并清理用户输入
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $location = trim($_POST['location'] ?? '');

    // 2. 输入验证
    /**
     * BUG FIX: 添加严格的输入验证
     */
    if (empty($title) || empty($eventTime)) {
        $error = '标题和时间不能为空';
    } elseif (strlen($title) < 3) {
        $error = '标题至少需要3个字符';
    } elseif (strlen($title) > 100) {
        $error = '标题过长（最多100个字符）';
    } elseif (strlen($description) > 500) {
        $error = '描述过长（最多500个字符）';
    } elseif (strlen($location) > 200) {
        $error = '地点过长（最多200个字符）';
    } else {
        // 验证事件时间是否为有效的日期时间格式
        // DateTime的构造函数会在格式不正确时抛出异常
        try {
            $eventDateTime = new DateTime($eventTime);
            // 检查事件时间是否在未来
            if ($eventDateTime <= new DateTime()) {
                $error = '活动时间必须在当前时间之后';
            }
        } catch (Exception $e) {
            $error = '活动时间格式无效';
        }
    }

    // 3. 如果验证通过，保存到数据库
    if (empty($error)) {
        try {
            $pdo = connectDB();
            
            /**
             * 创建新活动
             * 所有字段都使用参数化查询防止SQL注入
             */
            $stmt = $pdo->prepare('
                INSERT INTO activities 
                (creator_id, title, description, event_time, location) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $user['id'],
                $title,
                $description ?: null,
                $eventTime,
                $location ?: null
            ]);
            
            // 创建成功，重定向到活动列表
            redirect('/activities.php');
        } catch (PDOException $e) {
            // 数据库错误
            error_log('Activity create error: ' . $e->getMessage());
            $error = '创建活动失败，请稍后重试';
        }
    }
    
    // 如果有错误，保存错误信息到会话并重定向
    if (!empty($error)) {
        $_SESSION['activity_error'] = $error;
        $_SESSION['activity_data'] = [
            'title' => $title,
            'description' => $description,
            'event_time' => $eventTime,
            'location' => $location
        ];
        redirect('/activities.php');
    }
} else {
    // 非POST请求，重定向回活动列表
    redirect('/activities.php');
}
?>
?>