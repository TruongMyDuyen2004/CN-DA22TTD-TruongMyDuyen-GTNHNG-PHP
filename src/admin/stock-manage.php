<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'toggle_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'bulk_update') {
        $ids = $_POST['ids'] ?? [];
        $status = $_POST['status'] ?? 0;
        
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("UPDATE menu_items SET is_available = ? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$status], $ids));
        }
        
        echo json_encode(['success' => true, 'updated' => count($ids)]);
        exit;
    }
}

// Lấy danh mục
$stmt = $conn->query("SELECT * FROM categories ORDER BY display_order");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy filter
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Query món ăn
$sql = "SELECT m.*, c.name as category_name FROM menu_items m 
        LEFT JOIN categories c ON m.category_id = c.id WHERE 1=1";
$params = [];

if ($category_filter) {
    $sql .= " AND m.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter !== '') {
    $sql .= " AND m.is_available = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY c.display_order, m.name";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stmt = $conn->query("SELECT COUNT(*) as total, SUM(is_available) as available FROM menu_items");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
$total = $stats['total'];
$available = $stats['available'];
$unavailable = $total - $available;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tồn kho - Admin</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .stock-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .stat-icon.total { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .stat-icon.available { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.unavailable { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .stat-info h3 {
            font-size: 2rem;
            font-weight: 800;
            color: #1f2937;
            margin: 0;
        }
        .stat-info p {
            color: #6b7280;
            margin: 0.25rem 0 0;
            font-size: 0.9rem;
        }
        
        .filter-bar {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .filter-bar select {
            padding: 0.6rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            min-width: 180px;
        }
        .filter-bar select:focus {
            outline: none;
            border-color: #f97316;
        }
        
        .bulk-actions {
            display: flex;
            gap: 0.75rem;
            margin-left: auto;
        }
        .bulk-btn {
            padding: 0.6rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .bulk-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .bulk-btn.available {
            background: #10b981;
            color: white;
        }
        .bulk-btn.unavailable {
            background: #ef4444;
            color: white;
        }
        .bulk-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* ========================================
           STOCK TABLE - MODERN DESIGN
           ======================================== */
        
        .stock-table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .stock-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-bottom: 2px solid #bbf7d0;
        }
        
        .stock-table-header h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stock-table-header h3 i {
            color: #22c55e;
        }
        
        .stock-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .stock-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .stock-table th {
            padding: 16px 18px;
            text-align: left;
            font-weight: 700;
            font-size: 0.9rem;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .stock-table th:first-child {
            padding-left: 24px;
            width: 50px;
        }
        
        .stock-table td {
            padding: 18px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        
        .stock-table td:first-child {
            padding-left: 24px;
        }
        
        .stock-table tbody tr {
            transition: all 0.2s ease;
            background: white;
        }
        
        .stock-table tbody tr:hover {
            background: linear-gradient(135deg, #f0fdf4 0%, #fafafa 100%);
        }
        
        .stock-table tbody tr.unavailable {
            background: #fffbeb;
        }
        
        .stock-table tbody tr.unavailable:hover {
            background: #fef3c7;
        }
        
        .stock-table tbody tr.unavailable .item-details h4 {
            color: #92400e;
        }
        
        .stock-table tbody tr.unavailable .price-cell {
            color: #d97706;
        }
        
        /* Item Info Cell */
        .item-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .item-thumb {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-thumb-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
        }
        
        .item-details h4 {
            margin: 0 0 4px 0;
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .item-details .item-id {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        /* Category Badge */
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        
        /* Price Cell */
        .price-cell {
            font-size: 1.1rem;
            font-weight: 800;
            color: #22c55e;
        }
        
        /* Status Badge in Table */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        
        .status-pill.available {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }
        
        .status-pill.unavailable {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .status-pill i {
            font-size: 0.6rem;
        }
        
        /* Toggle Switch - Larger & Clearer */
        .switch {
            position: relative;
            width: 64px;
            height: 34px;
            flex-shrink: 0;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ef4444;
            transition: 0.3s ease;
            border-radius: 34px;
            border: 3px solid #dc2626;
        }
        
        .slider:before {
            position: absolute;
            content: "✗";
            height: 26px;
            width: 26px;
            left: 1px;
            bottom: 1px;
            background-color: white;
            transition: 0.3s ease;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            color: #ef4444;
        }
        
        input:checked + .slider {
            background: #22c55e;
            border-color: #16a34a;
        }
        
        input:checked + .slider:before {
            transform: translateX(30px);
            content: "✓";
            color: #22c55e;
        }
        
        .switch:hover .slider {
            box-shadow: 0 0 8px rgba(0,0,0,0.2);
        }
        
        /* Checkbox Style */
        .stock-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #22c55e;
        }
        
        /* Select All */
        .select-all-row {
            background: #f8fafc !important;
        }
        
        .select-all-row td {
            padding: 12px 16px !important;
            border-bottom: 2px solid #e5e7eb !important;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 9999;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .toast.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .toast.error { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Modern Page Header -->
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding: 20px 28px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%); border-radius: 16px; border: 2px solid #bbf7d0; box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1);">
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #166534; margin: 0; display: flex; align-items: center; gap: 14px;">
                <span style="width: 48px; height: 48px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);">
                    <i class="fas fa-boxes" style="color: white; font-size: 1.3rem;"></i>
                </span>
                Quản lý tồn kho
            </h1>
            <a href="menu-manage.php" class="btn btn-primary" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 700; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35); transition: all 0.3s ease;">
                <i class="fas fa-utensils"></i> Quản lý thực đơn
            </a>
        </div>

        <!-- Modern Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px;">
            <!-- Total -->
            <a href="?status=" class="stat-card-link" style="text-decoration: none; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 16px; padding: 24px; border: 2px solid <?php echo $status_filter === '' ? '#3b82f6' : '#93c5fd'; ?>; box-shadow: 0 4px 15px rgba(59, 130, 246, <?php echo $status_filter === '' ? '0.35' : '0.15'; ?>); display: flex; align-items: center; gap: 20px; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 15px rgba(59, 130, 246, <?php echo $status_filter === '' ? '0.35' : '0.15'; ?>';">
                <div style="width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: white; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); flex-shrink: 0;">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h3 style="font-size: 2.2rem; font-weight: 800; color: #1e40af; margin: 0; line-height: 1;"><?php echo $total; ?></h3>
                    <p style="color: #3b82f6; margin: 6px 0 0; font-size: 0.95rem; font-weight: 600;">Tổng số món</p>
                </div>
            </a>
            
            <!-- Available -->
            <a href="?status=1" class="stat-card-link" style="text-decoration: none; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 16px; padding: 24px; border: 2px solid <?php echo $status_filter === '1' ? '#22c55e' : '#86efac'; ?>; box-shadow: 0 4px 15px rgba(34, 197, 94, <?php echo $status_filter === '1' ? '0.35' : '0.15'; ?>); display: flex; align-items: center; gap: 20px; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(34, 197, 94, 0.25)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 15px rgba(34, 197, 94, <?php echo $status_filter === '1' ? '0.35' : '0.15'; ?>';">
                <div style="width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4); flex-shrink: 0;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h3 style="font-size: 2.2rem; font-weight: 800; color: #166534; margin: 0; line-height: 1;"><?php echo $available; ?></h3>
                    <p style="color: #22c55e; margin: 6px 0 0; font-size: 0.95rem; font-weight: 600;">Còn món</p>
                </div>
            </a>
            
            <!-- Unavailable -->
            <a href="?status=0" class="stat-card-link" style="text-decoration: none; background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); border-radius: 16px; padding: 24px; border: 2px solid <?php echo $status_filter === '0' ? '#ef4444' : '#fca5a5'; ?>; box-shadow: 0 4px 15px rgba(239, 68, 68, <?php echo $status_filter === '0' ? '0.35' : '0.15'; ?>); display: flex; align-items: center; gap: 20px; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(239, 68, 68, 0.25)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 15px rgba(239, 68, 68, <?php echo $status_filter === '0' ? '0.35' : '0.15'; ?>';">
                <div style="width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: white; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); flex-shrink: 0;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div>
                    <h3 style="font-size: 2.2rem; font-weight: 800; color: #991b1b; margin: 0; line-height: 1;"><?php echo $unavailable; ?></h3>
                    <p style="color: #ef4444; margin: 6px 0 0; font-size: 0.95rem; font-weight: 600;">Hết món</p>
                </div>
            </a>
        </div>

        <!-- Modern Filter Bar -->
        <div style="background: white; padding: 20px 24px; border-radius: 16px; margin-bottom: 24px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 2px solid #e5e7eb;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="width: 36px; height: 36px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #22c55e;">
                    <i class="fas fa-filter"></i>
                </span>
                <select id="categoryFilter" onchange="applyFilter()" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 0.95rem; min-width: 180px; font-weight: 500; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                    <option value="">📂 Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <select id="statusFilter" onchange="applyFilter()" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 0.95rem; min-width: 160px; font-weight: 500; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                <option value="">📊 Tất cả trạng thái</option>
                <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>✅ Còn món</option>
                <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>❌ Hết món</option>
            </select>
            
            <div style="margin-left: auto; display: flex; align-items: center; gap: 12px;">
                <span id="selectedCount" style="color: #6b7280; font-size: 0.9rem; font-weight: 600; background: #f3f4f6; padding: 8px 14px; border-radius: 8px;">Đã chọn: 0</span>
                <button onclick="bulkUpdate(1)" id="btnAvailable" disabled style="padding: 10px 20px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); transition: all 0.2s; opacity: 0.5;" onmouseover="if(!this.disabled) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(34, 197, 94, 0.4)'; }" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(34, 197, 94, 0.3)';">
                    <i class="fas fa-check"></i> Đánh dấu còn món
                </button>
                <button onclick="bulkUpdate(0)" id="btnUnavailable" disabled style="padding: 10px 20px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); transition: all 0.2s; opacity: 0.5;" onmouseover="if(!this.disabled) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(239, 68, 68, 0.4)'; }" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.3)';">
                    <i class="fas fa-times"></i> Đánh dấu hết món
                </button>
            </div>
        </div>

        <!-- Danh sách món - Table View -->
        <?php if (count($items) > 0): ?>
        <div class="stock-table-container">
            <div class="stock-table-header">
                <h3><i class="fas fa-clipboard-list"></i> Danh sách tồn kho</h3>
                <span style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem;">
                    <?php echo count($items); ?> món
                </span>
            </div>
            
            <table class="stock-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="stock-checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                        <th>Món ăn</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Trạng thái</th>
                        <th style="text-align: center;">Bật/Tắt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr class="<?php echo !$item['is_available'] ? 'unavailable' : ''; ?>" data-id="<?php echo $item['id']; ?>">
                        <td>
                            <input type="checkbox" class="stock-checkbox item-checkbox" value="<?php echo $item['id']; ?>" onchange="updateSelection()">
                        </td>
                        <td>
                            <div class="item-info">
                                <div class="item-thumb">
                                    <?php if ($item['image']): ?>
                                        <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="item-thumb-placeholder">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <span class="item-id">#<?php echo $item['id']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="category-badge">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($item['category_name'] ?? 'Chưa phân loại'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="price-cell"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                        </td>
                        <td>
                            <span class="status-pill <?php echo $item['is_available'] ? 'available' : 'unavailable'; ?>" id="status-pill-<?php echo $item['id']; ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo $item['is_available'] ? 'Còn món' : 'Hết món'; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <label class="switch">
                                <input type="checkbox" <?php echo $item['is_available'] ? 'checked' : ''; ?> 
                                       onchange="toggleStatus(<?php echo $item['id']; ?>, this.checked)">
                                <span class="slider"></span>
                            </label>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Không tìm thấy món ăn nào</h3>
            <p>Thử thay đổi bộ lọc để xem các món khác</p>
        </div>
        <?php endif; ?>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        function applyFilter() {
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            let url = '?';
            if (category) url += 'category=' + category + '&';
            if (status !== '') url += 'status=' + status;
            window.location.href = url;
        }
        
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateSelection();
        }
        
        async function toggleStatus(id, checked) {
            const status = checked ? 1 : 0;
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('id', id);
                formData.append('status', status);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    const statusPill = document.getElementById(`status-pill-${id}`);
                    
                    if (status) {
                        row.classList.remove('unavailable');
                        statusPill.className = 'status-pill available';
                        statusPill.innerHTML = '<i class="fas fa-circle"></i> Còn món';
                    } else {
                        row.classList.add('unavailable');
                        statusPill.className = 'status-pill unavailable';
                        statusPill.innerHTML = '<i class="fas fa-circle"></i> Hết món';
                    }
                    
                    showToast(status ? 'Đã đánh dấu còn món' : 'Đã đánh dấu hết món', 'success');
                }
            } catch (error) {
                showToast('Có lỗi xảy ra', 'error');
            }
        }
        
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const count = checkboxes.length;
            
            document.getElementById('selectedCount').textContent = 'Đã chọn: ' + count;
            
            const btnAvailable = document.getElementById('btnAvailable');
            const btnUnavailable = document.getElementById('btnUnavailable');
            
            btnAvailable.disabled = count === 0;
            btnUnavailable.disabled = count === 0;
            btnAvailable.style.opacity = count === 0 ? '0.5' : '1';
            btnUnavailable.style.opacity = count === 0 ? '0.5' : '1';
            btnAvailable.style.cursor = count === 0 ? 'not-allowed' : 'pointer';
            btnUnavailable.style.cursor = count === 0 ? 'not-allowed' : 'pointer';
            
            // Update select all checkbox
            const allCheckboxes = document.querySelectorAll('.item-checkbox');
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.checked = count === allCheckboxes.length && count > 0;
                selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
            }
        }
        
        async function bulkUpdate(status) {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) return;
            
            const action = status ? 'còn món' : 'hết món';
            if (!confirm(`Bạn có chắc muốn đánh dấu ${ids.length} món là ${action}?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'bulk_update');
                formData.append('status', status);
                ids.forEach(id => formData.append('ids[]', id));
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(`Đã cập nhật ${data.updated} món`, 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (error) {
                showToast('Có lỗi xảy ra', 'error');
            }
        }
        
        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            toast.className = `toast ${type} show`;
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>
