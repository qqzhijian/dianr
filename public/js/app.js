// Custom JavaScript for Dianr
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh online status
    setInterval(updateOnlineStatus, 60000); // every minute

    // Chat functionality
    const chatForm = document.getElementById('chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }

    // Profile view request
    const requestButtons = document.querySelectorAll('.request-profile');
    requestButtons.forEach(button => {
        button.addEventListener('click', function() {
            requestProfileView(this.dataset.userId);
        });
    });
});

function updateOnlineStatus() {
    fetch('/api/online-status')
        .then(response => response.json())
        .then(data => {
            // Update status indicators
            data.forEach(user => {
                const statusEl = document.querySelector(`.status-${user.id}`);
                if (statusEl) {
                    statusEl.className = `online-status ${user.status}`;
                }
            });
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
                msgEl.innerHTML = `<strong>${msg.sender_nickname}:</strong> ${msg.message} <small>${msg.sent_at}</small>`;
                messagesEl.appendChild(msgEl);
            });
            messagesEl.scrollTop = messagesEl.scrollHeight;
        });
}

function requestProfileView(userId) {
    fetch('/api/request-profile', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            target_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    });
}