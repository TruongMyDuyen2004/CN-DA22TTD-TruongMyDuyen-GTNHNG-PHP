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
        
        .stock-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }
        .stock-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s;
            position: relative;
            border: 1px solid #e5e7eb;
        }
        .stock-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            border-color: #22c55e;
        }
        .stock-card.unavailable {
            opacity: 0.75;
            border-color: #fca5a5;
        }
        .stock-card.unavailable .card-image img {
            filter: grayscale(50%);
        }
        .card-checkbox {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 10;
        }
        .card-checkbox input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #22c55e;
        }
        .card-image {
            height: 140px;
            overflow: hidden;
            position: relative;
        }
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .stock-card:hover .card-image img {
            transform: scale(1.05);
        }
        .card-image .placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        .status-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.available {
            background: #10b981;
            color: white;
        }
        .status-badge.unavailable {
            background: #ef4444;
            color: white;
        }
        .card-body {
            padding: 12px 14px;
        }
        .card-category {
            font-size: 0.75rem;
            color: #16a34a;
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .card-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-price {
            font-size: 1rem;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 10px;
        }
        
        /* Toggle Section - Gọn gàng */
        .toggle-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .toggle-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .toggle-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .toggle-icon.on {
            background: #dcfce7;
            color: #16a34a;
        }
        .toggle-icon.off {
            background: #fee2e2;
            color: #dc2626;
        }
        .toggle-text {
            font-size: 0.85rem;
            font-weight: 600;
        }
        .toggle-text.on {
            color: #16a34a;
        }
        .toggle-text.off {
            color: #dc2626;
        }
        .switch {
            position: relative;
            width: 44px;
            height: 24px;
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
            transition: 0.3s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        input:checked + .slider {
            background: #10b981;
        }
        input:checked + .slider:before {
            transform: translateX(20px);
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
        <div class="page-header">
            <h1><i class="fas fa-boxes"></i> Quản lý tồn kho</h1>
            <a href="menu-manage.php" class="btn btn-secondary">
                <i class="fas fa-utensils"></i> Quản lý thực đơn
            </a>
        </div>

        <!-- Thống kê - Style giống trang giảm giá -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#3b82f6';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); flex-shrink: 0;">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Tổng số món</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $available; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Còn món</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#ef4444';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); flex-shrink: 0;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $unavailable; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Hết món</p>
                </div>
            </div>
        </div>

        <!-- Bộ lọc & Bulk actions -->
        <div class="filter-bar">
            <select id="categoryFilter" onchange="applyFilter()">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <select id="statusFilter" onchange="applyFilter()">
                <option value="">Tất cả trạng thái</option>
                <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>✅ Còn món</option>
                <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>❌ Hết món</option>
            </select>
            
            <div class="bulk-actions">
                <span id="selectedCount" style="color: #6b7280; font-size: 0.9rem;">Đã chọn: 0</span>
                <button class="bulk-btn available" onclick="bulkUpdate(1)" id="btnAvailable" disabled>
                    <i class="fas fa-check"></i> Đánh dấu còn món
                </button>
                <button class="bulk-btn unavailable" onclick="bulkUpdate(0)" id="btnUnavailable" disabled>
                    <i class="fas fa-times"></i> Đánh dấu hết món
                </button>
            </div>
        </div>

        <!-- Danh sách món -->
        <?php if (count($items) > 0): ?>
        <div class="stock-grid">
            <?php foreach ($items as $item): ?>
            <div class="stock-card <?php echo !$item['is_available'] ? 'unavailable' : ''; ?>" data-id="<?php echo $item['id']; ?>">
                <div class="card-checkbox">
                    <input type="checkbox" class="item-checkbox" value="<?php echo $item['id']; ?>" onchange="updateSelection()">
                </div>
                <div class="card-image">
                    <?php if ($item['image']): ?>
                        <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php else: ?>
                        <div class="placeholder">🍽️</div>
                    <?php endif; ?>
                    <span class="status-badge <?php echo $item['is_available'] ? 'available' : 'unavailable'; ?>">
                        <?php echo $item['is_available'] ? 'Còn món' : 'Hết món'; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="card-category"><?php echo htmlspecialchars($item['category_name'] ?? 'Chưa phân loại'); ?></div>
                    <h3 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <div class="card-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</div>
                    <div class="toggle-section">
                        <div class="toggle-row">
                            <div class="toggle-left" id="toggle-info-<?php echo $item['id']; ?>">
                                <div class="toggle-icon <?php echo $item['is_available'] ? 'on' : 'off'; ?>">
                                    <i class="fas <?php echo $item['is_available'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                </div>
                                <span class="toggle-text <?php echo $item['is_available'] ? 'on' : 'off'; ?>">
                                    <?php echo $item['is_available'] ? 'Còn món' : 'Hết món'; ?>
                                </span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" <?php echo $item['is_available'] ? 'checked' : ''; ?> 
                                       onchange="toggleStatus(<?php echo $item['id']; ?>, this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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
                    const card = document.querySelector(`.stock-card[data-id="${id}"]`);
                    const badge = card.querySelector('.status-badge');
                    const toggleInfo = document.getElementById(`toggle-info-${id}`);
                    
                    if (status) {
                        card.classList.remove('unavailable');
                        badge.className = 'status-badge available';
                        badge.textContent = 'Còn món';
                        toggleInfo.innerHTML = '<div class="toggle-icon on"><i class="fas fa-check"></i></div><span class="toggle-text on">Còn món</span>';
                    } else {
                        card.classList.add('unavailable');
                        badge.className = 'status-badge unavailable';
                        badge.textContent = 'Hết món';
                        toggleInfo.innerHTML = '<div class="toggle-icon off"><i class="fas fa-times"></i></div><span class="toggle-text off">Hết món</span>';
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
            document.getElementById('btnAvailable').disabled = count === 0;
            document.getElementById('btnUnavailable').disabled = count === 0;
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
