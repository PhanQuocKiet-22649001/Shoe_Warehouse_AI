<aside class="sidebar">
    <div class="logo">
        SMART<br>WAREHOUSE
    </div>

    <div class="menu">
        <p class="menu-title">MENU CHÍNH</p>

        <ul>
            <?php if (isset($_SESSION['role']) && strtoupper($_SESSION['role']) === 'MANAGER'): ?>
                <li class="<?= $page === 'dashboard' ? 'active' : '' ?>" style="margin-bottom: 15px;">
                    <a href="index.php?page=dashboard">Tổng quan</a>
                </li>
            <?php endif; ?>

            <li class="<?= $page === 'categories' ? 'active' : '' ?>" style="margin-bottom: 15px;">
                <a href="index.php?page=categories">Quản lý danh mục</a>
            </li>

            <?php if (isset($_SESSION['role']) && strtoupper($_SESSION['role']) === 'MANAGER'): ?>
                <li class="<?= $page === 'employees' ? 'active' : '' ?>" style="margin-bottom: 15px;">
                    <a href="index.php?page=employees">Quản lí Nhân viên</a>
                </li>

                <li style="margin-bottom: 15px;">
                    <a href="index.php?page=report" class="<?= $page === 'report' ? 'active' : '' ?>">Thống kê báo cáo</a>
                </li>

                <li style="margin-bottom: 15px;">
                    <a href="#" id="btn-open-ai" class="<?= $page === 'ai-prediction' ? 'active' : '' ?>">Trợ lý AI</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <form method="POST" action="index.php" class="mt-3" id="logout-form">
        <button type="submit" name="logout" class="logout w-100">Đăng xuất</button>
    </form>
</aside>

<div id="ai-chat-container" class="ai-chat-minimized">
    <div class="ai-chat-header d-flex justify-content-between align-items-center px-3 py-2 bg-dark text-white">
        <div class="d-flex align-items-center">
            <div class="bg-success rounded-circle me-2 animate-pulse" style="width: 8px; height: 8px;"></div>
            <span class="fw-bold" style="font-size: 13px; letter-spacing: 0.5px;">SMART AI ASSISTANT</span>
        </div>
        <button id="ai-chat-minimize" class="btn btn-sm text-white p-0 border-0">
            <i class="fas fa-minus"></i>
        </button>
    </div>

    <div id="ai-chat-body" class="p-3 bg-white">
        <div class="bot-msg shadow-sm">Chào bồ! Tôi là trợ lý AI. Bồ cần hỏi gì về hàng hóa hay tồn kho không?</div>
    </div>

    <div class="ai-chat-footer p-2 bg-light border-top">
        <div class="input-group">
            <input type="text" id="ai-chat-input" class="form-control form-control-sm border-0 bg-transparent" placeholder="Hỏi về kho (VD: Nike size 40 còn mấy đôi?)..." autocomplete="off">
            <button id="ai-chat-send" class="btn btn-link text-primary p-0 px-2">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<style>
/* CSS CHUYÊN DỤNG CHO CHATBOT */
#ai-chat-container {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 380px;
    height: 500px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    transform-origin: bottom right;
    border: 1px solid rgba(0,0,0,0.1);
}

#ai-chat-container.ai-chat-minimized {
    transform: scale(0);
    opacity: 0;
    pointer-events: none;
}

#ai-chat-body {
    flex-grow: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background-color: #fcfcfc;
}

.user-msg, .bot-msg {
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 18px;
    font-size: 13.5px;
    line-height: 1.5;
}

.user-msg {
    align-self: flex-end;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border-bottom-right-radius: 4px;
}

.bot-msg {
    align-self: flex-start;
    background-color: white;
    color: #2c3e50;
    border: 1px solid #eee;
    border-bottom-left-radius: 4px;
}

.animate-pulse {
    animation: pulse-green 2s infinite;
}

@keyframes pulse-green {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(40, 167, 69, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}

.typing { font-style: italic; color: #999; font-size: 12px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatContainer = document.getElementById('ai-chat-container');
    const chatBody = document.getElementById('ai-chat-body');
    const chatInput = document.getElementById('ai-chat-input');
    const btnOpen = document.getElementById('btn-open-ai');
    const btnMinimize = document.getElementById('ai-chat-minimize');
    const btnSend = document.getElementById('ai-chat-send');
    const logoutForm = document.getElementById('logout-form');

    // 1. Tải lịch sử chat từ SessionStorage
    const savedChat = sessionStorage.getItem('ai_chat_history');
    if (savedChat) {
        chatBody.innerHTML = savedChat;
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    // 2. Click nút Sidebar -> Mở/Đóng
    if (btnOpen) {
        btnOpen.addEventListener('click', function(e) {
            e.preventDefault();
            chatContainer.classList.toggle('ai-chat-minimized');
            if (!chatContainer.classList.contains('ai-chat-minimized')) {
                chatInput.focus();
            }
        });
    }

    // 3. Click dấu trừ -> Thu nhỏ (Không mất lịch sử)
    btnMinimize.addEventListener('click', () => {
        chatContainer.classList.add('ai-chat-minimized');
    });

    // 4. Xóa lịch sử khi Đăng xuất
    if (logoutForm) {
        logoutForm.addEventListener('submit', () => {
            sessionStorage.removeItem('ai_chat_history');
        });
    }

    // 5. Gửi tin nhắn
    async function sendMessage() {
        const text = chatInput.value.trim();
        if (!text) return;

        appendMessage('user', text);
        chatInput.value = '';

        const loadingId = 'loading-' + Date.now();
        appendMessage('bot', '<span class="typing">Đang truy vấn kho...</span>', loadingId);

        try {
            const response = await fetch(`http://127.0.0.1:8000/ask?question=${encodeURIComponent(text)}`);
            const data = await response.json();
            
            const reply = data.status === 'success' ? data.answer : data.message;
            document.getElementById(loadingId).innerHTML = reply;
        } catch (error) {
            document.getElementById(loadingId).innerText = "Mất kết nối với AI Server (Cổng 8000).";
        }
        
        sessionStorage.setItem('ai_chat_history', chatBody.innerHTML);
    }

    function appendMessage(sender, text, id = '') {
        const msgDiv = document.createElement('div');
        msgDiv.className = sender === 'user' ? 'user-msg shadow-sm' : 'bot-msg shadow-sm';
        if (id) msgDiv.id = id;
        msgDiv.innerHTML = text;
        chatBody.appendChild(msgDiv);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    btnSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });
});
</script>