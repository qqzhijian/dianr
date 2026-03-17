<?php
/**
 * 文件名: logout.php
 * 
 * 用户退出登录处理页面
 * 
 * 功能说明：
 * - 销毁用户会话
 * - 清除所有会话数据
 * - 重定向到首页
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */

require_once 'config/config.php';

// ==================== 会话清理 ====================
/**
 * 销毁用户会话
 * 清除会话中的所有数据（user_id, current_user等）
 */
session_destroy();

// ==================== 重定向 ====================
/**
 * 重定向到首页
 */
redirect('/');
?>