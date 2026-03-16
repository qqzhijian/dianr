<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();

// Check if verified
if (!$user['is_verified']) {
    redirect('/verify.php');
}

$receiver_id = (int)($_GET['user'] ?? 0);

$title = '私聊';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>私聊</h2>
                <a href="/chat-lobby.php" class="btn btn-primary">
                    <i class="fas fa-comments"></i> 进入聊天大厅
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Contacts Sidebar -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">联系人</div>
                <div class="card-body">
                    <?php
                    $pdo = connectDB();
                    // Get users with chat history, not blacklisted
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT
                            u.id,
                            u.nickname,
                            u.last_seen,
                            COUNT(CASE WHEN c.is_read = 0 AND c.sender_id = u.id THEN 1 END) as unread_count,
                            MAX(c.sent_at) as last_message_time
                        FROM users u
                        LEFT JOIN chats c ON (c.sender_id = u.id AND c.receiver_id = ?) OR (c.sender_id = ? AND c.receiver_id = u.id)
                        LEFT JOIN blacklist b ON (b.user_id = ? AND b.blocked_user_id = u.id) OR (b.user_id = u.id AND b.blocked_user_id = ?)
                        WHERE u.id != ?
                        AND u.is_blacklisted = 0
                        AND b.id IS NULL
                        AND c.id IS NOT NULL
                        GROUP BY u.id, u.nickname, u.last_seen
                        ORDER BY last_message_time DESC
                    ");
                    $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
                    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($contacts)) {
                        echo '<p class="text-muted">暂无聊天记录</p>';
                        echo '<p class="text-center mt-3"><a href="/chat-lobby.php" class="btn btn-outline-primary btn-sm">去聊天大厅找人聊天</a></p>';
                    } else {
                        foreach ($contacts as $contact) {
                            $status = getOnlineStatus($contact['last_seen']);
                            $active = $receiver_id == $contact['id'] ? 'active' : '';
                            $unread_badge = $contact['unread_count'] > 0 ? " <span class='badge bg-danger'>{$contact['unread_count']}</span>" : '';

                            echo "<a href='/chat.php?user={$contact['id']}' class='list-group-item list-group-item-action {$active} d-flex justify-content-between align-items-center'>";
                            echo "<div><span class='online-status {$status}'></span>{$contact['nickname']}</div>";
                            echo $unread_badge;
                            echo "</a>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
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
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span id="chat-partner-status" class="online-status <?php echo getOnlineStatus($receiver['last_seen']); ?>"></span>
                                与 <?php echo $receiver['nickname']; ?> 聊天
                            </div>
                            <div>
                                <a href="/profile.php?id=<?php echo $receiver_id; ?>" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-user"></i> 查看资料
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="blockUser(<?php echo $receiver_id; ?>)">
                                    <i class="fas fa-ban"></i> 拉黑
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chat-messages" class="chat-messages">
                                <!-- Messages will be loaded here -->
                            </div>
                            <div class="chat-input-area mt-3">
                                <form id="chat-form" class="d-flex">
                                    <input type="hidden" id="receiver-id" value="<?php echo $receiver_id; ?>">
                                    <input type="text" id="message-input" class="form-control me-2"
                                           placeholder="输入消息..." maxlength="500">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                }
                ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h4>选择联系人开始聊天</h4>
                    <p class="text-muted">或 <a href="/chat-lobby.php">进入聊天大厅</a> 寻找新朋友</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const currentUserId = <?php echo $user['id']; ?>;
<?php if ($receiver_id && !$blocked): ?>
loadMessages();
setInterval(loadMessages, 3000); // Refresh every 3 seconds
<?php endif; ?>

function loadMessages() {
    const receiverId = document.getElementById('receiver-id').value;
    fetch(`/api/messages?receiver_id=${receiverId}`)
        .then(response => response.json())
        .then(data => {
            const messagesEl = document.getElementById('chat-messages');
            messagesEl.innerHTML = '';
            data.messages.forEach(msg => {
                const msgEl = document.createElement('div');
                msgEl.className = `message ${msg.sender_id == currentUserId ? 'sent' : 'received'}`;

                const time = new Date(msg.sent_at).toLocaleString('zh-CN', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                msgEl.innerHTML = `
                    <div class="message-content">
                        <div class="message-text">${escapeHtml(msg.message)}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;
                messagesEl.appendChild(msgEl);
            });
            messagesEl.scrollTop = messagesEl.scrollHeight;
        });
}

function sendMessage() {
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();
    if (!message) return;

    const receiverId = document.getElementById('receiver-id').value;

    fetch('/api/send-message', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            receiver_id: receiverId,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            loadMessages();
        }
    });
}

// Enter key to send message
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }

    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
});

function blockUser(userId) {
    if (confirm('确定要拉黑此用户吗？')) {
        fetch('/api/block-user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ blocked_user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            location.reload();
        });
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
.chat-messages {
    height: 400px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    background: #f8f9fa;
}

.message {
    margin-bottom: 15px;
    display: flex;
}

.message.sent {
    justify-content: flex-end;
}

.message.received {
    justify-content: flex-start;
}

.message-content {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
}

.message.sent .message-content {
    background: #007bff;
    color: white;
}

.message.received .message-content {
    background: white;
    color: #333;
    border: 1px solid #dee2e6;
}

.message-time {
    font-size: 0.75rem;
    margin-top: 5px;
    opacity: 0.7;
}

.chat-input-area {
    border-top: 1px solid #dee2e6;
    padding-top: 15px;
}

.list-group-item.active {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}
</style>

<?php include 'includes/footer.php'; ?>

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
const currentUserId = <?php echo $user['id']; ?>;
<?php if ($receiver_id && !$blocked): ?>
loadMessages();
setInterval(loadMessages, 5000); // Refresh every 5 seconds
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>