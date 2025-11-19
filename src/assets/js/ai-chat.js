/* ===================================
   AI CHATBOT - JAVASCRIPT
   =================================== */

class AIChatbot {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.init();
    }

    init() {
        this.createChatUI();
        this.attachEventListeners();
        this.showWelcomeMessage();
    }

    createChatUI() {
        const chatHTML = `
            <!-- Chat Button -->
            <div class="ai-chat-button" id="aiChatButton">
                <i class="fas fa-comments"></i>
            </div>

            <!-- Chat Window -->
            <div class="ai-chat-window" id="aiChatWindow">
                <!-- Header -->
                <div class="ai-chat-header">
                    <div class="ai-chat-avatar">
                        🤖
                    </div>
                    <div class="ai-chat-info">
                        <h3>Ngon Gallery AI</h3>
                        <p>Trợ lý ảo của bạn</p>
                    </div>
                </div>

                <!-- Messages -->
                <div class="ai-chat-messages" id="aiChatMessages">
                    <!-- Messages will be inserted here -->
                </div>

                <!-- Input -->
                <div class="ai-chat-input">
                    <input 
                        type="text" 
                        id="aiChatInput" 
                        placeholder="Nhập tin nhắn..."
                        autocomplete="off"
                    >
                    <button class="ai-chat-send" id="aiChatSend">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', chatHTML);
    }

    attachEventListeners() {
        const button = document.getElementById('aiChatButton');
        const sendBtn = document.getElementById('aiChatSend');
        const input = document.getElementById('aiChatInput');

        button.addEventListener('click', () => this.toggleChat());
        sendBtn.addEventListener('click', () => this.sendMessage());
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
    }

    toggleChat() {
        this.isOpen = !this.isOpen;
        const button = document.getElementById('aiChatButton');
        const window = document.getElementById('aiChatWindow');

        button.classList.toggle('active');
        window.classList.toggle('active');

        if (this.isOpen && this.messages.length === 0) {
            this.showWelcomeMessage();
        }
    }

    showWelcomeMessage() {
        const messagesContainer = document.getElementById('aiChatMessages');
        messagesContainer.innerHTML = `
            <div class="ai-welcome">
                <div class="ai-welcome-icon">
                    🤖
                </div>
                <h4>Xin chào! 👋</h4>
                <p>Tôi là trợ lý ảo của Ngon Gallery. Tôi có thể giúp bạn:</p>
            </div>
        `;

        setTimeout(() => {
            this.addBotMessage('Chào bạn! Tôi có thể giúp gì cho bạn hôm nay?', [
                'Xem thực đơn',
                'Đặt bàn',
                'Giờ mở cửa',
                'Địa chỉ nhà hàng',
                'Khuyến mãi'
            ]);
        }, 500);
    }

    sendMessage() {
        const input = document.getElementById('aiChatInput');
        const message = input.value.trim();

        if (!message) return;

        this.addUserMessage(message);
        input.value = '';

        // Show typing indicator
        this.showTyping();

        // Simulate AI response
        setTimeout(() => {
            this.hideTyping();
            this.handleUserMessage(message);
        }, 1000 + Math.random() * 1000);
    }

    addUserMessage(text) {
        const messagesContainer = document.getElementById('aiChatMessages');
        const time = this.getCurrentTime();

        const messageHTML = `
            <div class="ai-message user">
                <div class="ai-message-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="ai-message-content">
                    <div class="ai-message-bubble">${this.escapeHtml(text)}</div>
                    <div class="ai-message-time">${time}</div>
                </div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
        this.messages.push({ type: 'user', text, time });
    }

    addBotMessage(text, quickReplies = []) {
        const messagesContainer = document.getElementById('aiChatMessages');
        const time = this.getCurrentTime();

        let quickRepliesHTML = '';
        if (quickReplies.length > 0) {
            quickRepliesHTML = `
                <div class="ai-quick-replies">
                    ${quickReplies.map(reply => 
                        `<button class="ai-quick-reply" onclick="aiChatbot.handleQuickReply('${reply}')">${reply}</button>`
                    ).join('')}
                </div>
            `;
        }

        const messageHTML = `
            <div class="ai-message bot">
                <div class="ai-message-avatar">
                    🤖
                </div>
                <div class="ai-message-content">
                    <div class="ai-message-bubble">${text}</div>
                    <div class="ai-message-time">${time}</div>
                    ${quickRepliesHTML}
                </div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
        this.messages.push({ type: 'bot', text, time });
    }

    showTyping() {
        const messagesContainer = document.getElementById('aiChatMessages');
        const typingHTML = `
            <div class="ai-typing" id="aiTyping">
                <div class="ai-message-avatar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    🤖
                </div>
                <div class="ai-typing-dots">
                    <div class="ai-typing-dot"></div>
                    <div class="ai-typing-dot"></div>
                    <div class="ai-typing-dot"></div>
                </div>
            </div>
        `;
        messagesContainer.insertAdjacentHTML('beforeend', typingHTML);
        this.scrollToBottom();
    }

    hideTyping() {
        const typing = document.getElementById('aiTyping');
        if (typing) typing.remove();
    }

    handleUserMessage(message) {
        const lowerMessage = message.toLowerCase();

        // Menu
        if (lowerMessage.includes('thực đơn') || lowerMessage.includes('menu') || lowerMessage.includes('món ăn')) {
            this.addBotMessage(
                '🍽️ Bạn có thể xem thực đơn đầy đủ của chúng tôi tại đây: <a href="index.php?page=menu" style="color: #667eea; font-weight: 600;">Xem thực đơn</a><br><br>Chúng tôi có các món ăn Việt Nam truyền thống như Phở, Bún chả, Cơm tấm và nhiều món khác!',
                ['Đặt bàn', 'Giá cả', 'Khuyến mãi']
            );
        }
        // Reservation
        else if (lowerMessage.includes('đặt bàn') || lowerMessage.includes('reservation') || lowerMessage.includes('book')) {
            this.addBotMessage(
                '📅 Bạn muốn đặt bàn? Tuyệt vời!<br><br>Vui lòng truy cập trang đặt bàn: <a href="index.php?page=reservation" style="color: #667eea; font-weight: 600;">Đặt bàn ngay</a><br><br>Hoặc gọi hotline: <strong>0123 456 789</strong>',
                ['Xem thực đơn', 'Giờ mở cửa', 'Địa chỉ']
            );
        }
        // Hours
        else if (lowerMessage.includes('giờ') || lowerMessage.includes('mở cửa') || lowerMessage.includes('hours')) {
            this.addBotMessage(
                '🕐 Giờ mở cửa của Ngon Gallery:<br><br>' +
                '📍 <strong>Thứ 2 - Thứ 6:</strong> 10:00 - 22:00<br>' +
                '📍 <strong>Thứ 7 - Chủ nhật:</strong> 09:00 - 23:00<br><br>' +
                'Chúng tôi luôn sẵn sàng phục vụ bạn!',
                ['Đặt bàn', 'Địa chỉ', 'Liên hệ']
            );
        }
        // Location
        else if (lowerMessage.includes('địa chỉ') || lowerMessage.includes('location') || lowerMessage.includes('ở đâu')) {
            this.addBotMessage(
                '📍 Địa chỉ Ngon Gallery:<br><br>' +
                '<strong>123 Đường Nguyễn Huệ, Quận 1, TP.HCM</strong><br><br>' +
                '🚗 Bạn có thể đến bằng xe bus, taxi hoặc xe máy.<br>' +
                '🅿️ Có bãi đỗ xe miễn phí cho khách hàng.',
                ['Đặt bàn', 'Giờ mở cửa', 'Liên hệ']
            );
        }
        // Promotion
        else if (lowerMessage.includes('khuyến mãi') || lowerMessage.includes('promotion') || lowerMessage.includes('giảm giá')) {
            this.addBotMessage(
                '🎉 Khuyến mãi đặc biệt:<br><br>' +
                '✨ Giảm 20% cho đơn hàng đầu tiên<br>' +
                '✨ Miễn phí giao hàng cho đơn từ 200k<br>' +
                '✨ Tích điểm đổi quà hấp dẫn<br><br>' +
                'Đăng ký thành viên để nhận thêm nhiều ưu đãi!',
                ['Đăng ký', 'Xem thực đơn', 'Đặt món']
            );
        }
        // Contact
        else if (lowerMessage.includes('liên hệ') || lowerMessage.includes('contact') || lowerMessage.includes('hotline')) {
            this.addBotMessage(
                '📞 Thông tin liên hệ:<br><br>' +
                '☎️ <strong>Hotline:</strong> 0123 456 789<br>' +
                '📧 <strong>Email:</strong> info@ngongallery.vn<br>' +
                '🌐 <strong>Website:</strong> ngongallery.vn<br><br>' +
                'Hoặc bạn có thể gửi tin nhắn: <a href="index.php?page=contact" style="color: #667eea; font-weight: 600;">Liên hệ ngay</a>',
                ['Đặt bàn', 'Địa chỉ', 'Giờ mở cửa']
            );
        }
        // Default
        else {
            this.addBotMessage(
                'Xin lỗi, tôi chưa hiểu câu hỏi của bạn. Bạn có thể hỏi tôi về:<br><br>' +
                '• Thực đơn và món ăn<br>' +
                '• Đặt bàn<br>' +
                '• Giờ mở cửa<br>' +
                '• Địa chỉ nhà hàng<br>' +
                '• Khuyến mãi<br>' +
                '• Thông tin liên hệ',
                ['Xem thực đơn', 'Đặt bàn', 'Liên hệ']
            );
        }
    }

    handleQuickReply(reply) {
        const input = document.getElementById('aiChatInput');
        input.value = reply;
        this.sendMessage();
    }

    scrollToBottom() {
        const messagesContainer = document.getElementById('aiChatMessages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chatbot when DOM is ready
let aiChatbot;
document.addEventListener('DOMContentLoaded', () => {
    aiChatbot = new AIChatbot();
});
