<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "?page=login";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Kiểm tra xem bảng có cột customer_id không
$hasCustomerId = false;
try {
    $check = $conn->query("SHOW COLUMNS FROM reservations LIKE 'customer_id'");
    $hasCustomerId = $check->rowCount() > 0;
} catch (Exception $e) {}

// Lấy email của user
$user_email = '';
try {
    $stmt = $conn->prepare("SELECT email FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_email = $user['email'] ?? '';
} catch (Exception $e) {}

// Xử lý hủy đặt bàn từ người dùng
$cancel_message = '';
$cancel_type = '';
if (isset($_POST['user_cancel_reservation'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $cancel_reason = trim($_POST['user_cancel_reason'] ?? '');
    
    // Kiểm tra đặt bàn thuộc về user này
    try {
        // Query tùy thuộc vào có cột customer_id hay không
        if ($hasCustomerId) {
            $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND (customer_id = ? OR email = ?)");
            $stmt->execute([$reservation_id, $_SESSION['customer_id'], $user_email]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND email = ?");
            $stmt->execute([$reservation_id, $user_email]);
        }
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation && $reservation['status'] == 'pending') {
            // Thêm prefix để biết là user tự hủy
            $full_reason = "Khách hàng tự hủy: " . $cancel_reason;
            
            $update = $conn->prepare("UPDATE reservations SET status = 'cancelled', cancel_reason = ? WHERE id = ?");
            $update->execute([$full_reason, $reservation_id]);
            
            $cancel_message = "Đã hủy đặt bàn #$reservation_id thành công!";
            $cancel_type = 'success';
        } elseif ($reservation && $reservation['status'] == 'confirmed') {
            $cancel_message = "Không thể hủy đặt bàn đã được xác nhận. Vui lòng liên hệ nhà hàng!";
            $cancel_type = 'error';
        } else {
            $cancel_message = "Không thể hủy đặt bàn này!";
            $cancel_type = 'error';
        }
    } catch (Exception $e) {
        $cancel_message = "Có lỗi xảy ra: " . $e->getMessage();
        $cancel_type = 'error';
    }
}

// Xử lý xóa đặt bàn khỏi danh sách (chỉ cho phép xóa đã hủy hoặc đã hoàn thành)
if (isset($_POST['delete_reservation'])) {
    $reservation_id = intval($_POST['reservation_id']);
    
    try {
        if ($hasCustomerId) {
            $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND (customer_id = ? OR email = ?)");
            $stmt->execute([$reservation_id, $_SESSION['customer_id'], $user_email]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND email = ?");
            $stmt->execute([$reservation_id, $user_email]);
        }
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Chỉ cho phép xóa đặt bàn đã hủy, đã hoàn thành hoặc không đến
        if ($reservation && in_array($reservation['status'], ['cancelled', 'completed', 'no_show'])) {
            $delete = $conn->prepare("DELETE FROM reservations WHERE id = ?");
            $delete->execute([$reservation_id]);
            
            $cancel_message = "Đã xóa đặt bàn #$reservation_id khỏi danh sách!";
            $cancel_type = 'success';
        } else {
            $cancel_message = "Không thể xóa đặt bàn đang chờ xử lý!";
            $cancel_type = 'error';
        }
    } catch (Exception $e) {
        $cancel_message = "Có lỗi xảy ra: " . $e->getMessage();
        $cancel_type = 'error';
    }
}

// Lấy danh sách đặt bàn của user
$reservations = [];
try {
    if ($hasCustomerId) {
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE customer_id = ? OR email = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['customer_id'], $user_email]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE email = ? ORDER BY created_at DESC");
        $stmt->execute([$user_email]);
    }
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$status_config = [
    'pending' => ['label' => 'Chờ xác nhận', 'icon' => 'fa-clock', 'color' => '#f59e0b'],
    'confirmed' => ['label' => 'Đã xác nhận', 'icon' => 'fa-check-circle', 'color' => '#22c55e'],
    'completed' => ['label' => 'Hoàn thành', 'icon' => 'fa-check-double', 'color' => '#16a34a'],
    'cancelled' => ['label' => 'Đã hủy', 'icon' => 'fa-times-circle', 'color' => '#ef4444'],
    'no_show' => ['label' => 'Không đến', 'icon' => 'fa-user-slash', 'color' => '#6b7280']
];

$location_labels = [
    'indoor' => 'Trong nhà', 'outdoor' => 'Sân vườn', 'vip' => 'Phòng VIP',
    'private_room' => 'Phòng riêng', 'any' => 'Bất kỳ'
];
?>

<section class="my-rsv-page">
    <div class="my-rsv-container">
        <!-- Header -->
        <div class="my-rsv-header">
            <div>
                <h1><i class="fas fa-calendar-check"></i> Lịch sử đặt bàn</h1>
                <p>Theo dõi trạng thái các lần đặt bàn của bạn</p>
            </div>
            <a href="?page=reservation" class="btn-new"><i class="fas fa-plus"></i> Đặt bàn mới</a>
        </div>

        <?php if ($cancel_message): ?>
        <div class="cancel-alert <?php echo $cancel_type; ?>">
            <i class="fas <?php echo $cancel_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $cancel_message; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($reservations)): ?>
        <div class="my-rsv-empty">
            <i class="fas fa-calendar-alt"></i>
            <h3>Chưa có đặt bàn nào</h3>
            <p>Hãy đặt bàn để có trải nghiệm tốt nhất!</p>
            <a href="?page=reservation" class="btn-book"><i class="fas fa-concierge-bell"></i> Đặt bàn ngay</a>
        </div>
        <?php else: ?>
        
        <div class="my-rsv-list">
            <?php foreach ($reservations as $res): 
                $status = $status_config[$res['status']] ?? $status_config['pending'];
            ?>
            <div class="my-rsv-card">
                <!-- Left: Date -->
                <div class="rsv-date-box" style="background: <?php echo $status['color']; ?>">
                    <span class="day"><?php echo date('d', strtotime($res['reservation_date'])); ?></span>
                    <span class="month"><?php echo date('M', strtotime($res['reservation_date'])); ?></span>
                    <span class="year"><?php echo date('Y', strtotime($res['reservation_date'])); ?></span>
                </div>

                <!-- Middle: Info -->
                <div class="rsv-info">
                    <div class="rsv-status" style="color: <?php echo $status['color']; ?>">
                        <i class="fas <?php echo $status['icon']; ?>"></i>
                        <span><?php echo $status['label']; ?></span>
                        <?php if ($res['status'] == 'confirmed'): ?>
                        <span class="badge-confirmed">✓ Đã xác nhận</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="rsv-details">
                        <div class="detail">
                            <i class="fas fa-clock"></i>
                            <span><?php echo date('H:i', strtotime($res['reservation_time'])); ?></span>
                        </div>
                        <div class="detail">
                            <i class="fas fa-users"></i>
                            <span><?php echo $res['number_of_guests']; ?> người</span>
                        </div>
                        <div class="detail">
                            <i class="fas fa-chair"></i>
                            <span><?php echo $location_labels[$res['table_preference'] ?? 'any'] ?? 'Bất kỳ'; ?></span>
                        </div>
                    </div>

                    <?php if (!empty($res['special_request'])): ?>
                    <div class="rsv-note">
                        <i class="fas fa-sticky-note"></i>
                        <?php echo htmlspecialchars($res['special_request']); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Status Message -->
                    <div class="rsv-message <?php echo $res['status']; ?>">
                        <?php if ($res['status'] == 'pending'): ?>
                            <i class="fas fa-hourglass-half"></i> Đang chờ nhà hàng xác nhận...
                        <?php elseif ($res['status'] == 'confirmed'): ?>
                            <i class="fas fa-check-circle"></i> Hẹn gặp bạn vào <?php echo date('d/m/Y', strtotime($res['reservation_date'])); ?> lúc <?php echo date('H:i', strtotime($res['reservation_time'])); ?>!
                        <?php elseif ($res['status'] == 'completed'): ?>
                            <i class="fas fa-smile"></i> Cảm ơn bạn đã đến!
                        <?php elseif ($res['status'] == 'cancelled'): ?>
                            <i class="fas fa-times-circle"></i> Đặt bàn đã bị hủy
                        <?php endif; ?>
                    </div>

                    <?php if ($res['status'] == 'cancelled' && !empty($res['cancel_reason'])): ?>
                    <div class="rsv-cancel-reason">
                        <div class="cancel-reason-header">
                            <i class="fas fa-comment-alt"></i>
                            <span>Lý do từ nhà hàng:</span>
                        </div>
                        <p><?php echo htmlspecialchars($res['cancel_reason']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right: Actions -->
                <div class="rsv-actions">
                    <span class="rsv-id">#<?php echo $res['id']; ?></span>
                    
                    <?php if ($res['status'] == 'pending'): ?>
                    <button type="button" class="btn-cancel-rsv" onclick="openUserCancelModal(<?php echo $res['id']; ?>, '<?php echo date('d/m/Y', strtotime($res['reservation_date'])); ?>', '<?php echo date('H:i', strtotime($res['reservation_time'])); ?>')">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <?php elseif ($res['status'] == 'confirmed'): ?>
                    <span class="rsv-confirmed-note">
                        <i class="fas fa-lock"></i> Đã xác nhận
                    </span>
                    <?php endif; ?>
                    
                    <?php if (in_array($res['status'], ['cancelled', 'completed', 'no_show'])): ?>
                    <button type="button" class="btn-delete-rsv" onclick="confirmDeleteReservation(<?php echo $res['id']; ?>)">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                    <?php endif; ?>
                    
                    <span class="rsv-created"><?php echo date('d/m/Y', strtotime($res['created_at'])); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Form xóa đặt bàn ẩn -->
<form id="deleteReservationForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_reservation" value="1">
    <input type="hidden" name="reservation_id" id="deleteReservationId">
</form>

<!-- Modal Hủy đặt bàn -->
<div id="userCancelModal" class="user-cancel-modal" style="display: none;">
    <div class="modal-overlay" onclick="closeUserCancelModal()"></div>
    <div class="modal-box">
        <div class="modal-header">
            <h3>Hủy đặt bàn</h3>
            <button class="modal-close" onclick="closeUserCancelModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="cancel-form">
            <input type="hidden" name="user_cancel_reservation" value="1">
            <input type="hidden" name="reservation_id" id="userCancelId">
            
            <div class="cancel-info-box">
                <div>
                    <p>Bạn đang hủy đặt bàn ngày <strong id="cancelDate"></strong> lúc <strong id="cancelTime"></strong></p>
                    <small>Hành động này không thể hoàn tác</small>
                </div>
            </div>
            
            <div class="cancel-reason-input">
                <label>Lý do hủy</label>
                <textarea name="user_cancel_reason" id="userCancelReason" rows="3" placeholder="Vui lòng cho chúng tôi biết lý do..." required></textarea>
                
                <span class="quick-reasons-label">Chọn nhanh:</span>
                <div class="quick-reasons" style="display: grid !important; grid-template-columns: repeat(2, 1fr) !important; gap: 8px !important;">
                    <span class="quick-btn" data-reason="Đặt nhầm ngày/giờ" style="display: block !important; background: #dcfce7 !important; border: 2px solid #22c55e !important; color: #166534 !important; padding: 10px !important; border-radius: 10px !important; text-align: center !important; font-weight: 600 !important; cursor: pointer !important;">Nhầm ngày/giờ</span>
                    <span class="quick-btn" data-reason="Thay đổi kế hoạch" style="display: block !important; background: #dcfce7 !important; border: 2px solid #22c55e !important; color: #166534 !important; padding: 10px !important; border-radius: 10px !important; text-align: center !important; font-weight: 600 !important; cursor: pointer !important;">Đổi kế hoạch</span>
                    <span class="quick-btn" data-reason="Có việc đột xuất" style="display: block !important; background: #dcfce7 !important; border: 2px solid #22c55e !important; color: #166534 !important; padding: 10px !important; border-radius: 10px !important; text-align: center !important; font-weight: 600 !important; cursor: pointer !important;">Việc đột xuất</span>
                    <span class="quick-btn" data-reason="Muốn đặt lại với thông tin khác" style="display: block !important; background: #dcfce7 !important; border: 2px solid #22c55e !important; color: #166534 !important; padding: 10px !important; border-radius: 10px !important; text-align: center !important; font-weight: 600 !important; cursor: pointer !important;">Đặt lại</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-back" onclick="closeUserCancelModal()">
                    Quay lại
                </button>
                <button type="submit" class="btn-confirm-cancel">
                    Xác nhận hủy
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.my-rsv-page {
    min-height: 100vh;
    padding: 2rem 0 4rem;
    background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 50%, #f0fdf4 100%);
}
.my-rsv-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Header */
.my-rsv-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 20px;
    color: white;
}
.my-rsv-header h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}
.my-rsv-header h1 i { margin-right: 0.5rem; }
.my-rsv-header p {
    margin: 0.25rem 0 0;
    opacity: 0.9;
}
.btn-new {
    padding: 0.75rem 1.5rem;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-new:hover {
    background: rgba(255,255,255,0.3);
}

/* Empty */
.my-rsv-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.my-rsv-empty i {
    font-size: 4rem;
    color: #22c55e;
    margin-bottom: 1rem;
}
.my-rsv-empty h3 {
    color: #1f2937;
    margin: 0 0 0.5rem;
}
.my-rsv-empty p {
    color: #6b7280;
    margin: 0 0 1.5rem;
}
.btn-book {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
}

/* Card List */
.my-rsv-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Card */
.my-rsv-card {
    display: flex;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
}
.my-rsv-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    border-color: #22c55e;
}

/* Date Box */
.rsv-date-box {
    width: 90px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    padding: 1rem;
    flex-shrink: 0;
}
.rsv-date-box .day {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
}
.rsv-date-box .month {
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
}
.rsv-date-box .year {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Info */
.rsv-info {
    flex: 1;
    padding: 1rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.rsv-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
    font-size: 0.95rem;
}
.badge-confirmed {
    background: #dcfce7;
    color: #15803d;
    padding: 0.25rem 0.6rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    margin-left: 0.5rem;
}

.rsv-details {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.rsv-details .detail {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: #374151;
    font-size: 0.9rem;
}
.rsv-details .detail i {
    color: #22c55e;
    width: 16px;
}

.rsv-note {
    font-size: 0.85rem;
    color: #6b7280;
    background: #f9fafb;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    border-left: 3px solid #fbbf24;
}
.rsv-note i {
    color: #fbbf24;
    margin-right: 0.5rem;
}

/* Message */
.rsv-message {
    font-size: 0.85rem;
    padding: 0.6rem 1rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.rsv-message.pending {
    background: #fef3c7;
    color: #92400e;
}
.rsv-message.confirmed {
    background: #dcfce7;
    color: #15803d;
}
.rsv-message.completed {
    background: #dcfce7;
    color: #15803d;
}
.rsv-message.cancelled {
    background: #fee2e2;
    color: #b91c1c;
}

/* Cancel Reason */
.rsv-cancel-reason {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 0.85rem 1rem;
    margin-top: 0.5rem;
}
.cancel-reason-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #b91c1c;
    margin-bottom: 0.4rem;
}
.cancel-reason-header i {
    color: #ef4444;
}
.rsv-cancel-reason p {
    margin: 0;
    font-size: 0.9rem;
    color: #7f1d1d;
    font-style: italic;
    line-height: 1.5;
}

/* Actions */
.rsv-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: space-between;
    padding: 1rem;
    background: #f9fafb;
    border-left: 1px solid #e5e7eb;
    min-width: 100px;
}
.rsv-id {
    font-size: 0.8rem;
    color: #9ca3af;
    font-weight: 500;
}
.rsv-created {
    font-size: 0.75rem;
    color: #9ca3af;
}

/* Responsive */
@media (max-width: 768px) {
    .my-rsv-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    .my-rsv-card {
        flex-direction: column;
    }
    .rsv-date-box {
        width: 100%;
        flex-direction: row;
        gap: 0.5rem;
        padding: 0.75rem;
    }
    .rsv-date-box .day { font-size: 1.5rem; }
    .rsv-actions {
        flex-direction: row;
        border-left: none;
        border-top: 1px solid #e5e7eb;
    }
}

/* Cancel Alert */
.cancel-alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-weight: 600;
}
.cancel-alert.success {
    background: #dcfce7;
    border: 2px solid #22c55e;
    color: #15803d;
}
.cancel-alert.error {
    background: #fee2e2;
    border: 2px solid #ef4444;
    color: #b91c1c;
}

/* Cancel Button */
.btn-cancel-rsv {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 0.85rem;
    background: #fee2e2;
    border: 2px solid #fecaca;
    border-radius: 8px;
    color: #dc2626;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-cancel-rsv:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: white;
    transform: translateY(-2px);
}

/* Delete Button */
.btn-delete-rsv {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #f3f4f6;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    color: #9ca3af;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-delete-rsv:hover {
    background: #fee2e2;
    border-color: #fecaca;
    color: #dc2626;
    transform: translateY(-2px);
}

/* Confirmed Note - Cannot Cancel */
.rsv-confirmed-note {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 0.85rem;
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #15803d;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
}
.rsv-confirmed-note i {
    font-size: 0.75rem;
}

/* User Cancel Modal */
.user-cancel-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.user-cancel-modal .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(4px);
}
.user-cancel-modal .modal-box {
    position: relative;
    background: white;
    border-radius: 20px;
    max-width: 450px;
    width: 100%;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    animation: modalIn 0.3s ease;
    border: 2px solid #e5e7eb;
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(-20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.user-cancel-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-bottom: 2px solid #22c55e;
}
.user-cancel-modal .modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #166534;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.user-cancel-modal .modal-header h3 i {
    color: #ef4444;
}
.user-cancel-modal .modal-close {
    background: white;
    border: 2px solid #e5e7eb;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.user-cancel-modal .modal-close:hover {
    background: #fee2e2;
    border-color: #fecaca;
    color: #ef4444;
}

.cancel-info-box {
    padding: 1rem 1.25rem;
    background: #fef3c7;
    border-bottom: 2px solid #fbbf24;
}
.cancel-info-box p {
    margin: 0;
    color: #78350f;
    font-size: 0.95rem;
    font-weight: 600;
}
.cancel-info-box strong {
    color: #92400e;
}
.cancel-info-box small {
    display: block;
    color: #a16207;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.cancel-reason-input {
    padding: 1.25rem;
    background: white;
}
.cancel-reason-input label {
    display: block;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}
.cancel-reason-input textarea {
    width: 100%;
    padding: 0.85rem 1rem;
    border: 2px solid #22c55e;
    border-radius: 12px;
    font-size: 0.95rem;
    resize: none;
    font-family: inherit;
    transition: all 0.2s;
    background: #ffffff !important;
    color: #1f2937 !important;
}
.cancel-reason-input textarea:focus {
    outline: none;
    border-color: #16a34a;
    background: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
}
.cancel-reason-input textarea::placeholder {
    color: #6b7280 !important;
}

.quick-reasons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
    margin-top: 0.75rem;
}
.quick-btn {
    padding: 0.6rem 0.75rem;
    background: #f0fdf4;
    border: 2px solid #22c55e;
    border-radius: 10px;
    font-size: 0.85rem;
    color: #166534;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 600;
    text-align: center;
}
.quick-btn:hover {
    background: #22c55e;
    border-color: #22c55e;
    color: white;
}

.modal-actions {
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}
.btn-back {
    flex: 1;
    padding: 0.75rem;
    background: white;
    border: 2px solid #22c55e;
    border-radius: 10px;
    color: #22c55e;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
    font-size: 0.9rem;
}
.btn-back:hover {
    background: #f0fdf4;
}
.btn-confirm-cancel {
    flex: 1;
    padding: 0.75rem;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
    font-size: 0.9rem;
}
.btn-confirm-cancel:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

/* Force override dark theme for modal */
.user-cancel-modal .modal-box,
.user-cancel-modal .cancel-reason-input,
.user-cancel-modal .modal-actions {
    background: #ffffff !important;
}

.user-cancel-modal textarea,
.user-cancel-modal .cancel-reason-input textarea,
#userCancelReason {
    background: #ffffff !important;
    background-color: #ffffff !important;
    color: #1f2937 !important;
    border: 2px solid #22c55e !important;
}

/* Label for reason input */
.user-cancel-modal .cancel-reason-input label {
    display: block !important;
    color: #1f2937 !important;
    font-weight: 600 !important;
    font-size: 0.95rem !important;
    margin-bottom: 0.5rem !important;
}

/* Quick reasons label */
.quick-reasons-label {
    display: block !important;
    color: #1f2937 !important;
    font-weight: 600 !important;
    font-size: 0.9rem !important;
    margin: 1rem 0 0.5rem !important;
}

.user-cancel-modal .quick-reasons {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 0.5rem !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.user-cancel-modal .quick-btn {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    background: #dcfce7 !important;
    background-color: #dcfce7 !important;
    border: 2px solid #22c55e !important;
    color: #166534 !important;
    padding: 0.65rem 0.5rem !important;
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    border-radius: 10px !important;
    text-align: center !important;
    cursor: pointer !important;
}

.user-cancel-modal .quick-btn:hover {
    background: #22c55e !important;
    color: #ffffff !important;
}
</style>


<script>
function openUserCancelModal(id, date, time) {
    document.getElementById('userCancelId').value = id;
    document.getElementById('cancelDate').textContent = date;
    document.getElementById('cancelTime').textContent = time;
    document.getElementById('userCancelReason').value = '';
    document.getElementById('userCancelModal').style.display = 'flex';
}

function closeUserCancelModal() {
    document.getElementById('userCancelModal').style.display = 'none';
}

// Delete reservation function
function confirmDeleteReservation(id) {
    if (confirm('Bạn có chắc muốn xóa đặt bàn #' + id + ' khỏi danh sách?\n\nHành động này không thể hoàn tác!')) {
        document.getElementById('deleteReservationId').value = id;
        document.getElementById('deleteReservationForm').submit();
    }
}

// Quick reason buttons
document.querySelectorAll('.quick-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('userCancelReason').value = this.dataset.reason;
    });
});

// Close on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUserCancelModal();
    }
});
</script>
