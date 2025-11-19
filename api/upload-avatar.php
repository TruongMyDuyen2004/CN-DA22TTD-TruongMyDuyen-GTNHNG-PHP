<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$db = new Database();
$conn = $db->connect();
$customer_id = $_SESSION['customer_id'];

// Kiểm tra file upload
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn ảnh']);
    exit;
}

$file = $_FILES['avatar'];
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)']);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'Kích thước ảnh không được vượt quá 5MB']);
    exit;
}

// Create uploads directory if not exists
$upload_dir = '../uploads/avatars/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $customer_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Không thể upload ảnh']);
    exit;
}

// Get old avatar to delete
$stmt = $conn->prepare("SELECT avatar FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$old_avatar = $stmt->fetchColumn();

// Update database
$avatar_url = 'uploads/avatars/' . $filename;
$stmt = $conn->prepare("UPDATE customers SET avatar = ? WHERE id = ?");

if ($stmt->execute([$avatar_url, $customer_id])) {
    // Delete old avatar file if exists
    if ($old_avatar && file_exists('../' . $old_avatar)) {
        unlink('../' . $old_avatar);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật ảnh đại diện thành công',
        'avatar_url' => $avatar_url
    ]);
} else {
    // Delete uploaded file if database update fails
    unlink($filepath);
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật']);
}
?>
