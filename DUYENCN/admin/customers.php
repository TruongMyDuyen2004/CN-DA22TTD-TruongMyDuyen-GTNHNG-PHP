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

$stmt = $conn->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active' OR status IS NULL");
$active_customers = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM customers WHERE status = 'blocked'");
$blocked_customers = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng - Admin</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    * { box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; }
    .main-content { background: #f8fafc !important; padding: 24px; min-height: 100vh; }
    
    /* Page Header */
    .page-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 28px; padding: 24px 28px;
        background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        border-radius: 20px; border: 2px solid #bbf7d0;
        box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1);
    }
    .page-header h1 {
        font-size: 28px; font-weight: 800; color: #166534; margin: 0;
        display: flex; align-items: center; gap: 14px;
    }
    .page-header h1 i {
        font-size: 32px; color: #22c55e;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        padding: 14px; border-radius: 16px;
    }
    .btn-export {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: #fff !important; padding: 14px 28px; border-radius: 12px;
        border: none; cursor: pointer; font-weight: 700; font-size: 15px;
        display: flex; align-items: center; gap: 10px;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
        transition: all 0.3s ease;
    }
    .btn-export:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(34, 197, 94, 0.45); }

    /* Stats Grid */
    .stats-grid {
        display: flex !important; 
        justify-content: center !important; 
        gap: 20px !important;
        margin-bottom: 28px !important; 
        flex-wrap: wrap !important;
        grid-template-columns: unset !important;
    }
    .stat-card {
        background: #ffffff !important; 
        border-radius: 20px !important; 
        padding: 24px 32px !important;
        border: 2px solid #e5e7eb !important; 
        min-width: 200px !important; 
        max-width: 240px !important;
        display: flex !important; 
        flex-direction: column !important;
        align-items: center !important; 
        justify-content: center !important;
        text-align: center !important;
        gap: 16px !important;
        text-decoration: none !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important; 
        overflow: hidden !important;
        flex: 0 0 auto !important;
    }
    .stat-card::before {
        content: '' !important; 
        position: absolute !important; 
        top: 0 !important; 
        left: 0 !important; 
        right: 0 !important; 
        height: 4px !important;
        border-radius: 20px 20px 0 0 !important;
    }
    .stat-card:hover { transform: translateY(-6px) !important; box-shadow: 0 12px 35px rgba(0,0,0,0.12) !important; }
    .stat-card.green::before { background: linear-gradient(90deg, #22c55e, #4ade80) !important; }
    .stat-card.green:hover { border-color: #22c55e !important; }
    .stat-card.orange::before { background: linear-gradient(90deg, #f59e0b, #fbbf24) !important; }
    .stat-card.orange:hover { border-color: #f59e0b !important; }
    .stat-card.blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa) !important; }
    .stat-card.blue:hover { border-color: #3b82f6 !important; }
    .stat-card.red::before { background: linear-gradient(90deg, #ef4444, #f87171) !important; }
    .stat-card.red:hover { border-color: #ef4444 !important; }
    
    .stat-icon {
        width: 60px !important; 
        height: 60px !important; 
        border-radius: 16px !important;
        display: flex !important; 
        align-items: center !important; 
        justify-content: center !important;
        font-size: 26px !important; 
        color: #fff !important; 
        flex-shrink: 0 !important;
        margin: 0 auto !important;
    }
    .stat-card.green .stat-icon { background: linear-gradient(135deg, #22c55e, #16a34a) !important; }
    .stat-card.orange .stat-icon { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
    .stat-card.blue .stat-icon { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; }
    .stat-card.red .stat-icon { background: linear-gradient(135deg, #ef4444, #dc2626) !important; }
    
    .stat-info { text-align: center !important; width: 100% !important; }
    .stat-info h3 { font-size: 32px !important; font-weight: 800 !important; color: #1f2937 !important; margin: 0 !important; line-height: 1 !important; text-align: center !important; }
    .stat-info p { font-size: 14px !important; color: #6b7280 !important; margin: 6px 0 0 !important; font-weight: 600 !important; text-align: center !important; }

    /* Filter Section */
    .filter-section {
        background: #ffffff; border-radius: 20px; padding: 24px 28px;
        margin-bottom: 28px; border: 2px solid #e5e7eb;
        box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    }
    .filter-form {
        display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;
    }
    .filter-group { flex: 1; min-width: 200px; }
    .filter-group label {
        display: block; font-size: 13px; font-weight: 700; color: #374151;
        margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
    }
    .filter-group label i { color: #22c55e; }
    .filter-group input, .filter-group select {
        width: 100%; padding: 14px 18px; border: 2px solid #e5e7eb;
        border-radius: 12px; font-size: 15px; color: #1f2937;
        background: #f9fafb; transition: all 0.3s ease;
    }
    .filter-group input:focus, .filter-group select:focus {
        border-color: #22c55e; outline: none;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
    }
    .filter-actions { display: flex; gap: 12px; }
    .btn-search {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: #fff; padding: 14px 24px; border-radius: 12px;
        border: none; cursor: pointer; font-weight: 700; font-size: 15px;
        display: flex; align-items: center; gap: 8px;
        transition: all 0.3s ease;
    }
    .btn-search:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4); }
    .btn-clear {
        background: #f3f4f6; color: #6b7280; padding: 14px 24px;
        border-radius: 12px; border: 2px solid #e5e7eb;
        text-decoration: none; font-weight: 600; font-size: 15px;
        display: flex; align-items: center; gap: 8px;
        transition: all 0.3s ease;
    }
    .btn-clear:hover { background: #e5e7eb; color: #374151 !important; }

    /* Alert */
    .alert {
        padding: 16px 20px; border-radius: 14px; margin-bottom: 20px;
        font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 12px;
    }
    .alert-success { background: #dcfce7; color: #166534; border: 2px solid #86efac; }
    .alert-danger { background: #fee2e2; color: #991b1b; border: 2px solid #fca5a5; }

    /* Table */
    .table-container {
        background: #ffffff; border-radius: 20px; overflow: hidden;
        border: 2px solid #e5e7eb; box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
    .data-table th {
        padding: 18px 16px; text-align: left; font-size: 13px;
        font-weight: 700; color: #374151; text-transform: uppercase;
        letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;
    }
    .data-table td {
        padding: 18px 16px; font-size: 15px; color: #4b5563;
        border-bottom: 1px solid #f1f5f9; vertical-align: middle;
    }
    .data-table tbody tr { transition: all 0.2s ease; }
    .data-table tbody tr:hover { background: #f0fdf4; }
    .data-table tbody tr:last-child td { border-bottom: none; }

    /* Customer Avatar */
    .customer-cell { display: flex; align-items: center; gap: 14px; }
    .customer-avatar {
        width: 48px; height: 48px; border-radius: 14px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-size: 18px; font-weight: 700; flex-shrink: 0;
    }
    .customer-name { font-weight: 700; color: #1f2937; font-size: 15px; }
    .customer-id { font-size: 12px; color: #9ca3af; margin-top: 2px; }

    /* Badges */
    .badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 14px; border-radius: 10px;
        font-size: 13px; font-weight: 700;
    }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    .badge-warning { background: #fef3c7; color: #92400e; }

    .price-tag {
        font-weight: 800; color: #059669; font-size: 15px;
        background: #ecfdf5; padding: 8px 14px; border-radius: 10px;
        display: inline-block;
    }

    /* Action Buttons */
    .action-buttons { display: flex; gap: 8px; }
    .btn-action {
        width: 42px; height: 42px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        border: none; cursor: pointer; font-size: 16px;
        transition: all 0.3s ease;
    }
    .btn-action:hover { transform: scale(1.1); }
    .btn-view { background: #dbeafe; color: #2563eb; }
    .btn-view:hover { background: #3b82f6; color: #fff; }
    .btn-lock { background: #fef3c7; color: #d97706; }
    .btn-lock:hover { background: #f59e0b; color: #fff; }
    .btn-delete { background: #fee2e2; color: #dc2626; }
    .btn-delete:hover { background: #ef4444; color: #fff; }

    /* Empty State */
    .empty-state {
        text-align: center; padding: 60px 20px; color: #9ca3af;
    }
    .empty-state i { font-size: 64px; margin-bottom: 20px; color: #d1d5db; }
    .empty-state strong { display: block; font-size: 20px; color: #6b7280; margin-bottom: 8px; }
    .empty-state p { font-size: 15px; }

    @media (max-width: 768px) {
        .stats-grid { flex-direction: column; align-items: stretch; }
        .stat-card { min-width: auto; }
        .filter-form { flex-direction: column; }
        .filter-group { min-width: auto; }
        .page-header { flex-direction: column; gap: 16px; text-align: center; }
    }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Quản lý khách hàng</h1>
            <button onclick="exportCustomers()" class="btn-export">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <a href="customers.php" class="stat-card green">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_customers); ?></h3>
                    <p>Tổng khách hàng</p>
                </div>
            </a>
            <a href="customers.php?today=1" class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($new_today); ?></h3>
                    <p>Đăng ký hôm nay</p>
                </div>
            </a>
            <a href="customers.php?status=active" class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($active_customers); ?></h3>
                    <p>Đang hoạt động</p>
                </div>
            </a>
            <a href="customers.php?status=blocked" class="stat-card red">
                <div class="stat-icon"><i class="fas fa-user-lock"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($blocked_customers); ?></h3>
                    <p>Đã khóa</p>
                </div>
            </a>
        </div>

        <!-- Filter -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Tìm kiếm</label>
                    <input type="text" name="search" placeholder="Nhập tên, email hoặc SĐT..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group" style="max-width: 220px;">
                    <label><i class="fas fa-filter"></i> Trạng thái</label>
                    <select name="status">
                        <option value="">Tất cả</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="blocked" <?php echo $status_filter == 'blocked' ? 'selected' : ''; ?>>Đã khóa</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i> Tìm kiếm</button>
                    <?php if ($search || $status_filter || $today_filter): ?>
                        <a href="customers.php" class="btn-clear"><i class="fas fa-times"></i> Xóa lọc</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Khách hàng</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Đơn hàng</th>
                        <th>Chi tiêu</th>
                        <th>Ngày đăng ký</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <strong>Không tìm thấy khách hàng</strong>
                                    <p>Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>
                                <div class="customer-cell">
                                    <div class="customer-avatar">
                                        <?php echo strtoupper(mb_substr($customer['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="customer-name"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                                        <div class="customer-id">#<?php echo $customer['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone'] ?? '—'); ?></td>
                            <td><span class="badge badge-info"><i class="fas fa-shopping-bag"></i> <?php echo $customer['total_orders']; ?></span></td>
                            <td><span class="price-tag"><?php echo number_format($customer['total_spent'], 0, ',', '.'); ?>đ</span></td>
                            <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                            <td>
                                <?php $status = $customer['status'] ?? 'active'; ?>
                                <?php if ($status == 'active'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-ban"></i> Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="customer_detail.php?id=<?php echo $customer['id']; ?>" class="btn-action btn-view" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $status; ?>">
                                        <button type="submit" name="toggle_status" class="btn-action btn-lock" title="<?php echo $status == 'active' ? 'Khóa tài khoản' : 'Mở khóa'; ?>">
                                            <i class="fas fa-<?php echo $status == 'active' ? 'lock' : 'unlock'; ?>"></i>
                                        </button>
                                    </form>
                                    <a href="?delete=<?php echo $customer['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Xác nhận xóa khách hàng này?')" title="Xóa">
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
        const customers = <?php echo json_encode($customers); ?>;
        if (customers.length === 0) { alert('Không có dữ liệu để xuất'); return; }
        
        let csv = 'ID,Họ tên,Email,Số điện thoại,Tổng đơn,Tổng chi tiêu,Ngày đăng ký,Trạng thái\n';
        customers.forEach(c => {
            csv += `${c.id},"${c.full_name}","${c.email}","${c.phone || ''}",${c.total_orders},${c.total_spent},"${c.created_at}","${c.status || 'active'}"\n`;
        });
        
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'khach_hang_' + new Date().toISOString().slice(0,10) + '.csv';
        link.click();
        alert('✅ Đã xuất file thành công!');
    }
    </script>
</body>
</html>
