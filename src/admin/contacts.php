<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;
    
    if ($action == 'mark_read' && $id) {
        $stmt = $conn->prepare("UPDATE contacts SET status = 'read' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action == 'mark_replied' && $id) {
        $stmt = $conn->prepare("UPDATE contacts SET status = 'replied' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action == 'delete' && $id) {
        $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    echo '<script>window.location.href = "contacts.php";</script>';
    exit;
}

// Lọc
$status_filter = $_GET['status'] ?? 'all';
$where = "1=1";
if ($status_filter != 'all') {
    $where .= " AND status = '$status_filter'";
}

// Lấy danh sách liên hệ
$stmt = $conn->query("
    SELECT * FROM contacts
    WHERE $where
    ORDER BY created_at DESC
");
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count
    FROM contacts
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý liên hệ - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-fix.css">
    <link rel="stylesheet" href="../assets/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: slideUp 0.3s;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .modal-header h3 {
            margin: 0;
            color: #FF6B35;
            font-size: 1.5rem;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }
        .close-modal:hover {
            color: #FF6B35;
        }
        .contact-info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .contact-info-box h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.1rem;
        }
        .contact-detail {
            margin-bottom: 10px;
            display: flex;
            align-items: start;
        }
        .contact-detail i {
            color: #FF6B35;
            margin-right: 10px;
            margin-top: 3px;
            width: 20px;
        }
        .original-message {
            background: white;
            padding: 15px;
            border-left: 4px solid #FF6B35;
            border-radius: 4px;
            margin: 15px 0;
        }
        .original-message h5 {
            margin: 0 0 10px 0;
            color: #FF6B35;
        }
        .reply-form {
            margin-top: 20px;
        }
        .reply-form textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
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
        .reply-form .form-check {
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        .reply-form .form-check input {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .reply-form .form-check label {
            cursor: pointer;
            user-select: none;
        }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        .btn-reply {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-reply:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
        .btn-reply:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn-cancel:hover {
            background: #5a6268;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(50px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        .unread {
            background-color: #fff3cd;
        }
        .message-preview {
            max-width: 300px;
            font-size: 0.9rem;
        }
        .reply-history {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .reply-history h5 {
            margin: 0 0 10px 0;
            color: #2e7d32;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .reply-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .reply-item:last-child {
            margin-bottom: 0;
        }
        .reply-meta {
            font-size: 0.85rem;
            color: #666;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-envelope"></i> Quản lý liên hệ</h1>
            </div>
            
            <!-- Thống kê -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Tổng liên hệ</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon"><i class="fas fa-envelope-open"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['new_count']; ?></h3>
                        <p>Chưa đọc</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon"><i class="fas fa-eye"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['read_count']; ?></h3>
                        <p>Đã đọc</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon"><i class="fas fa-reply"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['replied_count']; ?></h3>
                        <p>Đã trả lời</p>
                    </div>
                </div>
            </div>
            
            <!-- Bộ lọc -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status" onchange="this.form.submit()">
                                <option value="all">Tất cả</option>
                                <option value="new" <?php echo $status_filter == 'new' ? 'selected' : ''; ?>>Chưa đọc</option>
                                <option value="read" <?php echo $status_filter == 'read' ? 'selected' : ''; ?>>Đã đọc</option>
                                <option value="replied" <?php echo $status_filter == 'replied' ? 'selected' : ''; ?>>Đã trả lời</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Danh sách liên hệ -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Danh sách liên hệ</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Liên hệ</th>
                                    <th>Nội dung</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày gửi</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($contacts as $contact): ?>
                                <tr class="<?php echo $contact['status'] == 'new' ? 'unread' : ''; ?>">
                                    <td>#<?php echo $contact['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($contact['name']); ?></strong></td>
                                    <td>
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['email']); ?><br>
                                        <?php if($contact['phone']): ?>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['phone']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="message-preview">
                                            <?php echo nl2br(htmlspecialchars(substr($contact['message'], 0, 100))); ?>
                                            <?php if(strlen($contact['message']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'new' => '<span class="badge badge-warning">Chưa đọc</span>',
                                            'read' => '<span class="badge badge-info">Đã đọc</span>',
                                            'replied' => '<span class="badge badge-success">Đã trả lời</span>'
                                        ];
                                        echo $badges[$contact['status']] ?? $contact['status'];
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="viewContact(<?php echo htmlspecialchars(json_encode($contact)); ?>)" class="btn btn-sm btn-primary" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="openReplyModal(<?php echo htmlspecialchars(json_encode($contact)); ?>)" class="btn btn-sm btn-success" title="Trả lời">
                                                <i class="fas fa-reply"></i>
                                            </button>
                                            <a href="?action=delete&id=<?php echo $contact['id']; ?>" class="btn btn-sm btn-danger" title="Xóa" onclick="return confirm('Xác nhận xóa liên hệ này?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal xem chi tiết -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-envelope-open"></i> Chi tiết liên hệ</h3>
                <button class="close-modal" onclick="closeViewModal()">&times;</button>
            </div>
            <div id="viewModalBody"></div>
        </div>
    </div>

    <!-- Modal trả lời -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-reply"></i> Trả lời liên hệ</h3>
                <button class="close-modal" onclick="closeReplyModal()">&times;</button>
            </div>
            <div id="replyModalBody"></div>
        </div>
    </div>

    <script>
    let currentContact = null;

    function viewContact(contact) {
        currentContact = contact;
        
        // Đánh dấu đã đọc nếu chưa đọc
        if (contact.status === 'new') {
            fetch(`?action=mark_read&id=${contact.id}`)
                .then(() => {
                    // Cập nhật UI
                    const row = event.target.closest('tr');
                    if (row) {
                        row.classList.remove('unread');
                        const badge = row.querySelector('.badge-warning');
                        if (badge) {
                            badge.className = 'badge badge-info';
                            badge.textContent = 'Đã đọc';
                        }
                    }
                });
        }
        
        const modalBody = document.getElementById('viewModalBody');
        modalBody.innerHTML = `
            <div class="contact-info-box">
                <h4><i class="fas fa-user"></i> Thông tin người gửi</h4>
                <div class="contact-detail">
                    <i class="fas fa-user"></i>
                    <div><strong>Tên:</strong> ${escapeHtml(contact.name)}</div>
                </div>
                <div class="contact-detail">
                    <i class="fas fa-envelope"></i>
                    <div><strong>Email:</strong> <a href="mailto:${escapeHtml(contact.email)}">${escapeHtml(contact.email)}</a></div>
                </div>
                ${contact.phone ? `
                <div class="contact-detail">
                    <i class="fas fa-phone"></i>
                    <div><strong>Điện thoại:</strong> <a href="tel:${escapeHtml(contact.phone)}">${escapeHtml(contact.phone)}</a></div>
                </div>
                ` : ''}
                <div class="contact-detail">
                    <i class="fas fa-clock"></i>
                    <div><strong>Thời gian:</strong> ${formatDateTime(contact.created_at)}</div>
                </div>
            </div>
            
            <div class="original-message">
                <h5><i class="fas fa-comment"></i> Nội dung tin nhắn</h5>
                <p style="white-space: pre-wrap; margin: 0;">${escapeHtml(contact.message)}</p>
            </div>
            
            ${contact.admin_reply ? `
            <div class="reply-history">
                <h5><i class="fas fa-check-circle"></i> Đã trả lời</h5>
                <div class="reply-item">
                    <p style="white-space: pre-wrap; margin: 0;">${escapeHtml(contact.admin_reply)}</p>
                    <div class="reply-meta">
                        <i class="fas fa-clock"></i> ${formatDateTime(contact.replied_at)}
                    </div>
                </div>
            </div>
            ` : ''}
            
            <div class="modal-footer">
                ${!contact.admin_reply ? `
                <button class="btn-reply" onclick="closeViewModal(); openReplyModal(currentContact);">
                    <i class="fas fa-reply"></i> Trả lời
                </button>
                ` : ''}
                <button class="btn-cancel" onclick="closeViewModal()">Đóng</button>
            </div>
        `;
        
        document.getElementById('viewModal').classList.add('active');
    }

    function openReplyModal(contact) {
        currentContact = contact;
        
        const modalBody = document.getElementById('replyModalBody');
        modalBody.innerHTML = `
            <div class="contact-info-box">
                <h4><i class="fas fa-user"></i> Trả lời cho: ${escapeHtml(contact.name)}</h4>
                <div class="contact-detail">
                    <i class="fas fa-envelope"></i>
                    <div>${escapeHtml(contact.email)}</div>
                </div>
            </div>
            
            <div class="original-message">
                <h5><i class="fas fa-comment"></i> Tin nhắn gốc</h5>
                <p style="white-space: pre-wrap; margin: 0;">${escapeHtml(contact.message)}</p>
                <div style="font-size: 0.85rem; color: #666; margin-top: 8px;">
                    <i class="fas fa-clock"></i> ${formatDateTime(contact.created_at)}
                </div>
            </div>
            
            ${contact.admin_reply ? `
            <div class="reply-history">
                <h5><i class="fas fa-history"></i> Phản hồi trước đó</h5>
                <div class="reply-item">
                    <p style="white-space: pre-wrap; margin: 0;">${escapeHtml(contact.admin_reply)}</p>
                    <div class="reply-meta">
                        <i class="fas fa-clock"></i> ${formatDateTime(contact.replied_at)}
                    </div>
                </div>
            </div>
            ` : ''}
            
            <form class="reply-form" onsubmit="sendReply(event)">
                <label for="replyMessage"><strong><i class="fas fa-pen"></i> Nội dung phản hồi:</strong></label>
                <textarea 
                    id="replyMessage" 
                    name="reply_message" 
                    placeholder="Nhập nội dung phản hồi cho khách hàng..."
                    required
                ></textarea>
                
                <div class="form-check">
                    <input type="checkbox" id="sendEmail" name="send_email" checked>
                    <label for="sendEmail">
                        <i class="fas fa-paper-plane"></i> Gửi email thông báo cho khách hàng
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn-reply" id="sendReplyBtn">
                        <i class="fas fa-paper-plane"></i> Gửi phản hồi
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeReplyModal()">Hủy</button>
                </div>
            </form>
        `;
        
        document.getElementById('replyModal').classList.add('active');
        
        // Focus vào textarea
        setTimeout(() => {
            document.getElementById('replyMessage').focus();
        }, 300);
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.remove('active');
    }

    function closeReplyModal() {
        document.getElementById('replyModal').classList.remove('active');
    }

    async function sendReply(event) {
        event.preventDefault();
        
        const btn = document.getElementById('sendReplyBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
        
        const replyMessage = document.getElementById('replyMessage').value;
        const sendEmail = document.getElementById('sendEmail').checked;
        
        try {
            const response = await fetch('../api/send-contact-reply-simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    contact_id: currentContact.id,
                    reply_message: replyMessage,
                    send_email: sendEmail
                })
            });
            
            const result = await response.json();
            console.log('API Response:', result); // Debug
            
            if (result.success) {
                alert('✅ ' + result.message + (result.email_sent ? '\n📧 Email đã được gửi thành công!' : ''));
                closeReplyModal();
                location.reload();
            } else {
                alert('❌ Lỗi: ' + result.message);
                console.error('Error detail:', result);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Có lỗi xảy ra khi gửi phản hồi: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Đóng modal khi click bên ngoài
    window.onclick = function(event) {
        const viewModal = document.getElementById('viewModal');
        const replyModal = document.getElementById('replyModal');
        
        if (event.target === viewModal) {
            closeViewModal();
        }
        if (event.target === replyModal) {
            closeReplyModal();
        }
    }

    // Đóng modal khi nhấn ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeViewModal();
            closeReplyModal();
        }
    });
    </script>
</body>
</html>
