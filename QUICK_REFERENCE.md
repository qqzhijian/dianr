# DianR 编码规范快速参考指南

## 目录
1. [文件头注释](#文件头注释)
2. [函数注释](#函数注释)
3. [代码区段](#代码区段)
4. [命名规范](#命名规范)
5. [常用代码片段](#常用代码片段)
6. [安全检查清单](#安全检查清单)

---

## 文件头注释

### 模板
```php
<?php
/**
 * 文件名: filename.php
 * 
 * 功能说明（简洁，1-3句）：
 * - 第一个功能
 * - 第二个功能
 * - 第三个功能
 * 
 * 详细说明（可选，仅在必要时）
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */
```

### 示例
```php
<?php
/**
 * 文件名: login.php
 * 
 * 用户登录页面和表单处理
 * 
 * 功能说明：
 * - 显示登录表单
 * - 处理POST登录请求
 * - 验证用户凭证
 * 
 * @author DianR Team
 * @version 1.0.0
 * @since 2024-01-01
 */
```

---

## 函数注释

### 模板
```php
/**
 * 简洁的一句话说明函数作用
 * 
 * 详细说明（如有必要）
 * 可以分多行说明函数的工作原理、算法等
 * 
 * @param type $name 参数说明
 * @return type 返回值说明
 * 
 * @example
 * $result = functionName('value');
 * echo $result;
 */
function functionName(string $name): string
{
    // 函数体
}
```

### 示例
```php
/**
 * 获取当前登录用户的完整信息
 * 
 * 首先尝试从会话缓存中获取，如果不存在则从数据库查询
 * 查询结果缓存在 $_SESSION['current_user'] 中
 * 
 * @return array|null 用户数据数组，或null如果用户未登录
 * 
 * @example
 * if (isLoggedIn()) {
 *     $user = getCurrentUser();
 *     echo $user['nickname'];
 * }
 */
function getCurrentUser(): ?array
{
    // ...
}
```

### PHPDoc标签速查

| 标签 | 说明 | 示例 |
|-----|------|------|
| @param | 参数说明 | `@param string $email 用户邮箱` |
| @return | 返回值 | `@return bool 成功返回true` |
| @throws | 异常 | `@throws PDOException 数据库错误` |
| @example | 使用例子 | `@example $result = func();` |
| @see | 参考链接 | `@see otherFunction()` |
| @author | 作者 | `@author John Doe` |
| @version | 版本 | `@version 1.0.0` |
| @since | 创建时间 | `@since 2024-01-01` |
| @deprecated | 已弃用 | `@deprecated 请用newFunc()` |

---

## 代码区段

### 区段注释模板
```php
// ==================== 区段名称 ====================
// 代码内容
```

### 标准的区段顺序

```php
<?php
// 1. 文件头PHPDoc注释
/**
 * ...
 */

// 2. 导入依赖
require_once 'config/config.php';

// 3. 权限检查
ensureLoggedIn();

// 4. 常量定义
define('CONSTANT_NAME', 'value');

// 5. 业务逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ...
}

// 6. 页面准备
$title = '页面标题';
include 'includes/header.php';

// 7. 页面渲染
?>
<!-- HTML内容 -->
<?php include 'includes/footer.php'; ?>
```

---

## 命名规范

### 常量 (CONSTANT_NAME)
```php
define('DB_HOST', '127.0.0.1');
define('ONLINE_THRESHOLD', 120);
define('SITE_NAME', '点燃');
```

### 变量 ($variableName)
```php
$userId = 123;
$userName = '张三';
$isLoggedIn = true;
$userList = [];
```

### 函数 (functionName)
```php
function getCurrentUser(): ?array { }
function hashPassword(string $password): string { }
function maskMobile(string $mobile): string { }
```

### 类 (ClassName)
```php
class UserManager { }
class ActivityHandler { }
class DatabaseConnection { }
```

### 文件名
- PHP文件: `config.php`, `helper_functions.php`
- HTML文件: `index.html`, `login.html`
- CSS文件: `style.css`, `responsive.css`

---

## 常用代码片段

### 1. 确保用户已登录
```php
<?php
require_once 'config/config.php';

// 检查登录状态
ensureLoggedIn();

// 获取当前用户
$user = getCurrentUser();
?>
```

### 2. 处理表单提交
```php
<?php
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取输入
    $input = trim($_POST['field'] ?? '');
    
    // 验证
    if (empty($input)) {
        $errors[] = '字段不能为空';
    }
    
    // 处理
    if (empty($errors)) {
        // 执行操作
    }
}
?>
```

### 3. 数据库查询
```php
<?php
// 获取连接
$pdo = connectDB();

// 预编译查询
$stmt = $pdo->prepare('
    SELECT id, name, email 
    FROM users 
    WHERE id = ? 
    LIMIT 1
');

// 执行查询
$stmt->execute([$userId]);

// 获取结果
$user = $stmt->fetch();

// 多条记录
$users = $stmt->fetchAll();
?>
```

### 4. HTML输出（防XSS）
```php
<?php
// 转义字符串输出
echo htmlspecialchars($userInput);

// 转义属性值
echo 'href="' . htmlspecialchars($url) . '"';

// 数组方式输出
$nickname = htmlspecialchars($user['nickname']);
echo "<h1>{$nickname}</h1>";
?>
```

### 5. 错误处理
```php
<?php
try {
    $pdo = connectDB();
    // 数据库操作
} catch (PDOException $e) {
    // 记录错误
    error_log('Error: ' . $e->getMessage());
    // 显示友好提示
    echo '操作失败，请稍后重试';
}
?>
```

### 6. 条件判断最佳实践
```php
<?php
// ✅ 好的做法 - 使用强类型比较
if ($user === null) {
    // 处理未登录
}

if ((int)$count > 0) {
    // 处理非空
}

// ✅ 推荐的布尔判断
if (isLoggedIn()) {
    // 已登录
}

// ❌ 避免的做法
if ($user) {
    // 可能存在类型转换问题
}
?>
```

---

## 安全检查清单

### 在提交代码前检查

- [ ] **XSS防护**: 所有动态输出都使用了 `htmlspecialchars()`
- [ ] **SQL注入防护**: 所有数据库查询都使用了预编译语句
- [ ] **认证检查**: 需要登录的页面都在顶部调用了 `ensureLoggedIn()`
- [ ] **权限检查**: 管理员功能都调用了 `requireAdmin()`
- [ ] **输入验证**: 所有用户输入都进行了 `trim()` 和验证
- [ ] **错误处理**: 数据库操作用了 try-catch
- [ ] **密码安全**: 使用了 `hashPassword()` 和 `verifyPassword()`
- [ ] **没有硬编码密码**: 所有凭证都从环境变量读取

### 代码质量检查

- [ ] **注释完整**: 所有函数都有PHPDoc注释
- [ ] **命名清晰**: 变量名和函数名易于理解
- [ ] **格式统一**: 缩进和空行符合规范
- [ ] **没有重复代码**: 考虑提取为公共函数
- [ ] **异常处理**: 可能出错的地方都有处理
- [ ] **性能考虑**: 是否有N+1查询问题

---

## 快速修复常见问题

### 问题：SQL语句中的参数未被转义
**错误示例：**
```php
$stmt = $pdo->query("SELECT * FROM users WHERE id = $userId");  // ❌ SQL注入风险
```

**正确做法：**
```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');      // ✅ 使用预编译
$stmt->execute([$userId]);
$user = $stmt->fetch();
```

### 问题：输出的HTML包含用户输入
**错误示例：**
```php
echo "<p>欢迎, {$_POST['name']}</p>";  // ❌ XSS攻击风险
```

**正确做法：**
```php
echo '<p>欢迎, ' . htmlspecialchars($_POST['name']) . '</p>';  // ✅ 转义输出
```

### 问题：函数没有注释
**错误示例：**
```php
function maskMobile($mobile) {  // ❌ 缺少注释
    return substr($mobile, 0, 3) . '****' . substr($mobile, 7);
}
```

**正确做法：**
```php
/**
 * 隐藏手机号的中间数字
 * @param string $mobile 原始手机号
 * @return string 隐藏后的手机号 (138****5678)
 */
function maskMobile(string $mobile): string {
    return substr($mobile, 0, 3) . '****' . substr($mobile, 7);
}
```

---

## 开发工作流

### 1. 编写新文件
```
1. 添加文件头PHPDoc注释
2. 按标准顺序组织代码
3. 为所有函数添加PHPDoc
4. 添加代码区段注释
5. 测试代码功能
6. 运行安全检查清单
7. 提交代码
```

### 2. 修改现有文件
```
1. 保持原有注释风格
2. 为新函数添加注释
3. 更新文件头中的修改日期
4. 添加重大改动的注释
5. 运行安全检查清单
6. 提交代码
```

### 3. 代码审查
```
1. 检查是否符合命名规范
2. 检查是否有PHPDoc注释
3. 检查是否有安全问题
4. 检查是否有逻辑错误
5. 检查代码格式是否统一
```

---

## 参考资源

- 📄 [完整的代码审查报告](CODE_REVIEW_AND_IMPROVEMENTS.md)
- 📄 [详细的编码规范](CODE_STANDARDS.md)
- 📄 [优化总结](OPTIMIZATION_SUMMARY.md)
- 🔗 [PHPDoc官方文档](https://www.phpdoc.org/docs/latest/index.html)
- 🔗 [PHP安全最佳实践](https://www.php.net/manual/en/security.php)

---

## 常见问题 (FAQ)

**Q: 什么时候需要添加注释？**  
A: 所有函数、类都需要。复杂的逻辑、特殊处理也需要添加行注释。

**Q: 注释应该有多详细？**  
A: 足够让不熟悉代码的人理解即可，避免过度注释和冗余说明。

**Q: 可以混用不同的注释风格吗？**  
A: 不建议。为了一致性，在整个项目中保持统一的风格。

**Q: 如何处理快速修复或临时代码？**  
A: 使用 `// HACK:` 或 `// TODO:` 标记，并在后续优化时处理。

---

**版本**: 1.0.0  
**最后更新**: 2024-01-01  
**维护**: DianR Team
