<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->connect();

$success = '';
$error = '';

// Xử lý xóa khách hàng
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Đã xóa khách hàng thành công';
        }
    } catch (PDOException $e) {
        $error = 'Không thể xóa khách hàng này (có thể đang có đơn hàng)';
    }
}

// Xử lý khóa/mở khóa tài khoản
if (isset($_POST['toggle_status'])) {
    $id = intval($_POST['customer_id']);
    $status = $_POST['status'] == 'active' ? 'blocked' : 'active';
    
    $stmt = $conn->prepare("UPDATE customers SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        $success = 'Đã cập nhật trạng thái tài khoản';
    }
}

// Lấy danh sách khách hàng
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$today_filter = isset($_GET['today']) ? true : false;

$sql = "SELECT c.*, 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent
        FROM customers c
        LEFT JOIN orders o ON c.id = o.customer_id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($status_filter) {
    $sql .= " AND c.status = ?";
    $params[] = $status_filter;
}

if ($today_filter) {
    $sql .= " AND DATE(c.created_at) = CURDATE()";
}

$sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stmt = $conn->query("SELECT COUNT(*) as total FROM customers");
$total_customers = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM customers WHERE DATE(created_at) = CURDATE()");
$new_today = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Quản lý khách hàng</h1>
            <div class="header-actions">
                <button onclick="exportCustomers()" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Xuất Excel
                </button>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Thống kê - Style giống trang giảm giá -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <a href="customers.php" style="text-decoration: none;">
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo number_format($total_customers); ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Tổng khách hàng</p>
                    </div>
                </div>
            </a>
            
            <a href="customers.php?today=1" style="text-decoration: none;">
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f59e0b';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); flex-shrink: 0;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo number_format($new_today); ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Đăng ký hôm nay</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Tìm kiếm và lọc -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="search-box" style="display: flex; align-items: center; gap: 16px; padding: 0 20px;">
                    <i class="fas fa-search" style="color: #6b7280; font-size: 1rem; flex-shrink: 0;"></i>
                    <input type="text" name="search" placeholder="Tìm theo tên, email, SĐT..." value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 0 !important; margin-left: 0 !important;">
                </div>
                
                <select name="status" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="blocked" <?php echo $status_filter == 'blocked' ? 'selected' : ''; ?>>Đã khóa</option>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Lọc
                </button>
                
                <?php if ($search || $status_filter): ?>
                    <a href="customers.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bảng khách hàng -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Tổng đơn</th>
                        <th>Tổng chi tiêu</th>
                        <th>Ngày đăng ký</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Không có khách hàng nào</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>#<?php echo $customer['id']; ?></td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                                    </div>
                                    <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></td>
                            <td><span class="badge badge-info"><?php echo $customer['total_orders']; ?> đơn</span></td>
                            <td><strong><?php echo number_format($customer['total_spent'], 0, ',', '.'); ?>đ</strong></td>
                            <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                            <td>
                                <?php 
                                $status = $customer['status'] ?? 'active';
                                $statusClass = $status == 'active' ? 'success' : 'danger';
                                $statusText = $status == 'active' ? 'Hoạt động' : 'Đã khóa';
                                ?>
                                <span class="badge badge-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="customer_detail.php?id=<?php echo $customer['id']; ?>" class="btn-icon" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $status; ?>">
                                        <button type="submit" name="toggle_status" class="btn-icon" title="<?php echo $status == 'active' ? 'Khóa' : 'Mở khóa'; ?>">
                                            <i class="fas fa-<?php echo $status == 'active' ? 'lock' : 'unlock'; ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <a href="?delete=<?php echo $customer['id']; ?>" 
                                       class="btn-icon btn-danger" 
                                       onclick="return confirm('Bạn có chắc muốn xóa khách hàng này?')"
                                       title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function exportCustomers() {
        // Tạo CSV từ dữ liệu khách hàng
        const customers = <?php echo json_encode($customers); ?>;
        
        if (customers.length === 0) {
            alert('Không có dữ liệu để xuất');
            return;
        }
        
        // Header CSV
        let csv = 'ID,Họ tên,Email,Số điện thoại,Tổng đơn,Tổng chi tiêu,Ngày đăng ký,Trạng thái\n';
        
        // Dữ liệu
        customers.forEach(c => {
            csv += `${c.id},"${c.full_name}","${c.email}","${c.phone || ''}",${c.total_orders},${c.total_spent},"${c.created_at}","${c.status || 'active'}"\n`;
        });
        
        // Tạo và download file
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'khach_hang_' + new Date().toISOString().slice(0,10) + '.csv';
        link.click();
        
        alert('✅ Đã xuất file CSV thành công!');
    }
    </script>
</body>
</html>
