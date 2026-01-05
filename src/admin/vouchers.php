<?php
/**
 * Admin - Quản lý Voucher/Coupon
 */
session_start();
require_once __DIR__ . '/../config/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

$message = '';
$error = '';

// Xử lý thêm voucher mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $code = strtoupper(trim($_POST['code']));
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $discount_type = $_POST['discount_type'];
        $discount_value = floatval($_POST['discount_value']);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $min_order_value = floatval($_POST['min_order_value'] ?? 0);
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        $usage_per_user = intval($_POST['usage_per_user'] ?? 1);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $conn->prepare("INSERT INTO vouchers (code, name, description, discount_type, discount_value, max_discount, min_order_value, usage_limit, usage_per_user, start_date, end_date, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $max_discount, $min_order_value, $usage_limit, $usage_per_user, $start_date, $end_date, $is_active, $_SESSION['admin_id']]);
            $message = "Tạo voucher <strong>$code</strong> thành công!";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "Mã voucher <strong>$code</strong> đã tồn tại!";
            } else {
                $error = "Lỗi: " . $e->getMessage();
            }
        }
    }
    
    if ($_POST['action'] === 'toggle') {
        $id = intval($_POST['id']);
        $conn->exec("UPDATE vouchers SET is_active = NOT is_active WHERE id = $id");
        header('Location: vouchers.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        $conn->exec("DELETE FROM vouchers WHERE id = $id");
        $message = "Đã xóa voucher!";
    }
    
    // Xử lý cập nhật voucher
    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $code = strtoupper(trim($_POST['code']));
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $discount_type = $_POST['discount_type'];
        $discount_value = floatval($_POST['discount_value']);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $min_order_value = floatval($_POST['min_order_value'] ?? 0);
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        $usage_per_user = intval($_POST['usage_per_user'] ?? 1);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $conn->prepare("UPDATE vouchers SET code=?, name=?, description=?, discount_type=?, discount_value=?, max_discount=?, min_order_value=?, usage_limit=?, usage_per_user=?, start_date=?, end_date=?, is_active=? WHERE id=?");
            $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $max_discount, $min_order_value, $usage_limit, $usage_per_user, $start_date, $end_date, $is_active, $id]);
            $message = "Cập nhật voucher <strong>$code</strong> thành công!";
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// Lấy danh sách voucher
$vouchers = $conn->query("SELECT * FROM vouchers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 AND end_date >= NOW() THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_active = 0 OR end_date < NOW() THEN 1 ELSE 0 END) as inactive,
    SUM(used_count) as total_used
FROM vouchers")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Voucher - Admin</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    body { background: #f8fafc !important; font-family: 'Inter', sans-serif; }
    .main-content { background: #f8fafc !important; padding: 1.75rem 2rem; }
    
    /* Page Header - Modern */
    .page-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1.75rem; padding: 1.25rem 1.75rem;
        background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        border-radius: 16px; border: 2px solid #bbf7d0;
        box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1);
    }
    .page-header h1 {
        color: #166534 !important; font-size: 1.6rem; font-weight: 800;
        display: flex; align-items: center; gap: 0.85rem; margin: 0;
    }
    .page-header h1 .icon-box {
        width: 48px; height: 48px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.35);
    }
    .page-header h1 .icon-box i { color: white; font-size: 1.25rem; }
    
    /* Buttons */
    .btn { 
        padding: 0.8rem 1.5rem; border: none; border-radius: 12px; cursor: pointer; 
        font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; 
        transition: all 0.25s; text-decoration: none; font-size: 0.95rem; 
    }
    .btn-primary { 
        background: linear-gradient(135deg, #22c55e, #16a34a); color: white; 
        box-shadow: 0 4px 15px rgba(34,197,94,0.35);
    }
    .btn-primary:hover { 
        background: linear-gradient(135deg, #16a34a, #15803d); 
        transform: translateY(-3px); box-shadow: 0 8px 25px rgba(34,197,94,0.4); 
    }
    .btn-secondary { background: white; color: #374151; border: 2px solid #e5e7eb; }
    .btn-secondary:hover { border-color: #22c55e; color: #22c55e; background: #f0fdf4; }
    .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
    .btn-danger:hover { background: linear-gradient(135deg, #dc2626, #b91c1c); transform: translateY(-2px); }
    .btn-sm { padding: 0.55rem 0.9rem; font-size: 0.85rem; border-radius: 10px; }

    /* Stats Cards - Modern with Links */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 1.75rem; }
    
    .stat-card {
        text-decoration: none;
        border-radius: 16px; padding: 1.5rem 1.75rem; 
        display: flex; align-items: center; gap: 1.25rem; 
        transition: all 0.3s ease; cursor: pointer;
        position: relative; overflow: hidden;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card .stat-icon { 
        width: 64px; height: 64px; border-radius: 14px; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 1.5rem; flex-shrink: 0;
    }
    .stat-card .stat-content h3 { 
        font-size: 2.25rem; font-weight: 900; margin: 0; line-height: 1; 
    }
    .stat-card .stat-content p { 
        font-size: 1rem; margin: 0.4rem 0 0; font-weight: 600; 
    }
    .stat-card .active-badge {
        position: absolute; top: 12px; right: 14px;
        padding: 4px 12px; border-radius: 12px;
        font-size: 0.75rem; font-weight: 800;
    }
    
    /* Card Total - Purple/Green */
    .stat-card.stat-total {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 3px solid #93c5fd;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.15);
    }
    .stat-card.stat-total.active {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        border-color: #1d4ed8;
        box-shadow: 0 8px 30px rgba(59, 130, 246, 0.4);
    }
    .stat-card.stat-total .stat-icon { 
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    .stat-card.stat-total.active .stat-icon { background: white; color: #3b82f6; }
    .stat-card.stat-total .stat-content h3 { color: #1e40af; }
    .stat-card.stat-total .stat-content p { color: #3b82f6; }
    .stat-card.stat-total.active .stat-content h3,
    .stat-card.stat-total.active .stat-content p { color: white; }
    .stat-card.stat-total.active .active-badge { background: white; color: #3b82f6; }
    .stat-card.stat-total:hover { box-shadow: 0 12px 35px rgba(59, 130, 246, 0.3); }
    
    /* Card Active - Green */
    .stat-card.stat-success {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border: 3px solid #86efac;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.15);
    }
    .stat-card.stat-success.active {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border-color: #16a34a;
        box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4);
    }
    .stat-card.stat-success .stat-icon { 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
    }
    .stat-card.stat-success.active .stat-icon { background: white; color: #22c55e; }
    .stat-card.stat-success .stat-content h3 { color: #166534; }
    .stat-card.stat-success .stat-content p { color: #22c55e; }
    .stat-card.stat-success.active .stat-content h3,
    .stat-card.stat-success.active .stat-content p { color: white; }
    .stat-card.stat-success.active .active-badge { background: white; color: #22c55e; }
    .stat-card.stat-success:hover { box-shadow: 0 12px 35px rgba(34, 197, 94, 0.3); }
    
    /* Card Inactive - Red */
    .stat-card.stat-danger {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border: 3px solid #fca5a5;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.15);
    }
    .stat-card.stat-danger.active {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border-color: #dc2626;
        box-shadow: 0 8px 30px rgba(239, 68, 68, 0.4);
    }
    .stat-card.stat-danger .stat-icon { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    .stat-card.stat-danger.active .stat-icon { background: white; color: #ef4444; }
    .stat-card.stat-danger .stat-content h3 { color: #991b1b; }
    .stat-card.stat-danger .stat-content p { color: #ef4444; }
    .stat-card.stat-danger.active .stat-content h3,
    .stat-card.stat-danger.active .stat-content p { color: white; }
    .stat-card.stat-danger.active .active-badge { background: white; color: #ef4444; }
    .stat-card.stat-danger:hover { box-shadow: 0 12px 35px rgba(239, 68, 68, 0.3); }
    
    /* Card Used - Orange */
    .stat-card.stat-warning {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        border: 3px solid #fed7aa;
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.15);
    }
    .stat-card.stat-warning .stat-icon { 
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;
        box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
    }
    .stat-card.stat-warning .stat-content h3 { color: #92400e; }
    .stat-card.stat-warning .stat-content p { color: #d97706; }
    .stat-card.stat-warning:hover { box-shadow: 0 12px 35px rgba(249, 115, 22, 0.3); }
    
    @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .stats-grid { grid-template-columns: 1fr; } }

    /* Alert */
    .alert { 
        padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; 
        display: flex; align-items: center; gap: 0.75rem; font-weight: 600;
    }
    .alert-success { 
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); 
        color: #166534; border: 2px solid #86efac; 
    }
    .alert-error { 
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); 
        color: #991b1b; border: 2px solid #fca5a5; 
    }
    
    /* Card Container */
    .card {
        background: white; border-radius: 18px; 
        border: 2px solid #e5e7eb; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .card-header {
        padding: 1.25rem 1.75rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 2px solid #e2e8f0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .card-header h3 {
        margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;
        display: flex; align-items: center; gap: 0.6rem;
    }
    .card-header h3 i { color: #22c55e; }
    .card-header .badge {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white; padding: 0.4rem 1rem; border-radius: 20px;
        font-size: 0.85rem; font-weight: 700;
        box-shadow: 0 3px 10px rgba(34, 197, 94, 0.3);
    }
    
    /* Table Styles */
    .table-container { overflow-x: auto; }
    .modern-table { width: 100%; border-collapse: collapse; }
    .modern-table thead { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
    .modern-table th {
        padding: 1rem 1.25rem; text-align: left;
        font-weight: 700; color: #475569; font-size: 0.85rem;
        text-transform: uppercase; letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }
    .modern-table td {
        padding: 1.15rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        vertical-align: middle; color: #1f2937;
    }
    .modern-table tbody tr { transition: all 0.2s; background: white; }
    .modern-table tbody tr:hover { background: #f0fdf4; }
    
    /* Voucher Code */
    .voucher-code {
        font-family: 'Courier New', monospace;
        font-size: 1rem; font-weight: 800;
        color: #166534;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        padding: 0.5rem 1rem; border-radius: 10px;
        border: 2px solid #86efac;
        display: inline-block;
    }
    .voucher-name { font-weight: 700; color: #1f2937; margin-bottom: 4px; }
    .voucher-desc { font-size: 0.85rem; color: #6b7280; }
    
    /* Badges */
    .badge-discount {
        padding: 0.4rem 0.9rem; border-radius: 20px;
        font-weight: 800; font-size: 0.9rem;
    }
    .badge-discount.percent {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #166534; border: 2px solid #86efac;
    }
    .badge-discount.fixed {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1d4ed8; border: 2px solid #93c5fd;
    }
    .badge-status {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.4rem 0.9rem; border-radius: 20px;
        font-weight: 700; font-size: 0.8rem;
    }
    .badge-status.active-status {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #166534; border: 2px solid #86efac;
    }
    .badge-status.inactive-status {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e; border: 2px solid #fcd34d;
    }
    .badge-status.expired-status {
        background: #f3f4f6; color: #6b7280; border: 2px solid #e5e7eb;
    }
    
    .usage-info { font-size: 0.9rem; color: #4b5563; }
    .usage-info strong { color: #1f2937; font-weight: 700; }
    
    /* Action Buttons */
    .action-btns { display: flex; gap: 0.5rem; justify-content: center; }
    .action-btn {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        border: none; cursor: pointer; transition: all 0.2s;
        font-size: 0.9rem; text-decoration: none;
    }
    .action-btn:hover { transform: translateY(-2px); }
    .action-btn.edit { background: #dbeafe; color: #1d4ed8; }
    .action-btn.edit:hover { background: #3b82f6; color: white; }
    .action-btn.toggle { background: #fef3c7; color: #d97706; }
    .action-btn.toggle:hover { background: #f59e0b; color: white; }
    .action-btn.delete { background: #fee2e2; color: #dc2626; }
    .action-btn.delete:hover { background: #ef4444; color: white; }

    /* Empty State */
    .empty-state {
        text-align: center; padding: 4rem 2rem;
    }
    .empty-state .icon {
        width: 90px; height: 90px; margin: 0 auto 1.5rem;
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
    }
    .empty-state .icon i { font-size: 2.5rem; color: #9ca3af; }
    .empty-state h4 { color: #374151; font-size: 1.15rem; margin: 0 0 0.5rem; }
    .empty-state p { color: #6b7280; margin: 0 0 1.5rem; }
    
    /* Modal */
    .modal-overlay { 
        display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
        background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); 
        z-index: 1000; align-items: center; justify-content: center; padding: 20px; 
    }
    .modal-overlay.show { display: flex; }
    .modal-content { 
        background: white; border-radius: 24px; width: 100%; max-width: 720px; 
        border: none; box-shadow: 0 25px 80px rgba(0,0,0,0.25);
        overflow: hidden; max-height: 90vh; overflow-y: auto;
    }
    .modal-header { 
        padding: 1rem 1.5rem; 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; z-index: 10;
    }
    .modal-header h3 { 
        color: white; font-size: 1.1rem; margin: 0; font-weight: 800;
        display: flex; align-items: center; gap: 0.6rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .modal-header h3 i { font-size: 1rem; }
    .modal-close { 
        background: rgba(255,255,255,0.2); border: none; color: white; 
        font-size: 1.25rem; cursor: pointer; width: 32px; height: 32px; 
        border-radius: 8px; display: flex; align-items: center; justify-content: center; 
        transition: all 0.3s; font-weight: 300;
    }
    .modal-close:hover { background: rgba(255,255,255,0.35); transform: rotate(90deg); }
    .modal-body { padding: 1.25rem !important; background: #f8fafc !important; }
    
    /* Form Section Dividers */
    .form-section {
        background: #ffffff !important;
        border-radius: 12px !important;
        padding: 1rem 1.25rem !important;
        margin-bottom: 0.75rem !important;
        border: 2px solid #e5e7eb !important;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
    }
    .form-section:hover {
        border-color: #86efac !important;
        box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1) !important;
    }
    .form-section-title {
        font-size: 0.7rem !important;
        font-weight: 800 !important;
        color: #22c55e !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        margin-bottom: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        padding-bottom: 0.5rem !important;
        border-bottom: 2px dashed #e5e7eb !important;
    }
    .form-section-title i {
        font-size: 0.85rem !important;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%) !important;
        padding: 0.35rem !important;
        border-radius: 6px !important;
        color: #16a34a !important;
    }
    
    /* Form Styles */
    .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 0; }
    .form-row:last-child { margin-bottom: 0; }
    .form-group { margin-bottom: 0.6rem; }
    .form-group:last-child { margin-bottom: 0; }
    .form-group label { 
        display: flex; align-items: center; gap: 0.4rem;
        font-weight: 700; color: #374151; margin-bottom: 0.35rem; font-size: 0.8rem; 
    }
    .form-group label i { color: #22c55e; font-size: 0.75rem; }
    .form-group label .required { color: #ef4444; margin-left: 2px; }
    .form-group input, .form-group select, .form-group textarea { 
        width: 100%; padding: 0.6rem 0.9rem; 
        border: 2px solid #e5e7eb; border-radius: 10px; 
        font-size: 0.85rem; background: #ffffff; color: #1f2937;
        transition: all 0.25s; font-weight: 500; box-sizing: border-box;
    }
    .form-group input:hover, .form-group select:hover, .form-group textarea:hover {
        border-color: #d1d5db;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
        outline: none; border-color: #22c55e; background: #f0fdf4;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12); 
    }
    .form-group textarea { resize: vertical; min-height: 50px; }
    .form-group select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1rem;
        padding-right: 2.5rem;
    }
    .form-group .input-hint {
        font-size: 0.7rem;
        color: #6b7280;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    .form-group .input-hint i { font-size: 0.65rem; }
    
    /* Input with icon */
    .input-with-icon {
        position: relative;
    }
    .input-with-icon input {
        padding-left: 2.75rem;
    }
    .input-with-icon .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 0.9rem;
        pointer-events: none;
    }
    .input-with-icon input:focus + .input-icon,
    .input-with-icon input:focus ~ .input-icon {
        color: #22c55e;
    }
    
    .checkbox-group { 
        display: flex; align-items: center; gap: 0.6rem; 
        padding: 0.75rem 1rem; 
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-radius: 10px; border: 2px solid #bbf7d0;
        cursor: pointer;
        transition: all 0.25s;
        margin-bottom: 0.75rem;
    }
    .checkbox-group:hover {
        border-color: #86efac;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.15);
    }
    .checkbox-group input[type="checkbox"] { 
        width: 18px; height: 18px; accent-color: #22c55e; cursor: pointer;
        border-radius: 4px;
    }
    .checkbox-group label { 
        font-size: 0.85rem; color: #166534; cursor: pointer; margin: 0 !important; font-weight: 700;
        display: flex; align-items: center; gap: 0.4rem;
    }
    
    .btn-submit { 
        width: 100%; padding: 0.85rem; 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); 
        color: white; border: none; border-radius: 10px; 
        font-size: 0.95rem; font-weight: 800; cursor: pointer; margin-top: 0;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
        transition: all 0.3s;
        display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    }
    .btn-submit:hover { 
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
    }
    .btn-submit:active {
        transform: translateY(-1px);
    }
    .btn-submit i { font-size: 0.9rem; }
    
    /* Responsive form */
    @media (max-width: 600px) {
        .form-row { grid-template-columns: 1fr; }
        .modal-body { padding: 1.25rem; }
        .form-section { padding: 1.25rem; }
    }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <span class="icon-box"><i class="fas fa-ticket-alt"></i></span>
                    Quản lý Voucher
                </h1>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Tạo Voucher mới
                </button>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards - Click to filter without reload -->
            <div class="stats-grid">
                <div class="stat-card stat-total active" onclick="filterVouchers('all')" data-filter="all">
                    <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                        <p>Tổng Voucher</p>
                    </div>
                    <span class="active-badge">ĐANG XEM</span>
                </div>
                <div class="stat-card stat-success" onclick="filterVouchers('active')" data-filter="active">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['active'] ?? 0; ?></h3>
                        <p>Đang hoạt động</p>
                    </div>
                    <span class="active-badge" style="display:none;">ĐANG XEM</span>
                </div>
                <div class="stat-card stat-danger" onclick="filterVouchers('inactive')" data-filter="inactive">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['inactive'] ?? 0; ?></h3>
                        <p>Không hoạt động</p>
                    </div>
                    <span class="active-badge" style="display:none;">ĐANG XEM</span>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_used'] ?? 0; ?></h3>
                        <p>Lượt sử dụng</p>
                    </div>
                </div>
            </div>

            <!-- Voucher Table -->
            <?php if (empty($vouchers)): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                    <h4>Chưa có voucher nào</h4>
                    <p>Tạo voucher đầu tiên để thu hút khách hàng!</p>
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-plus"></i> Tạo Voucher
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Danh sách Voucher</h3>
                    <span class="badge" id="voucherCount"><?php echo count($vouchers); ?> voucher</span>
                </div>
                <div class="table-container">
                    <table class="modern-table" id="voucherTable">
                        <thead>
                            <tr>
                                <th>Mã voucher</th>
                                <th>Tên & Mô tả</th>
                                <th>Giảm giá</th>
                                <th>Điều kiện</th>
                                <th>Sử dụng</th>
                                <th>Trạng thái</th>
                                <th style="text-align:center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vouchers as $v): 
                                $now = new DateTime();
                                $end = new DateTime($v['end_date']);
                                $isExpired = $end < $now;
                                $status = $isExpired ? 'expired' : ($v['is_active'] ? 'active' : 'inactive');
                                $statusText = $isExpired ? 'Hết hạn' : ($v['is_active'] ? 'Hoạt động' : 'Tạm dừng');
                                $statusClass = $isExpired ? 'expired-status' : ($v['is_active'] ? 'active-status' : 'inactive-status');
                            ?>
                            <tr data-status="<?php echo $status; ?>">
                                <td><span class="voucher-code"><?php echo htmlspecialchars($v['code']); ?></span></td>
                                <td>
                                    <div class="voucher-name"><?php echo htmlspecialchars($v['name']); ?></div>
                                    <div class="voucher-desc"><?php echo htmlspecialchars($v['description']); ?></div>
                                </td>
                                <td>
                                    <?php if ($v['discount_type'] === 'percent'): ?>
                                    <span class="badge-discount percent"><?php echo $v['discount_value']; ?>%</span>
                                    <?php if ($v['max_discount']): ?>
                                    <div style="font-size:0.8rem;color:#6b7280;margin-top:4px;">Tối đa <?php echo number_format($v['max_discount']); ?>đ</div>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="badge-discount fixed"><?php echo number_format($v['discount_value']); ?>đ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="usage-info">
                                        Đơn tối thiểu: <strong><?php echo number_format($v['min_order_value']); ?>đ</strong>
                                    </div>
                                    <div class="usage-info" style="margin-top:4px;font-size:0.85rem;color:#6b7280;">
                                        <i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($v['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($v['end_date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="usage-info">
                                        <strong><?php echo $v['used_count']; ?></strong> / <?php echo $v['usage_limit'] ?? '∞'; ?>
                                    </div>
                                    <div class="usage-info" style="margin-top:4px;font-size:0.85rem;color:#6b7280;">
                                        <?php echo $v['usage_per_user']; ?> lần/user
                                    </div>
                                </td>
                                <td><span class="badge-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <button type="button" class="action-btn edit" title="Chỉnh sửa" onclick='openEditModal(<?php echo json_encode($v); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                            <button type="submit" class="action-btn toggle" title="Bật/Tắt">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa voucher này?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                            <button type="submit" class="action-btn delete" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Thêm Voucher -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Tạo Voucher Mới</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <!-- Section: Thông tin cơ bản -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-info-circle"></i> Thông tin cơ bản
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-barcode"></i> Mã voucher <span class="required">*</span></label>
                                <input type="text" name="code" required placeholder="VD: SALE20" style="text-transform:uppercase;">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Tên voucher <span class="required">*</span></label>
                                <input type="text" name="name" required placeholder="VD: Giảm 20% cuối tuần">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Mô tả</label>
                            <textarea name="description" rows="2" placeholder="Mô tả ngắn về voucher..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Section: Giá trị giảm giá -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-percent"></i> Giá trị giảm giá
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-sliders-h"></i> Loại giảm <span class="required">*</span></label>
                                <select name="discount_type" id="discountType" onchange="toggleMaxDiscount()">
                                    <option value="percent">Phần trăm (%)</option>
                                    <option value="fixed">Số tiền cố định (đ)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-coins"></i> Giá trị <span class="required">*</span></label>
                                <input type="number" name="discount_value" required min="1" placeholder="VD: 20">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" id="maxDiscountGroup">
                                <label><i class="fas fa-hand-holding-usd"></i> Giảm tối đa (đ)</label>
                                <input type="number" name="max_discount" min="0" placeholder="VD: 100000">
                                <div class="input-hint"><i class="fas fa-info-circle"></i> Áp dụng cho loại phần trăm</div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-shopping-cart"></i> Đơn tối thiểu (đ)</label>
                                <input type="number" name="min_order_value" min="0" value="0" placeholder="VD: 100000">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section: Giới hạn sử dụng -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-users"></i> Giới hạn sử dụng
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-layer-group"></i> Tổng lượt dùng</label>
                                <input type="number" name="usage_limit" min="1" placeholder="Không giới hạn">
                                <div class="input-hint"><i class="fas fa-infinity"></i> Để trống = không giới hạn</div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Lượt/người dùng</label>
                                <input type="number" name="usage_per_user" min="1" value="1">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section: Thời gian hiệu lực -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-clock"></i> Thời gian hiệu lực
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Ngày bắt đầu <span class="required">*</span></label>
                                <input type="datetime-local" name="start_date" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar-check"></i> Ngày kết thúc <span class="required">*</span></label>
                                <input type="datetime-local" name="end_date" required value="<?php echo date('Y-m-d\TH:i', strtotime('+1 month')); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="isActive" checked>
                        <label for="isActive"><i class="fas fa-power-off"></i> Kích hoạt ngay</label>
                    </div>
                    
                    <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Tạo Voucher</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Chỉnh sửa Voucher -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Chỉnh sửa Voucher</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <!-- Section: Thông tin cơ bản -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-info-circle"></i> Thông tin cơ bản
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-barcode"></i> Mã voucher <span class="required">*</span></label>
                                <input type="text" name="code" id="edit_code" required style="text-transform:uppercase;" placeholder="VD: SALE20">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Tên voucher <span class="required">*</span></label>
                                <input type="text" name="name" id="edit_name" required placeholder="VD: Giảm 20% cuối tuần">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Mô tả</label>
                            <textarea name="description" id="edit_description" rows="2" placeholder="Mô tả ngắn về voucher..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Section: Giá trị giảm giá -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-percent"></i> Giá trị giảm giá
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-sliders-h"></i> Loại giảm <span class="required">*</span></label>
                                <select name="discount_type" id="edit_discount_type" onchange="toggleEditMaxDiscount()">
                                    <option value="percent">Phần trăm (%)</option>
                                    <option value="fixed">Số tiền cố định (đ)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-coins"></i> Giá trị <span class="required">*</span></label>
                                <input type="number" name="discount_value" id="edit_discount_value" required min="1" placeholder="VD: 20">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" id="editMaxDiscountGroup">
                                <label><i class="fas fa-hand-holding-usd"></i> Giảm tối đa (đ)</label>
                                <input type="number" name="max_discount" id="edit_max_discount" min="0" placeholder="VD: 100000">
                                <div class="input-hint"><i class="fas fa-info-circle"></i> Áp dụng cho loại phần trăm</div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-shopping-cart"></i> Đơn tối thiểu (đ)</label>
                                <input type="number" name="min_order_value" id="edit_min_order_value" min="0" placeholder="VD: 100000">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section: Giới hạn sử dụng -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-users"></i> Giới hạn sử dụng
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-layer-group"></i> Tổng lượt dùng</label>
                                <input type="number" name="usage_limit" id="edit_usage_limit" min="1" placeholder="Không giới hạn">
                                <div class="input-hint"><i class="fas fa-infinity"></i> Để trống = không giới hạn</div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Lượt/người dùng</label>
                                <input type="number" name="usage_per_user" id="edit_usage_per_user" min="1" value="1">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section: Thời gian hiệu lực -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-clock"></i> Thời gian hiệu lực
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Ngày bắt đầu <span class="required">*</span></label>
                                <input type="datetime-local" name="start_date" id="edit_start_date" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar-check"></i> Ngày kết thúc <span class="required">*</span></label>
                                <input type="datetime-local" name="end_date" id="edit_end_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="edit_is_active">
                        <label for="edit_is_active"><i class="fas fa-power-off"></i> Kích hoạt voucher</label>
                    </div>
                    
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function openModal() {
        document.getElementById('addModal').classList.add('show');
    }
    
    function closeModal() {
        document.getElementById('addModal').classList.remove('show');
    }
    
    function openEditModal(voucher) {
        document.getElementById('edit_id').value = voucher.id;
        document.getElementById('edit_code').value = voucher.code;
        document.getElementById('edit_name').value = voucher.name;
        document.getElementById('edit_description').value = voucher.description || '';
        document.getElementById('edit_discount_type').value = voucher.discount_type;
        document.getElementById('edit_discount_value').value = voucher.discount_value;
        document.getElementById('edit_max_discount').value = voucher.max_discount || '';
        document.getElementById('edit_min_order_value').value = voucher.min_order_value;
        document.getElementById('edit_usage_limit').value = voucher.usage_limit || '';
        document.getElementById('edit_usage_per_user').value = voucher.usage_per_user;
        document.getElementById('edit_start_date').value = voucher.start_date.replace(' ', 'T').slice(0, 16);
        document.getElementById('edit_end_date').value = voucher.end_date.replace(' ', 'T').slice(0, 16);
        document.getElementById('edit_is_active').checked = voucher.is_active == 1;
        
        toggleEditMaxDiscount();
        document.getElementById('editModal').classList.add('show');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('show');
    }
    
    function toggleMaxDiscount() {
        const type = document.getElementById('discountType').value;
        document.getElementById('maxDiscountGroup').style.display = type === 'percent' ? 'block' : 'none';
    }
    
    function toggleEditMaxDiscount() {
        const type = document.getElementById('edit_discount_type').value;
        document.getElementById('editMaxDiscountGroup').style.display = type === 'percent' ? 'block' : 'none';
    }
    
    // Filter vouchers without page reload
    function filterVouchers(filter) {
        console.log('Filter clicked:', filter);
        const table = document.getElementById('voucherTable');
        console.log('Table found:', table);
        if (!table) {
            console.log('Table not found!');
            return;
        }
        
        const rows = table.querySelectorAll('tbody tr');
        console.log('Rows found:', rows.length);
        let visibleCount = 0;
        
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            console.log('Row status:', status);
            let show = false;
            
            if (filter === 'all') {
                show = true;
            } else if (filter === 'active') {
                show = status === 'active';
            } else if (filter === 'inactive') {
                show = status === 'inactive' || status === 'expired';
            }
            
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        console.log('Visible count:', visibleCount);
        
        // Update count badge
        const countBadge = document.getElementById('voucherCount');
        if (countBadge) {
            countBadge.textContent = visibleCount + ' voucher';
        }
        
        // Update active card state
        document.querySelectorAll('.stat-card[data-filter]').forEach(card => {
            const cardFilter = card.getAttribute('data-filter');
            const badge = card.querySelector('.active-badge');
            
            if (cardFilter === filter) {
                card.classList.add('active');
                if (badge) badge.style.display = '';
            } else {
                card.classList.remove('active');
                if (badge) badge.style.display = 'none';
            }
        });
    }
    
    // Close modal when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });
    
    // Initialize
    toggleMaxDiscount();
    </script>
</body>
</html>
