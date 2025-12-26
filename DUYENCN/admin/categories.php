<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý thêm danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO categories (name, name_en, display_order) VALUES (?, ?, ?)");
            $stmt->execute([$name, $name_en, $display_order]);
            echo json_encode(['success' => true, 'message' => 'Thêm danh mục thành công']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        
        if (empty($name) || $id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, name_en = ?, display_order = ? WHERE id = ?");
            $stmt->execute([$name, $name_en, $display_order, $id]);
            echo json_encode(['success' => true, 'message' => 'Cập nhật danh mục thành công']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        // Kiểm tra có món ăn trong danh mục không
        $stmt = $conn->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => "Không thể xóa! Danh mục này có $count món ăn"]);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Xóa danh mục thành công']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Lấy danh sách danh mục với số lượng món
$stmt = $conn->query("
    SELECT c.*, COUNT(m.id) as item_count 
    FROM categories c 
    LEFT JOIN menu_items m ON c.id = m.category_id 
    GROUP BY c.id 
    ORDER BY c.display_order, c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-folder-open"></i> Quản lý danh mục món ăn</h1>
            <button onclick="showAddModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm danh mục
            </button>
        </div>

        <!-- Thống kê -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <a href="categories.php" style="text-decoration: none; background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#3b82f6';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); flex-shrink: 0;">
                    <i class="fas fa-folder"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo count($categories); ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Tổng danh mục</p>
                </div>
            </a>
            <a href="menu-manage.php" style="text-decoration: none; background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo array_sum(array_column($categories, 'item_count')); ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Tổng món ăn</p>
                </div>
            </a>
        </div>

        <!-- Danh sách danh mục dạng bảng hiện đại -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Danh sách danh mục</h3>
                <span class="badge"><?php echo count($categories); ?> danh mục</span>
            </div>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">#</th>
                        <th style="width: 40%;">Tên danh mục</th>
                        <th style="width: 20%;">Số món</th>
                        <th style="width: 20%;">Thứ tự</th>
                        <th style="width: 130px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $index => $cat): ?>
                    <tr>
                        <td>
                            <span class="row-number"><?php echo $index + 1; ?></span>
                        </td>
                        <td>
                            <div class="category-info">
                                <div class="category-icon">
                                    <i class="fas fa-folder"></i>
                                </div>
                                <div>
                                    <div class="category-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                                    <div class="category-name-en"><?php echo htmlspecialchars($cat['name_en'] ?? '—'); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="item-count"><?php echo $cat['item_count']; ?> món</span>
                        </td>
                        <td>
                            <span class="order-badge"><?php echo $cat['display_order']; ?></span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button onclick='editCategory(<?php echo json_encode($cat); ?>)' class="btn-action btn-edit" title="Sửa">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button onclick="deleteCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>')" class="btn-action btn-delete" title="Xóa">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal thêm danh mục -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Thêm danh mục mới</h3>
                <button onclick="closeModal('addModal')" class="close-btn">&times;</button>
            </div>
            <form id="addForm">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Tên danh mục (Tiếng Việt) *</label>
                    <input type="text" name="name" required placeholder="VD: Khai vị, Món chính...">
                </div>
                <div class="form-group">
                    <label>Tên danh mục (Tiếng Anh)</label>
                    <input type="text" name="name_en" placeholder="VD: Appetizers, Main dishes...">
                </div>
                <div class="form-group">
                    <label>Thứ tự hiển thị</label>
                    <input type="number" name="display_order" value="0" min="0">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal sửa danh mục -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Sửa danh mục</h3>
                <button onclick="closeModal('editModal')" class="close-btn">&times;</button>
            </div>
            <form id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Tên danh mục (Tiếng Việt) *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Tên danh mục (Tiếng Anh)</label>
                    <input type="text" name="name_en" id="edit_name_en">
                </div>
                <div class="form-group">
                    <label>Thứ tự hiển thị</label>
                    <input type="number" name="display_order" id="edit_display_order" min="0">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                </div>
            </form>
        </div>
    </div>


    <style>
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .table-header {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .table-header h3 i {
            color: #22c55e;
        }
        .table-header .badge {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Modern Table */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }
        .modern-table thead tr {
            background: #f8fafc;
        }
        .modern-table th {
            padding: 1rem 1.25rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            text-align: center;
        }
        .modern-table th:nth-child(2) {
            text-align: left;
        }
        .modern-table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
        }
        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        .modern-table td {
            padding: 1rem 1.25rem;
            vertical-align: middle;
            text-align: center;
        }
        .modern-table td:nth-child(2) {
            text-align: left;
        }
        
        /* Table Layout */
        .modern-table {
            table-layout: fixed;
        }
        
        /* Row Number */
        .row-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        
        /* Category Info */
        .category-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .category-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.1rem;
        }
        .category-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.95rem;
            margin-bottom: 2px;
        }
        .category-name-en {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        
        /* Item Count */
        .item-count {
            display: inline-block;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Order Badge */
        .order-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            border: 2px solid #e2e8f0;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.85rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.active {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #15803d;
        }
        .status-badge.inactive {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        .btn-action {
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .btn-edit {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
        }
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
        }
        .btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        
        /* Modal Styles */
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
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.9rem;
        }
        .form-group input,
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
            background: #22c55e;
            border: none;
            color: white;
        }
        .modal-footer .btn-primary:hover {
            background: #16a34a;
        }

    </style>

    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'flex';
            document.getElementById('addForm').reset();
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        
        function editCategory(cat) {
            document.getElementById('edit_id').value = cat.id;
            document.getElementById('edit_name').value = cat.name;
            document.getElementById('edit_name_en').value = cat.name_en || '';
            document.getElementById('edit_display_order').value = cat.display_order || 0;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function deleteCategory(id, name) {
            if (confirm(`Bạn có chắc muốn xóa danh mục "${name}"?`)) {
                submitAction('delete', { id: id });
            }
        }
        
        async function submitAction(action, data) {
            const formData = new FormData();
            formData.append('action', action);
            for (let key in data) {
                formData.append(key, data[key]);
            }
            
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
