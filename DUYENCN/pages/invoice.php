<?php
// Trang in hóa đơn
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$order_id = $_GET['id'] ?? 0;
if (!$order_id) {
    echo '<script>window.location.href = "?page=orders";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT o.*, c.full_name, c.email, c.phone as customer_phone
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['customer_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<script>alert("Không tìm thấy đơn hàng!"); window.location.href = "?page=orders";</script>';
    exit;
}

// Lấy danh sách món
$stmt = $conn->prepare("
    SELECT oi.*, m.name, m.image 
    FROM order_items oi 
    JOIN menu_items m ON oi.menu_item_id = m.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính tạm tính
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Trạng thái đơn hàng
$status_labels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'preparing' => 'Đang chuẩn bị',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];
$status_text = $status_labels[$order['status']] ?? $order['status'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #<?php echo $order['order_number']; ?> - Ngon Gallery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .invoice-header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        
        .invoice-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .invoice-header .logo i {
            font-size: 2rem;
        }
        
        .invoice-header .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .invoice-header .slogan {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 15px;
        }
        
        .invoice-header .title {
            font-size: 1.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        /* Body */
        .invoice-body {
            padding: 30px;
        }
        
        /* Info Section */
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-block h3 {
            color: #22c55e;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .info-row .label {
            color: #6b7280;
            min-width: 100px;
        }
        
        .info-row .value {
            color: #111827;
            font-weight: 500;
        }
        
        .info-row .value.order-number {
            color: #22c55e;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .info-row .value i {
            color: #22c55e;
            margin-right: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-confirmed { background: #dbeafe; color: #2563eb; }
        .status-preparing { background: #e0e7ff; color: #4f46e5; }
        .status-shipping { background: #fce7f3; color: #db2777; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        
        /* Products Table */
        .products-section {
            margin-bottom: 30px;
        }
        
        .products-section h3 {
            color: #111827;
            font-size: 1rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #22c55e;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th {
            background: #f8fafc;
            color: #6b7280;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .products-table th:nth-child(3),
        .products-table th:nth-child(4),
        .products-table th:nth-child(5) {
            text-align: center;
        }
        
        .products-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .product-img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
        }
        
        .product-img.no-img {
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
        }
        
        .product-name {
            font-weight: 600;
            color: #111827;
        }
        
        .products-table td:nth-child(3),
        .products-table td:nth-child(4) {
            text-align: center;
            color: #6b7280;
        }
        
        .products-table td:nth-child(5) {
            text-align: right;
            font-weight: 700;
            color: #111827;
        }
        
        /* Totals */
        .totals-section {
            margin-left: auto;
            width: 300px;
            margin-bottom: 30px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 0.95rem;
        }
        
        .total-row .label {
            color: #6b7280;
        }
        
        .total-row .value {
            color: #111827;
        }
        
        .total-row.shipping .label i {
            color: #22c55e;
            margin-right: 5px;
        }
        
        .total-row.discount {
            color: #22c55e;
        }
        
        .total-row.final {
            border-top: 2px solid #111827;
            margin-top: 10px;
            padding-top: 15px;
        }
        
        .total-row.final .label {
            font-size: 1.1rem;
            font-weight: 700;
            color: #111827;
        }
        
        .total-row.final .value {
            font-size: 1.3rem;
            font-weight: 800;
            color: #ef4444;
        }
        
        /* Footer */
        .invoice-footer {
            background: #f8fafc;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .invoice-footer .thanks {
            color: #22c55e;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .invoice-footer .contact {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .invoice-footer .contact i {
            color: #22c55e;
            margin-right: 5px;
        }
        
        /* Print Button */
        .print-actions {
            text-align: center;
            padding: 20px;
            background: #fff;
        }
        
        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 30px;
            background: #fff;
            color: #6b7280;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            margin-left: 10px;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            border-color: #22c55e;
            color: #22c55e;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: #fff;
            }
            
            .invoice-container {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
            
            .print-actions {
                display: none !important;
            }
            
            .invoice-header {
                background: #22c55e !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .status-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        @media (max-width: 600px) {
            .info-section {
                grid-template-columns: 1fr;
            }
            
            .totals-section {
                width: 100%;
            }
            
            .products-table th:nth-child(2),
            .products-table td:nth-child(2) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="logo">
                <i class="fas fa-utensils"></i>
                <h1>NGON GALLERY</h1>
            </div>
            <p class="slogan">Ẩm thực Việt Nam - Hương vị truyền thống</p>
            <h2 class="title">Hóa Đơn Bán Hàng</h2>
        </div>
        
        <!-- Body -->
        <div class="invoice-body">
            <!-- Info Section -->
            <div class="info-section">
                <div class="info-block">
                    <h3>Thông tin đơn hàng</h3>
                    <div class="info-row">
                        <span class="label">Mã đơn:</span>
                        <span class="value order-number">#<?php echo $order['order_number']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Ngày đặt:</span>
                        <span class="value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Trạng thái:</span>
                        <span class="value">
                            <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo $status_text; ?></span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Thanh toán:</span>
                        <span class="value"><?php echo $order['payment_method'] === 'cash' ? 'COD - Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng'; ?></span>
                    </div>
                </div>
                
                <div class="info-block">
                    <h3>Thông tin người nhận</h3>
                    <div class="info-row">
                        <span class="value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="value"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['delivery_phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="value"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['delivery_address']); ?></span>
                    </div>
                    <?php if ($order['note']): ?>
                    <div class="info-row">
                        <span class="value"><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($order['note']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Products -->
            <div class="products-section">
                <h3><i class="fas fa-shopping-bag"></i> Chi tiết sản phẩm</h3>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th colspan="2">Sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td colspan="2">
                                <div class="product-info">
                                    <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" class="product-img">
                                    <?php else: ?>
                                    <div class="product-img no-img"><i class="fas fa-utensils"></i></div>
                                    <?php endif; ?>
                                    <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                            <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Totals -->
            <div class="totals-section">
                <div class="total-row">
                    <span class="label">Tạm tính:</span>
                    <span class="value"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span>
                </div>
                <div class="total-row shipping">
                    <span class="label"><i class="fas fa-truck"></i> Phí vận chuyển:</span>
                    <span class="value"><?php echo $order['delivery_fee'] > 0 ? '+' . number_format($order['delivery_fee'], 0, ',', '.') . 'đ' : 'Miễn phí'; ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="total-row discount">
                    <span class="label">Giảm giá:</span>
                    <span class="value">-<?php echo number_format($order['discount_amount'], 0, ',', '.'); ?>đ</span>
                </div>
                <?php endif; ?>
                <div class="total-row final">
                    <span class="label">TỔNG THANH TOÁN:</span>
                    <span class="value"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</span>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="invoice-footer">
            <p class="thanks">Cảm ơn bạn đã mua hàng tại NGON GALLERY!</p>
            <p class="contact"><i class="fas fa-phone"></i> Hotline: 0384 848 127</p>
        </div>
    </div>
    
    <!-- Print Actions -->
    <div class="print-actions">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> In hóa đơn
        </button>
        <a href="?page=orders" class="btn-back">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
    
    <script>
        // Tự động mở hộp thoại in khi trang load
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
