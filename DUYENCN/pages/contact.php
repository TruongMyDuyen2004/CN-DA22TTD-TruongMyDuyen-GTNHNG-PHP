<?php
$message = '';
$messageType = '';
$db = new Database();
$conn = $db->connect();

// Lấy thông tin người dùng đã đăng nhập từ database
$customer_email = '';
$customer_name = '';
$customer_phone = '';
$is_logged_in = isset($_SESSION['customer_id']);

if ($is_logged_in) {
    try {
        $stmt = $conn->prepare("SELECT full_name, email, phone FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer_data) {
            $customer_name = $customer_data['full_name'] ?? '';
            $customer_email = $customer_data['email'] ?? '';
            $customer_phone = $customer_data['phone'] ?? '';
        }
    } catch (PDOException $e) {
        // Fallback to session
        $customer_email = $_SESSION['customer_email'] ?? '';
        $customer_name = $_SESSION['customer_name'] ?? '';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Nếu đã đăng nhập, sử dụng thông tin từ database
    if ($is_logged_in) {
        $name = $customer_name;
        $email = $customer_email;
        $phone = $customer_phone;
    } else {
        $name = htmlspecialchars($_POST['name'] ?? '');
        $email = htmlspecialchars($_POST['email'] ?? '');
        $phone = htmlspecialchars($_POST['phone'] ?? '');
    }
    $msg = htmlspecialchars($_POST['message'] ?? '');
    
    if (!empty($name) && !empty($email) && !empty($msg)) {
        try {
            $stmt = $conn->prepare("INSERT INTO contacts (name, email, phone, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $msg]);
            
            $message = __('thank_you_contact');
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = __('error_occurred');
            $messageType = 'error';
        }
    } else {
        $message = __('fill_all_fields');
        $messageType = 'error';
    }
}
$current_lang = getCurrentLanguage();
?>

<!-- Contact Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge"><?php echo $current_lang === 'en' ? 'Contact' : 'Liên hệ'; ?></span>
        <h1 class="about-hero-title"><?php echo $current_lang === 'en' ? 'Contact Ngon Gallery' : 'Liên Hệ Ngon Gallery'; ?></h1>
        <p class="about-hero-subtitle"><?php echo $current_lang === 'en' ? 'We are always ready to listen to you' : 'Chúng tôi luôn sẵn sàng lắng nghe bạn'; ?></p>
    </div>
</section>

<section class="contact-page">
    <div class="contact-container">

        <?php if ($message): ?>
        <div class="contact-alert <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <span><?php echo $message; ?></span>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="contact-grid">
            <!-- Left: Contact Info -->
            <div class="contact-info-card">
                <h2><?php echo $current_lang === 'en' ? 'Get in Touch' : 'Thông Tin Liên Hệ'; ?></h2>
                
                <div class="info-items">
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="info-text">
                            <strong><?php echo $current_lang === 'en' ? 'Address' : 'Địa chỉ'; ?></strong>
                            <span>126 Nguyễn Thiện Thành, Phường 5, TP. Trà Vinh</span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="info-text">
                            <strong><?php echo $current_lang === 'en' ? 'Phone' : 'Điện thoại'; ?></strong>
                            <span>0384848127</span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="info-text">
                            <strong>Email</strong>
                            <span>info@ngongallery.vn</span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-clock"></i></div>
                        <div class="info-text">
                            <strong><?php echo $current_lang === 'en' ? 'Hours' : 'Giờ mở cửa'; ?></strong>
                            <span>10:00 - 22:00 (<?php echo $current_lang === 'en' ? 'Daily' : 'Hàng ngày'; ?>)</span>
                        </div>
                    </div>
                </div>

                <div class="social-links">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" title="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Right: Contact Form -->
            <div class="contact-form-card">
                <h2><?php echo $current_lang === 'en' ? 'Send Message' : 'Gửi Tin Nhắn'; ?></h2>
                
                <form method="POST" action="index.php?page=contact" class="contact-form">
                    <?php if ($is_logged_in): ?>
                    <!-- Người dùng đã đăng nhập - Hiển thị thông tin không cho sửa -->
                    <div class="user-info-display">
                        <div class="user-info-row">
                            <div class="user-info-item">
                                <label><?php echo $current_lang === 'en' ? 'Full Name' : 'Họ và tên'; ?></label>
                                <div class="info-value">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($customer_name); ?></span>
                                </div>
                            </div>
                            <div class="user-info-item">
                                <label>Email</label>
                                <div class="info-value">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($customer_email); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="user-info-item full-width">
                            <label><?php echo $current_lang === 'en' ? 'Phone' : 'Số điện thoại'; ?></label>
                            <div class="info-value">
                                <i class="fas fa-phone"></i>
                                <span><?php echo !empty($customer_phone) ? htmlspecialchars($customer_phone) : ($current_lang === 'en' ? 'Not updated' : 'Chưa cập nhật'); ?></span>
                            </div>
                        </div>
                        <div class="edit-profile-hint">
                            <i class="fas fa-info-circle"></i>
                            <span><?php echo $current_lang === 'en' ? 'To change your information, please go to' : 'Để thay đổi thông tin, vui lòng vào'; ?></span>
                            <a href="index.php?page=profile"><?php echo $current_lang === 'en' ? 'Profile' : 'Hồ sơ'; ?></a>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Khách chưa đăng nhập - Cho phép nhập thông tin -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo $current_lang === 'en' ? 'Full Name' : 'Họ và tên'; ?> *</label>
                            <input type="text" name="name" placeholder="<?php echo $current_lang === 'en' ? 'Your name' : 'Nhập họ tên'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" placeholder="email@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo $current_lang === 'en' ? 'Phone' : 'Số điện thoại'; ?></label>
                        <input type="tel" name="phone" placeholder="0912 345 678">
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label><?php echo $current_lang === 'en' ? 'Message' : 'Nội dung'; ?> *</label>
                        <textarea name="message" rows="5" placeholder="<?php echo $current_lang === 'en' ? 'Your message...' : 'Nội dung tin nhắn...'; ?>" required></textarea>
                    </div>

                    <button type="submit" class="btn-send">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo $current_lang === 'en' ? 'Send Message' : 'Gửi tin nhắn'; ?>
                    </button>
                </form>
                
                <?php if ($is_logged_in): ?>
                <div class="form-note">
                    <i class="fas fa-bell"></i>
                    <?php echo $current_lang === 'en' ? 'Check replies via the bell icon in the header' : 'Xem phản hồi qua biểu tượng chuông trên header'; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Map -->
        <div class="contact-map">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3929.8876!2d106.3421!3d9.9347!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31a0175b5e5b5b5b%3A0x5b5b5b5b5b5b5b5b!2s126%20Nguy%E1%BB%85n%20Thi%E1%BB%87n%20Th%C3%A0nh%2C%20Ph%C6%B0%E1%BB%9Dng%205%2C%20Tr%C3%A0%20Vinh!5e0!3m2!1svi!2s!4v1702000000000!5m2!1svi!2s" 
                width="100%" 
                height="350" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>
</section>

<style>
/* Contact Page Styles - White Theme */
.contact-page {
    background: #ffffff;
    min-height: 100vh;
    padding: 2rem 0;
}

.contact-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Hero */
.contact-hero {
    text-align: center;
    padding: 2rem 0 2.5rem;
}

.contact-hero h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.5rem 0;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.contact-hero p {
    color: #6b7280;
    font-size: 1.1rem;
    margin: 0;
}

/* Alert */
.contact-alert {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    font-weight: 500;
}

.contact-alert.success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
}

.contact-alert.error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

/* Grid Layout */
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

/* Info Card - Light Green Theme */
.contact-info-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
    border-radius: 24px;
    padding: 2.5rem;
    border: 2px solid #86efac !important;
    box-shadow: 0 8px 24px rgba(34, 197, 94, 0.15) !important;
}

.contact-info-card h2 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #166534 !important;
    margin: 0 0 2rem 0;
    padding-bottom: 1.25rem;
    border-bottom: 3px solid #22c55e;
    position: relative;
}

.contact-info-card h2::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 60px;
    height: 3px;
    background: #16a34a;
}

.info-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 1.25rem 1.5rem;
    background: #ffffff;
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid #f3f4f6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.info-item:hover {
    background: #f0fdf4;
    border-color: #22c55e;
    box-shadow: 0 8px 20px rgba(34, 197, 94, 0.15);
    transform: translateX(8px);
}

.info-icon {
    width: 56px;
    height: 56px;
    min-width: 56px;
    background: rgba(34, 197, 94, 0.1);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: 2px solid rgba(34, 197, 94, 0.2);
}

.info-icon i {
    font-size: 1.4rem;
    color: #22c55e;
}

.info-item:hover .info-icon {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-color: #22c55e;
    transform: scale(1.05);
}

.info-item:hover .info-icon i {
    color: #ffffff;
}

.info-text {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    flex: 1;
}

.info-text strong {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
}

.info-text span {
    font-size: 1rem;
    color: #111827;
    font-weight: 500;
    line-height: 1.4;
}

.social-links {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e5e7eb;
    justify-content: center;
}

.social-links a {
    width: 48px;
    height: 48px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    text-decoration: none;
    font-size: 1.2rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.social-links a:hover {
    background: #22c55e;
    color: #ffffff;
    border-color: #22c55e;
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 8px 20px rgba(34, 197, 94, 0.35);
}

/* Form Card */
.contact-form-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.contact-form-card h2 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 1.5rem 0;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.contact-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
}

.form-group input,
.form-group textarea {
    padding: 0.9rem 1.1rem;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    color: #111827;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #22c55e;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: #9ca3af;
}

.form-group input[readonly] {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.btn-send {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 0.5rem;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.btn-send:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
}

.form-note {
    margin-top: 1.25rem;
    padding: 1rem;
    background: #dbeafe;
    border-radius: 10px;
    color: #1e40af;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    border: 1px solid #93c5fd;
}

.form-note i {
    color: #3b82f6;
}

/* User Info Display (for logged in users) */
.user-info-display {
    background: #f0fdf4;
    border: 2px solid #86efac;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
}

.user-info-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.user-info-item {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.user-info-item.full-width {
    margin-bottom: 1rem;
}

.user-info-item label {
    font-size: 0.8rem;
    color: #16a34a;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

.info-value {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.7rem 1rem;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #d1fae5;
}

.info-value i {
    color: #22c55e;
    font-size: 0.9rem;
    width: 18px;
}

.info-value span {
    color: #111827;
    font-size: 0.95rem;
}

.edit-profile-hint {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #dbeafe;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.edit-profile-hint i {
    color: #3b82f6;
}

.edit-profile-hint a {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    margin-left: 0.25rem;
}

.edit-profile-hint a:hover {
    text-decoration: underline;
}

@media (max-width: 600px) {
    .user-info-row {
        grid-template-columns: 1fr;
    }
}

/* Map */
.contact-map {
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.contact-map iframe {
    display: block;
}

/* Responsive */
@media (max-width: 900px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .contact-container {
        padding: 0 1rem;
    }
    
    .contact-hero h1 {
        font-size: 1.8rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .contact-info-card,
    .contact-form-card {
        padding: 1.5rem;
    }
}
</style>
