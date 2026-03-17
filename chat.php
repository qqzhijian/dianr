<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
if (!$user) {
    redirect('/login.php');
}

// Check if verified
if (!isset($user['is_verified']) || !$user['is_verified']) {
    redirect('/verify.php');
}
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
const currentUserId = <?php echo $user['id']; ?>;
<?php if ($receiver_id && !$blocked): ?>
loadMessages();
setInterval(loadMessages, 5000); // Refresh every 5 seconds

function loadMessages() {
    fetch('/api/messages.php?receiver=' + <?php echo $receiver_id; ?>)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('chat-messages');
            container.innerHTML = '';
            data.forEach(msg => {
                const div = document.createElement('div');
                div.className = 'message ' + (msg.sender_id == currentUserId ? 'sent' : 'received');
                div.innerHTML = `<strong>${msg.sender_name}:</strong> ${msg.content}<br><small>${msg.created_at}</small>`;
                container.appendChild(div);
            });
            container.scrollTop = container.scrollHeight;
        });
}

document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    if (!content) return;

    ajaxPost('/api/send_message.php', { receiver_id: <?php echo $receiver_id; ?>, content: content }, function() {
        input.value = '';
        loadMessages();
    });
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>