<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Lấy danh sách giỏ hàng với thông tin khách hàng và món ăn
$stmt = $conn->query("
    SELECT 
        c.id,
        c.customer_id,
        c.menu_item_id,
        c.quantity,
        c.created_at,
        cu.full_name as customer_name,
        cu.email as customer_email,
        m.name as menu_name,
        m.price as menu_price,
        m.image as menu_image
    FROM cart c
    LEFT JOIN customers cu ON c.customer_id = cu.id
    LEFT JOIN menu_items m ON c.menu_item_id = m.id
    ORDER BY c.created_at DESC
");
$carts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stmt = $conn->query("SELECT COUNT(DISTINCT customer_id) as total_customers FROM cart");
$total_customers = $stmt->fetch()['total_customers'] ?? 0;

$stmt = $conn->query("SELECT SUM(quantity) as total_items FROM cart");
$total_items = $stmt->fetch()['total_items'] ?? 0;

$stmt = $conn->query("
    SELECT SUM(c.quantity * m.price) as total_value 
    FROM cart c 
    LEFT JOIN menu_items m ON c.menu_item_id = m.id
");
$total_value = $stmt->fetch()['total_value'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý giỏ hàng - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> Quản lý giỏ hàng</h1>
        </div>

        <!-- Thống kê - Style giống trang giảm giá -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#3b82f6';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); flex-shrink: 0;">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total_customers; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Khách có giỏ hàng</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total_items; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Tổng món trong giỏ</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f97316';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); flex-shrink: 0;">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo number_format($total_value, 0, ',', '.'); ?>đ</h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Tổng giá trị</p>
                </div>
            </div>
        </div>

        <!-- Danh sách giỏ hàng -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Danh sách giỏ hàng (<?php echo count($carts); ?> mục)</h3>
            </div>
            <div class="card-body">
                <?php if (count($carts) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Khách hàng</th>
                                <th>Món ăn</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($carts as $cart): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($cart['customer_name'] ?? 'N/A'); ?></strong>
                                    <br><small style="color: #6b7280;"><?php echo htmlspecialchars($cart['customer_email'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php if ($cart['menu_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($cart['menu_image']); ?>" 
                                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 6px; display: flex; align-items: center; justify-content: center;">🍽️</div>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($cart['menu_name'] ?? 'N/A'); ?></span>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge badge-info"><?php echo $cart['quantity']; ?></span>
                                </td>
                                <td><?php echo number_format($cart['menu_price'] ?? 0, 0, ',', '.'); ?>đ</td>
                                <td>
                                    <strong style="color: #f97316;">
                                        <?php echo number_format(($cart['menu_price'] ?? 0) * $cart['quantity'], 0, ',', '.'); ?>đ
                                    </strong>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($cart['created_at'])); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #9ca3af;">
                    <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>Chưa có giỏ hàng nào</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
