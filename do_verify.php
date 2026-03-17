<?php
/**
 * 文件名: do_verify.php
 * 
 * 账号验证处理页面
 * 
 * 功能说明：
 * - 处理用户验证码提交
 * - 验证验证码有效性
 * - 更新用户的验证状态
 * - 允许用户使用聊天等功能
 * 
 * 注意：当前使用硬编码验证码作为示例
 * 生产环境应该使用SMS或邮件验证
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

// ==================== 参数和状态 ====================
$error = null;

// ==================== 表单处理 ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取并清理验证码输入
    $code = trim($_POST['code'] ?? '');
    
    /**
     * BUG FIX: 验证码验证逻辑
     * 当前使用硬编码验证码
     * 实际应用中应该：
     * 1. 使用SMS/邮件系统发送验证码
     * 2. 将验证码保存到数据库，设置5分钟过期
     * 3. 验证时检查是否过期和是否超过试错次数
     */
    $expectedCode = '123456';  // TODO: 从数据库或短信服务获取
    
    if ($code === $expectedCode) {
        // 验证成功 - 更新用户验证状态
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
        $stmt->execute([$user['id']]);
        
        // 刷新用户会话缓存
        reloadCurrentUser();
        
        // 重定向到首页
        redirect('/');
    } else {
        // 验证失败
        $error = '验证码错误，请重试';
    }
    
    // 验证失败时返回验证页面
    if (!empty($error)) {
        redirect('/verify.php?error=' . urlencode($error));
    }
}

// 如果GET请求，直接重定向到验证页面
redirect('/verify.php');
?>