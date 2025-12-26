<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Kiểm tra và thêm cột discount nếu chưa có
try {
    $conn->query("SELECT discount_percent FROM menu_items LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE menu_items ADD COLUMN discount_percent INT DEFAULT 0");
    $conn->exec("ALTER TABLE menu_items ADD COLUMN original_price DECIMAL(10,2) DEFAULT NULL");
    $conn->exec("UPDATE menu_items SET original_price = price WHERE original_price IS NULL");
}

// Xử lý AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_discount') {
        $id = $_POST['id'] ?? 0;
        $discount = min(100, max(0, intval($_POST['discount'] ?? 0)));
        
        // Lấy giá gốc
        $stmt = $conn->prepare("SELECT original_price, price FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            $original = $item['original_price'] ?: $item['price'];
            $new_price = $discount > 0 ? round($original * (100 - $discount) / 100, -3) : $original;
            
            $stmt = $conn->prepare("UPDATE menu_items SET discount_percent = ?, price = ?, original_price = ? WHERE id = ?");
            $stmt->execute([$discount, $new_price, $original, $id]);
            
            echo json_encode(['success' => true, 'new_price' => $new_price]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy món']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'bulk_discount') {
        $ids = $_POST['ids'] ?? [];
        $discount = min(100, max(0, intval($_POST['discount'] ?? 0)));
        
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $stmt = $conn->prepare("SELECT original_price, price FROM menu_items WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($item) {
                    $original = $item['original_price'] ?: $item['price'];
                    $new_price = $discount > 0 ? round($original * (100 - $discount) / 100, -3) : $original;
                    
                    $stmt = $conn->prepare("UPDATE menu_items SET discount_percent = ?, price = ?, original_price = ? WHERE id = ?");
                    $stmt->execute([$discount, $new_price, $original, $id]);
                }
            }
        }
        
        echo json_encode(['success' => true, 'updated' => count($ids)]);
        exit;
    }
    
    if ($_POST['action'] === 'remove_discount') {
        $id = $_POST['id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT original_price FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item && $item['original_price']) {
            $stmt = $conn->prepare("UPDATE menu_items SET discount_percent = 0, price = original_price WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Xóa toàn bộ giảm giá
    if ($_POST['action'] === 'remove_all_discounts') {
        $stmt = $conn->query("UPDATE menu_items SET discount_percent = 0, price = original_price WHERE discount_percent > 0 AND original_price IS NOT NULL");
        $count = $stmt->rowCount();
        echo json_encode(['success' => true, 'updated' => $count]);
        exit;
    }
    
    // Giảm giá toàn bộ
    if ($_POST['action'] === 'apply_all_discounts') {
        $discount = min(100, max(0, intval($_POST['discount'] ?? 0)));
        
        // Lấy tất cả món chưa giảm giá
        $stmt = $conn->query("SELECT id, original_price, price FROM menu_items WHERE is_available = 1");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;
        
        foreach ($items as $item) {
            $original = $item['original_price'] ?: $item['price'];
            $new_price = $discount > 0 ? round($original * (100 - $discount) / 100, -3) : $original;
            
            $update = $conn->prepare("UPDATE menu_items SET discount_percent = ?, price = ?, original_price = ? WHERE id = ?");
            $update->execute([$discount, $new_price, $original, $item['id']]);
            $count++;
        }
        
        echo json_encode(['success' => true, 'updated' => $count]);
        exit;
    }
}

// Lấy danh mục
$stmt = $conn->query("SELECT * FROM categories ORDER BY display_order");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy filter
$category_filter = $_GET['category'] ?? '';
$discount_filter = $_GET['discount'] ?? '';
$search = trim($_GET['search'] ?? '');

// Query món ăn
$sql = "SELECT m.*, c.name as category_name FROM menu_items m 
        LEFT JOIN categories c ON m.category_id = c.id WHERE m.is_available = 1";
$params = [];

// Search by name
if ($search) {
    $sql .= " AND m.name LIKE ?";
    $params[] = "%$search%";
}

if ($category_filter) {
    $sql .= " AND m.category_id = ?";
    $params[] = $category_filter;
}

if ($discount_filter === 'has') {
    $sql .= " AND m.discount_percent > 0";
} elseif ($discount_filter === 'none') {
    $sql .= " AND (m.discount_percent = 0 OR m.discount_percent IS NULL)";
}

$sql .= " ORDER BY m.discount_percent DESC, c.display_order, m.name";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stmt = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN discount_percent > 0 THEN 1 ELSE 0 END) as discounted FROM menu_items WHERE is_available = 1");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
$total = $stats['total'];
$discounted = $stats['discounted'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý giảm giá - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .discount-stats {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 1rem !important;
            margin-bottom: 1.5rem !important;
        }
        .stat-card {
            background: white !important;
            border-radius: 12px !important;
            padding: 1rem 1.25rem !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            display: flex !important;
            align-items: center !important;
            gap: 1rem !important;
            border: 1px solid #e5e7eb !important;
        }
        .stat-icon {
            width: 48px !important;
            height: 48px !important;
            border-radius: 10px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.2rem !important;
            color: white !important;
        }
        .stat-icon.total { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important; }
        .stat-icon.discount { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important; }
        .stat-icon.normal { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important; }
        .stat-info h3 { font-size: 1.5rem !important; font-weight: 700 !important; color: #1f2937 !important; margin: 0 !important; }
        .stat-info p { color: #6b7280 !important; margin: 0 !important; font-size: 0.8rem !important; }
        
        .filter-bar {
            background: white !important;
            padding: 1rem !important;
            border-radius: 10px !important;
            margin-bottom: 1.25rem !important;
            display: flex !important;
            gap: 0.75rem !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04) !important;
            border: 1px solid #e5e7eb !important;
        }
        .filter-bar select {
            padding: 0.5rem 0.75rem !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            font-size: 0.85rem !important;
            min-width: 150px !important;
            background: white !important;
            color: #374151 !important;
        }
        .filter-bar select:focus { outline: none !important; border-color: #22c55e !important; }
        
        .bulk-actions {
            display: flex !important;
            gap: 0.5rem !important;
            margin-left: auto !important;
            align-items: center !important;
        }
        .bulk-discount-input {
            display: flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
        }
        .bulk-discount-input input {
            width: 50px !important;
            padding: 0.4rem !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            text-align: center !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            background: white !important;
            color: #1f2937 !important;
        }
        .bulk-discount-input span {
            color: #6b7280 !important;
            font-size: 0.85rem !important;
        }
        .bulk-btn {
            padding: 0.5rem 1rem !important;
            border: none !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.4rem !important;
            background: #22c55e !important;
            color: white !important;
            font-size: 0.85rem !important;
            transition: all 0.2s !important;
        }
        .bulk-btn:disabled { opacity: 0.5 !important; cursor: not-allowed !important; }
        .bulk-btn:hover:not(:disabled) { background: #16a34a !important; }
        
        .discount-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)) !important;
            gap: 1rem !important;
        }
        .discount-card {
            background: white !important;
            border-radius: 10px !important;
            overflow: hidden !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            position: relative !important;
            border: 1px solid #e5e7eb !important;
            transition: all 0.2s !important;
        }
        .discount-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
            border-color: #22c55e !important;
        }
        .discount-card.has-discount { 
            border-color: #f97316 !important;
            background: linear-gradient(to bottom, #fff7ed 0%, white 30%) !important;
        }
        .card-checkbox {
            position: absolute !important;
            top: 8px !important;
            left: 8px !important;
            z-index: 10 !important;
        }
        .card-checkbox input {
            width: 18px !important;
            height: 18px !important;
            cursor: pointer !important;
            accent-color: #22c55e !important;
        }
        .card-image {
            height: 120px !important;
            overflow: hidden !important;
            position: relative !important;
        }
        .card-image img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }
        .card-image .placeholder {
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 2rem !important;
        }
        .discount-badge {
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            padding: 4px 10px !important;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
            color: white !important;
            border-radius: 15px !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
        }
        .card-body { padding: 12px !important; background: white !important; }
        .card-category {
            font-size: 0.7rem !important;
            color: #22c55e !important;
            font-weight: 600 !important;
            margin-bottom: 4px !important;
            text-transform: uppercase !important;
        }
        .card-title {
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            color: #1f2937 !important;
            margin-bottom: 8px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .price-row {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin-bottom: 10px !important;
        }
        .current-price {
            font-size: 1rem !important;
            font-weight: 700 !important;
            color: #dc2626 !important;
        }
        .original-price {
            font-size: 0.8rem !important;
            color: #9ca3af !important;
            text-decoration: line-through !important;
        }
        .discount-control {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            padding: 8px 10px !important;
            background: #f9fafb !important;
            border-radius: 8px !important;
            border: 1px solid #e5e7eb !important;
        }
        .discount-control label {
            font-size: 0.8rem !important;
            color: #6b7280 !important;
            font-weight: 500 !important;
        }
        .discount-input {
            display: flex !important;
            align-items: center !important;
            gap: 2px !important;
            flex: 1 !important;
        }
        .discount-input input {
            width: 45px !important;
            padding: 6px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            text-align: center !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            background: white !important;
            color: #1f2937 !important;
        }
        .discount-input input:focus { outline: none !important; border-color: #22c55e !important; }
        .discount-input span {
            color: #6b7280 !important;
            font-size: 0.8rem !important;
        }
        .btn-apply {
            padding: 6px 10px !important;
            background: #22c55e !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            font-size: 0.8rem !important;
            transition: all 0.2s !important;
        }
        .btn-apply:hover { background: #16a34a !important; }
        .btn-remove {
            padding: 6px 8px !important;
            background: #fee2e2 !important;
            color: #dc2626 !important;
            border: none !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            font-size: 0.75rem !important;
            transition: all 0.2s !important;
        }
        .btn-remove:hover { background: #fecaca !important; }
        
        .quick-discount-btns {
            display: flex !important;
            gap: 4px !important;
            margin-top: 8px !important;
            flex-wrap: wrap !important;
        }
        .quick-btn {
            padding: 4px 8px !important;
            background: #f0fdf4 !important;
            color: #16a34a !important;
            border: 1px solid #bbf7d0 !important;
            border-radius: 4px !important;
            font-size: 0.7rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
        }
        .quick-btn:hover { 
            background: #22c55e !important; 
            color: white !important;
            border-color: #22c55e !important;
        }
        
        .toast {
            position: fixed !important;
            bottom: 2rem !important;
            right: 2rem !important;
            padding: 0.75rem 1.25rem !important;
            border-radius: 8px !important;
            color: white !important;
            font-weight: 600 !important;
            font-size: 0.9rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            z-index: 9999 !important;
            transform: translateY(100px) !important;
            opacity: 0 !important;
            transition: all 0.3s ease !important;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-percent"></i> Quản lý giảm giá</h1>
            <a href="menu-manage.php" class="btn btn-secondary">
                <i class="fas fa-utensils"></i> Quản lý thực đơn
            </a>
        </div>

        <!-- Thống kê - Style giống trang khách hàng với hiệu ứng hover -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#3b82f6';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); flex-shrink: 0;">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Tổng số món</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f97316';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); flex-shrink: 0;">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $discounted; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Đang giảm giá</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total - $discounted; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Giá gốc</p>
                </div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div style="background: white; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 2px solid #d1d5db;">
            <!-- Thanh tìm kiếm -->
            <div style="position: relative; flex: 0 0 220px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                <input type="text" id="searchInput" placeholder="Tìm tên món..." value="<?php echo htmlspecialchars($search); ?>" 
                       onkeypress="if(event.key==='Enter')applyFilter()"
                       style="width: 100%; padding: 0.6rem 1rem 0.6rem 2.5rem; border: 2px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; background: white; color: #374151;">
            </div>
            
            <select id="categoryFilter" onchange="applyFilter()" style="padding: 0.6rem 1rem; border: 2px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; min-width: 160px; background: white; color: #374151;">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <select id="discountFilter" onchange="applyFilter()" style="padding: 0.6rem 1rem; border: 2px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; min-width: 160px; background: white; color: #374151;">
                <option value="">Tất cả</option>
                <option value="has" <?php echo $discount_filter === 'has' ? 'selected' : ''; ?>>Đang giảm giá</option>
                <option value="none" <?php echo $discount_filter === 'none' ? 'selected' : ''; ?>>Chưa giảm giá</option>
            </select>
            
            <div style="display: flex; gap: 0.75rem; margin-left: auto; align-items: center;">
                <span id="selectedCount" style="color: #6b7280; font-size: 0.9rem; font-weight: 500;">Đã chọn: 0</span>
                <div style="display: flex; align-items: center; gap: 0.25rem;">
                    <input type="number" id="bulkDiscount" min="0" max="100" value="15" placeholder="%" style="width: 55px; padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 8px; text-align: center; font-weight: 600; font-size: 0.9rem; background: white; color: #1f2937;">
                    <span style="color: #6b7280; font-size: 0.9rem;">%</span>
                </div>
                <button class="bulk-btn" onclick="bulkDiscount()" id="btnBulk" disabled style="padding: 0.6rem 1.25rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; background: #22c55e; color: white; font-size: 0.9rem;">
                    <i class="fas fa-tags"></i> Áp dụng
                </button>
                <button onclick="applyAllDiscounts()" style="padding: 0.6rem 1rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; background: #f97316; color: white; font-size: 0.85rem;">
                    <i class="fas fa-percent"></i> Giảm toàn bộ
                </button>
                <button onclick="removeAllDiscounts()" style="padding: 0.6rem 1rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; background: #ef4444; color: white; font-size: 0.85rem;">
                    <i class="fas fa-trash"></i> Xóa toàn bộ
                </button>
            </div>
        </div>

        <!-- Danh sách món -->
        <div class="discount-grid">
            <?php foreach ($items as $item): 
                $has_discount = ($item['discount_percent'] ?? 0) > 0;
                $original = $item['original_price'] ?: $item['price'];
            ?>
            <div class="discount-card <?php echo $has_discount ? 'has-discount' : ''; ?>" data-id="<?php echo $item['id']; ?>">
                <div class="card-checkbox">
                    <input type="checkbox" class="item-checkbox" value="<?php echo $item['id']; ?>" onchange="updateSelection()">
                </div>
                <div class="card-image">
                    <?php if ($item['image']): ?>
                        <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="">
                    <?php else: ?>
                        <div class="placeholder">🍽️</div>
                    <?php endif; ?>
                    <?php if ($has_discount): ?>
                    <span class="discount-badge">-<?php echo $item['discount_percent']; ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="card-category"><?php echo htmlspecialchars($item['category_name'] ?? 'Chưa phân loại'); ?></div>
                    <h3 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <div class="price-row">
                        <span class="current-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                        <?php if ($has_discount): ?>
                        <span class="original-price"><?php echo number_format($original, 0, ',', '.'); ?>đ</span>
                        <?php endif; ?>
                    </div>
                    <div class="discount-control">
                        <label>Giảm:</label>
                        <div class="discount-input">
                            <input type="number" id="discount_<?php echo $item['id']; ?>" 
                                   min="0" max="100" 
                                   value="<?php echo $item['discount_percent'] ?? 0; ?>"
                                   onkeypress="if(event.key==='Enter')applyDiscount(<?php echo $item['id']; ?>)">
                            <span>%</span>
                        </div>
                        <button class="btn-apply" onclick="applyDiscount(<?php echo $item['id']; ?>)">
                            <i class="fas fa-check"></i>
                        </button>
                        <?php if ($has_discount): ?>
                        <button class="btn-remove" onclick="removeDiscount(<?php echo $item['id']; ?>)" title="Xóa giảm giá">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="quick-discount-btns">
                        <button class="quick-btn" onclick="setQuickDiscount(<?php echo $item['id']; ?>, 5)">5%</button>
                        <button class="quick-btn" onclick="setQuickDiscount(<?php echo $item['id']; ?>, 10)">10%</button>
                        <button class="quick-btn" onclick="setQuickDiscount(<?php echo $item['id']; ?>, 15)">15%</button>
                        <button class="quick-btn" onclick="setQuickDiscount(<?php echo $item['id']; ?>, 20)">20%</button>
                        <button class="quick-btn" onclick="setQuickDiscount(<?php echo $item['id']; ?>, 30)">30%</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        function applyFilter() {
            const search = document.getElementById('searchInput').value.trim();
            const category = document.getElementById('categoryFilter').value;
            const discount = document.getElementById('discountFilter').value;
            let url = '?';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (category) url += 'category=' + category + '&';
            if (discount) url += 'discount=' + discount;
            window.location.href = url;
        }
        
        function setQuickDiscount(id, percent) {
            document.getElementById('discount_' + id).value = percent;
            applyDiscount(id);
        }
        
        async function applyDiscount(id) {
            const discount = document.getElementById('discount_' + id).value;
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_discount');
                formData.append('id', id);
                formData.append('discount', discount);
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    showToast('Đã cập nhật giảm giá ' + discount + '%');
                    setTimeout(() => location.reload(), 800);
                }
            } catch (error) {
                showToast('Có lỗi xảy ra', 'error');
            }
        }
        
        async function removeDiscount(id) {
            if (!confirm('Xóa giảm giá cho món này?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'remove_discount');
                formData.append('id', id);
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    showToast('Đã xóa giảm giá');
                    setTimeout(() => location.reload(), 800);
                }
            } catch (error) {
                showToast('Có lỗi xảy ra', 'error');
            }
        }
        
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selectedCount').textContent = 'Đã chọn: ' + count;
            document.getElementById('btnBulk').disabled = count === 0;
        }
        
        async function bulkDiscount() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            const discount = document.getElementById('bulkDiscount').value;
            
            if (ids.length === 0) return;
            if (!confirm(`Áp dụng giảm ${discount}% cho ${ids.length} món?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'bulk_discount');
                formData.append('discount', discount);
                ids.forEach(id => formData.append('ids[]', id));
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    showToast(`Đã giảm giá ${data.updated} món`);
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (error) {
                showToast('Có lỗi xảy ra', 'error');
            }
        }
        
        // Giảm giá toàn bộ
        async function applyAllDiscounts() {
            const discount = document.getElementById('bulkDiscount').value;
            if (!confirm(`Áp dụng giảm ${discount}% cho TẤT CẢ món ăn?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'apply_all_discounts');
                formData.append('discount', discount);
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    showToast(`Đã giảm giá ${data.updated} món`);
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (error) {
                showToast('Có lỗi xảy ra', 'error');
            }
        }
        
        // Xóa toàn bộ giảm giá
        async function removeAllDiscounts() {
            if (!confirm('Xóa TẤT CẢ giảm giá? Tất cả món sẽ trở về giá gốc.')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'remove_all_discounts');
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    showToast(`Đã xóa giảm giá ${data.updated} món`);
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (error) {
                showToast('Có lỗi xảy ra', 'error');
            }
        }
        
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
