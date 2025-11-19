<?php
// Trang xem và trả lời tin nhắn liên hệ
$contacts = [];
$searchEmail = '';
$searched = false;
$replySuccess = false;
$replyError = '';

// Xử lý gửi tin nhắn trả lời
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_to_contact'])) {
    $contact_id = intval($_POST['contact_id'] ?? 0);
    $reply_message = trim($_POST['reply_message'] ?? '');
    $user_email = htmlspecialchars($_POST['user_email'] ?? '');
    
    if ($contact_id && !empty($reply_message) && !empty($user_email)) {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // Tạo tin nhắn mới như một follow-up
            $stmt = $conn->prepare("
                INSERT INTO contacts (name, email, phone, message, status)
                SELECT name, email, phone, ?, 'new'
                FROM contacts
                WHERE id = ?
            ");
            $followup_message = "📩 Trả lời tin nhắn #" . $contact_id . ":\n\n" . $reply_message;
            $stmt->execute([$followup_message, $contact_id]);
            
            $replySuccess = true;
            $searchEmail = $user_email;
            $searched = true;
            
        } catch(PDOException $e) {
            $replyError = __('error_occurred');
        }
    }
}

// Tìm kiếm tin nhắn
if (($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_email'])) || $searched) {
    if (!$searched) {
        $searchEmail = htmlspecialchars($_POST['search_email'] ?? '');
        $searched = true;
    }
    
    if (!empty($searchEmail) && filter_var($searchEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            $stmt = $conn->prepare("
                SELECT 
                    c.*,
                    a.username as admin_username
                FROM contacts c
                LEFT JOIN admins a ON c.replied_by = a.id
                WHERE c.email = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$searchEmail]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $error = __('error_occurred');
        }
    }
}
?>

<section class="my-contacts-section">
    <div class="container">
        <h2><i class="fas fa-comments"></i> <?php echo __('my_contacts_title'); ?></h2>
        <p class="section-subtitle"><?php echo __('my_contacts_subtitle'); ?></p>
        
        <?php if ($replySuccess): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <strong>Đã gửi tin nhắn thành công!</strong> Chúng tôi sẽ phản hồi sớm nhất có thể.
        </div>
        <?php endif; ?>
        
        <?php if ($replyError): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $replyError; ?>
        </div>
        <?php endif; ?>
        
        <!-- Form tìm kiếm -->
        <div class="search-contacts-box">
            <form method="POST" action="index.php?page=my-contacts" class="search-form">
                <div class="search-input-group">
                    <i class="fas fa-envelope"></i>
                    <input 
                        type="email" 
                        name="search_email" 
                        placeholder="<?php echo __('enter_your_email'); ?>" 
                        value="<?php echo htmlspecialchars($searchEmail); ?>"
                        required
                    >
                    <button type="submit" class="btn btn-search">
                        <i class="fas fa-search"></i> <?php echo __('search'); ?>
                    </button>
                </div>
                <p class="search-hint">
                    <i class="fas fa-info-circle"></i> 
                    <?php echo __('search_contacts_hint'); ?>
                </p>
            </form>
        </div>
        
        <?php if ($searched): ?>
            <?php if (count($contacts) > 0): ?>
                <!-- Danh sách tin nhắn -->
                <div class="contacts-list">
                    <div class="contacts-header">
                        <h3>
                            <i class="fas fa-list"></i> 
                            Cuộc hội thoại của bạn (<?php echo count($contacts); ?> tin nhắn)
                        </h3>
                    </div>
                    
                    <?php foreach($contacts as $index => $contact): ?>
                    <div class="contact-item <?php echo $contact['status']; ?>" id="contact-<?php echo $contact['id']; ?>">
                        <div class="contact-item-header">
                            <div class="contact-meta">
                                <span class="contact-id">#<?php echo $contact['id']; ?></span>
                                <span class="contact-date">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?>
                                </span>
                                <span class="contact-status status-<?php echo $contact['status']; ?>">
                                    <?php
                                    $status_labels = [
                                        'new' => '<i class="fas fa-envelope"></i> Mới',
                                        'read' => '<i class="fas fa-eye"></i> Đã xem',
                                        'replied' => '<i class="fas fa-check-circle"></i> Đã trả lời'
                                    ];
                                    echo $status_labels[$contact['status']] ?? $contact['status'];
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="contact-item-body">
                            <!-- Tin nhắn của người dùng -->
                            <div class="message-bubble user-message">
                                <div class="message-header">
                                    <i class="fas fa-user"></i>
                                    <strong>Bạn</strong>
                                </div>
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?>
                                </div>
                            </div>
                            
                            <!-- Phản hồi từ admin -->
                            <?php if ($contact['admin_reply']): ?>
                            <div class="message-bubble admin-message">
                                <div class="message-header">
                                    <i class="fas fa-user-shield"></i>
                                    <strong>Ngon Gallery</strong>
                                    <?php if ($contact['admin_username']): ?>
                                    <span class="admin-name">(<?php echo htmlspecialchars($contact['admin_username']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($contact['admin_reply'])); ?>
                                </div>
                                <div class="message-time">
                                    <?php if ($contact['replied_at']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($contact['replied_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Form trả lời tiếp -->
                            <div class="reply-section">
                                <button class="btn-toggle-reply" onclick="toggleReplyForm(<?php echo $contact['id']; ?>)">
                                    <i class="fas fa-reply"></i> Trả lời tin nhắn này
                                </button>
                                
                                <div class="reply-form-container" id="reply-form-<?php echo $contact['id']; ?>" style="display: none;">
                                    <form method="POST" action="index.php?page=my-contacts" class="reply-form">
                                        <input type="hidden" name="reply_to_contact" value="1">
                                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                        <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($searchEmail); ?>">
                                        
                                        <div class="form-group">
                                            <label><i class="fas fa-pen"></i> Tin nhắn của bạn:</label>
                                            <textarea 
                                                name="reply_message" 
                                                rows="4" 
                                                placeholder="Nhập tin nhắn trả lời..."
                                                required
                                            ></textarea>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-send">
                                                <i class="fas fa-paper-plane"></i> Gửi tin nhắn
                                            </button>
                                            <button type="button" class="btn btn-cancel" onclick="toggleReplyForm(<?php echo $contact['id']; ?>)">
                                                Hủy
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <?php else: ?>
                            <!-- Chưa có phản hồi -->
                            <div class="waiting-reply">
                                <i class="fas fa-hourglass-half"></i>
                                <p>Chúng tôi đang xem xét và sẽ phản hồi sớm nhất có thể</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Không tìm thấy -->
                <div class="no-contacts">
                    <i class="fas fa-inbox"></i>
                    <h3>Không tìm thấy tin nhắn</h3>
                    <p>Không có tin nhắn nào với email này. Hãy gửi tin nhắn mới cho chúng tôi!</p>
                    <a href="index.php?page=contact" class="btn">
                        <i class="fas fa-paper-plane"></i> Gửi tin nhắn mới
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<style>
.my-contacts-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 80vh;
}

.my-contacts-section h2 {
    text-align: center;
    color: #FF6B35;
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.section-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 40px;
    font-size: 1.1rem;
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 2px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 2px solid #f5c6cb;
}

.search-contacts-box {
    background: white;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    max-width: 700px;
    margin: 0 auto 40px;
}

.search-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.search-input-group {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fa;
    padding: 15px 20px;
    border-radius: 10px;
    border: 2px solid #e0e0e0;
    transition: border-color 0.3s;
}

.search-input-group:focus-within {
    border-color: #FF6B35;
}

.search-input-group i {
    color: #FF6B35;
    font-size: 1.2rem;
}

.search-input-group input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 1rem;
    padding: 5px;
    outline: none;
}

.btn-search {
    background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
    white-space: nowrap;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
}

.search-hint {
    text-align: center;
    color: #666;
    font-size: 0.9rem;
    margin: 0;
}

.contacts-list {
    max-width: 900px;
    margin: 0 auto;
}

.contacts-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.contacts-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.3rem;
}

.contact-item {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.contact-item-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.contact-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.contact-id {
    font-weight: bold;
    color: #FF6B35;
    font-size: 1.1rem;
}

.contact-date {
    color: #666;
    font-size: 0.9rem;
}

.contact-status {
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-new {
    background: #fff3cd;
    color: #856404;
}

.status-read {
    background: #d1ecf1;
    color: #0c5460;
}

.status-replied {
    background: #d4edda;
    color: #155724;
}

.contact-item-body {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Message Bubbles */
.message-bubble {
    padding: 20px;
    border-radius: 15px;
    position: relative;
}

.user-message {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 4px solid #2196F3;
    margin-left: 0;
    margin-right: 50px;
}

.admin-message {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    border-left: 4px solid #4CAF50;
    margin-left: 50px;
    margin-right: 0;
}

.message-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 0.95rem;
}

.message-header i {
    font-size: 1.1rem;
}

.user-message .message-header {
    color: #1976D2;
}

.admin-message .message-header {
    color: #388E3C;
}

.admin-name {
    color: #666;
    font-weight: normal;
    font-size: 0.85rem;
}

.message-content {
    color: #333;
    line-height: 1.6;
    margin-bottom: 10px;
}

.message-time {
    font-size: 0.8rem;
    color: #666;
    text-align: right;
}

/* Reply Section */
.reply-section {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px dashed #e0e0e0;
}

.btn-toggle-reply {
    background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: transform 0.2s;
}

.btn-toggle-reply:hover {
    transform: translateY(-2px);
}

.reply-form-container {
    margin-top: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.reply-form .form-group {
    margin-bottom: 15px;
}

.reply-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.reply-form textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    transition: border-color 0.3s;
}

.reply-form textarea:focus {
    outline: none;
    border-color: #FF6B35;
}

.form-actions {
    display: flex;
    gap: 10px;
}

.btn-send {
    background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: transform 0.2s;
}

.btn-send:hover {
    transform: translateY(-2px);
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.waiting-reply {
    background: #fff3cd;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    border: 2px dashed #ffc107;
}

.waiting-reply i {
    font-size: 2rem;
    color: #856404;
    margin-bottom: 10px;
}

.waiting-reply p {
    margin: 0;
    color: #856404;
    font-weight: 600;
}

.no-contacts {
    background: white;
    padding: 60px 40px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.no-contacts i {
    font-size: 4rem;
    color: #ccc;
    margin-bottom: 20px;
}

.no-contacts h3 {
    color: #333;
    margin-bottom: 10px;
}

.no-contacts p {
    color: #666;
    margin-bottom: 30px;
}

.no-contacts .btn {
    background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%);
    color: white;
    padding: 12px 30px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    transition: transform 0.2s;
}

.no-contacts .btn:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .my-contacts-section h2 {
        font-size: 2rem;
    }
    
    .search-contacts-box {
        padding: 25px;
    }
    
    .search-input-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .btn-search {
        width: 100%;
    }
    
    .contact-item {
        padding: 20px;
    }
    
    .user-message, .admin-message {
        margin-left: 0;
        margin-right: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-send, .btn-cancel {
        width: 100%;
    }
}
</style>

<script>
function toggleReplyForm(contactId) {
    const form = document.getElementById('reply-form-' + contactId);
    if (form.style.display === 'none') {
        form.style.display = 'block';
        // Scroll to form
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        form.style.display = 'none';
    }
}
</script>
