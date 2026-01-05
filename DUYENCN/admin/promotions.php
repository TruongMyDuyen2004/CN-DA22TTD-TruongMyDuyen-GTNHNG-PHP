<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Kiểm tra và tạo bảng promotions nếu chưa có
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS promotions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            discount_type ENUM('percent', 'fixed') DEFAULT 'percent',
            discount_value DECIMAL(10,2) NOT NULL,
            min_order_value DECIMAL(10,2) DEFAULT 0,
            max_discount DECIMAL(10,2) DEFAULT NULL,
            usage_limit INT DEFAULT NULL,
            used_count INT DEFAULT 0,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    // Bảng đã tồn tại
}

// Xử lý AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discount_type = $_POST['discount_type'] ?? 'percent';
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $min_order_value = floatval($_POST['min_order_value'] ?? 0);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if (empty($code) || empty($name) || $discount_value <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
            exit;
        }
        
        // Kiểm tra mã đã tồn tại
        $stmt = $conn->prepare("SELECT id FROM promotions WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi đã tồn tại']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO promotions (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $min_order_value, $max_discount, $usage_limit, $start_date, $end_date]);
            echo json_encode(['success' => true, 'message' => 'Thêm khuyến mãi thành công']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discount_type = $_POST['discount_type'] ?? 'percent';
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $min_order_value = floatval($_POST['min_order_value'] ?? 0);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $conn->prepare("
                UPDATE promotions SET code = ?, name = ?, description = ?, discount_type = ?, discount_value = ?, 
                min_order_value = ?, max_discount = ?, usage_limit = ?, start_date = ?, end_date = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $min_order_value, $max_discount, $usage_limit, $start_date, $end_date, $is_active, $id]);
            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("DELETE FROM promotions WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Xóa thành công']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("UPDATE promotions SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Lấy danh sách khuyến mãi
$stmt = $conn->query("SELECT * FROM promotions ORDER BY created_at DESC");
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$active_count = 0;
$expired_count = 0;
$now = new DateTime();

foreach ($promotions as $promo) {
    $end = new DateTime($promo['end_date']);
    if ($promo['is_active'] && $end >= $now) {
        $active_count++;
    } else {
        $expired_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khuyến mãi - Admin</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-tags"></i> Quản lý khuyến mãi</h1>
            <button onclick="showAddModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm khuyến mãi
            </button>
        </div>

        <!-- Thống kê -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $active_count; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Đang hoạt động</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f59e0b';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); flex-shrink: 0;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $expired_count; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Hết hạn/Tắt</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#6366f1';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); flex-shrink: 0;">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo count($promotions); ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Tổng khuyến mãi</p>
                </div>
            </div>
        </div>

        <!-- Danh sách -->
        <div class="table-container" style="background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #e2e8f0;">
            <div style="padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-list" style="color: #22c55e;"></i> Danh sách khuyến mãi
                </h3>
                <span style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 0.35rem 0.85rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                    <?php echo count($promotions); ?> mã
                </span>
            </div>
            <div style="overflow-x: auto;">
                <table class="modern-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 1rem; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: left;">Mã</th>
                                <th style="padding: 1rem; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: left;">Tên chương trình</th>
                                <th style="padding: 1rem; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: center;">Giảm giá</th>
                                <th style="padding: 1rem; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: center;">Đơn tối thiểu</th>
                                <th style="padding: 1rem; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: center;">Sử dụng</th>
                                <th style="padding: 1rem; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: center;">Thời gian</th>
                                <th style="padding: 1rem; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: center;">Trạng thái</th>
                                <th style="padding: 1rem; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($promotions) > 0): ?>
                                <?php foreach ($promotions as $promo): 
                                    $end = new DateTime($promo['end_date']);
                                    $is_expired = $end < $now;
                                    $status_class = $promo['is_active'] && !$is_expired ? 'success' : 'danger';
                                    $status_text = $is_expired ? 'Hết hạn' : ($promo['is_active'] ? 'Hoạt động' : 'Tắt');
                                ?>
                                <tr style="transition: all 0.2s; border-bottom: 1px solid #f1f5f9;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='white'">
                                    <td style="padding: 1rem;">
                                        <code style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 0.4rem 0.8rem; border-radius: 8px; font-weight: 700; color: #92400e; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($promo['code']); ?>
                                        </code>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($promo['name']); ?></div>
                                        <?php if ($promo['description']): ?>
                                        <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 2px;"><?php echo htmlspecialchars(substr($promo['description'], 0, 50)); ?>...</div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <span style="color: #dc2626; font-weight: 800; font-size: 1.1rem;">
                                            <?php if ($promo['discount_type'] === 'percent'): ?>
                                                -<?php echo intval($promo['discount_value']); ?>%
                                            <?php else: ?>
                                                -<?php echo number_format($promo['discount_value'], 0, ',', '.'); ?>đ
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($promo['max_discount']): ?>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">Tối đa: <?php echo number_format($promo['max_discount'], 0, ',', '.'); ?>đ</div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center; font-weight: 600; color: #374151;"><?php echo number_format($promo['min_order_value'], 0, ',', '.'); ?>đ</td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <span style="background: #dbeafe; color: #1d4ed8; padding: 0.3rem 0.7rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                            <?php echo $promo['used_count']; ?>/<?php echo $promo['usage_limit'] ?? '∞'; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; text-align: center; font-size: 0.8rem; color: #64748b;">
                                        <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?><br>
                                        <span style="color: #22c55e;">→</span> <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <?php if ($promo['is_active'] && !$is_expired): ?>
                                            <span style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #15803d; padding: 0.4rem 0.85rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.3rem;">
                                                <i class="fas fa-check-circle"></i> Hoạt động
                                            </span>
                                        <?php elseif ($is_expired): ?>
                                            <span style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; padding: 0.4rem 0.85rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.3rem;">
                                                <i class="fas fa-clock"></i> Hết hạn
                                            </span>
                                        <?php else: ?>
                                            <span style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #dc2626; padding: 0.4rem 0.85rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.3rem;">
                                                <i class="fas fa-times-circle"></i> Tắt
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                            <button onclick="togglePromo(<?php echo $promo['id']; ?>)" style="width: 36px; height: 36px; border: none; border-radius: 10px; cursor: pointer; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3); transition: all 0.2s;" title="Bật/Tắt">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <button onclick='editPromo(<?php echo json_encode($promo); ?>)' style="width: 36px; height: 36px; border: none; border-radius: 10px; cursor: pointer; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3); transition: all 0.2s;" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deletePromo(<?php echo $promo['id']; ?>, '<?php echo htmlspecialchars($promo['code'], ENT_QUOTES); ?>')" style="width: 36px; height: 36px; border: none; border-radius: 10px; cursor: pointer; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3); transition: all 0.2s;" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-tags" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                                        <p style="color: #6b7280;">Chưa có khuyến mãi nào</p>
                                        <button onclick="showAddModal()" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer;">Thêm khuyến mãi đầu tiên</button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Thêm khuyến mãi mới</h3>
                <button onclick="closeModal('addModal')" class="close-btn">&times;</button>
            </div>
            <form id="addForm">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Mã khuyến mãi *</label>
                        <input type="text" name="code" required placeholder="VD: SALE20, FREESHIP..." style="text-transform: uppercase;">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Tên chương trình *</label>
                        <input type="text" name="name" required placeholder="VD: Giảm 20% đơn hàng">
                    </div>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" rows="2" placeholder="Mô tả chi tiết khuyến mãi..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Loại giảm giá *</label>
                        <select name="discount_type">
                            <option value="percent">Phần trăm (%)</option>
                            <option value="fixed">Số tiền cố định (VNĐ)</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Giá trị giảm *</label>
                        <input type="number" name="discount_value" required min="0" step="0.01" placeholder="VD: 20 hoặc 50000">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Đơn hàng tối thiểu</label>
                        <input type="number" name="min_order_value" min="0" value="0" placeholder="0">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Giảm tối đa (VNĐ)</label>
                        <input type="number" name="max_discount" min="0" placeholder="Để trống = không giới hạn">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Giới hạn sử dụng</label>
                        <input type="number" name="usage_limit" min="1" placeholder="Để trống = không giới hạn">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Ngày bắt đầu *</label>
                        <input type="datetime-local" name="start_date" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Ngày kết thúc *</label>
                        <input type="datetime-local" name="end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal sửa -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Sửa khuyến mãi</h3>
                <button onclick="closeModal('editModal')" class="close-btn">&times;</button>
            </div>
            <form id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Mã khuyến mãi *</label>
                        <input type="text" name="code" id="edit_code" required style="text-transform: uppercase;">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Tên chương trình *</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" id="edit_description" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Loại giảm giá *</label>
                        <select name="discount_type" id="edit_discount_type">
                            <option value="percent">Phần trăm (%)</option>
                            <option value="fixed">Số tiền cố định (VNĐ)</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Giá trị giảm *</label>
                        <input type="number" name="discount_value" id="edit_discount_value" required min="0" step="0.01">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Đơn hàng tối thiểu</label>
                        <input type="number" name="min_order_value" id="edit_min_order_value" min="0">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Giảm tối đa (VNĐ)</label>
                        <input type="number" name="max_discount" id="edit_max_discount" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Giới hạn sử dụng</label>
                        <input type="number" name="usage_limit" id="edit_usage_limit" min="1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Ngày bắt đầu *</label>
                        <input type="datetime-local" name="start_date" id="edit_start_date" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Ngày kết thúc *</label>
                        <input type="datetime-local" name="end_date" id="edit_end_date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <span>Kích hoạt khuyến mãi</span>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                </div>
            </form>
        </div>
    </div>


    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 2px solid #e5e7eb;
            background: #f8fafc;
        }
        .modal-header h3 { 
            margin: 0; 
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .modal-header h3 i {
            color: #22c55e;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 1.75rem;
            cursor: pointer;
            color: #9ca3af;
            line-height: 1;
        }
        .close-btn:hover {
            color: #ef4444;
        }
        .form-group {
            padding: 0 1.5rem;
            margin-bottom: 1rem;
        }
        .form-row {
            display: flex;
            gap: 1rem;
            padding: 0 1.5rem;
            margin-bottom: 1rem;
        }
        .form-row .form-group {
            padding: 0;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.9rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #d1d5db;
            border-radius: 10px;
            font-size: 0.95rem;
            background: white;
            color: #1f2937;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #22c55e;
        }
        .modal-footer {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            padding: 1.25rem 1.5rem;
            border-top: 2px solid #e5e7eb;
            background: #f8fafc;
        }
        .modal-footer .btn {
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .modal-footer .btn-secondary {
            background: white;
            border: 2px solid #d1d5db;
            color: #6b7280;
        }
        .modal-footer .btn-primary {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border: none;
            color: white;
        }
        .modal-footer .btn-primary:hover {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        }
    </style>

    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'flex';
            document.getElementById('addForm').reset();
            // Set default dates
            const now = new Date();
            const nextMonth = new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000);
            document.querySelector('#addForm [name="start_date"]').value = now.toISOString().slice(0, 16);
            document.querySelector('#addForm [name="end_date"]').value = nextMonth.toISOString().slice(0, 16);
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        
        function editPromo(promo) {
            document.getElementById('edit_id').value = promo.id;
            document.getElementById('edit_code').value = promo.code;
            document.getElementById('edit_name').value = promo.name;
            document.getElementById('edit_description').value = promo.description || '';
            document.getElementById('edit_discount_type').value = promo.discount_type;
            document.getElementById('edit_discount_value').value = promo.discount_value;
            document.getElementById('edit_min_order_value').value = promo.min_order_value;
            document.getElementById('edit_max_discount').value = promo.max_discount || '';
            document.getElementById('edit_usage_limit').value = promo.usage_limit || '';
            document.getElementById('edit_start_date').value = promo.start_date.replace(' ', 'T');
            document.getElementById('edit_end_date').value = promo.end_date.replace(' ', 'T');
            document.getElementById('edit_is_active').checked = promo.is_active == 1;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function deletePromo(id, code) {
            if (confirm(`Bạn có chắc muốn xóa khuyến mãi "${code}"?`)) {
                submitAction({ action: 'delete', id: id });
            }
        }
        
        function togglePromo(id) {
            submitAction({ action: 'toggle', id: id });
        }
        
        async function submitAction(data) {
            const formData = new FormData();
            for (let key in data) {
                formData.append(key, data[key]);
            }
            
            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                alert('❌ Lỗi: ' + error.message);
            }
        }
        
        document.getElementById('addForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                alert('❌ Lỗi: ' + error.message);
            }
        });
        
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                alert('❌ Lỗi: ' + error.message);
            }
        });
    </script>
</body>
</html>
