<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_ids'])) {
    $bulk_action = $_POST['bulk_action'];
    $selected_ids = $_POST['selected_ids'];
    
    if (!empty($selected_ids) && is_array($selected_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($selected_ids), '?'));
        
        switch($bulk_action) {
            case 'confirm_all':
                $stmt = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE id IN ($ids_placeholder) AND status = 'pending'");
                $stmt->execute($selected_ids);
                $affected = $stmt->rowCount();
                $_SESSION['message'] = "Đã xác nhận $affected đặt bàn";
                $_SESSION['message_type'] = 'success';
                break;
                
            case 'cancel_all':
                $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id IN ($ids_placeholder) AND status IN ('pending', 'confirmed')");
                $stmt->execute($selected_ids);
                $affected = $stmt->rowCount();
                $_SESSION['message'] = "Đã hủy $affected đặt bàn";
                $_SESSION['message_type'] = 'success';
                break;
        }
    }
    
    echo '<script>window.location.href = "reservations.php";</script>';
    exit;
}

// Xử lý actions đơn lẻ
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;
    
    switch($action) {
        case 'confirm':
            $stmt = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Đã xác nhận đặt bàn";
            $_SESSION['message_type'] = 'success';
            break;
        case 'cancel':
            $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Đã hủy đặt bàn";
            $_SESSION['message_type'] = 'success';
            break;
        case 'complete':
            $stmt = $conn->prepare("UPDATE reservations SET status = 'completed' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Đã đánh dấu hoàn thành";
            $_SESSION['message_type'] = 'success';
            break;
        case 'no_show':
            $stmt = $conn->prepare("UPDATE reservations SET status = 'no_show' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Đã đánh dấu không đến";
            $_SESSION['message_type'] = 'success';
            break;
    }
    
    echo '<script>window.location.href = "reservations.php";</script>';
    exit;
}

// Hiển thị thông báo
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Lọc
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? '';

$where = "1=1";
if ($status_filter != 'all') {
    $where .= " AND status = '$status_filter'";
}
if ($date_filter) {
    $where .= " AND reservation_date = '$date_filter'";
}

// Lấy danh sách đặt bàn
$stmt = $conn->query("
    SELECT r.*
    FROM reservations r
    WHERE $where
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
");
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM reservations
    WHERE reservation_date >= CURDATE()
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đặt bàn - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-fix.css">
    <link rel="stylesheet" href="../assets/css/admin-reservations.css">
    <link rel="stylesheet" href="../assets/css/admin-orange-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-calendar-alt"></i> Quản lý đặt bàn</h1>
                <div class="header-actions">
                    <a href="../index.php" target="_blank" class="btn btn-secondary">
                        <i class="fas fa-external-link-alt"></i> Xem website
                    </a>
                </div>
            </div>
            
            <!-- Thống kê -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Tổng đặt bàn</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Chờ xác nhận</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['confirmed']; ?></h3>
                        <p>Đã xác nhận</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['completed']; ?></h3>
                        <p>Hoàn thành</p>
                    </div>
                </div>
            </div>
            
            <!-- Bộ lọc -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-filter"></i> Bộ lọc</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Trạng thái</label>
                                <select name="status" onchange="this.form.submit()">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                    <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ngày (để trống = tất cả)</label>
                                <input type="date" name="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message_type === 'success' ? '✅' : '❌'; ?> <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Danh sách đặt bàn -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Danh sách đặt bàn (<?php echo count($reservations); ?>)</h2>
                </div>
                <div class="card-body">
                    <!-- Bulk Actions -->
                    <form method="POST" id="bulkForm">
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; align-items: center;">
                            <button type="button" onclick="selectAll()" class="btn btn-small btn-secondary">
                                <i class="fas fa-check-square"></i> Chọn tất cả
                            </button>
                            <button type="button" onclick="deselectAll()" class="btn btn-small btn-secondary">
                                <i class="fas fa-square"></i> Bỏ chọn
                            </button>
                            <div style="border-left: 2px solid #e5e7eb; height: 2rem;"></div>
                            <button type="button" onclick="confirmBulkAction('confirm_all')" class="btn btn-small btn-success">
                                <i class="fas fa-check-circle"></i> Xác nhận đã chọn
                            </button>
                            <button type="button" onclick="confirmBulkAction('cancel_all')" class="btn btn-small btn-danger">
                                <i class="fas fa-times-circle"></i> Hủy đã chọn
                            </button>
                            <span id="selectedCount" style="margin-left: auto; color: #6b7280; font-size: 0.875rem;">
                                Đã chọn: <strong>0</strong>
                            </span>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="40"><input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)"></th>
                                        <th>ID</th>
                                    <th>Khách hàng</th>
                                    <th>Liên hệ</th>
                                    <th>Ngày giờ</th>
                                    <th>Số khách</th>
                                    <th>Vị trí</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($reservations as $res): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_ids[]" value="<?php echo $res['id']; ?>" class="row-checkbox" onchange="updateSelectedCount()"></td>
                                    <td>#<?php echo $res['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($res['customer_name']); ?></strong>
                                        <?php if($res['occasion']): ?>
                                        <br><small class="text-muted"><i class="fas fa-gift"></i> <?php echo htmlspecialchars($res['occasion']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($res['phone']); ?><br>
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($res['email']); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($res['reservation_date'])); ?><br>
                                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($res['reservation_time'])); ?>
                                    </td>
                                    <td><i class="fas fa-users"></i> <?php echo $res['number_of_guests']; ?> người</td>
                                    <td>
                                        <?php
                                        $locations = [
                                            'indoor' => '<i class="fas fa-home"></i> Trong nhà',
                                            'outdoor' => '<i class="fas fa-tree"></i> Sân vườn',
                                            'vip' => '<i class="fas fa-crown"></i> VIP',
                                            'private_room' => '<i class="fas fa-door-closed"></i> Phòng riêng',
                                            'any' => '<i class="fas fa-question"></i> Bất kỳ'
                                        ];
                                        echo $locations[$res['table_preference']] ?? $res['table_preference'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'pending' => '<span class="badge badge-warning">Chờ xác nhận</span>',
                                            'confirmed' => '<span class="badge badge-success">Đã xác nhận</span>',
                                            'completed' => '<span class="badge badge-info">Hoàn thành</span>',
                                            'cancelled' => '<span class="badge badge-danger">Đã hủy</span>',
                                            'no_show' => '<span class="badge badge-dark">Không đến</span>'
                                        ];
                                        echo $badges[$res['status']] ?? $res['status'];
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if($res['status'] == 'pending'): ?>
                                            <a href="?action=confirm&id=<?php echo $res['id']; ?>" class="btn btn-sm btn-success" title="Xác nhận">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?action=cancel&id=<?php echo $res['id']; ?>" class="btn btn-sm btn-danger" title="Hủy" onclick="return confirm('Xác nhận hủy đặt bàn?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <?php elseif($res['status'] == 'confirmed'): ?>
                                            <a href="?action=complete&id=<?php echo $res['id']; ?>" class="btn btn-sm btn-info" title="Hoàn thành">
                                                <i class="fas fa-check-double"></i>
                                            </a>
                                            <a href="?action=no_show&id=<?php echo $res['id']; ?>" class="btn btn-sm btn-warning" title="Không đến">
                                                <i class="fas fa-user-times"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($res)); ?>)" title="Chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
        </main>
    </div>
    
    <!-- Modal Chi tiết -->
    <div id="detailModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Chi tiết đặt bàn</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be inserted here -->
            </div>
        </div>
    </div>
    
    <style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
    }
    
    .modal-content {
        position: relative;
        background: white;
        border-radius: 24px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-header {
        padding: 2rem;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    
    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }
    
    .modal-body {
        padding: 2rem;
        overflow-y: auto;
        max-height: calc(80vh - 100px);
    }
    
    .detail-row {
        display: flex;
        padding: 1rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 700;
        color: #475569;
        min-width: 150px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .detail-label i {
        color: #f97316;
        width: 20px;
    }
    
    .detail-value {
        color: #1e293b;
        font-weight: 500;
        flex: 1;
    }
    </style>
    
    <script>
    function viewDetails(reservation) {
        const locations = {
            'indoor': '<i class="fas fa-home"></i> Trong nhà',
            'outdoor': '<i class="fas fa-tree"></i> Sân vườn',
            'vip': '<i class="fas fa-crown"></i> VIP',
            'private_room': '<i class="fas fa-door-closed"></i> Phòng riêng',
            'any': '<i class="fas fa-question"></i> Bất kỳ'
        };
        
        const statuses = {
            'pending': '<span class="badge badge-warning">Chờ xác nhận</span>',
            'confirmed': '<span class="badge badge-success">Đã xác nhận</span>',
            'completed': '<span class="badge badge-info">Hoàn thành</span>',
            'cancelled': '<span class="badge badge-danger">Đã hủy</span>',
            'no_show': '<span class="badge badge-dark">Không đến</span>'
        };
        
        const date = new Date(reservation.reservation_date);
        const formattedDate = date.toLocaleDateString('vi-VN');
        
        const html = `
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-hashtag"></i> ID:</div>
                <div class="detail-value">#${reservation.id}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-user"></i> Khách hàng:</div>
                <div class="detail-value">${reservation.customer_name}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-envelope"></i> Email:</div>
                <div class="detail-value">${reservation.email}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-phone"></i> Điện thoại:</div>
                <div class="detail-value">${reservation.phone}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-calendar"></i> Ngày:</div>
                <div class="detail-value">${formattedDate}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-clock"></i> Giờ:</div>
                <div class="detail-value">${reservation.reservation_time}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-users"></i> Số khách:</div>
                <div class="detail-value">${reservation.number_of_guests} người</div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-map-marker-alt"></i> Vị trí:</div>
                <div class="detail-value">${locations[reservation.table_preference] || reservation.table_preference}</div>
            </div>
            ${reservation.occasion ? `
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-gift"></i> Dịp đặc biệt:</div>
                <div class="detail-value">${reservation.occasion}</div>
            </div>
            ` : ''}
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-info-circle"></i> Trạng thái:</div>
                <div class="detail-value">${statuses[reservation.status] || reservation.status}</div>
            </div>
            ${reservation.special_request ? `
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-comment"></i> Yêu cầu:</div>
                <div class="detail-value">${reservation.special_request}</div>
            </div>
            ` : ''}
            <div class="detail-row">
                <div class="detail-label"><i class="fas fa-clock"></i> Tạo lúc:</div>
                <div class="detail-value">${new Date(reservation.created_at).toLocaleString('vi-VN')}</div>
            </div>
        `;
        
        document.getElementById('modalBody').innerHTML = html;
        document.getElementById('detailModal').style.display = 'flex';
    }
    
    function closeModal() {
        document.getElementById('detailModal').style.display = 'none';
    }
    
    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    // Bulk Actions Functions
    function selectAll() {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = true);
        document.getElementById('selectAllCheckbox').checked = true;
        updateSelectedCount();
    }
    
    function deselectAll() {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllCheckbox').checked = false;
        updateSelectedCount();
    }
    
    function toggleAll(checkbox) {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = checkbox.checked);
        updateSelectedCount();
    }
    
    function updateSelectedCount() {
        const checked = document.querySelectorAll('.row-checkbox:checked').length;
        document.getElementById('selectedCount').innerHTML = 'Đã chọn: <strong>' + checked + '</strong>';
        
        // Update select all checkbox
        const total = document.querySelectorAll('.row-checkbox').length;
        document.getElementById('selectAllCheckbox').checked = (checked === total && total > 0);
    }
    
    function confirmBulkAction(action) {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        
        if (checked.length === 0) {
            alert('⚠️ Vui lòng chọn ít nhất một đặt bàn');
            return;
        }
        
        let message = '';
        if (action === 'confirm_all') {
            message = `Xác nhận ${checked.length} đặt bàn đã chọn?`;
        } else if (action === 'cancel_all') {
            message = `⚠️ HỦY ${checked.length} đặt bàn đã chọn?\n\nHành động này không thể hoàn tác!`;
        }
        
        if (confirm(message)) {
            const form = document.getElementById('bulkForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'bulk_action';
            input.value = action;
            form.appendChild(input);
            form.submit();
        }
    }
    </script>
</body>
</html>
