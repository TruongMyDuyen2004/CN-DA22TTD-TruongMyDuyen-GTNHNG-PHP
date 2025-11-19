<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý bộ lọc
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'name';

// Lấy danh mục
$stmt = $conn->query("SELECT * FROM categories ORDER BY display_order");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xây dựng query
$sql = "SELECT m.*, c.name as category_name 
        FROM menu_items m 
        LEFT JOIN categories c ON m.category_id = c.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (m.name LIKE ? OR m.name_en LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $sql .= " AND m.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter !== '') {
    $sql .= " AND m.is_available = ?";
    $params[] = $status_filter;
}

// Lọc theo loại món (dựa vào tên danh mục)
if ($type_filter) {
    switch ($type_filter) {
        case 'main':
            $sql .= " AND (c.name LIKE '%chính%' OR c.name LIKE '%cơm%' OR c.name LIKE '%phở%' OR c.name LIKE '%bún%')";
            break;
        case 'side':
            $sql .= " AND (c.name LIKE '%phụ%' OR c.name LIKE '%khai vị%' OR c.name LIKE '%salad%')";
            break;
        case 'drink':
            $sql .= " AND (c.name LIKE '%uống%' OR c.name LIKE '%nước%' OR c.name LIKE '%trà%' OR c.name LIKE '%cà phê%')";
            break;
        case 'dessert':
            $sql .= " AND (c.name LIKE '%tráng miệng%' OR c.name LIKE '%bánh%' OR c.name LIKE '%chè%')";
            break;
    }
}

// Sắp xếp
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY m.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY m.price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY m.id DESC";
        break;
    default: // name
        $sql .= " ORDER BY c.display_order, m.name";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$total = count($menu_items);
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
    <style>
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        .filter-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
    </style>
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
                <button onclick="showAddModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Thêm món mới
                </button>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-section">
            <h3 style="margin-bottom: 1rem; color: #1f2937;">
                <i class="fas fa-filter"></i> Bộ lọc tìm kiếm
            </h3>
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Tìm kiếm theo tên</label>
                        <input type="text" 
                               name="search" 
                               placeholder="Nhập tên món ăn (VD: Bánh xèo, Phở, Cơm...)" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               style="grid-column: span 2;">
                    </div>
                </div>
                
                <div class="filter-grid" style="grid-template-columns: repeat(4, 1fr); margin-top: 1rem;">
                    <div class="filter-group">
                        <label><i class="fas fa-utensils"></i> Loại món</label>
                        <select name="category">
                            <option value="">-- Tất cả loại --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> Phân loại</label>
                        <select name="type">
                            <option value="">-- Tất cả --</option>
                            <option value="main" <?php echo ($_GET['type'] ?? '') == 'main' ? 'selected' : ''; ?>>🍽️ Món chính</option>
                            <option value="side" <?php echo ($_GET['type'] ?? '') == 'side' ? 'selected' : ''; ?>>🥗 Món phụ</option>
                            <option value="drink" <?php echo ($_GET['type'] ?? '') == 'drink' ? 'selected' : ''; ?>>🥤 Đồ uống</option>
                            <option value="dessert" <?php echo ($_GET['type'] ?? '') == 'dessert' ? 'selected' : ''; ?>>🍰 Tráng miệng</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-toggle-on"></i> Trạng thái</label>
                        <select name="status">
                            <option value="">-- Tất cả --</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>✅ Còn món</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>❌ Hết món</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-sort-amount-down"></i> Sắp xếp</label>
                        <select name="sort">
                            <option value="name" <?php echo ($_GET['sort'] ?? '') == 'name' ? 'selected' : ''; ?>>Tên A-Z</option>
                            <option value="price_asc" <?php echo ($_GET['sort'] ?? '') == 'price_asc' ? 'selected' : ''; ?>>Giá thấp → cao</option>
                            <option value="price_desc" <?php echo ($_GET['sort'] ?? '') == 'price_desc' ? 'selected' : ''; ?>>Giá cao → thấp</option>
                            <option value="newest" <?php echo ($_GET['sort'] ?? '') == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Đặt lại
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                </div>
            </form>
        </div>

        <!-- Kết quả -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Danh sách món ăn (<?php echo $total; ?> món)</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="60">STT</th>
                                <th width="80">Hình</th>
                                <th>Tên món</th>
                                <th width="150">Danh mục</th>
                                <th width="120">Giá</th>
                                <th width="120">Trạng thái</th>
                                <th width="150">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($menu_items) > 0): ?>
                                <?php foreach ($menu_items as $index => $item): ?>
                                <tr>
                                    <td style="text-align: center; font-weight: 600;">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['image']): ?>
                                            <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                                🍽️
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="display: block; margin-bottom: 2px; color: #1f2937;">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </strong>
                                        <?php if ($item['name_en']): ?>
                                            <small style="color: #9ca3af;">
                                                <?php echo htmlspecialchars($item['name_en']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning">
                                            <?php echo htmlspecialchars($item['category_name'] ?? 'Chưa phân loại'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: #f97316; font-size: 1.05rem;">
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
                                        <div style="display: flex; gap: 0.4rem; justify-content: center;">
                                            <a href="../index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>" 
                                               target="_blank" 
                                               class="btn-icon btn-info" 
                                               title="Xem">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="editMenuItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                                    class="btn-icon btn-warning" 
                                                    title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteMenuItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>')" 
                                                    class="btn-icon btn-danger" 
                                                    title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 3rem; color: #9ca3af;">
                                        <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.3;"></i>
                                        <strong>Không tìm thấy món ăn nào</strong>
                                        <p style="margin-top: 0.5rem;">Thử thay đổi bộ lọc hoặc thêm món mới</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm món mới -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Thêm món ăn mới</h3>
                <button onclick="closeAddModal()" class="close-btn">&times;</button>
            </div>
            <form id="addForm" method="POST" action="api/add-menu-item.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Hình ảnh món ăn</label>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 2px solid #e5e7eb;">
                            <img id="add_preview_img" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                            <div id="add_preview_placeholder" style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                🍽️
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <input type="file" name="image" id="add_image" accept="image/*" onchange="previewAddImage(this)">
                            <small style="color: #6b7280; display: block; margin-top: 0.5rem;">Chọn ảnh món ăn (JPG, PNG, tối đa 5MB)</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Tên món (Tiếng Việt) *</label>
                    <input type="text" name="name" required placeholder="VD: Phở bò, Bánh xèo...">
                </div>
                
                <div class="form-group">
                    <label>Tên món (Tiếng Anh)</label>
                    <input type="text" name="name_en" placeholder="VD: Beef Pho, Vietnamese Pancake...">
                </div>
                
                <div class="form-group">
                    <label>Giá (VNĐ) *</label>
                    <input type="number" name="price" required min="0" step="1000" placeholder="VD: 45000">
                </div>
                
                <div class="form-group">
                    <label>Danh mục *</label>
                    <select name="category_id" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Mô tả (Tiếng Việt)</label>
                    <textarea name="description" rows="3" placeholder="Mô tả món ăn..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Mô tả (Tiếng Anh)</label>
                    <textarea name="description_en" rows="3" placeholder="Dish description..."></textarea>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_available" value="1" checked>
                        <span>Món ăn đang có sẵn</span>
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeAddModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Thêm món
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal chỉnh sửa -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Chỉnh sửa món ăn</h3>
                <button onclick="closeEditModal()" class="close-btn">&times;</button>
            </div>
            <form id="editForm" method="POST" action="api/update-menu-item.php" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="current_image" id="edit_current_image">
                
                <div class="form-group">
                    <label>Hình ảnh món ăn</label>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div id="current_image_preview" style="width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 2px solid #e5e7eb;">
                            <img id="preview_img" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                            <div id="preview_placeholder" style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                🍽️
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <input type="file" name="image" id="edit_image" accept="image/*" onchange="previewImage(this)" style="margin-bottom: 0.5rem;">
                            <small style="color: #6b7280; display: block;">Chọn ảnh mới để thay đổi (JPG, PNG, tối đa 5MB)</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Tên món (Tiếng Việt) *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                
                <div class="form-group">
                    <label>Tên món (Tiếng Anh)</label>
                    <input type="text" name="name_en" id="edit_name_en">
                </div>
                
                <div class="form-group">
                    <label>Giá (VNĐ) *</label>
                    <input type="number" name="price" id="edit_price" required min="0" step="1000">
                </div>
                
                <div class="form-group">
                    <label>Danh mục *</label>
                    <select name="category_id" id="edit_category_id" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Mô tả (Tiếng Việt)</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Mô tả (Tiếng Anh)</label>
                    <textarea name="description_en" id="edit_description_en" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_available" id="edit_is_available" value="1">
                        <span>Món ăn đang có sẵn</span>
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
            font-size: 0.85rem;
        }
        .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn-icon.btn-info {
            background: #3b82f6;
        }
        .btn-icon.btn-warning {
            background: #f59e0b;
        }
        .btn-icon.btn-danger {
            background: #ef4444;
        }
        
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
            border-radius: 12px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 2px solid #f3f4f6;
        }
        .modal-header h3 {
            margin: 0;
            color: #1f2937;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #9ca3af;
            line-height: 1;
        }
        .close-btn:hover {
            color: #ef4444;
        }
        .form-group {
            padding: 0 1.5rem;
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        .modal-footer {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            padding: 1.5rem;
            border-top: 2px solid #f3f4f6;
        }
    </style>

    <script>
        function showAddModal() {
            console.log('showAddModal called');
            const modal = document.getElementById('addModal');
            const form = document.getElementById('addForm');
            
            if (!modal) {
                console.error('Modal element not found!');
                alert('❌ Lỗi: Không tìm thấy modal. Vui lòng tải lại trang.');
                return;
            }
            
            if (!form) {
                console.error('Form element not found!');
                alert('❌ Lỗi: Không tìm thấy form. Vui lòng tải lại trang.');
                return;
            }
            
            console.log('Opening modal...');
            modal.style.display = 'flex';
            form.reset();
            
            const previewImg = document.getElementById('add_preview_img');
            const previewPlaceholder = document.getElementById('add_preview_placeholder');
            
            if (previewImg) previewImg.style.display = 'none';
            if (previewPlaceholder) previewPlaceholder.style.display = 'flex';
            
            console.log('Modal opened successfully');
        }
        
        function closeAddModal() {
            console.log('closeAddModal called');
            const modal = document.getElementById('addModal');
            if (modal) {
                modal.style.display = 'none';
                console.log('Modal closed');
            } else {
                console.error('Modal element not found when closing');
            }
        }
        
        function previewAddImage(input) {
            const previewImg = document.getElementById('add_preview_img');
            const previewPlaceholder = document.getElementById('add_preview_placeholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    previewPlaceholder.style.display = 'none';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Xử lý submit form thêm món
        document.getElementById('addForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            console.log('Form submitted');
            const formData = new FormData(this);
            
            // Log form data
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }
            
            try {
                console.log('Sending request to api/add-menu-item.php');
                const response = await fetch('api/add-menu-item.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Kiểm tra response có phải JSON không
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Response is not JSON:', text);
                    alert('❌ Lỗi: Server trả về dữ liệu không hợp lệ\n\nChi tiết: ' + text.substring(0, 200));
                    return;
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success) {
                    alert('✅ Thêm món ăn thành công!');
                    location.reload();
                } else {
                    let errorMsg = '❌ Lỗi: ' + (data.message || 'Không thể thêm món');
                    if (data.debug) {
                        errorMsg += '\n\nDebug: ' + JSON.stringify(data.debug);
                    }
                    alert(errorMsg);
                    console.error('Error:', data);
                }
            } catch (error) {
                console.error('Catch error:', error);
                alert('❌ Có lỗi xảy ra: ' + error.message + '\n\nVui lòng mở Console (F12) để xem chi tiết');
            }
        });
        
        // Đóng modal khi click bên ngoài
        document.getElementById('addModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
        
        function editMenuItem(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_name_en').value = item.name_en || '';
            document.getElementById('edit_price').value = item.price;
            document.getElementById('edit_category_id').value = item.category_id;
            document.getElementById('edit_description').value = item.description || '';
            document.getElementById('edit_description_en').value = item.description_en || '';
            document.getElementById('edit_is_available').checked = item.is_available == 1;
            document.getElementById('edit_current_image').value = item.image || '';
            
            // Hiển thị ảnh hiện tại
            const previewImg = document.getElementById('preview_img');
            const previewPlaceholder = document.getElementById('preview_placeholder');
            
            if (item.image) {
                previewImg.src = '../' + item.image;
                previewImg.style.display = 'block';
                previewPlaceholder.style.display = 'none';
            } else {
                previewImg.style.display = 'none';
                previewPlaceholder.style.display = 'flex';
            }
            
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function previewImage(input) {
            const previewImg = document.getElementById('preview_img');
            const previewPlaceholder = document.getElementById('preview_placeholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    previewPlaceholder.style.display = 'none';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Đóng modal khi click bên ngoài
        document.getElementById('editModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Xử lý submit form
        document.getElementById('editForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('api/update-menu-item.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Cập nhật món ăn thành công!');
                    location.reload();
                } else {
                    alert('❌ Lỗi: ' + (data.message || 'Không thể cập nhật'));
                }
            } catch (error) {
                alert('❌ Có lỗi xảy ra: ' + error.message);
            }
        });
        
        // Hàm xóa món ăn
        async function deleteMenuItem(id, name) {
            if (!confirm(`⚠️ Bạn có chắc chắn muốn xóa món "${name}"?\n\nHành động này không thể hoàn tác!`)) {
                return;
            }
            
            // Xác nhận lần 2
            if (!confirm(`🚨 XÁC NHẬN LẦN CUỐI!\n\nXóa món "${name}" sẽ xóa luôn:\n- Tất cả đánh giá của món này\n- Lịch sử đặt món\n- Dữ liệu liên quan\n\nBạn có chắc chắn?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('id', id);
                
                const response = await fetch('api/delete-menu-item.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Đã xóa món ăn thành công!');
                    location.reload();
                } else {
                    alert('❌ Lỗi: ' + (data.message || 'Không thể xóa'));
                }
            } catch (error) {
                alert('❌ Có lỗi xảy ra: ' + error.message);
            }
        }
    </script>
</body>
</html>
