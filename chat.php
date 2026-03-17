<?php
/**
 * 文件名: chat.php
 * 
 * 用户聊天功能页面
 * 
 * 功能说明：
 * - 显示联系人列表
 * - 显示聊天消息历史
 * - 支持实时消息发送
 * - 检查黑名单和验证状态
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

// 检查用户是否已验证
if (!isset($user['is_verified']) || !$user['is_verified']) {
    redirect('/verify.php');
}

// ==================== 业务逻辑 ====================
/**
 * 获取要与之聊天的用户ID
 */
$receiver_id = (int)($_GET['user'] ?? 0);

$title = '聊天';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">联系人</div>
            <div class="card-body">
                <?php
                $pdo = connectDB();
                // Get users not blacklisted and not self
                $stmt = $pdo->prepare("SELECT u.id, u.nickname, u.last_seen FROM users u LEFT JOIN blacklist b ON (b.user_id = ? AND b.blocked_user_id = u.id) OR (b.user_id = u.id AND b.blocked_user_id = ?) WHERE u.id != ? AND u.is_blacklisted = 0 AND b.id IS NULL ORDER BY u.last_seen DESC");
                $stmt->execute([$user['id'], $user['id'], $user['id']]);
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($contacts)) {
                    echo '<p>暂无联系人</p>';
                } else {
                    foreach ($contacts as $contact) {
                        $status = getOnlineStatus($contact['last_seen']);
                        $active = $receiver_id == $contact['id'] ? 'active' : '';
                        echo "<a href='/chat.php?user={$contact['id']}' class='list-group-item list-group-item-action {$active}'>";
                        echo "<span class='online-status {$status}'></span>{$contact['nickname']}";
                        echo "</a>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <?php if ($receiver_id): ?>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_blacklisted = 0");
            $stmt->execute([$receiver_id]);
            $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$receiver) {
                echo '<div class="alert alert-danger">用户不存在</div>';
            } else {
                // Check if blocked
                $stmt = $pdo->prepare("SELECT id FROM blacklist WHERE (user_id = ? AND blocked_user_id = ?) OR (user_id = ? AND blocked_user_id = ?)");
                $stmt->execute([$user['id'], $receiver_id, $receiver_id, $user['id']]);
                $blocked = $stmt->fetch();
                if ($blocked) {
                    echo '<div class="alert alert-warning">您已被对方拉黑或已拉黑对方</div>';
                } else {
            ?>
                <div class="card">
                    <div class="card-header">与 <?php echo $receiver['nickname']; ?> 聊天</div>
                    <div class="card-body">
                        <div id="chat-messages" class="chat-messages">
                            <!-- Messages will be loaded here -->
                        </div>
                        <form id="chat-form" class="chat-input">
                            <input type="hidden" id="receiver-id" value="<?php echo $receiver_id; ?>">
                            <input type="text" id="message-input" class="form-control" placeholder="输入消息..." required>
                            <button type="submit" class="btn btn-primary">发送</button>
                        </form>
                    </div>
                </div>
            <?php
                }
            }
            ?>
        <?php else: ?>
            <div class="alert alert-info">选择一个联系人开始聊天</div>
        <?php endif; ?>
    </div>
</div>

<script>
/**
 * 聊天功能的JavaScript代码
 * 
 * 功能说明：
 * - 定期加载新消息
 * - 处理消息发送
 * - 更新聊天界面
 */

const currentUserId = <?php echo $user['id']; ?>;

<?php if ($receiver_id && !$blocked): ?>
// 页面加载时加载一次消息
loadMessages();
// 每5秒自动刷新一次消息
setInterval(loadMessages, 5000);

/**
 * 加载聊天消息
 * 
 * 从服务器获取与接收方的所有消息，并更新聊天界面
 * 
 * BUG FIX: 原来使用 div.innerHTML = \`...\` 会导致XSS漏洞
 * 修复为使用 textContent 或适当的HTML转义
 */
function loadMessages() {
    fetch('/api/messages.php?receiver=' + <?php echo $receiver_id; ?>)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('chat-messages');
            container.innerHTML = '';
            data.forEach(msg => {
                const div = document.createElement('div');
                div.className = 'message ' + (msg.sender_id == currentUserId ? 'sent' : 'received');
                
                // BUG FIX: 使用 textContent 而不是 innerHTML 来防止XSS攻击
                // 这样用户输入的内容会被当作纯文本，而不是HTML
                const senderNameSpan = document.createElement('strong');
                senderNameSpan.textContent = msg.sender_name;
                
                const contentSpan = document.createElement('span');
                contentSpan.textContent = msg.content;
                
                const timeSmall = document.createElement('small');
                timeSmall.textContent = msg.created_at;
                
                // 组织HTML元素
                div.appendChild(senderNameSpan);
                div.appendChild(document.createTextNode(': '));
                div.appendChild(contentSpan);
                div.appendChild(document.createElement('br'));
                div.appendChild(timeSmall);
                
                container.appendChild(div);
            });
            container.scrollTop = container.scrollHeight;
        })
        .catch(err => {
            console.error('加载消息失败:', err);
        });
}

/**
 * 处理表单提交（发送消息）
 */
document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    if (!content) return;

    // 发送消息到服务器
    ajaxPost('/api/send_message.php', { 
        receiver_id: <?php echo $receiver_id; ?>, 
        content: content 
    }, function() {
        // 发送成功，清空输入框并刷新消息列表
        input.value = '';
        loadMessages();
    }, function(err) {
        // 发送失败
        alert('消息发送失败，请稍后重试');
        console.error('发送失败:', err);
    });
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>