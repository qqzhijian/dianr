<?php
/**
 * 文件名: update_profile.php
 * 
 * 用户资料更新处理页面
 * 
 * 功能说明：
 * - 处理用户资料更新请求
 * - 验证输入数据的有效性
 * - 更新数据库中的用户信息
 * - 刷新用户会话缓存
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

// ==================== 表单处理 ====================
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取并清理用户输入
    $gender = trim($_POST['gender'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $region = trim($_POST['region'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $profession = trim($_POST['profession'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $businessAddress = trim($_POST['business_address'] ?? '');
    $businessScope = trim($_POST['business_scope'] ?? '');

    // ==================== 输入验证 ====================
    /**
     * BUG FIX: 添加严格的输入验证，防止无法接受的数据
     */
    
    // 验证性别（如果提供）
    if (!empty($gender) && !in_array($gender, ['male', 'female', 'other'])) {
        $errors[] = '性别值无效';
    }
    
    // 验证年龄范围
    if ($age && ($age < 13 || $age > 150)) {
        $errors[] = '年龄必须在13-150岁之间';
    }
    
    // 验证字段长度
    if (strlen($region) > 100) {
        $errors[] = '地区名称过长（最多100个字符）';
    }
    if (strlen($bio) > 500) {
        $errors[] = '简介过长（最多500个字符）';
    }
    if (strlen($profession) > 200) {
        $errors[] = '从业简介过长（最多200个字符）';
    }
    if (strlen($expertise) > 200) {
        $errors[] = '擅长领域过长（最多200个字符）';
    }
    if (strlen($businessName) > 100) {
        $errors[] = '商家名称过长（最多100个字符）';
    }
    if (strlen($businessAddress) > 200) {
        $errors[] = '地址过长（最多200个字符）';
    }
    if (strlen($businessScope) > 200) {
        $errors[] = '经营范围过长（最多200个字符）';
    }

    // ==================== 业务逻辑 ====================
    if (empty($errors)) {
        try {
            $pdo = connectDB();
            
            /**
             * 更新用户资料到数据库
             * 所有字段都使用参数化查询防止SQL注入
             */
            $stmt = $pdo->prepare('
                UPDATE users 
                SET 
                    gender = ?, 
                    age = ?, 
                    region = ?, 
                    bio = ?, 
                    profession = ?, 
                    expertise = ?, 
                    business_name = ?, 
                    business_address = ?, 
                    business_scope = ? 
                WHERE id = ?
            ');
            
            // 如果字段为空，保存为NULL
            $stmt->execute([
                $gender ?: null,
                $age ?: null,
                $region ?: null,
                $bio ?: null,
                $profession ?: null,
                $expertise ?: null,
                $businessName ?: null,
                $businessAddress ?: null,
                $businessScope ?: null,
                $user['id']
            ]);

            // 刷新用户会话信息
            reloadCurrentUser();
            
            // 重定向回个人资料页面
            redirect('/profile.php');
        } catch (PDOException $e) {
            // 数据库错误
            error_log('Profile update error: ' . $e->getMessage());
            $errors[] = '更新资料失败，请稍后重试';
        }
    }
    
    // 如果有错误，显示错误信息并返回表单
    if (!empty($errors)) {
        $_SESSION['profile_errors'] = $errors;
        $_SESSION['profile_data'] = [
            'gender' => $gender,
            'age' => $age,
            'region' => $region,
            'bio' => $bio
        ];
        redirect('/profile.php');
    }
} else {
    // 非POST请求，重定向回个人资料页面
    redirect('/profile.php');
}
?>