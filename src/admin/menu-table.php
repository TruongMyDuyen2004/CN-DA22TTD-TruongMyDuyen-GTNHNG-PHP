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

// Thống kê
$total = count($menu_items);
$available = count(array_filter($menu_items, fn($item) => $item['is_available']));
$unavailable = $total - $available;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thực đơn - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-unified.css">
    <link rel="stylesheet" href="../assets/css/admin-orange-theme.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Header -->
        <div class="page-header">
            <h1><i class="fas fa-utensils"></i> Quản lý thực đơn</h1>
            <div class="header-actions">
                <a href="../index.php?page=menu" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt"></i> Xem trang người dùng
                </a>
                <button onclick="alert('Tính năng thêm món đang phát triển')" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Thêm món mới
                </button>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total; ?></h3>
                    <p>Tổng món ăn</p>
                </div>
            </div>
            
            <div class="stat-card stat-success">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $available; ?></h3>
                    <p>Đang bán</p>
                </div>
            </div>
            
            <div class="stat-card stat-danger">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $unavailable; ?></h3>
                    <p>Hết hàng</p>
                </div>
            </div>
        </div>

        <!-- Bảng danh sách -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Danh sách món ăn</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="80">Hình ảnh</th>
                                <th>Tên món</th>
                                <th>Danh mục</th>
                                <th width="120">Giá</th>
                                <th width="100">Trạng thái</th>
                                <th width="200">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($menu_items) > 0): ?>
                                <?php foreach ($menu_items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if ($item['image']): ?>
                                            <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                                🍽️
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="display: block; margin-bottom: 4px;"><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <?php if ($item['name_en']): ?>
                                            <small style="color: #6b7280;"><?php echo htmlspecialchars($item['name_en']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning">
                                            <?php echo htmlspecialchars($item['category_name'] ?? 'Chưa phân loại'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: #f97316; font-size: 1.1rem;">
                                            <?php echo number_format($item['price'], 0, ',', '.'); ?>đ
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($item['is_available']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Còn món
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">
                                                <i class="fas fa-times-circle"></i> Hết món
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="../index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>" 
                                               target="_blank" 
                                               class="btn-action btn-info" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="alert('Tính năng sửa đang phát triển')" 
                                                    class="btn-action btn-primary" 
                                                    title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="if(confirm('Xóa món <?php echo htmlspecialchars($item['name']); ?>?')) alert('Tính năng xóa đang phát triển')" 
                                                    class="btn-action btn-danger" 
                                                    title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 3rem; color: #6b7280;">
                                        <i class="fas fa-utensils" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.3;"></i>
                                        <strong>Chưa có món ăn nào</strong>
                                        <p style="margin-top: 0.5rem;">Hãy thêm món ăn đầu tiên!</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn-action.btn-info {
            background: #3b82f6;
        }
        .btn-action.btn-primary {
            background: #f97316;
        }
        .btn-action.btn-danger {
            background: #ef4444;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table thead th {
            background: #f9fafb;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        .data-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .data-table tbody tr:hover {
            background: #f9fafb;
        }
    </style>
</body>
</html>
