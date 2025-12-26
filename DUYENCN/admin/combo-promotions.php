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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Combo - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    body { background: #f8fafc !important; }
    .main-content { background: #f8fafc !important; padding: 2rem; }
    
    .page-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0;
    }
    .page-header h1 {
        color: #1f2937 !important; font-size: 1.75rem; font-weight: 800;
        display: flex; align-items: center; gap: 0.75rem; margin: 0;
    }
    .page-header h1 i { color: #22c55e; }
    
    .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; text-decoration: none; font-size: 0.9rem; }
    .btn-primary { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; }
    .btn-primary:hover { background: linear-gradient(135deg, #16a34a, #15803d); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(34,197,94,0.3); }
    .btn-secondary { background: white; color: #374151; border: 2px solid #e5e7eb; }
    .btn-secondary:hover { border-color: #22c55e; color: #22c55e; }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover { background: #dc2626; }
    .btn-sm { padding: 0.5rem 1rem; font-size: 0.8rem; }
    
    /* Stats */
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: white; border-radius: 14px; padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1rem; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: all 0.3s; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .stat-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: white; }
    .stat-card.stat-primary .stat-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .stat-card.stat-success .stat-icon { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .stat-card.stat-warning .stat-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .stat-content h3 { font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; }
    .stat-content p { color: #6b7280; font-size: 0.85rem; margin: 0.2rem 0 0; }
    
    /* Alert */
    .alert { padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-tags"></i> Quản lý Combo Khuyến mãi</h1>
            <a href="?add=1" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Combo mới</a>
        </div>
        
        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $_GET['msg'] === 'saved' ? 'Đã lưu combo thành công!' : 'Đã xóa combo!'; ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-content"><h3><?php echo $total_combos; ?></h3><p>Tổng Combo</p></div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content"><h3><?php echo $active_combos; ?></h3><p>Đang hoạt động</p></div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-content"><h3><?php echo $featured_combos; ?></h3><p>Combo Hot</p></div>
            </div>
        </div>

        <?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
        <!-- Form thêm/sửa -->
        <div class="card" style="background:white;border-radius:16px;padding:1.5rem;border:1px solid #e5e7eb;box-shadow:0 4px 15px rgba(0,0,0,0.05);">
            <h2 style="margin:0 0 1.5rem;font-size:1.1rem;color:#1f2937;display:flex;align-items:center;gap:0.5rem;">
                <i class="fas fa-edit" style="color:#22c55e;"></i> <?php echo $edit_combo ? 'Sửa Combo' : 'Thêm Combo mới'; ?>
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_combo['id'] ?? 0; ?>">
                <input type="hidden" name="current_image" value="<?php echo $edit_combo['image'] ?? ''; ?>">
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-weight:600;color:#374151;margin-bottom:0.5rem;font-size:0.9rem;">Tên Combo *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($edit_combo['title'] ?? ''); ?>" required placeholder="VD: COMBO GIA ĐÌNH" style="width:100%;padding:0.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:0.95rem;">
                    </div>
                    <div>
                        <label style="display:block;font-weight:600;color:#374151;margin-bottom:0.5rem;font-size:0.9rem;">Giảm giá (%)</label>
                        <input type="number" name="discount_percent" value="<?php echo $edit_combo['discount_percent'] ?? 20; ?>" min="0" max="100" style="width:100%;padding:0.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:0.95rem;">
                    </div>
                    <div>
                        <label style="display:block;font-weight:600;color:#374151;margin-bottom:0.5rem;font-size:0.9rem;">Ngày bắt đầu</label>
                        <input type="date" name="start_date" value="<?php echo $edit_combo['start_date'] ?? date('Y-m-d'); ?>" style="width:100%;padding:0.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:0.95rem;">
                    </div>
                    <div>
                        <label style="display:block;font-weight:600;color:#374151;margin-bottom:0.5rem;font-size:0.9rem;">Ngày kết thúc</label>
                        <input type="date" name="end_date" value="<?php echo $edit_combo['end_date'] ?? date('Y-m-d', strtotime('+30 days')); ?>" style="width:100%;padding:0.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:0.95rem;">
                    </div>
                </div>
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-weight:600;color:#374151;margin-bottom:0.5rem;font-size:0.9rem;">Mô tả</label>
                    <textarea name="description" rows="2" placeholder="Mô tả ngắn về combo..." style="width:100%;padding:0.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:0.95rem;resize:vertical;"><?php echo htmlspecialchars($edit_combo['description'] ?? ''); ?></textarea>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                    <div>
                        <label style="display:block;font-weight:600;color:#374151;margin-bottom:0.5rem;font-size:0.9rem;">Hình ảnh</label>
                        <input type="file" name="image" accept="image/*" style="width:100%;padding:0.6rem;border:2px solid #e5e7eb;border-radius:10px;font-size:0.9rem;">
                    </div>
                    <div style="display:flex;align-items:end;">
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-weight:500;color:#374151;">
                            <input type="checkbox" name="is_featured" <?php echo ($edit_combo['is_featured'] ?? 0) ? 'checked' : ''; ?> style="width:18px;height:18px;">
                            <span>Đánh dấu HOT</span>
                        </label>
                    </div>
                </div>
                
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-weight:600;color:#374151;margin-bottom:0.75rem;font-size:0.9rem;"><i class="fas fa-utensils" style="color:#22c55e;"></i> Chọn món trong Combo *</label>
                    
                    <!-- Thanh tìm kiếm -->
                    <div style="margin-bottom:0.75rem;display:flex;gap:0.75rem;align-items:center;">
                        <div style="flex:1;position:relative;">
                            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
                            <input type="text" id="searchMenuItem" placeholder="Tìm kiếm món ăn..." style="width:100%;padding:0.75rem 1rem 0.75rem 2.5rem;border:2px solid #e5e7eb;border-radius:10px;font-size:0.95rem;" onkeyup="filterMenuItems()">
                        </div>
                        <span id="selectedCount" style="background:#dcfce7;color:#166534;padding:0.5rem 1rem;border-radius:8px;font-weight:600;font-size:0.85rem;">Đã chọn: 0</span>
                    </div>
                    
                    <div id="menuItemsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:0.75rem;max-height:350px;overflow-y:auto;padding:0.5rem;border:2px solid #e5e7eb;border-radius:12px;background:#f9fafb;">
                        <?php foreach ($menu_items_list as $item): 
                            $checked = in_array($item['id'], $edit_items);
                            $item_img = $item['image'];
                            if ($item_img && !preg_match('/^https?:\/\//', $item_img)) {
                                $item_img = '../' . $item_img;
                            }
                        ?>
                        <label class="menu-item-option" data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>" style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem;border:2px solid <?php echo $checked ? '#22c55e' : '#e5e7eb'; ?>;border-radius:10px;cursor:pointer;background:<?php echo $checked ? '#f0fdf4' : 'white'; ?>;transition:all 0.2s;">
                            <input type="checkbox" name="menu_items[]" value="<?php echo $item['id']; ?>" <?php echo $checked ? 'checked' : ''; ?> style="display:none;" onchange="this.parentElement.style.borderColor=this.checked?'#22c55e':'#e5e7eb';this.parentElement.style.background=this.checked?'#f0fdf4':'white';updateSelectedCount()">
                            <?php if ($item_img): ?>
                            <img src="<?php echo htmlspecialchars($item_img); ?>" style="width:45px;height:45px;border-radius:8px;object-fit:cover;">
                            <?php else: ?>
                            <div style="width:45px;height:45px;background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#9ca3af;"><i class="fas fa-utensils"></i></div>
                            <?php endif; ?>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.85rem;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div style="font-size:0.8rem;color:#22c55e;font-weight:600;"><?php echo number_format($item['price']); ?>đ</div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div style="display:flex;gap:0.75rem;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Combo</button>
                    <a href="combo-promotions.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
                </div>
            </form>
        </div>
        <?php else: ?>

        <!-- Bảng danh sách -->
        <div class="card" style="background:white;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 4px 15px rgba(0,0,0,0.05);">
            <div style="padding:1.25rem 1.5rem;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:1rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fas fa-list" style="color:#22c55e;"></i> Danh sách Combo
                </h3>
                <span style="background:linear-gradient(135deg,#22c55e,#16a34a);color:white;padding:0.35rem 0.85rem;border-radius:20px;font-size:0.75rem;font-weight:600;"><?php echo $total_combos; ?> combo</span>
            </div>
            
            <?php if (empty($combos)): ?>
            <div style="text-align:center;padding:4rem 2rem;">
                <i class="fas fa-tags" style="font-size:3rem;color:#d1d5db;margin-bottom:1rem;display:block;"></i>
                <p style="color:#6b7280;margin-bottom:1rem;">Chưa có combo nào</p>
                <a href="?add=1" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Combo đầu tiên</a>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="padding:1rem;text-align:left;font-weight:600;color:#374151;font-size:0.85rem;border-bottom:2px solid #e5e7eb;">COMBO</th>
                            <th style="padding:1rem;text-align:center;font-weight:600;color:#374151;font-size:0.85rem;border-bottom:2px solid #e5e7eb;">GIẢM GIÁ</th>
                            <th style="padding:1rem;text-align:center;font-weight:600;color:#374151;font-size:0.85rem;border-bottom:2px solid #e5e7eb;">GIÁ COMBO</th>
                            <th style="padding:1rem;text-align:center;font-weight:600;color:#374151;font-size:0.85rem;border-bottom:2px solid #e5e7eb;">SỐ MÓN</th>
                            <th style="padding:1rem;text-align:center;font-weight:600;color:#374151;font-size:0.85rem;border-bottom:2px solid #e5e7eb;">THỜI GIAN</th>
                            <th style="padding:1rem;text-align:center;font-weight:600;color:#374151;font-size:0.85rem;border-bottom:2px solid #e5e7eb;">TRẠNG THÁI</th>
                            <th style="padding:1rem;text-align:center;font-weight:600;color:#374151;font-size:0.85rem;border-bottom:2px solid #e5e7eb;">THAO TÁC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($combos as $combo): 
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM promotion_items WHERE promotion_id = ?");
                            $stmt->execute([$combo['id']]);
                            $item_count = $stmt->fetchColumn();
                            $img_src = $combo['image'];
                            if ($img_src && !preg_match('/^https?:\/\//', $img_src)) $img_src = '../uploads/promotions/' . $img_src;
                        ?>
                        <tr style="border-bottom:1px solid #f3f4f6;<?php echo !$combo['is_active'] ? 'opacity:0.5;' : ''; ?>">
                            <td style="padding:1rem;">
                                <div style="display:flex;align-items:center;gap:1rem;">
                                    <?php if ($img_src): ?>
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" style="width:60px;height:60px;border-radius:10px;object-fit:cover;">
                                    <?php else: ?>
                                    <div style="width:60px;height:60px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.25rem;"><i class="fas fa-utensils"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight:700;color:#1f2937;margin-bottom:0.25rem;display:flex;align-items:center;gap:0.5rem;">
                                            <?php echo htmlspecialchars($combo['title']); ?>
                                            <?php if ($combo['is_featured']): ?>
                                            <span style="background:#ff6b35;color:white;padding:2px 8px;border-radius:10px;font-size:0.7rem;font-weight:600;">HOT</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:0.8rem;color:#6b7280;max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($combo['description'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:1rem;text-align:center;">
                                <span style="background:#fee2e2;color:#dc2626;padding:0.35rem 0.75rem;border-radius:20px;font-weight:700;font-size:0.85rem;">-<?php echo $combo['discount_percent']; ?>%</span>
                            </td>
                            <td style="padding:1rem;text-align:center;font-weight:700;color:#ef4444;font-size:1rem;"><?php echo number_format($combo['combo_price'] ?? 0); ?>đ</td>
                            <td style="padding:1rem;text-align:center;">
                                <span style="background:#dbeafe;color:#1d4ed8;padding:0.35rem 0.75rem;border-radius:20px;font-weight:600;font-size:0.85rem;"><?php echo $item_count; ?> món</span>
                            </td>
                            <td style="padding:1rem;text-align:center;font-size:0.85rem;color:#6b7280;">
                                <?php echo date('d/m/Y', strtotime($combo['start_date'])); ?><br>
                                <span style="color:#9ca3af;">→ <?php echo date('d/m/Y', strtotime($combo['end_date'])); ?></span>
                            </td>
                            <td style="padding:1rem;text-align:center;">
                                <?php if ($combo['is_active']): ?>
                                <span style="background:#dcfce7;color:#166534;padding:0.35rem 0.75rem;border-radius:20px;font-weight:600;font-size:0.8rem;"><i class="fas fa-check-circle"></i> Hoạt động</span>
                                <?php else: ?>
                                <span style="background:#f3f4f6;color:#6b7280;padding:0.35rem 0.75rem;border-radius:20px;font-weight:600;font-size:0.8rem;"><i class="fas fa-pause-circle"></i> Tắt</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:1rem;text-align:center;">
                                <div style="display:flex;gap:0.5rem;justify-content:center;">
                                    <a href="?toggle=<?php echo $combo['id']; ?>" class="btn btn-sm btn-secondary" title="<?php echo $combo['is_active'] ? 'Tắt' : 'Bật'; ?>"><i class="fas fa-<?php echo $combo['is_active'] ? 'eye-slash' : 'eye'; ?>"></i></a>
                                    <a href="?edit=<?php echo $combo['id']; ?>" class="btn btn-sm btn-primary" title="Sửa"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?php echo $combo['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa combo này?')" title="Xóa"><i class="fas fa-trash"></i></a>
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
