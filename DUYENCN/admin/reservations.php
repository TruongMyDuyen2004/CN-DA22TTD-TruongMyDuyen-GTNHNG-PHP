<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Kiểm tra và thêm cột cancel_reason nếu chưa có
try {
    $conn->query("SELECT cancel_reason FROM reservations LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE reservations ADD COLUMN cancel_reason TEXT DEFAULT NULL");
}

// Xử lý hủy đặt bàn với lý do (POST)
if (isset($_POST['cancel_with_reason'])) {
    $id = intval($_POST['reservation_id']);
    $reason = trim($_POST['cancel_reason'] ?? '');
    
    $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled', cancel_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $id]);
    
    $_SESSION['message'] = "Đã hủy đặt bàn #$id";
    $_SESSION['message_type'] = 'success';
    
    echo '<script>window.location.href = "reservations.php";</script>';
    exit;
}

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
if ($status_filter == 'customer_cancelled') {
    $where .= " AND status = 'cancelled' AND cancel_reason LIKE 'Khách hàng tự hủy:%'";
} elseif ($status_filter != 'all') {
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
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'cancelled' AND cancel_reason LIKE 'Khách hàng tự hủy:%' THEN 1 ELSE 0 END) as customer_cancelled
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
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* Modern Green & White Theme for Reservations */
    body { background: #f8fafc !important; }
    .main-content { background: #f8fafc !important; padding: 2rem; }
    
    /* Quick Filter Tabs - Modern Style */
    .quick-filter-tabs {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
        background: white;
        padding: 1rem 1.25rem;
        border-radius: 20px;
        border: none;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .filter-tab {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.25s ease;
        background: #f8fafc;
        color: #64748b;
        border: 2px solid #e2e8f0;
    }
    .filter-tab i {
        font-size: 0.85rem;
    }
    .filter-tab:hover {
        background: #f0fdf4;
        border-color: #86efac;
        color: #22c55e;
        transform: translateY(-1px);
    }
    .filter-tab.active {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
        border-color: #86efac;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.2);
    }
    .tab-count {
        background: #e2e8f0;
        color: #64748b;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 24px;
        text-align: center;
    }
    .filter-tab.active .tab-count {
        background: #22c55e;
        color: white;
    }
    
    /* Date Filter Row */
    .date-filter-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .date-filter-form {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: white;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        border: 2px solid #e5e7eb;
    }
    .date-filter-form label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .date-filter-form label i { color: #22c55e; }
    .date-filter-form input[type="date"] {
        border: none;
        padding: 0.4rem;
        font-size: 0.9rem;
        color: #374151;
        background: transparent;
        cursor: pointer;
    }
    .date-filter-form input[type="date"]:focus { outline: none; }
    .clear-date {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #fee2e2;
        color: #dc2626;
        text-decoration: none;
        font-size: 0.7rem;
    }
    .clear-date:hover { background: #fecaca; }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .page-header h1 {
        color: #1f2937 !important;
        font-size: 1.75rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }
    .page-header h1 i { color: #22c55e; }
    
    /* Stats Cards - Same as Discount Page */
    .rsv-stats {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 1.25rem !important;
        margin-bottom: 1.5rem !important;
    }
    .rsv-stats .stat-card {
        background: white !important;
        border-radius: 14px !important;
        padding: 1.25rem 1.5rem !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06) !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 1.25rem !important;
        border: 1px solid #e5e7eb !important;
        text-align: left !important;
        transition: all 0.2s !important;
    }
    .rsv-stats .stat-card:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
    }
    .rsv-stats .stat-icon {
        width: 56px !important;
        height: 56px !important;
        border-radius: 12px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.4rem !important;
        color: white !important;
        flex-shrink: 0 !important;
        margin: 0 !important;
    }
    .rsv-stats .stat-icon.total { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important; }
    .rsv-stats .stat-icon.pending { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important; }
    .rsv-stats .stat-icon.confirmed { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important; }
    .rsv-stats .stat-icon.completed { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important; }
    .rsv-stats .stat-info h3 { 
        font-size: 1.75rem !important; 
        font-weight: 700 !important; 
        color: #1f2937 !important; 
        margin: 0 !important; 
        line-height: 1 !important;
    }
    .rsv-stats .stat-info p { 
        color: #6b7280 !important; 
        margin: 0.25rem 0 0 !important; 
        font-size: 0.85rem !important; 
        font-weight: 500 !important;
    }
    
    @media (max-width: 1200px) {
        .rsv-stats { grid-template-columns: repeat(2, 1fr) !important; }
    }
    @media (max-width: 600px) {
        .rsv-stats { grid-template-columns: 1fr !important; }
    }
    
    /* Hide old stats-grid styles */
    .stats-grid {
        background: white !important;
        border-radius: 14px !important;
        padding: 1.25rem 1.5rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
        transition: all 0.3s !important;
    }
    .stat-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
    }
    .stat-icon {
        width: 52px !important;
        height: 52px !important;
        border-radius: 12px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.3rem !important;
        color: white !important;
        flex-shrink: 0 !important;
    }
    
    /* Colorful variants - with !important */
    .stat-card.stat-primary .stat-icon { 
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important; 
    }
    .stat-card.stat-primary:hover { border-color: #3b82f6 !important; }
    
    .stat-card.stat-warning .stat-icon { 
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; 
    }
    .stat-card.stat-warning:hover { border-color: #f59e0b !important; }
    
    .stat-card.stat-success .stat-icon { 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important; 
    }
    .stat-card.stat-success:hover { border-color: #22c55e !important; }
    
    .stat-card.stat-info .stat-icon { 
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important; 
    }
    .stat-card.stat-info:hover { border-color: #8b5cf6 !important; }
    
    .stat-content h3 {
        font-size: 1.75rem !important;
        font-weight: 800 !important;
        color: #1f2937 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    .stat-content p {
        color: #6b7280 !important;
        font-size: 0.8rem !important;
        margin: 0.2rem 0 0 !important;
        font-weight: 500 !important;
    }
    
    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
    }
    @media (max-width: 600px) {
        .stats-grid { grid-template-columns: 1fr !important; }
    }
    
    /* Filter Card */
    .card {
        background: white;
        border-radius: 16px;
        border: 2px solid #e5e7eb;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 2px solid #f3f4f6;
        background: #f9fafb;
    }
    .card-header h2 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .card-header h2 i { color: #22c55e; }
    .card-body { padding: 1.5rem; }
    
    /* Filter Card - Modern Design */
    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 2px solid #e5e7eb;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .filter-form {
        display: flex;
        gap: 1.5rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        flex: 1;
        min-width: 200px;
    }
    .filter-group label {
        font-size: 0.85rem;
        font-weight: 700;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .filter-group label i {
        color: #22c55e;
    }
    .filter-group select,
    .filter-group input[type="date"] {
        padding: 0.85rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.95rem;
        color: #374151;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 500;
    }
    .filter-group select {
        background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2322c55e'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") no-repeat right 1rem center;
        background-size: 1.25rem;
        padding-right: 2.5rem;
        -webkit-appearance: none;
        -moz-appearance: none;
    }
    .filter-group select:hover,
    .filter-group input[type="date"]:hover {
        border-color: #22c55e;
    }
    .filter-group select:focus,
    .filter-group input[type="date"]:focus {
        outline: none;
        border-color: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
    }
    .filter-actions {
        display: flex;
        align-items: center;
    }
    .reset-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.85rem 1.5rem;
        background: #f3f4f6;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        color: #6b7280;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }
    .reset-btn:hover {
        background: #fee2e2;
        border-color: #fecaca;
        color: #dc2626;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .form-group label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #374151;
    }
    .form-group select,
    .form-group input {
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.9rem;
        min-width: 180px;
        transition: all 0.2s;
    }
    .form-group select:focus,
    .form-group input:focus {
        outline: none;
        border-color: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
    }
    
    /* Bulk Actions */
    .bulk-actions {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .bulk-btn {
        padding: 0.6rem 1rem;
        border: none;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s;
    }
    .bulk-btn.select-all { background: #dbeafe; color: #1d4ed8; }
    .bulk-btn.deselect { background: #f3f4f6; color: #6b7280; }
    .bulk-btn.confirm-all { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; }
    .bulk-btn.cancel-all { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
    .bulk-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .selected-count {
        font-size: 0.85rem;
        color: #6b7280;
        margin-left: auto;
    }
    
    /* Table */
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    .data-table th {
        background: #f9fafb;
        padding: 1rem;
        text-align: left;
        font-size: 0.8rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e5e7eb;
    }
    .data-table td {
        padding: 1rem;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .data-table tbody tr {
        transition: all 0.2s;
    }
    .data-table tbody tr:hover {
        background: #f0fdf4;
    }
    
    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.4rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-warning { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; }
    .badge-success { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #15803d; }
    .badge-info { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1d4ed8; }
    .badge-danger { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #b91c1c; }
    .badge-dark { background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%); color: #374151; }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    .btn-sm {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-sm:hover { transform: scale(1.1); }
    .btn-success { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; }
    .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
    .btn-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
    .btn-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
    .btn-secondary { 
        background: white; 
        color: #374151; 
        border: 2px solid #e5e7eb;
        padding: 0.6rem 1rem;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-secondary:hover { border-color: #22c55e; color: #22c55e; }
    
    .text-muted { color: #9ca3af; }
    
    /* Checkbox styling */
    input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #22c55e;
        cursor: pointer;
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> Quản lý đặt bàn</h1>
            <div class="header-actions">
                <a href="../index.php" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt"></i> Xem website
                </a>
            </div>
        </div>
            
            <!-- Thống kê - Style giống trang giảm giá -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#3b82f6';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); flex-shrink: 0;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $stats['total']; ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Tổng đặt bàn</p>
                    </div>
                </div>
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f59e0b';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); flex-shrink: 0;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $stats['pending']; ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Chờ xác nhận</p>
                    </div>
                </div>
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#8b5cf6';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); flex-shrink: 0;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $stats['confirmed']; ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Đã xác nhận</p>
                    </div>
                </div>
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $stats['completed']; ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Hoàn thành</p>
                    </div>
                </div>
            </div>
            
            <!-- Bộ lọc - Quick Tabs -->
            <div class="quick-filter-tabs">
                <a href="?status=all<?php echo $date_filter ? '&date='.$date_filter : ''; ?>" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span>Tất cả</span>
                    <span class="tab-count"><?php echo $stats['total']; ?></span>
                </a>
                <a href="?status=pending<?php echo $date_filter ? '&date='.$date_filter : ''; ?>" class="filter-tab pending <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span>Chờ xác nhận</span>
                    <span class="tab-count"><?php echo $stats['pending']; ?></span>
                </a>
                <a href="?status=confirmed<?php echo $date_filter ? '&date='.$date_filter : ''; ?>" class="filter-tab confirmed <?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    <span>Đã xác nhận</span>
                    <span class="tab-count"><?php echo $stats['confirmed']; ?></span>
                </a>
                <a href="?status=completed<?php echo $date_filter ? '&date='.$date_filter : ''; ?>" class="filter-tab completed <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-check-double"></i>
                    <span>Hoàn thành</span>
                    <span class="tab-count"><?php echo $stats['completed']; ?></span>
                </a>
                <a href="?status=cancelled<?php echo $date_filter ? '&date='.$date_filter : ''; ?>" class="filter-tab cancelled <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i>
                    <span>Đã hủy</span>
                    <span class="tab-count"><?php echo $stats['cancelled']; ?></span>
                </a>
                <a href="?status=customer_cancelled<?php echo $date_filter ? '&date='.$date_filter : ''; ?>" class="filter-tab customer-cancelled <?php echo $status_filter == 'customer_cancelled' ? 'active' : ''; ?>">
                    <i class="fas fa-user-times"></i>
                    <span>Khách tự hủy</span>
                    <span class="tab-count"><?php echo $stats['customer_cancelled']; ?></span>
                </a>
            </div>
            
            <!-- Date Filter -->
            <div class="date-filter-row">
                <form method="GET" class="date-filter-form">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <label><i class="fas fa-calendar-day"></i> Ngày:</label>
                    <input type="date" name="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
                    <?php if($date_filter): ?>
                    <a href="?status=<?php echo $status_filter; ?>" class="clear-date"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message_type === 'success' ? '✅' : '❌'; ?> <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Danh sách đặt bàn - Modern Card Style -->
            <div class="rsv-list-section">
                <div class="rsv-list-header">
                    <div class="rsv-list-title">
                        <i class="fas fa-list"></i>
                        <span>Danh sách đặt bàn</span>
                        <span class="rsv-count"><?php echo count($reservations); ?></span>
                    </div>
                    <div class="rsv-bulk-info" id="selectedCount">
                        Đã chọn: <strong>0</strong>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <form method="POST" id="bulkForm">
                    <div class="rsv-bulk-bar">
                        <div class="rsv-bulk-left">
                            <label class="rsv-select-all">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)">
                                <span>Chọn tất cả</span>
                            </label>
                            <button type="button" onclick="deselectAll()" class="rsv-bulk-btn outline">
                                <i class="fas fa-square"></i> Bỏ chọn
                            </button>
                        </div>
                        <div class="rsv-bulk-right">
                            <button type="button" onclick="confirmBulkAction('confirm_all')" class="rsv-bulk-btn success">
                                <i class="fas fa-check-circle"></i> Xác nhận
                            </button>
                            <button type="button" onclick="confirmBulkAction('cancel_all')" class="rsv-bulk-btn danger">
                                <i class="fas fa-times-circle"></i> Hủy
                            </button>
                        </div>
                    </div>

                    <!-- Reservation Cards -->
                    <div class="rsv-cards-grid">
                        <?php foreach($reservations as $res): 
                            $status_colors = [
                                'pending' => '#f59e0b',
                                'confirmed' => '#22c55e',
                                'completed' => '#3b82f6',
                                'cancelled' => '#ef4444',
                                'no_show' => '#6b7280'
                            ];
                            $status_labels = [
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'completed' => 'Hoàn thành',
                                'cancelled' => 'Đã hủy',
                                'no_show' => 'Không đến'
                            ];
                            $location_icons = [
                                'indoor' => 'fa-home',
                                'outdoor' => 'fa-tree',
                                'vip' => 'fa-crown',
                                'private_room' => 'fa-door-closed',
                                'any' => 'fa-chair'
                            ];
                            $location_labels = [
                                'indoor' => 'Trong nhà',
                                'outdoor' => 'Sân vườn',
                                'vip' => 'Phòng VIP',
                                'private_room' => 'Phòng riêng',
                                'any' => 'Bất kỳ'
                            ];
                            $table_pref = $res['table_preference'] ?? 'any';
                        ?>
                        <div class="rsv-card" data-status="<?php echo $res['status']; ?>">
                            <!-- Checkbox -->
                            <div class="rsv-card-check">
                                <input type="checkbox" name="selected_ids[]" value="<?php echo $res['id']; ?>" class="row-checkbox" onchange="updateSelectedCount()">
                            </div>

                            <!-- Date Box -->
                            <div class="rsv-card-date" style="background: <?php echo $status_colors[$res['status']] ?? '#6b7280'; ?>">
                                <span class="rsv-day"><?php echo date('d', strtotime($res['reservation_date'])); ?></span>
                                <span class="rsv-month"><?php echo date('M', strtotime($res['reservation_date'])); ?></span>
                                <span class="rsv-time"><?php echo date('H:i', strtotime($res['reservation_time'])); ?></span>
                            </div>

                            <!-- Main Info -->
                            <div class="rsv-card-main">
                                <div class="rsv-card-top">
                                    <div class="rsv-customer">
                                        <span class="rsv-name"><?php echo htmlspecialchars($res['customer_name']); ?></span>
                                        <span class="rsv-id">#<?php echo $res['id']; ?></span>
                                    </div>
                                    <span class="rsv-status" style="background: <?php echo $status_colors[$res['status']] ?? '#6b7280'; ?>15; color: <?php echo $status_colors[$res['status']] ?? '#6b7280'; ?>">
                                        <?php echo $status_labels[$res['status']] ?? $res['status']; ?>
                                    </span>
                                </div>

                                <div class="rsv-card-details">
                                    <div class="rsv-detail">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($res['phone']); ?></span>
                                    </div>
                                    <div class="rsv-detail">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $res['number_of_guests']; ?> người</span>
                                    </div>
                                    <div class="rsv-detail">
                                        <i class="fas <?php echo $location_icons[$table_pref] ?? 'fa-chair'; ?>"></i>
                                        <span><?php echo $location_labels[$table_pref] ?? $table_pref; ?></span>
                                    </div>
                                    <?php if(!empty($res['email'])): ?>
                                    <div class="rsv-detail">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($res['email']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if(!empty($res['occasion'])): ?>
                                <div class="rsv-occasion">
                                    <i class="fas fa-gift"></i> <?php echo htmlspecialchars($res['occasion']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($res['status'] == 'cancelled' && !empty($res['cancel_reason'])): ?>
                                <div class="rsv-cancel-reason-box">
                                    <?php if(strpos($res['cancel_reason'], 'Khách hàng tự hủy:') === 0): ?>
                                    <span class="cancel-by-customer"><i class="fas fa-user"></i> Khách tự hủy</span>
                                    <p><?php echo htmlspecialchars(str_replace('Khách hàng tự hủy: ', '', $res['cancel_reason'])); ?></p>
                                    <?php else: ?>
                                    <span class="cancel-by-admin"><i class="fas fa-store"></i> Nhà hàng hủy</span>
                                    <p><?php echo htmlspecialchars($res['cancel_reason']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="rsv-card-actions">
                                <?php if($res['status'] == 'pending'): ?>
                                <a href="?action=confirm&id=<?php echo $res['id']; ?>" class="rsv-action-btn confirm" title="Xác nhận">
                                    <i class="fas fa-check"></i>
                                </a>
                                <button type="button" class="rsv-action-btn cancel" title="Hủy" onclick="openCancelModal(<?php echo $res['id']; ?>, '<?php echo htmlspecialchars($res['customer_name'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php elseif($res['status'] == 'confirmed'): ?>
                                <a href="?action=complete&id=<?php echo $res['id']; ?>" class="rsv-action-btn complete" title="Hoàn thành">
                                    <i class="fas fa-check-double"></i>
                                </a>
                                <a href="?action=no_show&id=<?php echo $res['id']; ?>" class="rsv-action-btn noshow" title="Không đến">
                                    <i class="fas fa-user-slash"></i>
                                </a>
                                <button type="button" class="rsv-action-btn cancel" title="Hủy" onclick="openCancelModal(<?php echo $res['id']; ?>, '<?php echo htmlspecialchars($res['customer_name'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                                <button type="button" class="rsv-action-btn view" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($res)); ?>)" title="Chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if(empty($reservations)): ?>
                    <div class="rsv-empty">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Không có đặt bàn nào</h3>
                        <p>Chưa có đặt bàn phù hợp với bộ lọc</p>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        </main>
    </div>

    <style>
    /* Reservation List Section */
    .rsv-list-section {
        background: white;
        border-radius: 20px;
        border: 2px solid #e5e7eb;
        overflow: hidden;
    }
    .rsv-list-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        border-bottom: 2px solid #e5e7eb;
    }
    .rsv-list-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
    }
    .rsv-list-title i { color: #22c55e; }
    .rsv-count {
        background: #22c55e;
        color: white;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .rsv-bulk-info {
        font-size: 0.85rem;
        color: #6b7280;
    }

    /* Bulk Actions Bar */
    .rsv-bulk-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: #fafafa;
        border-bottom: 1px solid #e5e7eb;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .rsv-bulk-left, .rsv-bulk-right {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .rsv-select-all {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: #374151;
        font-weight: 600;
        cursor: pointer;
    }
    .rsv-select-all input {
        width: 18px;
        height: 18px;
        accent-color: #22c55e;
    }
    .rsv-bulk-btn {
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s;
        border: none;
    }
    .rsv-bulk-btn.outline {
        background: white;
        border: 2px solid #e5e7eb;
        color: #6b7280;
    }
    .rsv-bulk-btn.outline:hover { border-color: #22c55e; color: #22c55e; }
    .rsv-bulk-btn.success {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
    }
    .rsv-bulk-btn.danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    .rsv-bulk-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

    /* Cards Grid */
    .rsv-cards-grid {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
    }

    /* Single Card */
    .rsv-card {
        display: flex;
        align-items: stretch;
        background: white;
        border-radius: 16px;
        border: 2px solid #e5e7eb;
        overflow: hidden;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .rsv-card:hover {
        border-color: #22c55e;
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.15);
        transform: translateY(-2px);
    }
    .rsv-card[data-status="cancelled"] {
        opacity: 0.7;
        border-color: #fecaca;
    }
    .rsv-card[data-status="cancelled"]:hover {
        opacity: 1;
        border-color: #ef4444;
    }

    /* Checkbox */
    .rsv-card-check {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem 0.75rem;
        background: #f9fafb;
    }
    .rsv-card-check input {
        width: 20px;
        height: 20px;
        accent-color: #22c55e;
        cursor: pointer;
    }

    /* Date Box */
    .rsv-card-date {
        width: 80px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1rem 0.5rem;
        color: white;
        flex-shrink: 0;
        position: relative;
    }
    .rsv-card-date::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
        border-left: 8px solid currentColor;
        opacity: 0.3;
    }
    .rsv-day {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .rsv-month {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 0.15rem;
    }
    .rsv-time {
        font-size: 0.7rem;
        font-weight: 700;
        margin-top: 0.35rem;
        background: rgba(255,255,255,0.25);
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        backdrop-filter: blur(4px);
    }

    /* Main Info */
    .rsv-card-main {
        flex: 1;
        padding: 1rem 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        min-width: 0;
    }
    .rsv-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
    .rsv-customer {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .rsv-name {
        font-weight: 700;
        color: #1f2937;
        font-size: 1rem;
    }
    .rsv-id {
        font-size: 0.75rem;
        color: #9ca3af;
        font-weight: 600;
        background: #f3f4f6;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
    }
    .rsv-status {
        padding: 0.4rem 0.85rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .rsv-status::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    /* Details */
    .rsv-card-details {
        display: flex;
        flex-wrap: wrap;
        gap: 1.25rem;
    }
    .rsv-detail {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: #4b5563;
        font-weight: 500;
    }
    .rsv-detail i {
        color: #22c55e;
        width: 16px;
        font-size: 0.8rem;
    }

    /* Occasion */
    .rsv-occasion {
        font-size: 0.8rem;
        color: #d97706;
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        padding: 0.35rem 0.75rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        width: fit-content;
        font-weight: 600;
    }

    /* Cancel Reason Box */
    .rsv-cancel-reason-box {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: 0.6rem 0.85rem;
        margin-top: 0.5rem;
    }
    .rsv-cancel-reason-box .cancel-by-customer {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: #7c3aed;
        background: #ede9fe;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        margin-bottom: 0.4rem;
    }
    .rsv-cancel-reason-box .cancel-by-admin {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: #dc2626;
        background: #fee2e2;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        margin-bottom: 0.4rem;
    }
    .rsv-cancel-reason-box p {
        margin: 0;
        font-size: 0.8rem;
        color: #7f1d1d;
        font-style: italic;
        line-height: 1.4;
    }

    /* Actions */
    .rsv-card-actions {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 1rem;
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        border-left: 2px solid #e5e7eb;
    }
    .rsv-action-btn {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        transition: all 0.25s;
        text-decoration: none;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .rsv-action-btn:hover { 
        transform: scale(1.15) translateY(-2px); 
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    .rsv-action-btn.confirm { 
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); 
        color: #16a34a; 
    }
    .rsv-action-btn.confirm:hover { 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); 
        color: white; 
    }
    .rsv-action-btn.cancel { 
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); 
        color: #dc2626; 
    }
    .rsv-action-btn.cancel:hover { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
        color: white; 
    }
    .rsv-action-btn.complete { 
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); 
        color: #2563eb; 
    }
    .rsv-action-btn.complete:hover { 
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
        color: white; 
    }
    .rsv-action-btn.noshow { 
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); 
        color: #d97706; 
    }
    .rsv-action-btn.noshow:hover { 
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
        color: white; 
    }
    .rsv-action-btn.view { 
        background: white; 
        color: #6b7280;
        border: 2px solid #e5e7eb;
    }
    .rsv-action-btn.view:hover { 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); 
        color: white;
        border-color: transparent;
    }

    /* Empty State */
    .rsv-empty {
        text-align: center;
        padding: 4rem 2rem;
        color: #9ca3af;
    }
    .rsv-empty i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #d1d5db;
    }
    .rsv-empty h3 {
        margin: 0 0 0.5rem;
        color: #6b7280;
        font-size: 1.2rem;
    }
    .rsv-empty p {
        margin: 0;
        font-size: 0.95rem;
    }

    /* Responsive */
    @media (max-width: 900px) {
        .rsv-cards-grid {
            padding: 0.75rem;
            gap: 0.6rem;
        }
        .rsv-card {
            flex-wrap: wrap;
            border-radius: 14px;
        }
        .rsv-card-check {
            padding: 0.75rem;
        }
        .rsv-card-date {
            width: 65px;
            padding: 0.75rem 0.4rem;
        }
        .rsv-card-date::after { display: none; }
        .rsv-day { font-size: 1.4rem; }
        .rsv-card-main {
            padding: 0.85rem 1rem;
        }
        .rsv-card-actions {
            width: 100%;
            flex-direction: row;
            justify-content: flex-end;
            border-left: none;
            border-top: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
        }
    }
    </style>
    
    <!-- Modal Hủy đặt bàn -->
    <div id="cancelModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeCancelModal()"></div>
        <div class="modal-content cancel-modal">
            <div class="modal-header cancel-header">
                <h3><i class="fas fa-times-circle"></i> Hủy đặt bàn</h3>
                <button class="modal-close" onclick="closeCancelModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" class="cancel-form">
                <input type="hidden" name="cancel_with_reason" value="1">
                <input type="hidden" name="reservation_id" id="cancelReservationId">
                
                <div class="cancel-info">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Bạn đang hủy đặt bàn của <strong id="cancelCustomerName"></strong></p>
                </div>
                
                <div class="cancel-reason-section">
                    <label><i class="fas fa-comment-alt"></i> Lý do hủy (sẽ hiển thị cho khách hàng)</label>
                    <textarea name="cancel_reason" id="cancelReason" rows="3" placeholder="Ví dụ: Nhà hàng đã kín chỗ, xin lỗi quý khách..." required></textarea>
                    
                    <div class="quick-reasons">
                        <span class="quick-reason" data-reason="Nhà hàng đã kín chỗ vào thời điểm này">📅 Kín chỗ</span>
                        <span class="quick-reason" data-reason="Nhà hàng tạm đóng cửa để bảo trì">🔧 Bảo trì</span>
                        <span class="quick-reason" data-reason="Thông tin đặt bàn không hợp lệ">❌ Không hợp lệ</span>
                        <span class="quick-reason" data-reason="Khách hàng yêu cầu hủy">👤 Khách yêu cầu</span>
                    </div>
                </div>
                
                <div class="cancel-actions">
                    <button type="button" class="btn-cancel-back" onclick="closeCancelModal()">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </button>
                    <button type="submit" class="btn-cancel-confirm">
                        <i class="fas fa-times-circle"></i> Xác nhận hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    /* Cancel Modal Styles */
    .cancel-modal {
        max-width: 500px;
    }
    .cancel-header {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    }
    .cancel-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        background: #fef2f2;
        border-bottom: 1px solid #fecaca;
    }
    .cancel-info i {
        font-size: 1.5rem;
        color: #ef4444;
    }
    .cancel-info p {
        margin: 0;
        color: #991b1b;
        font-size: 0.95rem;
    }
    .cancel-reason-section {
        padding: 1.5rem;
    }
    .cancel-reason-section label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
    }
    .cancel-reason-section label i {
        color: #ef4444;
        margin-right: 0.5rem;
    }
    .cancel-reason-section textarea {
        width: 100%;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.95rem;
        resize: none;
        font-family: inherit;
        transition: all 0.2s;
    }
    .cancel-reason-section textarea:focus {
        outline: none;
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    .quick-reasons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .quick-reason {
        padding: 0.4rem 0.75rem;
        background: #f3f4f6;
        border-radius: 20px;
        font-size: 0.8rem;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.2s;
    }
    .quick-reason:hover {
        background: #fee2e2;
        color: #b91c1c;
    }
    .cancel-actions {
        display: flex;
        gap: 1rem;
        padding: 1rem 1.5rem;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
    }
    .btn-cancel-back {
        flex: 1;
        padding: 0.85rem;
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        color: #6b7280;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    .btn-cancel-back:hover {
        border-color: #22c55e;
        color: #22c55e;
    }
    .btn-cancel-confirm {
        flex: 1;
        padding: 0.85rem;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border: none;
        border-radius: 12px;
        color: white;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    .btn-cancel-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }
    </style>
    
    <!-- Modal Chi tiết - Modern Design -->
    <div id="detailModal" class="rsv-modal" style="display: none;">
        <div class="rsv-modal-overlay" onclick="closeModal()"></div>
        <div class="rsv-modal-box">
            <button class="rsv-modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="rsv-modal-content" id="modalBody">
                <!-- Content will be inserted here -->
            </div>
        </div>
    </div>
    
    <style>
    /* Modern Modal Styles */
    .rsv-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .rsv-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(8px);
        animation: fadeIn 0.3s;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .rsv-modal-box {
        position: relative;
        background: white;
        border-radius: 24px;
        max-width: 480px;
        width: 100%;
        max-height: 85vh;
        overflow: hidden;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
        animation: modalSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes modalSlide {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    .rsv-modal-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 36px;
        height: 36px;
        background: rgba(255,255,255,0.9);
        border: none;
        border-radius: 50%;
        color: #6b7280;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .rsv-modal-close:hover {
        background: #ef4444;
        color: white;
        transform: rotate(90deg);
    }
    .rsv-modal-content {
        overflow-y: auto;
        max-height: 85vh;
    }

    /* Modal Inner Styles */
    .md-header {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        padding: 2rem 1.5rem 3rem;
        text-align: center;
        position: relative;
    }
    .md-header::after {
        content: '';
        position: absolute;
        bottom: -20px;
        left: 0;
        right: 0;
        height: 40px;
        background: white;
        border-radius: 24px 24px 0 0;
    }
    .md-avatar {
        width: 70px;
        height: 70px;
        background: rgba(255,255,255,0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.75rem;
        color: white;
        backdrop-filter: blur(10px);
        border: 3px solid rgba(255,255,255,0.3);
    }
    .md-name {
        color: white;
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0 0 0.25rem;
    }
    .md-phone {
        color: rgba(255,255,255,0.9);
        font-size: 0.9rem;
    }
    .md-id {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: rgba(255,255,255,0.2);
        padding: 0.3rem 0.7rem;
        border-radius: 8px;
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .md-body {
        padding: 0.5rem 1.5rem 1.5rem;
        position: relative;
        z-index: 1;
    }

    /* Info Cards */
    .md-info-row {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .md-info-card {
        flex: 1;
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        border-radius: 16px;
        padding: 1rem;
        text-align: center;
        border: 2px solid #e5e7eb;
        transition: all 0.2s;
    }
    .md-info-card:hover {
        border-color: #22c55e;
        transform: translateY(-2px);
    }
    .md-info-icon {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        color: #22c55e;
        font-size: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .md-info-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.15rem;
    }
    .md-info-label {
        font-size: 0.7rem;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    /* Detail Items */
    .md-detail-item {
        display: flex;
        align-items: center;
        padding: 0.85rem 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .md-detail-item:last-child {
        border-bottom: none;
    }
    .md-detail-icon {
        width: 36px;
        height: 36px;
        background: #f0fdf4;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #22c55e;
        font-size: 0.85rem;
        margin-right: 0.85rem;
        flex-shrink: 0;
    }
    .md-detail-text {
        flex: 1;
    }
    .md-detail-label {
        font-size: 0.75rem;
        color: #9ca3af;
        font-weight: 500;
    }
    .md-detail-value {
        font-size: 0.95rem;
        color: #1f2937;
        font-weight: 600;
    }

    /* Status Badge */
    .md-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.85rem;
        border-radius: 14px;
        margin-top: 0.5rem;
        font-weight: 700;
        font-size: 0.9rem;
    }
    .md-status.pending {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
    }
    .md-status.confirmed {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
    }
    .md-status.completed {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1d4ed8;
    }
    .md-status.cancelled {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #b91c1c;
    }

    /* Cancel Reason in Modal */
    .md-cancel-reason-section {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border: 2px solid #fecaca;
        border-radius: 12px;
        padding: 1rem;
        margin-top: 1rem;
    }
    .md-cancel-header {
        margin-bottom: 0.75rem;
    }
    .md-cancel-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 8px;
    }
    .md-cancel-badge.customer {
        background: #ede9fe;
        color: #7c3aed;
    }
    .md-cancel-badge.admin {
        background: #fee2e2;
        color: #dc2626;
    }
    .md-cancel-content {
        display: flex;
        gap: 0.5rem;
        align-items: flex-start;
    }
    .md-cancel-content i {
        color: #dc2626;
        font-size: 0.9rem;
        margin-top: 0.1rem;
    }
    .md-cancel-content p {
        margin: 0;
        font-size: 0.9rem;
        color: #7f1d1d;
        font-style: italic;
        line-height: 1.5;
    }

    /* Modal Base Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }
    .modal-content {
        position: relative;
        background: white;
        border-radius: 20px;
        max-width: 600px;
        width: 95%;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        animation: modalSlideIn 0.3s ease;
    }
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-header {
        padding: 1.5rem 2rem;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.6rem;
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
        padding: 0;
        overflow-y: auto;
        max-height: calc(80vh - 80px);
        background: #f9fafb !important;
    }
    
    /* New Detail Card Styles */
    .detail-header-card {
        background: white;
        padding: 1.5rem;
        margin: 1rem;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .detail-avatar {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }
    .detail-customer-info h4 {
        margin: 0 0 0.25rem;
        font-size: 1.15rem;
        color: #1f2937;
        font-weight: 700;
    }
    .detail-customer-info p {
        margin: 0;
        color: #6b7280;
        font-size: 0.85rem;
    }
    .detail-id-badge {
        margin-left: auto;
        background: #f3f4f6;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-size: 0.8rem;
        color: #6b7280;
        font-weight: 600;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        padding: 0 1rem 1rem;
    }
    .detail-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    .detail-card.full-width {
        grid-column: span 2;
    }
    .detail-card-label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.75rem;
        color: #9ca3af;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.4rem;
    }
    .detail-card-label i {
        color: #22c55e;
        font-size: 0.7rem;
    }
    .detail-card-value {
        font-size: 1rem;
        color: #1f2937;
        font-weight: 600;
    }
    .detail-card-value.large {
        font-size: 1.25rem;
        font-weight: 700;
    }
    
    .detail-status-card {
        background: white;
        margin: 0 1rem 1rem;
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    .detail-status-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .detail-status-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    .detail-status-icon.pending { background: #fef3c7; color: #d97706; }
    .detail-status-icon.confirmed { background: #dcfce7; color: #16a34a; }
    .detail-status-icon.completed { background: #dbeafe; color: #2563eb; }
    .detail-status-icon.cancelled { background: #fee2e2; color: #dc2626; }
    .detail-status-text {
        font-weight: 700;
        color: #1f2937;
    }
    .detail-status-time {
        font-size: 0.8rem;
        color: #9ca3af;
    }
    
    /* Old styles - keep for fallback */
    .detail-row {
        display: flex;
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
        align-items: flex-start;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 700;
        color: #374151 !important;
        min-width: 140px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }
    
    .detail-label i {
        color: #22c55e !important;
        width: 20px;
        font-size: 0.9rem;
    }
    
    .detail-value {
        color: #111827 !important;
        font-weight: 600 !important;
        flex: 1;
        font-size: 0.95rem;
    }
    </style>
    
    <script>
    // Cancel Modal Functions
    function openCancelModal(id, customerName) {
        document.getElementById('cancelReservationId').value = id;
        document.getElementById('cancelCustomerName').textContent = customerName;
        document.getElementById('cancelReason').value = '';
        document.getElementById('cancelModal').style.display = 'flex';
    }
    
    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
    }
    
    // Quick reasons
    document.querySelectorAll('.quick-reason').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('cancelReason').value = this.dataset.reason;
        });
    });
    
    function viewDetails(reservation) {
        const locations = {
            'indoor': 'Trong nhà',
            'outdoor': 'Sân vườn',
            'vip': 'Phòng VIP',
            'private_room': 'Phòng riêng',
            'any': 'Bất kỳ'
        };
        
        const statusConfig = {
            'pending': { label: '⏳ Chờ xác nhận', icon: 'fa-clock', class: 'pending' },
            'confirmed': { label: '✅ Đã xác nhận', icon: 'fa-check-circle', class: 'confirmed' },
            'completed': { label: '🎉 Hoàn thành', icon: 'fa-check-double', class: 'completed' },
            'cancelled': { label: '❌ Đã hủy', icon: 'fa-times-circle', class: 'cancelled' },
            'no_show': { label: '🚫 Không đến', icon: 'fa-user-slash', class: 'cancelled' }
        };
        
        const date = new Date(reservation.reservation_date);
        const day = date.getDate();
        const month = date.toLocaleDateString('vi-VN', { month: 'short' });
        const year = date.getFullYear();
        const time = reservation.reservation_time.substring(0, 5);
        const status = statusConfig[reservation.status] || statusConfig['pending'];
        
        const html = `
            <!-- Header with gradient -->
            <div class="md-header">
                <span class="md-id">#${reservation.id}</span>
                <div class="md-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3 class="md-name">${reservation.customer_name}</h3>
                <p class="md-phone"><i class="fas fa-phone"></i> ${reservation.phone}</p>
            </div>
            
            <div class="md-body">
                <!-- Main Info Cards -->
                <div class="md-info-row">
                    <div class="md-info-card">
                        <div class="md-info-icon"><i class="fas fa-calendar-day"></i></div>
                        <div class="md-info-value">${day} ${month}</div>
                        <div class="md-info-label">Ngày đặt</div>
                    </div>
                    <div class="md-info-card">
                        <div class="md-info-icon"><i class="fas fa-clock"></i></div>
                        <div class="md-info-value">${time}</div>
                        <div class="md-info-label">Giờ đến</div>
                    </div>
                    <div class="md-info-card">
                        <div class="md-info-icon"><i class="fas fa-users"></i></div>
                        <div class="md-info-value">${reservation.number_of_guests}</div>
                        <div class="md-info-label">Số khách</div>
                    </div>
                </div>
                
                <!-- Detail Items -->
                <div class="md-detail-item">
                    <div class="md-detail-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="md-detail-text">
                        <div class="md-detail-label">Vị trí bàn</div>
                        <div class="md-detail-value">${locations[reservation.table_preference] || 'Bất kỳ'}</div>
                    </div>
                </div>
                
                <div class="md-detail-item">
                    <div class="md-detail-icon"><i class="fas fa-envelope"></i></div>
                    <div class="md-detail-text">
                        <div class="md-detail-label">Email</div>
                        <div class="md-detail-value">${reservation.email || 'Không có'}</div>
                    </div>
                </div>
                
                ${reservation.occasion ? `
                <div class="md-detail-item">
                    <div class="md-detail-icon"><i class="fas fa-gift"></i></div>
                    <div class="md-detail-text">
                        <div class="md-detail-label">Dịp đặc biệt</div>
                        <div class="md-detail-value">${reservation.occasion}</div>
                    </div>
                </div>
                ` : ''}
                
                ${reservation.special_request ? `
                <div class="md-detail-item">
                    <div class="md-detail-icon"><i class="fas fa-comment-dots"></i></div>
                    <div class="md-detail-text">
                        <div class="md-detail-label">Yêu cầu đặc biệt</div>
                        <div class="md-detail-value">${reservation.special_request}</div>
                    </div>
                </div>
                ` : ''}
                
                ${(reservation.status === 'cancelled' && reservation.cancel_reason) ? `
                <div class="md-cancel-reason-section">
                    <div class="md-cancel-header">
                        ${reservation.cancel_reason.startsWith('Khách hàng tự hủy:') ? 
                            '<span class="md-cancel-badge customer"><i class="fas fa-user"></i> Khách tự hủy</span>' : 
                            '<span class="md-cancel-badge admin"><i class="fas fa-store"></i> Nhà hàng hủy</span>'
                        }
                    </div>
                    <div class="md-cancel-content">
                        <i class="fas fa-quote-left"></i>
                        <p>${reservation.cancel_reason.replace('Khách hàng tự hủy: ', '')}</p>
                    </div>
                </div>
                ` : ''}
                
                <!-- Status -->
                <div class="md-status ${status.class}">
                    <i class="fas ${status.icon}"></i>
                    ${status.label}
                </div>
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
            closeCancelModal();
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
