<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Lấy danh sách món ăn
$stmt = $conn->query("
    SELECT m.*, c.name as category_name 
    FROM menu_items m 
    LEFT JOIN categories c ON m.category_id = c.id 
    ORDER BY c.display_order, m.name
");
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thực đơn - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; }
        .main-content { margin-left: 260px; padding: 2rem; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; }
        .page-header h1 { font-size: 2rem; font-weight: 700; color: #1f2937; display: flex; align-items: center; gap: 0.75rem; }
        .page-header h1 i { color: #f97316; }
        .header-actions { display: flex; gap: 1rem; }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; text-decoration: none; border-radius: 10px; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4); }
        .btn-secondary { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); box-shadow: 0 2px 8px rgba(107, 114, 128, 0.3); }
        .btn-secondary:hover { box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4); }
        .btn-add { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3); }
        .btn-add:hover { box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
        .menu-card { background: white; border-radius: 16px; padding: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; border: 2px solid transparent; overflow: hidden; }
        .menu-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); border-color: #f97316; }
        .menu-card-image { position: relative; width: 100%; height: 220px; overflow: hidden; }
        .menu-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
        .menu-card:hover img { transform: scale(1.05); }
        .menu-card-content { padding: 1.5rem; }
        .menu-card h3 { font-size: 1.25rem; margin-bottom: 0.5rem; color: #1f2937; font-weight: 700; }
        .menu-card .price { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0.75rem 0; }
        .menu-card .category { display: inline-block; padding: 0.4rem 0.9rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.75rem; }
        .menu-card .status { display: inline-block; padding: 0.4rem 0.9rem; border-radius: 20px; font-size: 0.8rem; margin-left: 0.5rem; font-weight: 600; }
        .menu-card .status.available { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; }
        .menu-card .status.unavailable { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b; }
        .menu-card .description { color: #6b7280; font-size: 0.9rem; line-height: 1.6; margin: 0.75rem 0; }
        .menu-card .actions { display: flex; gap: 0.75rem; margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid #f3f4f6; }
        .btn-small { padding: 0.6rem 1.2rem; font-size: 0.875rem; border-radius: 8px; font-weight: 600; }
        .btn-edit { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3); }
        .btn-edit:hover { box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4); }
        .btn-delete { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 2px 6px rgba(239, 68, 68, 0.3); }
        .btn-delete:hover { box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-card .number { font-size: 2rem; font-weight: bold; color: #f97316; }
        .stat-card .label { color: #6b7280; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-utensils"></i> Quản lý thực đơn</h1>
    </div>
    
    <div class="container">
        <div class="actions">
            <a href="../index.php?page=menu" target="_blank" class="btn btn-secondary">
                <i class="fas fa-external-link-alt"></i> Xem trang người dùng
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Về Dashboard
            </a>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="number"><?php echo count($menu_items); ?></div>
                <div class="label">Tổng món ăn</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo count(array_filter($menu_items, fn($item) => $item['is_available'])); ?></div>
                <div class="label">Đang bán</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo count(array_filter($menu_items, fn($item) => !$item['is_available'])); ?></div>
                <div class="label">Hết hàng</div>
            </div>
        </div>
        
        <h2 style="margin-bottom: 1.5rem; color: #1f2937;">Danh sách món ăn</h2>
        
        <?php if (count($menu_items) > 0): ?>
        <div class="menu-grid">
            <?php foreach ($menu_items as $item): ?>
            <div class="menu-card">
                <?php if ($item['image']): ?>
                    <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                <?php else: ?>
                    <div style="width:100%; height:200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:4rem; margin-bottom:1rem;">
                        🍽️
                    </div>
                <?php endif; ?>
                
                <span class="category"><?php echo htmlspecialchars($item['category_name'] ?? 'Chưa phân loại'); ?></span>
                <span class="status <?php echo $item['is_available'] ? 'available' : 'unavailable'; ?>">
                    <?php echo $item['is_available'] ? 'Còn món' : 'Hết món'; ?>
                </span>
                
                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                <?php if ($item['name_en']): ?>
                    <p style="color: #6b7280; font-size: 0.9rem;"><?php echo htmlspecialchars($item['name_en']); ?></p>
                <?php endif; ?>
                
                <div class="price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</div>
                
                <p style="color: #6b7280; font-size: 0.9rem; margin: 0.5rem 0;">
                    <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...
                </p>
                
                <div class="actions">
                    <a href="../index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>" target="_blank" class="btn btn-small btn-secondary">
                        <i class="fas fa-eye"></i> Xem
                    </a>
                    <button onclick="alert('Tính năng sửa đang phát triển')" class="btn btn-small btn-edit">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button onclick="if(confirm('Xóa món này?')) alert('Tính năng xóa đang phát triển')" class="btn btn-small btn-delete">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 4rem; background: white; border-radius: 12px;">
            <i class="fas fa-utensils" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
            <h3 style="color: #6b7280;">Chưa có món ăn nào</h3>
            <p style="color: #9ca3af; margin-top: 0.5rem;">Hãy thêm món ăn đầu tiên!</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
