<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Chat Messages Area -->
        <div class="col-md-9 col-lg-10">
            <div class="card border-0 shadow-sm" style="height: calc(100vh - 100px);">
                <!-- Chat Header -->
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <a href="/chat" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h5 class="mb-0">
                                <i class="fas fa-comments"></i> Sala #<?= $roomId ?>
                            </h5>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#searchModal">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#membersModal">
                                <i class="fas fa-users"></i> Membros (<?= count($members) ?>)
                            </button>
                            <a href="/chat/room/<?= $roomId ?>/settings" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-cog"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Messages Container -->
                <div class="card-body overflow-auto" id="messagesContainer" style="flex: 1; max-height: calc(100vh - 250px);">
                    <div id="messagesList">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Nenhuma mensagem ainda. Seja o primeiro a enviar!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <?= $this->include('chat/partials/message', ['message' => $message, 'employee' => $employee]) ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Typing Indicator -->
                    <div id="typingIndicator" class="text-muted small ms-3" style="display: none;">
                        <i class="fas fa-circle-notch fa-spin"></i> <span id="typingUser"></span> está digitando...
                    </div>
                </div>

                <!-- Message Input -->
                <div class="card-footer bg-white border-top">
                    <!-- Reply Preview -->
                    <div id="replyPreview" class="alert alert-info py-2 px-3 mb-2" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small><strong>Respondendo a:</strong></small>
                                <div class="small" id="replyText"></div>
                            </div>
                            <button type="button" class="btn-close btn-sm" onclick="cancelReply()"></button>
                        </div>
                    </div>

                    <form id="messageForm" onsubmit="sendMessage(event)">
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" title="Emoji">
                                <i class="far fa-smile"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" title="Anexar arquivo">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input
                                type="text"
                                class="form-control"
                                id="messageInput"
                                placeholder="Digite sua mensagem..."
                                autocomplete="off"
                                maxlength="5000"
                                required
                            >
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane"></i> Enviar
                            </button>
                        </div>
                        <small class="text-muted">
                            <span id="charCount">0</span>/5000 caracteres
                        </small>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar - Members -->
        <div class="col-md-3 col-lg-2 d-none d-md-block bg-light border-start" style="height: calc(100vh - 100px); overflow-y: auto;">
            <div class="p-3 border-bottom">
                <h6 class="mb-0">Membros (<?= count($members) ?>)</h6>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($members as $member): ?>
                    <div class="list-group-item">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle bg-<?= $member->employee_id === $employee['id'] ? 'primary' : 'secondary' ?> text-white me-2">
                                <?= strtoupper(substr($member->name, 0, 2)) ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small">
                                    <strong><?= esc($member->name) ?></strong>
                                    <?php if ($member->role === 'admin'): ?>
                                        <span class="badge bg-warning text-dark">Admin</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <i class="fas fa-circle <?= $member->is_online ?? false ? 'text-success' : 'text-secondary' ?>"></i>
                                    <?= $member->is_online ?? false ? 'Online' : 'Offline' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Members Modal (Mobile) -->
<div class="modal fade" id="membersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Membros</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <?php foreach ($members as $member): ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-secondary text-white me-2">
                                    <?= strtoupper(substr($member->name, 0, 2)) ?>
                                </div>
                                <div>
                                    <strong><?= esc($member->name) ?></strong><br>
                                    <small class="text-muted"><?= esc($member->department) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buscar Mensagens</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Digite para buscar...">
                </div>
                <div id="searchResults">
                    <!-- Search results will be displayed here -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: inline-flex;
    align-items-center;
    justify-content-center;
    font-weight: bold;
    font-size: 14px;
}

#messagesContainer {
    scroll-behavior: smooth;
}

#messagesContainer::-webkit-scrollbar {
    width: 8px;
}

#messagesContainer::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}

.message-bubble {
    max-width: 70%;
    word-wrap: break-word;
}

.message-own {
    margin-left: auto;
}

.message-time {
    font-size: 11px;
}

.reaction-picker {
    font-size: 20px;
}

.reaction-item {
    cursor: pointer;
    transition: transform 0.2s;
}

.reaction-item:hover {
    transform: scale(1.3);
}
</style>

<script src="/assets/js/chat.js"></script>
<script>
const roomId = <?= $roomId ?>;
const employeeId = <?= $employee['id'] ?>;
let replyToMessageId = null;

// Initialize WebSocket
const wsUrl = 'ws://<?= $_SERVER['HTTP_HOST'] ?? 'localhost' ?>:2346';
const authToken = 'Bearer <?= session()->get('auth_token') ?? 'session' ?>';
const chat = new ChatClient(wsUrl, authToken);

// Set current room
chat.setCurrentRoom(roomId);

// Event handlers
chat.onConnected = () => {
    console.log('Connected to chat');
    chat.joinRoom(roomId);
    chat.markAsRead(roomId);
};

chat.onMessageReceived = (data) => {
    if (data.room_id === roomId) {
        appendMessage(data);
        scrollToBottom();
        chat.markAsRead(roomId);
    }
};

chat.onTypingIndicator = (data) => {
    if (data.room_id === roomId && data.employee_id !== employeeId) {
        showTypingIndicator(data.typing);
    }
};

chat.onReaction = (data) => {
    if (data.room_id === roomId) {
        updateReaction(data);
    }
};

// Connect
chat.connect();

// Send message
function sendMessage(event) {
    event.preventDefault();

    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (!message) {
        return;
    }

    chat.sendMessage(roomId, message, replyToMessageId);

    input.value = '';
    replyToMessageId = null;
    document.getElementById('replyPreview').style.display = 'none';
    updateCharCount();
}

// Append message to chat
function appendMessage(data) {
    const messagesList = document.getElementById('messagesList');
    const isOwn = data.sender_id === employeeId;

    const messageHtml = `
        <div class="d-flex mb-3 ${isOwn ? 'justify-content-end' : ''}">
            <div class="message-bubble ${isOwn ? 'message-own bg-primary text-white' : 'bg-light'} p-3 rounded shadow-sm">
                ${!isOwn ? `<div class="small fw-bold mb-1">${escapeHtml(data.sender_name)}</div>` : ''}
                <div>${formatMessage(data.message)}</div>
                <div class="message-time text-${isOwn ? 'white' : 'muted'} mt-1">
                    ${formatMessageTime(data.timestamp)}
                </div>
            </div>
        </div>
    `;

    messagesList.insertAdjacentHTML('beforeend', messageHtml);
}

// Scroll to bottom
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
}

// Show typing indicator
function showTypingIndicator(isTyping) {
    const indicator = document.getElementById('typingIndicator');
    indicator.style.display = isTyping ? 'block' : 'none';

    if (isTyping) {
        scrollToBottom();
    }
}

// Typing indicator
let typingTimeout;
document.getElementById('messageInput').addEventListener('input', function() {
    clearTimeout(typingTimeout);

    chat.sendTyping(roomId, true);

    typingTimeout = setTimeout(() => {
        chat.sendTyping(roomId, false);
    }, 1000);

    updateCharCount();
});

// Character count
function updateCharCount() {
    const input = document.getElementById('messageInput');
    document.getElementById('charCount').textContent = input.value.length;
}

// Reply to message
function replyToMessage(messageId, messageText) {
    replyToMessageId = messageId;
    document.getElementById('replyText').textContent = messageText;
    document.getElementById('replyPreview').style.display = 'block';
    document.getElementById('messageInput').focus();
}

// Cancel reply
function cancelReply() {
    replyToMessageId = null;
    document.getElementById('replyPreview').style.display = 'none';
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatMessage(text) {
    let formatted = escapeHtml(text);
    formatted = formatted.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
    formatted = formatted.replace(/\n/g, '<br>');
    return formatted;
}

function formatMessageTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// Search messages
document.getElementById('searchInput')?.addEventListener('input', debounce(function(e) {
    const query = e.target.value.trim();

    if (query.length < 3) {
        document.getElementById('searchResults').innerHTML = '';
        return;
    }

    fetch(`/chat/room/${roomId}/search?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                let html = '<div class="list-group">';
                data.messages.forEach(msg => {
                    html += `
                        <div class="list-group-item">
                            <strong>${escapeHtml(msg.sender_name)}</strong>
                            <div class="small">${formatMessage(msg.message)}</div>
                            <small class="text-muted">${formatMessageTime(msg.created_at)}</small>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('searchResults').innerHTML = html;
            } else {
                document.getElementById('searchResults').innerHTML = '<p class="text-muted">Nenhum resultado encontrado.</p>';
            }
        });
}, 500));

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Auto-scroll to bottom on load
scrollToBottom();

// Mark as read when viewing
chat.markAsRead(roomId);
</script>

<?= $this->endSection() ?>
