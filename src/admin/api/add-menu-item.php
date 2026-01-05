<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$db = new Database();
$conn = $db->connect();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối database']);
    exit;
}

$name = $_POST['name'] ?? '';
$name_en = $_POST['name_en'] ?? '';
$price = $_POST['price'] ?? 0;
$category_id = $_POST['category_id'] ?? 0;
$region = $_POST['region'] ?? null;
$description = $_POST['description'] ?? '';
$description_en = $_POST['description_en'] ?? '';
$is_available = isset($_POST['is_available']) ? 1 : 0;

// Debug log
error_log("Add menu item - Name: $name, Price: $price, Category: $category_id");

if (!$name || !$price || !$category_id) {
    echo json_encode([
        'success' => false, 
        'message' => 'Thiếu thông tin bắt buộc',
        'debug' => [
            'name' => $name,
            'price' => $price,
            'category_id' => $category_id
        ]
    ]);
    exit;
}

// Xử lý upload ảnh
$image_path = '';

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../uploads/menu/';
    
    // Tạo thư mục nếu chưa có
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_name = $_FILES['image']['name'];
    $file_size = $_FILES['image']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Kiểm tra định dạng
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)']);
        exit;
    }
    
    // Kiểm tra kích thước (5MB)
    if ($file_size > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File ảnh quá lớn (tối đa 5MB)']);
        exit;
    }
    
    // Tạo tên file unique
    $new_file_name = 'menu_' . time() . '_' . uniqid() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_file_name;
    
    // Upload file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        $image_path = 'uploads/menu/' . $new_file_name;
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể upload ảnh']);
        exit;
    }
}

try {
    // Kiểm tra category_id có tồn tại không
    $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false, 
            'message' => 'Danh mục không tồn tại. Vui lòng chọn danh mục hợp lệ.',
            'category_id' => $category_id
        ]);
        exit;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO menu_items (name, name_en, price, category_id, region, description, description_en, is_available, image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $name, 
        $name_en, 
        $price, 
        $category_id,
        $region ?: null,
        $description, 
        $description_en, 
        $is_available,
        $image_path
    ]);
    
    if ($result) {
        $new_id = $conn->lastInsertId();
        error_log("Menu item added successfully - ID: $new_id");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thêm món "' . $name . '" thành công',
            'id' => $new_id
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode([
            'success' => false, 
            'message' => 'Không thể thêm món ăn',
            'error' => $errorInfo
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error adding menu item: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi database: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>
