<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$db = new Database();
$conn = $db->connect();

$id = $_POST['id'] ?? 0;
$name = $_POST['name'] ?? '';
$name_en = $_POST['name_en'] ?? '';
$price = $_POST['price'] ?? 0;
$category_id = $_POST['category_id'] ?? 0;
$description = $_POST['description'] ?? '';
$description_en = $_POST['description_en'] ?? '';
$is_available = isset($_POST['is_available']) ? 1 : 0;
$current_image = $_POST['current_image'] ?? '';

if (!$id || !$name || !$price || !$category_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

// Xử lý upload ảnh
$image_path = $current_image;

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
    $new_file_name = 'menu_' . $id . '_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_file_name;
    
    // Upload file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        $image_path = 'uploads/menu/' . $new_file_name;
        
        // Xóa ảnh cũ nếu có
        if ($current_image && file_exists('../../' . $current_image)) {
            @unlink('../../' . $current_image);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể upload ảnh']);
        exit;
    }
}

try {
    $stmt = $conn->prepare("
        UPDATE menu_items 
        SET name = ?, 
            name_en = ?, 
            price = ?, 
            category_id = ?, 
            description = ?, 
            description_en = ?, 
            is_available = ?,
            image = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $name, 
        $name_en, 
        $price, 
        $category_id, 
        $description, 
        $description_en, 
        $is_available,
        $image_path,
        $id
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
