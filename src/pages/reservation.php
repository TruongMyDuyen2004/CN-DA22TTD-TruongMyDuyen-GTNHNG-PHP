<?php
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $phone = htmlspecialchars($_POST['phone'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $guests = intval($_POST['guests'] ?? 0);
    $request = htmlspecialchars($_POST['request'] ?? '');
    
    if (!empty($name) && !empty($email) && !empty($phone) && !empty($date) && !empty($time) && $guests > 0) {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            $stmt = $conn->prepare("INSERT INTO reservations (customer_name, email, phone, reservation_date, reservation_time, number_of_guests, special_request) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $date, $time, $guests, $request]);
            
            $message = __('reservation_success');
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = __('reservation_error');
            $messageType = 'error';
        }
    } else {
        $message = __('fill_all_required');
        $messageType = 'error';
    }
}
?>

<section class="reservation-section">
    <div class="container">
        <h2><?php echo __('reservation_title'); ?></h2>
        <p class="section-subtitle"><?php echo __('reservation_subtitle_alt'); ?></p>
        
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>-message">
            <?php if ($messageType === 'success'): ?>
                <i class="fas fa-check-circle"></i>
            <?php else: ?>
                <i class="fas fa-exclamation-circle"></i>
            <?php endif; ?>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="reservation-content">
            <div class="reservation-info">
                <h3><?php echo __('reservation_info'); ?></h3>
                <div class="info-card">
                    <div class="info-icon">📞</div>
                    <div>
                        <h4><?php echo __('reservation_hotline'); ?></h4>
                        <p>(028) 1234 5678</p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">🕐</div>
                    <div>
                        <h4><?php echo __('service_hours'); ?></h4>
                        <p><?php echo __('weekdays'); ?>: 10:00 - 22:00</p>
                        <p><?php echo __('weekend'); ?>: 09:00 - 23:00</p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">👥</div>
                    <div>
                        <h4><?php echo __('capacity'); ?></h4>
                        <p><?php echo __('capacity_desc'); ?></p>
                        <p><?php echo __('private_room'); ?></p>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-icon">💳</div>
                    <div>
                        <h4><?php echo __('payment'); ?></h4>
                        <p><?php echo __('payment_methods'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="reservation-form-container">
                <form method="POST" action="index.php?page=reservation" class="reservation-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> <?php echo __('full_name'); ?> *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="phone"><i class="fas fa-phone"></i> <?php echo __('phone'); ?> *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> <?php echo __('email'); ?> *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date"><i class="fas fa-calendar"></i> <?php echo __('date'); ?> *</label>
                            <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="time"><i class="fas fa-clock"></i> <?php echo __('time'); ?> *</label>
                            <input type="time" id="time" name="time" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="guests"><i class="fas fa-users"></i> <?php echo __('guests'); ?> *</label>
                        <select id="guests" name="guests" required>
                            <option value=""><?php echo __('select_guests'); ?></option>
                            <?php for($i = 1; $i <= 20; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo __('people'); ?></option>
                            <?php endfor; ?>
                            <option value="20"><?php echo __('over_20_people'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="request"><i class="fas fa-comment"></i> <?php echo __('special_request'); ?></label>
                        <textarea id="request" name="request" rows="4" placeholder="<?php echo __('special_request_placeholder'); ?>"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">
                        <i class="fas fa-check"></i> <?php echo __('confirm_reservation'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
