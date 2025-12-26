<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;
    $email = $_GET['email'] ?? '';
    
    if ($action == 'mark_read' && $email) {
        $stmt = $conn->prepare("UPDATE contacts SET status = 'read' WHERE email = ? AND status = 'new'");
        $stmt->execute([$email]);
    } elseif ($action == 'delete' && $email) {
        $stmt = $conn->prepare("DELETE FROM contacts WHERE email = ?");
        $stmt->execute([$email]);
    } elseif ($action == 'delete_single' && $id) {
        $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: contacts.php');
    exit;
}

// Lọc
$status_filter = $_GET['status'] ?? 'all';

// Lấy danh sách conversations (gom theo email)
$conversations = [];
$stmt = $conn->query("SELECT * FROM contacts ORDER BY created_at DESC");
$allContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allContacts as $contact) {
    $email = $contact['email'];
    if (!isset($conversations[$email])) {
        $conversations[$email] = [
            'email' => $email,
            'name' => $contact['name'],
            'phone' => $contact['phone'],
            'messages' => [],
            'latest_time' => $contact['created_at'],
            'has_new' => false,
            'has_replied' => false,
            'total_messages' => 0
        ];
    }
    $conversations[$email]['messages'][] = $contact;
    $conversations[$email]['total_messages']++;
    if ($contact['status'] === 'new') $conversations[$email]['has_new'] = true;
    if ($contact['status'] === 'replied') $conversations[$email]['has_replied'] = true;
}

// Lọc theo status
if ($status_filter === 'new') {
    $conversations = array_filter($conversations, fn($c) => $c['has_new']);
} elseif ($status_filter === 'replied') {
    $conversations = array_filter($conversations, fn($c) => $c['has_replied']);
}

$stats = $conn->query("
    SELECT COUNT(DISTINCT email) as total,
        COUNT(DISTINCT CASE WHEN status = 'new' THEN email END) as new_count,
        COUNT(DISTINCT CASE WHEN status = 'read' THEN email END) as read_count,
        COUNT(DISTINCT CASE WHEN status = 'replied' THEN email END) as replied_count
    FROM contacts
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý liên hệ - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc !important; }
        .main-content { background: #f8fafc !important; padding: 1.5rem 2rem !important; }
        
        /* Force all text to be visible */
        .contact-card, .contact-card *, .modal-box, .modal-box * {
            color: #111827;
        }
        
        .page-header { margin-bottom: 1.5rem; }
        .page-header h1 {
            color: #1f2937 !important;
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
        }
        .page-header h1 i { color: #22c55e; }
        
        /* Filter */
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            border: 2px solid #e5e7eb;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: #4b5563;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .filter-btn:hover { border-color: #22c55e; color: #22c55e; }
        .filter-btn.active {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border-color: transparent;
        }
        
        /* Contact Cards Grid */
        .contacts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1rem;
        }
        .contact-card {
            background: white;
            border-radius: 16px;
            border: 2px solid #e5e7eb;
            overflow: hidden;
            transition: all 0.2s;
        }
        .contact-card:hover {
            border-color: #22c55e;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.15);
            transform: translateY(-2px);
        }
        .contact-card.unread {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        
        /* Card Header */
        .card-header-info {
            padding: 1.25rem 1.25rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #f1f5f9;
        }
        .sender-info { display: flex; gap: 0.85rem; align-items: center; }
        .sender-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .sender-details h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #111827 !important;
            margin: 0 0 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .new-badge {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            font-size: 0.6rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .sender-meta {
            font-size: 0.8rem;
            color: #374151 !important;
        }
        .sender-meta a {
            color: #374151 !important;
            text-decoration: none;
        }
        .sender-meta a:hover { color: #22c55e; }
        .sender-meta i { color: #22c55e; margin-right: 0.25rem; font-size: 0.7rem; }
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .status-badge.new { background: #fef3c7; color: #92400e; }
        .status-badge.read { background: #dbeafe; color: #1d4ed8; }
        .status-badge.replied { background: #dcfce7; color: #15803d; }
        
        /* Card Body - Message */
        .card-body-message {
            padding: 1rem 1.25rem;
        }
        .message-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            border-left: 4px solid #22c55e;
        }
        .message-text {
            font-size: 0.95rem;
            color: #111827 !important;
            line-height: 1.7;
            font-weight: 500;
            margin: 0;
        }
        .message-time {
            font-size: 0.8rem;
            color: #374151 !important;
            margin-top: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .message-time i { color: #22c55e; }
        
        /* Card Footer - Actions */
        .card-footer-actions {
            padding: 0.85rem 1.25rem;
            background: #f8fafc;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-view { background: #22c55e; color: white; }
        .btn-reply { background: #3b82f6; color: white; }
        .btn-delete { background: #ef4444; color: white; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #9ca3af;
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.5; }
        .empty-state h3 { color: #6b7280; font-size: 1rem; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 550px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 25px 80px rgba(0,0,0,0.25);
            animation: modalSlide 0.3s ease;
        }
        @keyframes modalSlide {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { 
            margin: 0; 
            font-size: 1.15rem; 
            font-weight: 700; 
            color: white !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .modal-close { 
            background: rgba(255,255,255,0.2); 
            border: none; 
            width: 32px;
            height: 32px;
            border-radius: 8px;
            font-size: 1rem; 
            color: white; 
            cursor: pointer;
            transition: all 0.2s;
        }
        .modal-close:hover { background: rgba(255,255,255,0.3); }
        .modal-body { padding: 1.5rem; }
        
        .detail-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 0.85rem;
            border: 1px solid #e5e7eb;
        }
        .detail-item:last-child { margin-bottom: 0; }
        .detail-label { 
            font-weight: 600; 
            color: #6b7280 !important; 
            font-size: 0.75rem; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.35rem; 
        }
        .detail-value { 
            color: #111827 !important; 
            font-size: 1rem; 
            font-weight: 600; 
        }
        .message-box-modal {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            border: 2px solid #22c55e;
            line-height: 1.7;
            white-space: pre-wrap;
            color: #111827 !important;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .reply-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95rem;
            resize: vertical;
            color: #111827;
            font-weight: 500;
        }
        .reply-form textarea:focus { outline: none; border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1); }
        .modal-footer { 
            padding: 1.25rem 1.5rem; 
            background: #f8fafc;
            border-radius: 0 0 20px 20px;
            display: flex; 
            gap: 0.75rem; 
            justify-content: flex-end; 
        }
        .btn-send { 
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); 
            color: white; 
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .btn-send:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3); }
        .btn-cancel { 
            background: #e5e7eb; 
            color: #374151; 
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .btn-cancel:hover { background: #d1d5db; }
        .btn-send { background: #22c55e; color: white; padding: 0.6rem 1.25rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-send:hover { background: #16a34a; }
        .btn-cancel { background: #6b7280; color: white; padding: 0.6rem 1.25rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-envelope"></i> Quản lý liên hệ</h1>
        </div>
        
        <!-- Stats -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem;">
            <div style="background: white; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#3b82f6';" onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='#d1d5db';">
                <div style="width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);"><i class="fas fa-envelope"></i></div>
                <div><h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo $stats['total'] ?? 0; ?></h3><p style="color: #6b7280; margin: 0; font-size: 0.8rem;">Tổng liên hệ</p></div>
            </div>
            <div style="background: white; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f59e0b';" onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='#d1d5db';">
                <div style="width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-envelope-open"></i></div>
                <div><h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo $stats['new_count'] ?? 0; ?></h3><p style="color: #6b7280; margin: 0; font-size: 0.8rem;">Chưa đọc</p></div>
            </div>
            <div style="background: white; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#8b5cf6';" onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='#d1d5db';">
                <div style="width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);"><i class="fas fa-eye"></i></div>
                <div><h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo $stats['read_count'] ?? 0; ?></h3><p style="color: #6b7280; margin: 0; font-size: 0.8rem;">Đã đọc</p></div>
            </div>
            <div style="background: white; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='#d1d5db';">
                <div style="width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"><i class="fas fa-reply"></i></div>
                <div><h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo $stats['replied_count'] ?? 0; ?></h3><p style="color: #6b7280; margin: 0; font-size: 0.8rem;">Đã trả lời</p></div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="filter-card">
            <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Tất cả</a>
            <a href="?status=new" class="filter-btn <?php echo $status_filter === 'new' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Chưa đọc</a>
            <a href="?status=read" class="filter-btn <?php echo $status_filter === 'read' ? 'active' : ''; ?>"><i class="fas fa-eye"></i> Đã đọc</a>
            <a href="?status=replied" class="filter-btn <?php echo $status_filter === 'replied' ? 'active' : ''; ?>"><i class="fas fa-reply"></i> Đã trả lời</a>
        </div>

        <!-- Contacts Grid -->
        <div class="contacts-grid">
            <?php if (empty($conversations)): ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <i class="fas fa-inbox"></i>
                    <h3>Không có liên hệ nào</h3>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $email => $conv): ?>
                    <div class="contact-card <?php echo $conv['has_new'] ? 'unread' : ''; ?>">
                        <div class="card-header-info">
                            <div class="sender-info">
                                <div class="sender-avatar"><?php echo strtoupper(mb_substr($conv['name'], 0, 1)); ?></div>
                                <div class="sender-details">
                                    <h4>
                                        <?php echo htmlspecialchars($conv['name']); ?>
                                        <?php if ($conv['has_new']): ?><span class="new-badge">Mới</span><?php endif; ?>
                                        <?php if ($conv['total_messages'] > 1): ?>
                                            <span style="background:#e0e7ff; color:#3730a3; font-size:0.65rem; padding:0.15rem 0.4rem; border-radius:10px; font-weight:700;"><?php echo $conv['total_messages']; ?> tin nhắn</span>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="sender-meta">
                                        <i class="fas fa-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a>
                                        <?php if (!empty($conv['phone'])): ?>
                                            <br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($conv['phone']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $conv['has_replied'] ? 'replied' : ($conv['has_new'] ? 'new' : 'read'); ?>">
                                <?php 
                                if ($conv['has_replied']) echo '✓ Đã trả lời';
                                elseif ($conv['has_new']) echo '⏳ Chờ xử lý';
                                else echo '👁 Đã đọc';
                                ?>
                            </span>
                        </div>
                        
                        <div class="card-body-message">
                            <?php 
                            // Hiển thị tin nhắn mới nhất
                            $latestMsg = $conv['messages'][0];
                            ?>
                            <div class="message-box">
                                <p class="message-text"><?php echo htmlspecialchars($latestMsg['message']); ?></p>
                                <div class="message-time">
                                    <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($latestMsg['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer-actions">
                            <button class="btn-action btn-view" onclick="viewConversation(<?php echo htmlspecialchars(json_encode($conv)); ?>)"><i class="fas fa-comments"></i> Xem hội thoại</button>
                            <a href="?action=delete&email=<?php echo urlencode($email); ?>" class="btn-action btn-delete" onclick="return confirm('Xóa toàn bộ hội thoại với <?php echo htmlspecialchars($conv['name']); ?>?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- View Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-envelope-open"></i> Chi tiết liên hệ</h3>
                <button class="modal-close" onclick="closeModal('viewModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="viewModalBody"></div>
        </div>
    </div>
    
    <!-- Reply Modal -->
    <div class="modal-overlay" id="replyModal">
        <div class="modal-box">
            <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                <h3><i class="fas fa-reply"></i> Trả lời liên hệ</h3>
                <button class="modal-close" onclick="closeModal('replyModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="replyModalBody"></div>
        </div>
    </div>
    
    <script>
    let currentConv = null;
    
    function viewConversation(conv) {
        currentConv = conv;
        // Đánh dấu đã đọc
        fetch('?action=mark_read&email=' + encodeURIComponent(conv.email));
        
        let messagesHtml = '';
        // Sắp xếp tin nhắn từ cũ đến mới
        const messages = [...conv.messages].reverse();
        
        messages.forEach((msg, idx) => {
            const isAdminMsg = msg.is_admin_message == 1 || msg.name === 'Admin';
            
            if (isAdminMsg) {
                // Tin nhắn của Admin - căn phải
                messagesHtml += `
                    <div style="margin-bottom: 1.25rem; display: flex; justify-content: flex-end;">
                        <div style="max-width: 85%;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem; justify-content: flex-end;">
                                <span style="font-size: 0.7rem; color: #9ca3af;">${new Date(msg.created_at).toLocaleString('vi-VN')}</span>
                                <span style="font-weight: 600; color: #111827; font-size: 0.85rem;">Admin</span>
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">
                                    <i class="fas fa-user-shield" style="font-size: 0.65rem;"></i>
                                </div>
                            </div>
                            <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 0.85rem 1rem; border-radius: 16px 0 16px 16px; color: white; font-size: 0.9rem; line-height: 1.6; font-weight: 500; margin-right: 36px;">
                                ${escapeHtml(msg.message)}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Tin nhắn của khách hàng - căn trái
                messagesHtml += `
                    <div style="margin-bottom: 1.25rem; display: flex; justify-content: flex-start;">
                        <div style="max-width: 85%;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">
                                    ${conv.name.charAt(0).toUpperCase()}
                                </div>
                                <span style="font-weight: 600; color: #111827; font-size: 0.85rem;">${escapeHtml(conv.name)}</span>
                                <span style="font-size: 0.7rem; color: #9ca3af;">${new Date(msg.created_at).toLocaleString('vi-VN')}</span>
                            </div>
                            <div style="background: #f3f4f6; padding: 0.85rem 1rem; border-radius: 0 16px 16px 16px; color: #111827; font-size: 0.9rem; line-height: 1.6; font-weight: 500; margin-left: 36px;">
                                ${escapeHtml(msg.message)}
                            </div>
                        </div>
                    </div>
                `;
                
                // Tin nhắn trả lời của Admin (cách cũ - từ admin_reply)
                if (msg.admin_reply) {
                    messagesHtml += `
                        <div style="margin-bottom: 1.25rem; display: flex; justify-content: flex-end;">
                            <div style="max-width: 85%;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem; justify-content: flex-end;">
                                    <span style="font-size: 0.7rem; color: #9ca3af;">${msg.replied_at ? new Date(msg.replied_at).toLocaleString('vi-VN') : ''}</span>
                                    <span style="font-weight: 600; color: #111827; font-size: 0.85rem;">Admin</span>
                                    <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">
                                        <i class="fas fa-user-shield" style="font-size: 0.65rem;"></i>
                                    </div>
                                </div>
                                <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 0.85rem 1rem; border-radius: 16px 0 16px 16px; color: white; font-size: 0.9rem; line-height: 1.6; font-weight: 500; margin-right: 36px;">
                                    ${escapeHtml(msg.admin_reply)}
                                </div>
                            </div>
                        </div>
                    `;
                }
            }
        });
        
        document.getElementById('viewModalBody').innerHTML = `
            <div class="detail-item">
                <div class="detail-label">THÔNG TIN LIÊN HỆ</div>
                <div class="detail-value">
                    <strong>${escapeHtml(conv.name)}</strong><br>
                    <span style="color: #374151;"><i class="fas fa-envelope" style="color:#22c55e; margin-right:0.25rem;"></i>${escapeHtml(conv.email)}</span>
                    ${conv.phone ? `<br><span style="color: #374151;"><i class="fas fa-phone" style="color:#22c55e; margin-right:0.25rem;"></i>${escapeHtml(conv.phone)}</span>` : ''}
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <div class="detail-label" style="margin-bottom: 0.75rem;">LỊCH SỬ HỘI THOẠI (${conv.total_messages} tin nhắn)</div>
                <div id="messagesContainer" style="max-height: 320px; overflow-y: auto; padding: 1rem; background: #fafafa; border-radius: 12px; border: 1px solid #e5e7eb;">
                    ${messagesHtml}
                </div>
            </div>
            <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 2px solid #e5e7eb;">
                <div class="detail-label" style="margin-bottom: 0.5rem;">TRẢ LỜI NHANH <span style="font-weight: 400; color: #9ca3af; font-size: 0.7rem;">(Enter để gửi, Shift+Enter để xuống dòng)</span></div>
                <form onsubmit="sendReplyFromView(event)" style="display: flex; gap: 0.75rem; align-items: flex-end;">
                    <textarea id="quickReplyMessage" placeholder="Nhập nội dung trả lời..." required style="flex: 1; min-height: 60px; padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 0.9rem; resize: none; color: #111827; font-weight: 500;" onkeydown="handleEnterKey(event)"></textarea>
                    <button type="submit" class="btn-send" style="height: 60px; padding: 0 1.25rem;">
                        <i class="fas fa-paper-plane"></i> Gửi
                    </button>
                </form>
            </div>
        `;
        document.getElementById('viewModal').classList.add('active');
        
        // Scroll xuống cuối tin nhắn
        setTimeout(() => {
            const container = document.getElementById('messagesContainer');
            if (container) container.scrollTop = container.scrollHeight;
        }, 100);
    }
    
    async function sendReplyFromView(e) {
        e.preventDefault();
        const msg = document.getElementById('quickReplyMessage').value;
        const latestMsg = currentConv.messages[0];
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        try {
            const res = await fetch('../api/send-contact-reply-simple.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({contact_id: latestMsg.id, reply_message: msg, send_email: true})
            });
            const data = await res.json();
            if (data.success) {
                // Thêm tin nhắn mới vào container - căn phải giống Admin
                const container = document.getElementById('messagesContainer');
                const newMsgHtml = `
                    <div style="margin-bottom: 1.25rem; display: flex; justify-content: flex-end;">
                        <div style="max-width: 85%;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem; justify-content: flex-end;">
                                <span style="font-size: 0.7rem; color: #9ca3af;">Vừa xong</span>
                                <span style="font-weight: 600; color: #111827; font-size: 0.85rem;">Admin</span>
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">
                                    <i class="fas fa-user-shield" style="font-size: 0.65rem;"></i>
                                </div>
                            </div>
                            <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 0.85rem 1rem; border-radius: 16px 0 16px 16px; color: white; font-size: 0.9rem; line-height: 1.6; font-weight: 500; margin-right: 36px;">
                                ${escapeHtml(msg)}
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += newMsgHtml;
                container.scrollTop = container.scrollHeight;
                document.getElementById('quickReplyMessage').value = '';
                btn.innerHTML = '<i class="fas fa-check"></i> Đã gửi';
                setTimeout(() => { btn.innerHTML = originalText; btn.disabled = false; }, 2000);
            } else {
                alert('Lỗi: ' + data.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch(err) {
            alert('Lỗi: ' + err.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
    
    function replyConversation(conv) {
        currentConv = conv;
        const latestMsg = conv.messages[0];
        
        document.getElementById('replyModalBody').innerHTML = `
            <div class="detail-item">
                <div class="detail-label">Trả lời cho</div>
                <div class="detail-value">${escapeHtml(conv.name)} &lt;${escapeHtml(conv.email)}&gt;</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Tin nhắn mới nhất</div>
                <div class="message-box-modal">${escapeHtml(latestMsg.message)}</div>
            </div>
            <form class="reply-form" onsubmit="sendReply(event)">
                <div style="margin-top: 1rem;">
                    <div class="detail-label">Nội dung phản hồi</div>
                    <textarea id="replyMessage" placeholder="Nhập nội dung phản hồi..." required style="margin-top: 0.5rem;"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('replyModal')"><i class="fas fa-times"></i> Hủy</button>
                    <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i> Gửi phản hồi</button>
                </div>
            </form>
        `;
        document.getElementById('replyModal').classList.add('active');
    }
    
    async function sendReply(e) {
        e.preventDefault();
        const msg = document.getElementById('replyMessage').value;
        const latestMsg = currentConv.messages[0];
        try {
            const res = await fetch('../api/send-contact-reply-simple.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({contact_id: latestMsg.id, reply_message: msg, send_email: true})
            });
            const data = await res.json();
            if (data.success) { alert('Đã gửi phản hồi!'); location.reload(); }
            else { alert('Lỗi: ' + data.message); }
        } catch(err) { alert('Lỗi: ' + err.message); }
    }
    
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
    function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text || ''; return div.innerHTML; }
    
    // Xử lý Enter để gửi tin nhắn (Shift+Enter để xuống dòng)
    function handleEnterKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            e.target.closest('form').dispatchEvent(new Event('submit', { cancelable: true }));
        }
    }
    
    document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); }));
    </script>
</body>
</html>
