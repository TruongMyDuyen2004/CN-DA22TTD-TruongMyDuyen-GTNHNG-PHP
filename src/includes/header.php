<header id="header" class="header-green">
    <div class="header-container">
        <div class="logo">
            <a href="index.php">
                <div class="logo-icon">
                    <img src="assets/images/logo.jpg" alt="Ngon Gallery Logo" class="logo-image">
                </div>
                <div class="logo-text">
                    <span class="logo-name">NGON GALLERY</span>
                    <span class="logo-subtitle">VIETNAMESE</span>
                </div>
            </a>
        </div>
        <nav class="main-nav">
            <ul class="nav-menu">
                <li><a href="index.php?page=home" class="nav-link <?php echo ($page == 'home') ? 'active' : ''; ?>">
                    <?php echo __('home'); ?>
                </a></li>
                <li><a href="index.php?page=about" class="nav-link <?php echo ($page == 'about') ? 'active' : ''; ?>">
                    <?php echo __('about'); ?>
                </a></li>
                <li><a href="index.php?page=menu" class="nav-link <?php echo ($page == 'menu') ? 'active' : ''; ?>">
                    <?php echo __('menu'); ?>
                </a></li>
                <li><a href="index.php?page=contact" class="nav-link <?php echo ($page == 'contact') ? 'active' : ''; ?>">
                    <?php echo __('contact'); ?>
                </a></li>
                <li><a href="index.php?page=news" class="nav-link <?php echo ($page == 'news') ? 'active' : ''; ?>">
                    <?php echo __('news'); ?>
                </a></li>
                <li><a href="index.php?page=all-reviews" class="nav-link <?php echo ($page == 'all-reviews') ? 'active' : ''; ?>">
                    <?php echo __('reviews'); ?>
                </a></li>
                <li><a href="index.php?page=promotions" class="nav-link nav-promo <?php echo ($page == 'promotions') ? 'active' : ''; ?>">
                    Khuyến mãi
                </a></li>
            </ul>
        </nav>
        
        <div class="header-actions">
            
            <?php if (isset($_SESSION['customer_id'])): 
                // Lấy số lượng món trong giỏ và avatar
                try {
                    $db_cart = new Database();
                    $conn_cart = $db_cart->connect();
                    $stmt_cart = $conn_cart->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
                    $stmt_cart->execute([$_SESSION['customer_id']]);
                    $cart_count = $stmt_cart->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                    
                    // Lấy avatar của user
                    $stmt_avatar = $conn_cart->prepare("SELECT avatar, full_name FROM customers WHERE id = ?");
                    $stmt_avatar->execute([$_SESSION['customer_id']]);
                    $user_data = $stmt_avatar->fetch(PDO::FETCH_ASSOC);
                    $user_avatar = !empty($user_data['avatar']) ? $user_data['avatar'] : '';
                    $user_name = !empty($user_data['full_name']) ? $user_data['full_name'] : ($_SESSION['customer_name'] ?? 'User');
                    
                    // Đếm số phản hồi chưa đọc
                    $unread_replies = 0;
                    if (!empty($_SESSION['customer_email'])) {
                        try {
                            $stmt_unread = $conn_cart->prepare("SELECT COUNT(*) as cnt FROM contacts WHERE email = ? AND status = 'replied' AND (user_read_at IS NULL OR user_read_at = '')");
                            $stmt_unread->execute([$_SESSION['customer_email']]);
                            $unread_replies = $stmt_unread->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
                        } catch (Exception $e) {
                            $unread_replies = 0;
                        }
                    }
                } catch (Exception $e) {
                    $cart_count = 0;
                    $user_avatar = '';
                    $user_name = $_SESSION['customer_name'] ?? 'User';
                    $unread_replies = 0;
                }
            ?>
                <a href="index.php?page=reservation" class="btn-reservation">
                    <i class="fas fa-calendar-alt"></i> ĐẶT BÀN
                </a>
                
                <a href="index.php?page=cart" class="cart-icon" title="<?php echo __('cart'); ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="index.php?page=orders" class="icon-btn" title="<?php echo __('orders'); ?>">
                    <i class="fas fa-box"></i>
                </a>
                
                <a href="index.php?page=help" class="icon-btn" title="Trợ giúp">
                    <i class="fas fa-question-circle"></i>
                </a>
                
                <!-- Thông báo tin nhắn - Dropdown Chat -->
                <div class="notification-dropdown">
                    <button class="icon-btn notification-btn" title="Tin nhắn" onclick="toggleChatDropdown(event)">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_replies > 0): ?>
                        <span class="notification-badge"><?php echo $unread_replies; ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Chat Dropdown Panel -->
                    <div class="chat-dropdown-panel" id="chatDropdownPanel">
                        <?php
                        // Lấy tin nhắn của user
                        $user_messages = [];
                        if (!empty($_SESSION['customer_email'])) {
                            try {
                                $stmt_msg = $conn_cart->prepare("
                                    SELECT c.*, a.username as admin_name 
                                    FROM contacts c 
                                    LEFT JOIN admins a ON c.replied_by = a.id
                                    WHERE c.email = ? 
                                    ORDER BY c.created_at DESC 
                                    LIMIT 20
                                ");
                                $stmt_msg->execute([$_SESSION['customer_email']]);
                                $user_messages = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Exception $e) {}
                        }
                        ?>
                        
                        <div class="chat-dropdown-header">
                            <div class="chat-dropdown-title">
                                <div class="chat-logo-small">
                                    <i class="fas fa-headset"></i>
                                    <span class="online-indicator"></span>
                                </div>
                                <div>
                                    <h4>Ngon Gallery</h4>
                                    <span>Hỗ trợ khách hàng</span>
                                </div>
                            </div>
                            <button class="chat-close-btn" onclick="closeChatDropdown()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="chat-dropdown-body" id="chatDropdownBody">
                            <?php if (count($user_messages) > 0): 
                                $reversed_messages = array_reverse($user_messages);
                                $last_date = '';
                            ?>
                                <?php foreach ($reversed_messages as $msg): 
                                    $msg_date = date('d/m/Y', strtotime($msg['created_at']));
                                    $is_today = ($msg_date == date('d/m/Y'));
                                    $is_yesterday = ($msg_date == date('d/m/Y', strtotime('-1 day')));
                                    $is_new = ($msg['status'] == 'replied' && empty($msg['user_read_at']));
                                    
                                    if ($msg_date != $last_date):
                                        $last_date = $msg_date;
                                        $display_date = $is_today ? 'Hôm nay' : ($is_yesterday ? 'Hôm qua' : $msg_date);
                                ?>
                                <div class="chat-date-divider">
                                    <span><?php echo $display_date; ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php 
                                // Kiểm tra xem đây là tin nhắn của admin hay user
                                $isAdminMsg = (!empty($msg['is_admin_message']) && $msg['is_admin_message'] == 1) || $msg['name'] === 'Admin';
                                ?>
                                
                                <?php if (!$isAdminMsg): ?>
                                <!-- User message -->
                                <div class="chat-msg chat-msg-user">
                                    <div class="chat-msg-bubble">
                                        <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                        <span class="chat-msg-time">
                                            <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                                            <?php if ($msg['status'] == 'replied'): ?>
                                                <i class="fas fa-check-double" style="color: #4ade80;"></i>
                                            <?php elseif ($msg['status'] == 'read'): ?>
                                                <i class="fas fa-check-double"></i>
                                            <?php else: ?>
                                                <i class="fas fa-check"></i>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Admin reply (cách cũ - từ admin_reply) -->
                                <?php if (!empty($msg['admin_reply'])): ?>
                                <div class="chat-msg chat-msg-admin <?php echo $is_new ? 'chat-msg-new' : ''; ?>">
                                    <div class="chat-admin-avatar">
                                        <i class="fas fa-headset"></i>
                                    </div>
                                    <div class="chat-msg-bubble">
                                        <?php if ($is_new): ?>
                                        <span class="chat-new-badge">Mới</span>
                                        <?php endif; ?>
                                        <p><?php echo nl2br(htmlspecialchars($msg['admin_reply'])); ?></p>
                                        <span class="chat-msg-time">
                                            <?php echo !empty($msg['replied_at']) ? date('H:i', strtotime($msg['replied_at'])) : ''; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php else: ?>
                                <!-- Admin message (cách mới - từ is_admin_message hoặc name='Admin') -->
                                <div class="chat-msg chat-msg-admin <?php echo $is_new ? 'chat-msg-new' : ''; ?>">
                                    <div class="chat-admin-avatar">
                                        <i class="fas fa-headset"></i>
                                    </div>
                                    <div class="chat-msg-bubble">
                                        <?php if ($is_new): ?>
                                        <span class="chat-new-badge">Mới</span>
                                        <?php endif; ?>
                                        <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                        <span class="chat-msg-time">
                                            <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php endforeach; ?>
                            <?php else: ?>
                            <div class="chat-empty-state">
                                <div class="chat-empty-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <p>Chưa có tin nhắn</p>
                                <span>Gửi tin nhắn đầu tiên để được hỗ trợ</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="chat-dropdown-footer">
                            <form class="chat-input-form" id="chatInputForm" onsubmit="sendChatMessage(event)">
                                <input type="text" id="chatMessageInput" placeholder="Nhập tin nhắn..." autocomplete="off">
                                <button type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="user-dropdown-modern">
                    <button class="user-btn-modern">
                        <?php 
                        $show_avatar = false;
                        if (!empty($user_avatar)) {
                            // Kiểm tra file tồn tại với đường dẫn tương đối từ thư mục gốc
                            $avatar_path = $user_avatar;
                            if (filter_var($user_avatar, FILTER_VALIDATE_URL) || file_exists($avatar_path)) {
                                $show_avatar = true;
                            }
                        }
                        ?>
                        <div class="user-avatar-wrapper">
                            <?php if ($show_avatar): ?>
                                <img src="<?php echo htmlspecialchars($user_avatar); ?>?v=<?php echo time(); ?>" 
                                     alt="Avatar" 
                                     class="user-avatar-modern" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="user-avatar-fallback" style="display:none;">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </div>
                            <?php else: ?>
                                <div class="user-avatar-fallback">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <span class="user-status-dot"></span>
                        </div>
                        <div class="user-info-modern">
                            <span class="user-name-modern"><?php echo htmlspecialchars($user_name); ?></span>
                            <span class="user-role-modern"><?php echo __('member'); ?></span>
                        </div>
                        <i class="fas fa-chevron-down user-arrow"></i>
                    </button>
                    <div class="user-menu-modern">
                        <div class="user-menu-header">
                            <div class="menu-avatar-wrapper">
                                <?php if ($show_avatar): ?>
                                    <img src="<?php echo htmlspecialchars($user_avatar); ?>?v=<?php echo time(); ?>" alt="Avatar" class="menu-avatar">
                                <?php else: ?>
                                    <div class="menu-avatar-fallback"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="menu-user-info">
                                <span class="menu-user-name"><?php echo htmlspecialchars($user_name); ?></span>
                                <span class="menu-user-email"><?php echo htmlspecialchars($_SESSION['customer_email'] ?? ''); ?></span>
                            </div>
                        </div>
                        <div class="user-menu-body">
                            <a href="index.php?page=profile" class="menu-item-modern">
                                <div class="menu-icon-wrapper"><i class="fas fa-user"></i></div>
                                <span><?php echo __('profile'); ?></span>
                            </a>
                            <a href="index.php?page=member-card" class="menu-item-modern">
                                <div class="menu-icon-wrapper"><i class="fas fa-credit-card"></i></div>
                                <span>Thẻ thành viên</span>
                            </a>
                            <a href="index.php?page=my-points" class="menu-item-modern">
                                <div class="menu-icon-wrapper"><i class="fas fa-star"></i></div>
                                <span>Điểm tích lũy</span>
                            </a>
                            <a href="index.php?page=vouchers" class="menu-item-modern">
                                <div class="menu-icon-wrapper"><i class="fas fa-ticket-alt"></i></div>
                                <span>Voucher của tôi</span>
                            </a>
                            <a href="index.php?page=favorites" class="menu-item-modern">
                                <div class="menu-icon-wrapper"><i class="fas fa-heart"></i></div>
                                <span>Món yêu thích</span>
                            </a>
                            <a href="index.php?page=orders" class="menu-item-modern">
                                <div class="menu-icon-wrapper"><i class="fas fa-box"></i></div>
                                <span><?php echo __('my_orders'); ?></span>
                            </a>
                            <a href="index.php?page=my-reservations" class="menu-item-modern">
                                <div class="menu-icon-wrapper"><i class="fas fa-calendar-check"></i></div>
                                <span><?php echo __('my_reservations'); ?></span>
                            </a>
                        </div>
                        <div class="user-menu-footer">
                            <a href="auth/logout.php" class="menu-logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span><?php echo __('logout'); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="index.php?page=reservation" class="btn-reservation">
                    <i class="fas fa-calendar-alt"></i> <?php echo __('reservation'); ?>
                </a>
                <div class="auth-dropdown">
                    <button class="auth-icon-btn" title="<?php echo __('login'); ?> / <?php echo __('register'); ?>">
                        <i class="fas fa-user"></i>
                    </button>
                    <div class="auth-menu">
                        <a href="auth/login.php"><i class="fas fa-sign-in-alt"></i> <?php echo __('login'); ?></a>
                        <a href="auth/register.php"><i class="fas fa-user-plus"></i> <?php echo __('register'); ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Chat Dropdown Styles -->
<style>
/* Notification Dropdown Container */
.notification-dropdown {
    position: relative;
}

.notification-btn {
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    font-size: 0.65rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Chat Dropdown Panel */
.chat-dropdown-panel {
    position: fixed;
    top: 80px;
    right: 20px;
    width: 380px;
    max-height: 550px;
    background: linear-gradient(180deg, #1a2744 0%, #0f1a2e 100%);
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    display: none;
    flex-direction: column;
    z-index: 10000;
    overflow: hidden;
}

.chat-dropdown-panel.active {
    display: flex;
}

/* Chat Header */
.chat-dropdown-header {
    background: linear-gradient(135deg, #15803d 0%, #166534 100%);
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.chat-dropdown-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.chat-logo-small {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.chat-logo-small i {
    font-size: 1.1rem;
    color: #1a1a1a;
}

.online-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    background: #22c55e;
    border-radius: 50%;
    border: 2px solid #15803d;
}

.chat-dropdown-title h4 {
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
}

.chat-dropdown-title span {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.75rem;
}

.chat-close-btn {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 8px;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.chat-close-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

/* Chat Body */
.chat-dropdown-body {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 350px;
    min-height: 200px;
}

.chat-dropdown-body::-webkit-scrollbar {
    width: 5px;
}

.chat-dropdown-body::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}

.chat-dropdown-body::-webkit-scrollbar-thumb {
    background: rgba(212, 165, 116, 0.3);
    border-radius: 3px;
}

/* Date Divider */
.chat-date-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0.5rem 0;
}

.chat-date-divider span {
    background: rgba(30, 41, 59, 0.8);
    color: rgba(255, 255, 255, 0.5);
    padding: 0.25rem 0.75rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 500;
}

/* Chat Messages */
.chat-msg {
    display: flex;
    gap: 0.5rem;
    max-width: 85%;
}

.chat-msg-user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.chat-msg-admin {
    align-self: flex-start;
}

.chat-admin-avatar {
    width: 28px;
    height: 28px;
    min-width: 28px;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: #1a1a1a;
}

.chat-msg-bubble {
    padding: 0.6rem 0.9rem;
    border-radius: 14px;
    position: relative;
}

.chat-msg-user .chat-msg-bubble {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-msg-admin .chat-msg-bubble {
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.9);
    border-bottom-left-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.chat-msg-new .chat-msg-bubble {
    background: rgba(34, 197, 94, 0.15);
    border-color: rgba(34, 197, 94, 0.3);
}

.chat-new-badge {
    display: inline-block;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    padding: 0.1rem 0.4rem;
    border-radius: 6px;
    font-size: 0.6rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
}

.chat-msg-bubble p {
    margin: 0;
    font-size: 0.85rem;
    line-height: 1.4;
    word-wrap: break-word;
}

.chat-msg-time {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.65rem;
    opacity: 0.6;
    margin-top: 0.3rem;
    justify-content: flex-end;
}

/* Empty State */
.chat-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
}

.chat-empty-icon {
    width: 60px;
    height: 60px;
    background: rgba(212, 165, 116, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.chat-empty-icon i {
    font-size: 1.5rem;
    color: rgba(212, 165, 116, 0.5);
}

.chat-empty-state p {
    color: #fff;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
}

.chat-empty-state span {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8rem;
}

/* Chat Footer */
.chat-dropdown-footer {
    padding: 0.75rem 1rem;
    background: rgba(15, 26, 46, 0.8);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.chat-input-form {
    display: flex;
    gap: 0.5rem;
}

.chat-input-form input {
    flex: 1;
    padding: 0.7rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    color: #fff;
    font-size: 0.9rem;
    outline: none;
    transition: all 0.3s ease;
}

.chat-input-form input:focus {
    border-color: #d4a574;
    background: rgba(0, 0, 0, 0.4);
}

.chat-input-form input::placeholder {
    color: rgba(255, 255, 255, 0.35);
}

.chat-input-form button {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    border: none;
    border-radius: 10px;
    color: #1a1a1a;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.chat-input-form button:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(212, 165, 116, 0.3);
}

.chat-input-form button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Responsive */
@media (max-width: 480px) {
    .chat-dropdown-panel {
        right: 10px;
        left: 10px;
        width: auto;
        top: 70px;
    }
}
</style>

<!-- Chat Dropdown Scripts -->
<script>
function toggleChatDropdown(event) {
    event.preventDefault();
    event.stopPropagation();
    const panel = document.getElementById('chatDropdownPanel');
    panel.classList.toggle('active');
    
    if (panel.classList.contains('active')) {
        setTimeout(() => {
            const body = document.getElementById('chatDropdownBody');
            if (body) body.scrollTop = body.scrollHeight;
        }, 100);
        markMessagesAsRead();
    }
}

function closeChatDropdown() {
    document.getElementById('chatDropdownPanel').classList.remove('active');
}

document.addEventListener('click', function(e) {
    const panel = document.getElementById('chatDropdownPanel');
    const btn = document.querySelector('.notification-btn');
    if (panel && btn && !panel.contains(e.target) && !btn.contains(e.target)) {
        panel.classList.remove('active');
    }
});

function sendChatMessage(event) {
    event.preventDefault();
    const input = document.getElementById('chatMessageInput');
    const btn = document.querySelector('.chat-input-form button');
    const message = input.value.trim();
    if (!message) return;
    
    // Disable button while sending
    btn.disabled = true;
    const originalIcon = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('api/send-contact-message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: message })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const body = document.getElementById('chatDropdownBody');
            const msgHtml = '<div class="chat-msg chat-msg-user"><div class="chat-msg-bubble"><p>' + escapeHtmlChat(message) + '</p><span class="chat-msg-time">' + new Date().toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'}) + ' <i class="fas fa-check"></i></span></div></div>';
            body.insertAdjacentHTML('beforeend', msgHtml);
            body.scrollTop = body.scrollHeight;
            input.value = '';
            input.focus(); // Focus lại để tiếp tục nhắn
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(() => alert('Không thể gửi tin nhắn'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalIcon;
    });
}

function escapeHtmlChat(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function markMessagesAsRead() {
    fetch('api/mark-messages-read.php', { method: 'POST' }).then(() => {
        const badge = document.querySelector('.notification-badge');
        if (badge) badge.style.display = 'none';
    }).catch(() => {});
}

// ========== THÔNG BÁO TIN NHẮN MỚI ==========
let lastMessageCount = <?php echo isset($unread_replies) ? $unread_replies : 0; ?>;
let lastNotifiedMessageId = localStorage.getItem('lastNotifiedMessageId') || 0;
let notificationSound = null;

// Tạo âm thanh thông báo
function createNotificationSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        return audioContext;
    } catch(e) {
        return null;
    }
}

// Phát âm thanh thông báo
function playNotificationSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
        
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.3);
    } catch(e) {}
}

// Hiển thị toast thông báo
function showNotificationToast(message) {
    // Xóa toast cũ nếu có
    const oldToast = document.querySelector('.notification-toast');
    if (oldToast) oldToast.remove();
    
    const toast = document.createElement('div');
    toast.className = 'notification-toast';
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-bell"></i>
        </div>
        <div class="toast-content">
            <strong>Tin nhắn mới!</strong>
            <p>${message.length > 50 ? message.substring(0, 50) + '...' : message}</p>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    // Animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Tự động ẩn sau 5 giây
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Kiểm tra tin nhắn mới
function checkNewMessages() {
    fetch('api/check-new-messages.php')
        .then(res => res.json())
        .then(data => {
            console.log('📬 Check messages:', data);
            
            if (data.success) {
                updateNotificationBadge(data.count);
                
                // Kiểm tra có tin nhắn mới chưa thông báo
                if (data.latest && data.latest.id && data.latest.admin_reply) {
                    const latestId = parseInt(data.latest.id);
                    const lastId = parseInt(lastNotifiedMessageId) || 0;
                    
                    console.log('Latest ID:', latestId, 'Last notified:', lastId);
                    
                    if (latestId > lastId) {
                        // Có tin nhắn mới - hiện thông báo
                        console.log('🔔 New message! Showing notification...');
                        playNotificationSound();
                        showNotificationToast(data.latest.admin_reply);
                        
                        // Lưu ID đã thông báo
                        lastNotifiedMessageId = latestId;
                        localStorage.setItem('lastNotifiedMessageId', latestId);
                    }
                }
                
                lastMessageCount = data.count;
            }
        })
        .catch(err => console.error('Check messages error:', err));
}

// Cập nhật badge thông báo
function updateNotificationBadge(count) {
    let badge = document.querySelector('.notification-badge');
    const btn = document.querySelector('.notification-btn');
    
    if (count > 0) {
        if (!badge && btn) {
            badge = document.createElement('span');
            badge.className = 'notification-badge';
            btn.appendChild(badge);
        }
        if (badge) {
            badge.textContent = count;
            badge.style.display = 'flex';
        }
    } else {
        if (badge) badge.style.display = 'none';
    }
}

// Bắt đầu polling
<?php if (isset($_SESSION['customer_id'])): ?>
// Kiểm tra ngay khi load trang
setTimeout(checkNewMessages, 2000);
// Sau đó polling mỗi 5 giây
setInterval(checkNewMessages, 5000);

// Debug: Log ra console
console.log('🔔 Notification system started');
console.log('Last notified ID:', lastNotifiedMessageId);
<?php endif; ?>
</script>

<!-- Toast Notification Styles -->
<style>
.notification-toast {
    position: fixed;
    top: 100px;
    right: 20px;
    background: linear-gradient(135deg, #15803d 0%, #166534 100%);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 10px 40px rgba(34, 197, 94, 0.3);
    z-index: 10001;
    transform: translateX(120%);
    transition: transform 0.3s ease;
    max-width: 350px;
}

.notification-toast.show {
    transform: translateX(0);
}

.toast-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    animation: ring 0.5s ease;
}

@keyframes ring {
    0%, 100% { transform: rotate(0); }
    20%, 60% { transform: rotate(15deg); }
    40%, 80% { transform: rotate(-15deg); }
}

.toast-icon i {
    color: #1a1a1a;
    font-size: 1rem;
}

.toast-content {
    flex: 1;
}

.toast-content strong {
    color: #d4a574;
    font-size: 0.9rem;
    display: block;
    margin-bottom: 0.25rem;
}

.toast-content p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.4;
}

.toast-close {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.toast-close:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

@media (max-width: 480px) {
    .notification-toast {
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
</style>
