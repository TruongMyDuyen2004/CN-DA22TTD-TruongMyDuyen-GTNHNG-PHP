<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý xóa combo
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->prepare("DELETE FROM promotion_items WHERE promotion_id = ?")->execute([$id]);
    $conn->prepare("DELETE FROM restaurant_promotions WHERE id = ?")->execute([$id]);
    header('Location: combo-promotions.php?msg=deleted');
    exit;
}

// Xử lý toggle trạng thái
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->prepare("UPDATE restaurant_promotions SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    header('Location: combo-promotions.php');
    exit;
}

// Xử lý thêm/sửa combo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    $discount_percent = intval($_POST['discount_percent'] ?? 20);
    $start_date = $_POST['start_date'] ?: date('Y-m-d');
    $end_date = $_POST['end_date'] ?: date('Y-m-d', strtotime('+30 days'));
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $menu_items = $_POST['menu_items'] ?? [];
    
    // Upload hình
    $image = $_POST['current_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/promotions/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'combo_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image = $filename;
        }
    }

    // Tính giá combo
    $total_price = 0;
    if (!empty($menu_items)) {
        $placeholders = implode(',', array_fill(0, count($menu_items), '?'));
        $stmt = $conn->prepare("SELECT SUM(price) as total FROM menu_items WHERE id IN ($placeholders)");
        $stmt->execute($menu_items);
        $total_price = $stmt->fetch()['total'] ?? 0;
    }
    $combo_price = round($total_price * (100 - $discount_percent) / 100);
    $discount_text = "Tiết kiệm {$discount_percent}%";
    
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE restaurant_promotions SET 
            title = ?, description = ?, image = ?, discount_text = ?, discount_percent = ?,
            start_date = ?, end_date = ?, is_featured = ?, combo_price = ? WHERE id = ?");
        $stmt->execute([$title, $description, $image, $discount_text, $discount_percent, $start_date, $end_date, $is_featured, $combo_price, $id]);
        $conn->prepare("DELETE FROM promotion_items WHERE promotion_id = ?")->execute([$id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO restaurant_promotions 
            (title, description, image, promo_type, discount_text, discount_percent, start_date, end_date, is_featured, is_active, combo_price)
            VALUES (?, ?, ?, 'combo', ?, ?, ?, ?, ?, 1, ?)");
        $stmt->execute([$title, $description, $image, $discount_text, $discount_percent, $start_date, $end_date, $is_featured, $combo_price]);
        $id = $conn->lastInsertId();
    }
    
    foreach ($menu_items as $menu_id) {
        $conn->prepare("INSERT INTO promotion_items (promotion_id, menu_item_id, quantity) VALUES (?, ?, 1)")->execute([$id, $menu_id]);
    }
    
    header('Location: combo-promotions.php?msg=saved');
    exit;
}

// Lấy danh sách combo
$combos = $conn->query("SELECT * FROM restaurant_promotions WHERE promo_type = 'combo' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$menu_items_list = $conn->query("SELECT id, name, price, image FROM menu_items WHERE is_available = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Lấy combo đang sửa
$edit_combo = null;
$edit_items = [];
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM restaurant_promotions WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_combo = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $conn->prepare("SELECT menu_item_id FROM promotion_items WHERE promotion_id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Thống kê
$total_combos = count($combos);
$active_combos = count(array_filter($combos, fn($c) => $c['is_active']));
$featured_combos = count(array_filter($combos, fn($c) => $c['is_featured']));

// Lấy filter từ URL
$status_filter = $_GET['status'] ?? '';

// Lọc combos theo status
$filtered_combos = $combos;
if ($status_filter === 'active') {
    $filtered_combos = array_filter($combos, fn($c) => $c['is_active']);
} elseif ($status_filter === 'featured') {
    $filtered_combos = array_filter($combos, fn($c) => $c['is_featured']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Combo - Admin</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../assets/images/logo.jpg">
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
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.75rem; }
    
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
    
    /* Card Total - Blue */
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
    
    /* Card Featured - Orange */
    .stat-card.stat-warning {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        border: 3px solid #fed7aa;
        box-shadow: 0 4px 15px rgba(249, 115, 22, 0.15);
    }
    .stat-card.stat-warning.active {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border-color: #d97706;
        box-shadow: 0 8px 30px rgba(249, 115, 22, 0.4);
    }
    .stat-card.stat-warning .stat-icon { 
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;
        box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
    }
    .stat-card.stat-warning.active .stat-icon { background: white; color: #f59e0b; }
    .stat-card.stat-warning .stat-content h3 { color: #92400e; }
    .stat-card.stat-warning .stat-content p { color: #d97706; }
    .stat-card.stat-warning.active .stat-content h3,
    .stat-card.stat-warning.active .stat-content p { color: white; }
    .stat-card.stat-warning.active .active-badge { background: white; color: #f59e0b; }
    .stat-card.stat-warning:hover { box-shadow: 0 12px 35px rgba(249, 115, 22, 0.3); }
    
    /* Alert */
    .alert { 
        padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; 
        display: flex; align-items: center; gap: 0.75rem; font-weight: 600;
    }
    .alert-success { 
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); 
        color: #166534; border: 2px solid #86efac; 
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
    
    /* Form Styles */
    .form-card { padding: 1.75rem; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
    .form-group { margin-bottom: 0; }
    .form-group label {
        display: block; font-weight: 700; color: #374151;
        margin-bottom: 0.6rem; font-size: 0.95rem;
    }
    .form-group label i { color: #22c55e; margin-right: 0.4rem; }
    .form-control {
        width: 100%; padding: 0.85rem 1.15rem;
        border: 2px solid #e5e7eb; border-radius: 12px;
        font-size: 1rem; background: #ffffff; color: #1f2937;
        transition: all 0.2s; font-weight: 500;
    }
    .form-control:focus {
        outline: none; border-color: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
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
        vertical-align: middle;
    }
    .modern-table tbody tr { transition: all 0.2s; background: white; }
    .modern-table tbody tr:hover { background: #f0fdf4; }
    .modern-table tbody tr.inactive { opacity: 0.55; }
    
    /* Combo Info Cell */
    .combo-info { display: flex; align-items: center; gap: 1rem; }
    .combo-thumb {
        width: 65px; height: 65px; border-radius: 12px;
        overflow: hidden; flex-shrink: 0; border: 2px solid #e5e7eb;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }
    .combo-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .combo-thumb-placeholder {
        width: 100%; height: 100%;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 1.4rem;
    }
    .combo-details h4 { margin: 0 0 0.3rem; font-size: 1rem; font-weight: 700; color: #1f2937; }
    .combo-details .hot-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white; padding: 3px 10px; border-radius: 12px;
        font-size: 0.7rem; font-weight: 800; margin-left: 0.5rem;
    }
    .combo-details p {
        margin: 0; font-size: 0.85rem; color: #6b7280;
        max-width: 280px; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
    }
    
    /* Badges */
    .badge-discount {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #dc2626; padding: 0.4rem 0.9rem; border-radius: 20px;
        font-weight: 800; font-size: 0.9rem;
        border: 2px solid #fca5a5;
    }
    .badge-price { font-weight: 800; color: #dc2626; font-size: 1.1rem; }
    .badge-count {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1d4ed8; padding: 0.4rem 0.9rem; border-radius: 20px;
        font-weight: 700; font-size: 0.85rem;
        border: 2px solid #93c5fd;
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
        background: #f3f4f6; color: #6b7280; border: 2px solid #e5e7eb;
    }
    
    /* Action Buttons */
    .action-btns { display: flex; gap: 0.5rem; justify-content: center; }
    .action-btn {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        border: none; cursor: pointer; transition: all 0.2s;
        font-size: 0.9rem; text-decoration: none;
    }
    .action-btn:hover { transform: translateY(-2px); }
    .action-btn.view { background: #dbeafe; color: #1d4ed8; }
    .action-btn.view:hover { background: #3b82f6; color: white; }
    .action-btn.edit { background: #dcfce7; color: #166534; }
    .action-btn.edit:hover { background: #22c55e; color: white; }
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
    
    /* Menu Items Grid */
    .menu-items-container {
        border: 2px solid #e5e7eb; border-radius: 14px;
        background: #f9fafb; padding: 0.75rem;
        max-height: 380px; overflow-y: auto;
    }
    .menu-item-option {
        display: flex; align-items: center; gap: 0.85rem;
        padding: 0.85rem 1rem; border: 2px solid #e5e7eb;
        border-radius: 12px; cursor: pointer; background: white;
        transition: all 0.2s ease;
    }
    .menu-item-option:hover { border-color: #86efac; background: #f0fdf4; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <span class="icon-box"><i class="fas fa-tags"></i></span>
                Quản lý Combo Khuyến mãi
            </h1>
            <a href="?add=1" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Combo mới</a>
        </div>
        
        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $_GET['msg'] === 'saved' ? 'Đã lưu combo thành công!' : 'Đã xóa combo!'; ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards with Links -->
        <div class="stats-grid">
            <a href="?status=" class="stat-card stat-total <?php echo $status_filter === '' ? 'active' : ''; ?>">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-content">
                    <h3><?php echo $total_combos; ?></h3>
                    <p>Tổng Combo</p>
                </div>
                <?php if ($status_filter === ''): ?>
                <span class="active-badge">ĐANG XEM</span>
                <?php endif; ?>
            </a>
            <a href="?status=active" class="stat-card stat-success <?php echo $status_filter === 'active' ? 'active' : ''; ?>">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <h3><?php echo $active_combos; ?></h3>
                    <p>Đang hoạt động</p>
                </div>
                <?php if ($status_filter === 'active'): ?>
                <span class="active-badge">ĐANG XEM</span>
                <?php endif; ?>
            </a>
            <a href="?status=featured" class="stat-card stat-warning <?php echo $status_filter === 'featured' ? 'active' : ''; ?>">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-content">
                    <h3><?php echo $featured_combos; ?></h3>
                    <p>Combo Hot</p>
                </div>
                <?php if ($status_filter === 'featured'): ?>
                <span class="active-badge">ĐANG XEM</span>
                <?php endif; ?>
            </a>
        </div>

        <?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
        <!-- Form thêm/sửa - Modern Design -->
        <div class="card" style="background:white;border-radius:20px;overflow:hidden;border:2px solid #e5e7eb;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
            <!-- Form Header -->
            <div style="background:linear-gradient(135deg, #22c55e 0%, #16a34a 100%);padding:1.25rem 1.75rem;display:flex;align-items:center;gap:0.75rem;">
                <div style="width:42px;height:42px;background:white;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-<?php echo $edit_combo ? 'edit' : 'plus'; ?>" style="color:#22c55e;font-size:1.1rem;"></i>
                </div>
                <h2 style="margin:0;font-size:1.2rem;color:white;font-weight:700;"><?php echo $edit_combo ? 'Sửa Combo' : 'Thêm Combo mới'; ?></h2>
            </div>
            
            <form method="POST" enctype="multipart/form-data" style="padding:2rem;">
                <input type="hidden" name="id" value="<?php echo $edit_combo['id'] ?? 0; ?>">
                <input type="hidden" name="current_image" value="<?php echo $edit_combo['image'] ?? ''; ?>">
                
                <!-- Row 1: Tên và Giảm giá -->
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                    <div>
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:700;color:#1f2937;margin-bottom:0.75rem;font-size:0.95rem;">
                            <i class="fas fa-tag" style="color:#22c55e;"></i> Tên Combo <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($edit_combo['title'] ?? ''); ?>" required placeholder="VD: COMBO GIA ĐÌNH, COMBO VĂN PHÒNG..." style="width:100%;padding:1rem 1.25rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;font-weight:500;transition:all 0.2s;" onfocus="this.style.borderColor='#22c55e';this.style.boxShadow='0 0 0 4px rgba(34,197,94,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                    </div>
                    <div>
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:700;color:#1f2937;margin-bottom:0.75rem;font-size:0.95rem;">
                            <i class="fas fa-percent" style="color:#f97316;"></i> Giảm giá (%)
                        </label>
                        <div style="position:relative;">
                            <input type="number" name="discount_percent" value="<?php echo $edit_combo['discount_percent'] ?? 20; ?>" min="0" max="100" style="width:100%;padding:1rem 3rem 1rem 1.25rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1.1rem;font-weight:700;color:#f97316;transition:all 0.2s;" onfocus="this.style.borderColor='#f97316';this.style.boxShadow='0 0 0 4px rgba(249,115,22,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                            <span style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);font-size:1.1rem;font-weight:700;color:#9ca3af;">%</span>
                        </div>
                    </div>
                </div>
                
                <!-- Row 2: Ngày bắt đầu và kết thúc -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                    <div>
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:700;color:#1f2937;margin-bottom:0.75rem;font-size:0.95rem;">
                            <i class="fas fa-calendar-alt" style="color:#3b82f6;"></i> Ngày bắt đầu
                        </label>
                        <input type="date" name="start_date" value="<?php echo $edit_combo['start_date'] ?? date('Y-m-d'); ?>" style="width:100%;padding:1rem 1.25rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;font-weight:500;transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 4px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                    </div>
                    <div>
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:700;color:#1f2937;margin-bottom:0.75rem;font-size:0.95rem;">
                            <i class="fas fa-calendar-check" style="color:#3b82f6;"></i> Ngày kết thúc
                        </label>
                        <input type="date" name="end_date" value="<?php echo $edit_combo['end_date'] ?? date('Y-m-d', strtotime('+30 days')); ?>" style="width:100%;padding:1rem 1.25rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;font-weight:500;transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 4px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                    </div>
                </div>
                
                <!-- Row 3: Mô tả -->
                <div style="margin-bottom:1.5rem;">
                    <label style="display:flex;align-items:center;gap:0.5rem;font-weight:700;color:#1f2937;margin-bottom:0.75rem;font-size:0.95rem;">
                        <i class="fas fa-align-left" style="color:#8b5cf6;"></i> Mô tả
                    </label>
                    <textarea name="description" rows="3" placeholder="Mô tả ngắn về combo khuyến mãi..." style="width:100%;padding:1rem 1.25rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;font-weight:500;resize:vertical;transition:all 0.2s;line-height:1.5;" onfocus="this.style.borderColor='#8b5cf6';this.style.boxShadow='0 0 0 4px rgba(139,92,246,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'"><?php echo htmlspecialchars($edit_combo['description'] ?? ''); ?></textarea>
                </div>
                
                <!-- Row 4: Hình ảnh và HOT -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
                    <div>
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:700;color:#1f2937;margin-bottom:0.75rem;font-size:0.95rem;">
                            <i class="fas fa-image" style="color:#ec4899;"></i> Hình ảnh
                        </label>
                        <div style="position:relative;">
                            <input type="file" name="image" accept="image/*" id="imageInput" style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;">
                            <div style="padding:1rem 1.25rem;border:2px dashed #d1d5db;border-radius:12px;background:#f9fafb;display:flex;align-items:center;gap:1rem;cursor:pointer;transition:all 0.2s;" onclick="document.getElementById('imageInput').click()" onmouseover="this.style.borderColor='#ec4899';this.style.background='#fdf2f8'" onmouseout="this.style.borderColor='#d1d5db';this.style.background='#f9fafb'">
                                <div style="width:48px;height:48px;background:linear-gradient(135deg,#ec4899,#db2777);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-cloud-upload-alt" style="color:white;font-size:1.2rem;"></i>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:#374151;font-size:0.95rem;">Chọn hình ảnh</div>
                                    <div style="font-size:0.8rem;color:#9ca3af;">PNG, JPG tối đa 5MB</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;">
                        <label style="display:flex;align-items:center;gap:1rem;cursor:pointer;padding:1rem 1.5rem;border:2px solid #e5e7eb;border-radius:12px;background:#f9fafb;transition:all 0.2s;width:100%;" onmouseover="this.style.borderColor='#ef4444';this.style.background='#fef2f2'" onmouseout="this.style.borderColor='#e5e7eb';this.style.background='#f9fafb'">
                            <input type="checkbox" name="is_featured" <?php echo ($edit_combo['is_featured'] ?? 0) ? 'checked' : ''; ?> style="width:22px;height:22px;accent-color:#ef4444;cursor:pointer;">
                            <div>
                                <div style="font-weight:700;color:#1f2937;font-size:0.95rem;display:flex;align-items:center;gap:0.5rem;">
                                    <i class="fas fa-fire" style="color:#ef4444;"></i> Đánh dấu HOT
                                </div>
                                <div style="font-size:0.8rem;color:#6b7280;">Hiển thị nổi bật trên trang chủ</div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Divider -->
                <div style="height:2px;background:linear-gradient(90deg,transparent,#e5e7eb,transparent);margin:1.5rem 0;"></div>
                
                <!-- Row 5: Chọn món -->
                <div style="margin-bottom:2rem;">
                    <label style="display:flex;align-items:center;gap:0.5rem;font-weight:700;color:#1f2937;margin-bottom:1rem;font-size:1rem;">
                        <i class="fas fa-utensils" style="color:#22c55e;"></i> Chọn món trong Combo <span style="color:#ef4444;">*</span>
                    </label>
                    
                    <!-- Thanh tìm kiếm -->
                    <div style="display:flex;gap:1rem;align-items:center;margin-bottom:1rem;">
                        <div style="flex:1;position:relative;">
                            <i class="fas fa-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
                            <input type="text" id="searchMenuItem" placeholder="Tìm kiếm món ăn..." style="width:100%;padding:0.9rem 1rem 0.9rem 2.75rem;border:2px solid #e5e7eb;border-radius:12px;font-size:0.95rem;font-weight:500;transition:all 0.2s;" onkeyup="filterMenuItems()" onfocus="this.style.borderColor='#22c55e';this.style.boxShadow='0 0 0 4px rgba(34,197,94,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                        </div>
                        <div id="selectedCount" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#166534;padding:0.75rem 1.25rem;border-radius:12px;font-weight:700;font-size:0.95rem;border:2px solid #86efac;white-space:nowrap;">
                            Đã chọn: 0
                        </div>
                    </div>
                    
                    <!-- Grid món ăn -->
                    <div id="menuItemsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;max-height:400px;overflow-y:auto;padding:1rem;border:2px solid #e5e7eb;border-radius:16px;background:#f8fafc;">
                        <?php foreach ($menu_items_list as $item): 
                            $checked = in_array($item['id'], $edit_items);
                            $item_img = $item['image'];
                            if ($item_img && !preg_match('/^https?:\/\//', $item_img)) {
                                $item_img = '../' . $item_img;
                            }
                        ?>
                        <label class="menu-item-option" data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>" style="display:flex;align-items:center;gap:1rem;padding:1rem;border:2px solid <?php echo $checked ? '#22c55e' : '#e5e7eb'; ?>;border-radius:14px;cursor:pointer;background:<?php echo $checked ? 'linear-gradient(135deg,#f0fdf4,#dcfce7)' : 'white'; ?>;transition:all 0.2s;box-shadow:0 2px 8px rgba(0,0,0,0.04);" onmouseover="if(!this.querySelector('input').checked){this.style.borderColor='#86efac';this.style.background='#f0fdf4'}" onmouseout="if(!this.querySelector('input').checked){this.style.borderColor='#e5e7eb';this.style.background='white'}">
                            <input type="checkbox" name="menu_items[]" value="<?php echo $item['id']; ?>" <?php echo $checked ? 'checked' : ''; ?> style="display:none;" onchange="this.parentElement.style.borderColor=this.checked?'#22c55e':'#e5e7eb';this.parentElement.style.background=this.checked?'linear-gradient(135deg,#f0fdf4,#dcfce7)':'white';updateSelectedCount()">
                            
                            <!-- Checkbox indicator -->
                            <div style="width:24px;height:24px;border-radius:8px;border:2px solid <?php echo $checked ? '#22c55e' : '#d1d5db'; ?>;background:<?php echo $checked ? '#22c55e' : 'white'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.2s;">
                                <?php if ($checked): ?>
                                <i class="fas fa-check" style="color:white;font-size:0.75rem;"></i>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item_img): ?>
                            <img src="<?php echo htmlspecialchars($item_img); ?>" style="width:50px;height:50px;border-radius:10px;object-fit:cover;border:2px solid #e5e7eb;">
                            <?php else: ?>
                            <div style="width:50px;height:50px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;"><i class="fas fa-utensils"></i></div>
                            <?php endif; ?>
                            
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.9rem;font-weight:700;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:0.25rem;"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div style="font-size:0.9rem;color:#dc2626;font-weight:700;"><?php echo number_format($item['price']); ?>đ</div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div style="display:flex;gap:1rem;justify-content:flex-end;">
                    <a href="combo-promotions.php" class="btn btn-secondary" style="padding:1rem 2rem;font-size:1rem;">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                    <button type="submit" class="btn btn-primary" style="padding:1rem 2.5rem;font-size:1rem;">
                        <i class="fas fa-save"></i> Lưu Combo
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>

        <!-- Bảng danh sách -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Danh sách Combo</h3>
                <span class="badge"><?php echo count($filtered_combos); ?> combo</span>
            </div>
            
            <?php if (empty($filtered_combos)): ?>
            <div class="empty-state">
                <div class="icon"><i class="fas fa-tags"></i></div>
                <h4>Không có combo nào</h4>
                <p><?php echo $status_filter ? 'Không có combo phù hợp với bộ lọc' : 'Chưa có combo nào được tạo'; ?></p>
                <a href="?add=1" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Combo đầu tiên</a>
            </div>
            <?php else: ?>
            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>COMBO</th>
                            <th style="text-align:center;">GIẢM GIÁ</th>
                            <th style="text-align:center;">GIÁ COMBO</th>
                            <th style="text-align:center;">SỐ MÓN</th>
                            <th style="text-align:center;">THỜI GIAN</th>
                            <th style="text-align:center;">TRẠNG THÁI</th>
                            <th style="text-align:center;">THAO TÁC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_combos as $combo): 
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM promotion_items WHERE promotion_id = ?");
                            $stmt->execute([$combo['id']]);
                            $item_count = $stmt->fetchColumn();
                            $img_src = $combo['image'];
                            if ($img_src && !preg_match('/^https?:\/\//', $img_src)) $img_src = '../uploads/promotions/' . $img_src;
                        ?>
                        <tr class="<?php echo !$combo['is_active'] ? 'inactive' : ''; ?>">
                            <td>
                                <div class="combo-info">
                                    <div class="combo-thumb">
                                        <?php if ($img_src): ?>
                                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="">
                                        <?php else: ?>
                                        <div class="combo-thumb-placeholder"><i class="fas fa-utensils"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="combo-details">
                                        <h4>
                                            <?php echo htmlspecialchars($combo['title']); ?>
                                            <?php if ($combo['is_featured']): ?>
                                            <span class="hot-badge"><i class="fas fa-fire"></i> HOT</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p><?php echo htmlspecialchars($combo['description'] ?? 'Không có mô tả'); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge-discount">-<?php echo $combo['discount_percent']; ?>%</span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge-price"><?php echo number_format($combo['combo_price'] ?? 0); ?>đ</span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge-count"><?php echo $item_count; ?> món</span>
                            </td>
                            <td style="text-align:center;font-size:0.85rem;color:#6b7280;">
                                <?php echo date('d/m/Y', strtotime($combo['start_date'])); ?><br>
                                <span style="color:#22c55e;">→</span> <?php echo date('d/m/Y', strtotime($combo['end_date'])); ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($combo['is_active']): ?>
                                <span class="badge-status active-status"><i class="fas fa-check-circle"></i> Hoạt động</span>
                                <?php else: ?>
                                <span class="badge-status inactive-status"><i class="fas fa-pause-circle"></i> Tắt</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="?toggle=<?php echo $combo['id']; ?>" class="action-btn view" title="<?php echo $combo['is_active'] ? 'Tắt' : 'Bật'; ?>">
                                        <i class="fas fa-<?php echo $combo['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                    </a>
                                    <a href="?edit=<?php echo $combo['id']; ?>" class="action-btn edit" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $combo['id']; ?>" class="action-btn delete" onclick="return confirm('Xóa combo này?')" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Tìm kiếm món ăn
function filterMenuItems() {
    const searchText = document.getElementById('searchMenuItem').value.toLowerCase().trim();
    const items = document.querySelectorAll('.menu-item-option');
    
    items.forEach(item => {
        const name = item.getAttribute('data-name');
        if (name.includes(searchText) || searchText === '') {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// Đếm số món đã chọn
function updateSelectedCount() {
    const checked = document.querySelectorAll('.menu-item-option input[type="checkbox"]:checked').length;
    const countEl = document.getElementById('selectedCount');
    if (countEl) {
        countEl.textContent = 'Đã chọn: ' + checked;
    }
}

// Khởi tạo khi trang load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>
</body>
</html>
