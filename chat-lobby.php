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

$title = '聊天大厅';
include 'includes/header.php';
?>

<div class="chat-lobby-container">
    <div class="row h-100">
        <!-- Online Users Sidebar -->
        <div class="col-md-4 col-lg-3 chat-sidebar">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">在线用户</h5>
                    <span id="online-count" class="badge bg-success">0</span>
                </div>
                <div class="card-body p-0">
                    <div id="online-users" class="online-users-list">
                        <!-- Online users will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="col-md-8 col-lg-9 chat-main">
            <div id="chat-area" class="chat-area">
                <!-- Welcome message -->
                <div class="welcome-message">
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h4>欢迎来到聊天大厅</h4>
                        <p class="text-muted">选择左侧的用户开始聊天，或等待他人发起对话</p>
                    </div>
                </div>

                <!-- Private chat interface -->
                <div id="private-chat" class="private-chat d-none">
                    <div class="chat-header">
                        <div class="d-flex align-items-center">
                            <span id="chat-partner-status" class="online-status online me-2"></span>
                            <h5 id="chat-partner-name" class="mb-0">聊天对象</h5>
                        </div>
                        <button id="close-chat" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="chat-messages" class="chat-messages">
                        <!-- Messages will be loaded here -->
                    </div>
                    <div class="chat-input-area">
                        <form id="chat-form" class="d-flex">
                            <input type="hidden" id="receiver-id" value="">
                            <input type="text" id="message-input" class="form-control me-2"
                                   placeholder="输入消息..." maxlength="500">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incoming message notification -->
<div id="message-notification" class="message-notification">
    <div class="notification-content">
        <span id="notification-text"></span>
        <button id="view-message" class="btn btn-sm btn-primary ms-2">查看</button>
        <button id="dismiss-notification" class="btn btn-sm btn-outline-secondary ms-1">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
const currentUserId = <?php echo $user['id']; ?>;
let currentChatUserId = null;
let messageCheckInterval = null;
let onlineUsersInterval = null;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadOnlineUsers();
    startOnlineUsersRefresh();
    startMessageCheck();

    // Chat form submission
    document.getElementById('chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Close chat button
    document.getElementById('close-chat').addEventListener('click', closeChat);

    // Notification buttons
    document.getElementById('view-message').addEventListener('click', viewPendingMessage);
    document.getElementById('dismiss-notification').addEventListener('click', dismissNotification);

    // Enter key to send message
    document.getElementById('message-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});

// Load online users
function loadOnlineUsers() {
    fetch('/api/online-users')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOnlineUsers(data.users);
            }
        })
        .catch(error => console.error('Error loading online users:', error));
}

// Display online users in sidebar
function displayOnlineUsers(users) {
    const container = document.getElementById('online-users');
    const countEl = document.getElementById('online-count');

    countEl.textContent = users.length;

    container.innerHTML = '';

    if (users.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3">暂无在线用户</div>';
        return;
    }

    users.forEach(user => {
        const userEl = document.createElement('div');
        userEl.className = 'online-user-item';
        userEl.dataset.userId = user.id;
        userEl.onclick = () => startPrivateChat(user.id, user.nickname);

        userEl.innerHTML = `
            <div class="d-flex align-items-center">
                <span class="online-status ${user.status} me-2"></span>
                <div class="flex-grow-1">
                    <div class="user-name">${user.nickname}</div>
                    <small class="text-muted">${user.role_text}</small>
                </div>
                ${user.unread_count > 0 ? `<span class="badge bg-danger ms-2">${user.unread_count}</span>` : ''}
            </div>
        `;

        container.appendChild(userEl);
    });
}

// Start private chat with user
function startPrivateChat(userId, userName) {
    currentChatUserId = userId;

    // Update UI
    document.getElementById('chat-partner-name').textContent = userName;
    document.getElementById('receiver-id').value = userId;
    document.getElementById('private-chat').classList.remove('d-none');
    document.querySelector('.welcome-message').classList.add('d-none');

    // Load chat history
    loadMessages();

    // Mark messages as read
    markMessagesAsRead(userId);

    // Focus input
    document.getElementById('message-input').focus();
}

// Close current chat
function closeChat() {
    currentChatUserId = null;
    document.getElementById('private-chat').classList.add('d-none');
    document.querySelector('.welcome-message').classList.remove('d-none');
    document.getElementById('chat-messages').innerHTML = '';
}

// Send message
function sendMessage() {
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();

    if (!message || !currentChatUserId) return;

    fetch('/api/send-message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            receiver_id: currentChatUserId,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            loadMessages();
        } else {
            alert(data.message || '发送失败');
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('发送失败，请重试');
    });
}

// Load messages for current chat
function loadMessages() {
    if (!currentChatUserId) return;

    fetch(`/api/messages?receiver_id=${currentChatUserId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.messages);
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

// Display messages in chat area
function displayMessages(messages) {
    const container = document.getElementById('chat-messages');
    container.innerHTML = '';

    if (messages.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3">暂无消息记录</div>';
        return;
    }

    messages.forEach(msg => {
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

        container.appendChild(msgEl);
    });

    // Scroll to bottom
    container.scrollTop = container.scrollHeight;
}

// Mark messages as read
function markMessagesAsRead(userId) {
    fetch('/api/mark-read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sender_id: userId })
    });
}

// Check for new messages
function checkNewMessages() {
    fetch('/api/check-messages')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications.length > 0) {
                showMessageNotification(data.notifications[0]);
            }

            // Update unread counts
            if (data.unread_counts) {
                updateUnreadCounts(data.unread_counts);
            }
        })
        .catch(error => console.error('Error checking messages:', error));
}

// Show message notification
function showMessageNotification(notification) {
    const notificationEl = document.getElementById('message-notification');
    document.getElementById('notification-text').textContent =
        `${notification.sender_nickname}: ${notification.message.substring(0, 50)}${notification.message.length > 50 ? '...' : ''}`;

    notificationEl.classList.add('show');

    // Auto hide after 10 seconds
    setTimeout(() => {
        notificationEl.classList.remove('show');
    }, 10000);
}

// View pending message
function viewPendingMessage() {
    const notificationEl = document.getElementById('message-notification');
    const notification = notificationEl.querySelector('#notification-text').textContent;

    // Extract sender name from notification
    const senderName = notification.split(':')[0].trim();

    // Find user by name and start chat (simplified)
    const userItems = document.querySelectorAll('.online-user-item');
    userItems.forEach(item => {
        const nameEl = item.querySelector('.user-name');
        if (nameEl && nameEl.textContent === senderName) {
            const userId = item.dataset.userId;
            startPrivateChat(userId, senderName);
        }
    });

    dismissNotification();
}

// Dismiss notification
function dismissNotification() {
    document.getElementById('message-notification').classList.remove('show');
}

// Update unread counts in sidebar
function updateUnreadCounts(counts) {
    document.querySelectorAll('.online-user-item').forEach(item => {
        const userId = item.dataset.userId;
        const badge = item.querySelector('.badge');
        const count = counts[userId] || 0;

        if (count > 0) {
            if (badge) {
                badge.textContent = count;
            } else {
                const badgeEl = document.createElement('span');
                badgeEl.className = 'badge bg-danger ms-2';
                badgeEl.textContent = count;
                item.querySelector('.d-flex').appendChild(badgeEl);
            }
        } else if (badge) {
            badge.remove();
        }
    });
}

// Start periodic updates
function startOnlineUsersRefresh() {
    onlineUsersInterval = setInterval(loadOnlineUsers, 30000); // every 30 seconds
}

function startMessageCheck() {
    messageCheckInterval = setInterval(checkNewMessages, 3000); // every 3 seconds
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (onlineUsersInterval) clearInterval(onlineUsersInterval);
    if (messageCheckInterval) clearInterval(messageCheckInterval);
});
</script>

<style>
.chat-lobby-container {
    height: calc(100vh - 200px);
    min-height: 600px;
}

.chat-sidebar {
    border-right: 1px solid #dee2e6;
}

.online-users-list {
    max-height: calc(100vh - 250px);
    overflow-y: auto;
}

.online-user-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f8f9fa;
    cursor: pointer;
    transition: background-color 0.2s;
}

.online-user-item:hover {
    background-color: #f8f9fa;
}

.online-user-item .user-name {
    font-weight: 500;
    margin-bottom: 2px;
}

.chat-area {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.chat-header {
    padding: 16px 20px;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
    display: flex;
    justify-content: between;
    align-items: center;
}

.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    max-height: calc(100vh - 350px);
}

.message {
    margin-bottom: 16px;
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
    padding: 12px 16px;
    border-radius: 18px;
    position: relative;
}

.message.sent .message-content {
    background: #007bff;
    color: white;
}

.message.received .message-content {
    background: #f1f3f4;
    color: #333;
}

.message-time {
    font-size: 0.75rem;
    margin-top: 4px;
    opacity: 0.7;
}

.chat-input-area {
    padding: 16px 20px;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
}

.message-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 16px;
    max-width: 350px;
    z-index: 1050;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
}

.message-notification.show {
    transform: translateY(0);
    opacity: 1;
}

.notification-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.welcome-message {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .chat-sidebar {
        display: none;
    }

    .chat-main {
        width: 100%;
    }
}
</style>

<?php include 'includes/footer.php'; ?>