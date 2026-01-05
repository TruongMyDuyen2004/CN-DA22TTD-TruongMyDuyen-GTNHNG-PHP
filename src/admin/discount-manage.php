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
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Reset để tránh xung đột với dark theme */
        .main-content {
            background: #f5f5f5 !important;
        }
        
        .page-header {
            background: white !important;
            padding: 1.25rem 1.5rem !important;
            border-radius: 12px !important;
            margin-bottom: 1.5rem !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
        }
        .page-header h1 {
            color: #1f2937 !important;
            font-size: 1.5rem !important;
        }
        .page-header h1 i {
            color: #22c55e !important;
        }
        
        /* Grid layout - card lớn hơn, ít card hơn mỗi hàng */
        .discount-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        /* Card đơn giản, sạch sẽ */
        .discount-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            position: relative;
            border: 2px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        .discount-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            border-color: #22c55e;
        }
        .discount-card.has-discount { 
            border-color: #f97316;
        }
        
        /* Checkbox */
        .card-checkbox {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 10;
            background: white;
            border-radius: 6px;
            padding: 3px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .card-checkbox input {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: #22c55e;
        }
        
        /* Hình ảnh */
        .card-image {
            height: 160px;
            overflow: hidden;
            position: relative;
        }
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-image .placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        
        /* Badge giảm giá */
        .discount-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 8px 14px;
            background: #ef4444;
            color: white;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 800;
        }
        
        /* Nội dung card */
        .card-body { 
            padding: 16px 18px;
            background: #ffffff;
        }
        
        /* Danh mục */
        .card-category {
            display: inline-block;
            font-size: 0.8rem;
            color: white;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
            background: #22c55e;
            padding: 4px 10px;
            border-radius: 6px;
        }
        
        /* Tên món */
        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        /* Giá */
        .price-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            padding-bottom: 14px;
            border-bottom: 2px solid #f3f4f6;
        }
        .current-price {
            font-size: 1.3rem;
            font-weight: 800;
            color: #dc2626;
        }
        .original-price {
            font-size: 1rem;
            color: #9ca3af;
            text-decoration: line-through;
        }
        
        /* Control giảm giá - đơn giản hơn */
        .discount-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .discount-control label {
            font-size: 0.95rem;
            color: #374151;
            font-weight: 600;
        }
        .discount-input {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
        }
        .discount-input input {
            width: 60px;
            padding: 10px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            text-align: center;
            font-weight: 700;
            font-size: 1rem;
            background: #ffffff;
            color: #1f2937;
        }
        .discount-input input:focus { 
            outline: none;
            border-color: #22c55e;
        }
        .discount-input span {
            color: #374151;
            font-size: 1rem;
            font-weight: 700;
        }
        
        /* Nút áp dụng */
        .btn-apply {
            padding: 10px 16px;
            background: #22c55e;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-apply:hover { 
            background: #16a34a;
        }
        
        /* Nút xóa */
        .btn-remove {
            padding: 10px 12px;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .btn-remove:hover { 
            background: #fecaca;
        }
        
        /* Quick buttons - gọn hơn */
        .quick-discount-btns {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .quick-btn {
            flex: 1;
            padding: 8px 0;
            background: #f3f4f6;
            color: #374151;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
        }
        .quick-btn:hover { 
            background: #22c55e;
            color: white;
            border-color: #22c55e;
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 9999;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            background: #22c55e;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        
        /* Filter bar */
        .filter-bar-custom {
            background: #ffffff;
            padding: 1.25rem 1.5rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 2px solid #e5e7eb;
        }
        .filter-bar-custom input,
        .filter-bar-custom select {
            padding: 0.7rem 1rem;
            border: 2px solid #d1d5db;
            border-radius: 10px;
            font-size: 0.95rem;
            background: #ffffff;
            color: #374151;
            font-weight: 500;
        }
        .filter-bar-custom input:focus,
        .filter-bar-custom select:focus {
            outline: none;
            border-color: #22c55e;
        }
        .filter-bar-custom .bulk-btn {
            padding: 0.7rem 1.25rem;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }
        .filter-bar-custom .bulk-btn:disabled { 
            opacity: 0.5;
            cursor: not-allowed;
        }
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

        <!-- Thống kê - Đơn giản, dễ nhìn -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <!-- Tổng số món -->
            <a href="?discount=" style="text-decoration: none; background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 1rem; border: 3px solid <?php echo $discount_filter === '' ? '#3b82f6' : '#e5e7eb'; ?>; cursor: pointer;">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: #3b82f6;">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h3 style="font-size: 2rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo $total; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 600;">Tổng số món</p>
                </div>
            </a>
            
            <!-- Đang giảm giá -->
            <a href="?discount=has" style="text-decoration: none; background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 1rem; border: 3px solid <?php echo $discount_filter === 'has' ? '#f97316' : '#e5e7eb'; ?>; cursor: pointer;">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: #f97316;">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h3 style="font-size: 2rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo $discounted; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 600;">Đang giảm giá</p>
                </div>
            </a>
            
            <!-- Giá gốc -->
            <a href="?discount=none" style="text-decoration: none; background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 1rem; border: 3px solid <?php echo $discount_filter === 'none' ? '#22c55e' : '#e5e7eb'; ?>; cursor: pointer;">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: #22c55e;">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <h3 style="font-size: 2rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo $total - $discounted; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 600;">Giá gốc</p>
                </div>
            </a>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-bar-custom">
            <!-- Thanh tìm kiếm -->
            <div style="position: relative; flex: 0 0 240px;">
                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.95rem;"></i>
                <input type="text" id="searchInput" placeholder="Tìm tên món..." value="<?php echo htmlspecialchars($search); ?>" 
                       onkeypress="if(event.key==='Enter')applyFilter()"
                       style="width: 100%; padding-left: 2.75rem;">
            </div>
            
            <select id="categoryFilter" onchange="applyFilter()" style="min-width: 180px;">
                <option value="">📂 Tất cả danh mục</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <select id="discountFilter" onchange="applyFilter()" style="min-width: 170px;">
                <option value="">📊 Tất cả trạng thái</option>
                <option value="has" <?php echo $discount_filter === 'has' ? 'selected' : ''; ?>>🏷️ Đang giảm giá</option>
                <option value="none" <?php echo $discount_filter === 'none' ? 'selected' : ''; ?>>💰 Chưa giảm giá</option>
            </select>
            
            <div style="display: flex; gap: 0.75rem; margin-left: auto; align-items: center;">
                <span id="selectedCount" style="color: #374151; font-size: 0.95rem; font-weight: 600; background: #f3f4f6; padding: 0.5rem 1rem; border-radius: 8px;">Đã chọn: 0</span>
                <div style="display: flex; align-items: center; gap: 4px; background: #f8fafc; padding: 4px 8px; border-radius: 8px; border: 2px solid #e5e7eb;">
                    <input type="number" id="bulkDiscount" min="0" max="100" value="15" placeholder="%" style="width: 55px; padding: 0.5rem; border: none; text-align: center; font-weight: 700; font-size: 1rem; background: transparent; color: #1f2937;">
                    <span style="color: #6b7280; font-size: 1rem; font-weight: 600;">%</span>
                </div>
                <button class="bulk-btn" onclick="bulkDiscount()" id="btnBulk" disabled style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;">
                    <i class="fas fa-tags"></i> Áp dụng
                </button>
                <button class="bulk-btn" onclick="applyAllDiscounts()" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white;">
                    <i class="fas fa-percent"></i> Giảm toàn bộ
                </button>
                <button class="bulk-btn" onclick="removeAllDiscounts()" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
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
