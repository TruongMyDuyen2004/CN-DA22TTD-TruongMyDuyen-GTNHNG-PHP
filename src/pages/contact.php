<?php
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $phone = htmlspecialchars($_POST['phone'] ?? '');
    $msg = htmlspecialchars($_POST['message'] ?? '');
    
    if (!empty($name) && !empty($email) && !empty($msg)) {
        try {
            $db = new Database();
            $conn = $db->connect();
            
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
?>

<section class="contact-section">
    <div class="container">
        <h2><?php echo __('contact_title'); ?></h2>
        <p class="section-subtitle"><?php echo __('contact_subtitle'); ?></p>
        
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>-message">
            <?php echo $message; ?>
            <?php if ($messageType == 'success'): ?>
            <p style="margin-top: 15px;">
                <a href="index.php?page=my-contacts" style="color: #FF6B35; font-weight: 600; text-decoration: underline;">
                    <i class="fas fa-envelope-open-text"></i> <?php echo __('view_my_messages'); ?>
                </a>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Thông báo tra cứu tin nhắn -->
        <div class="info-banner">
            <i class="fas fa-info-circle"></i>
            <span>
                <?php echo __('check_reply_message'); ?> 
                <a href="index.php?page=my-contacts">
                    <i class="fas fa-envelope-open-text"></i> <?php echo __('view_my_messages'); ?>
                </a>
            </span>
        </div>
        
        <div class="contact-content">
            <div class="contact-info">
                <h3><?php echo __('contact_info'); ?></h3>
                <div class="info-item">
                    <strong>📍 <?php echo __('address'); ?>:</strong>
                    <p>123 Đường Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh</p>
                </div>
                <div class="info-item">
                    <strong>📞 <?php echo __('phone_number'); ?>:</strong>
                    <p>(028) 1234 5678</p>
                </div>
                <div class="info-item">
                    <strong>✉️ <?php echo __('email_address'); ?>:</strong>
                    <p>info@ngongallery.vn</p>
                </div>
                <div class="info-item">
                    <strong>🕐 <?php echo __('opening_hours'); ?>:</strong>
                    <p><?php echo __('weekdays'); ?>: 10:00 - 22:00</p>
                    <p><?php echo __('weekend'); ?>: 09:00 - 23:00</p>
                </div>
            </div>
            
            <div class="contact-form">
                <h3><?php echo __('send_message'); ?></h3>
                <form method="POST" action="index.php?page=contact">
                    <div class="form-group">
                        <label for="name"><?php echo __('name'); ?> *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><?php echo __('email'); ?> *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone"><?php echo __('phone'); ?></label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="message"><?php echo __('message'); ?> *</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn"><?php echo __('send_message'); ?></button>
                </form>
            </div>
        </div>
    </div>
</section>
