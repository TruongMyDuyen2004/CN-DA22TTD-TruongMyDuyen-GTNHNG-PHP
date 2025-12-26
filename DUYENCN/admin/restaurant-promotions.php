<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Kiểm tra và tạo bảng restaurant_promotions nếu chưa có
try {
    $conn->query("SELECT 1 FROM restaurant_promotions LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("CREATE TABLE IF NOT EXISTS restaurant_promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        title_en VARCHAR(255),
        description TEXT,
        description_en TEXT,
        image VARCHAR(255),
        promo_type ENUM('combo', 'discount', 'event', 'seasonal', 'member') DEFAULT 'discount',
        discount_text VARCHAR(100),
        start_date DATE,
        end_date DATE,
        terms TEXT,
        terms_en TEXT,
        is_featured TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Xử lý AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add') {
        $title = trim($_POST['title'] ?? '');
        $title_en = trim($_POST['title_en'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $description_en = trim($_POST['description_en'] ?? '');
        $promo_type = $_POST['promo_type'] ?? 'discount';
        $discount_text = trim($_POST['discount_text'] ?? '');
        $start_date = $_POST['start_date'] ?: null;
        $end_date = $_POST['end_date'] ?: null;
        $terms = trim($_POST['terms'] ?? '');
        $terms_en = trim($_POST['terms_en'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $display_order = intval($_POST['display_order'] ?? 0);

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tiêu đề']);
            exit;
        }
        
        // Xử lý upload hình ảnh
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/promotions/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'promo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        }
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO restaurant_promotions (title, title_en, description, description_en, image, promo_type, discount_text, start_date, end_date, terms, terms_en, is_featured, display_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $title_en, $description, $description_en, $image, $promo_type, $discount_text, $start_date, $end_date, $terms, $terms_en, $is_featured, $display_order]);
            echo json_encode(['success' => true, 'message' => 'Thêm khuyến mãi thành công']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $title_en = trim($_POST['title_en'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $description_en = trim($_POST['description_en'] ?? '');
        $promo_type = $_POST['promo_type'] ?? 'discount';
        $discount_text = trim($_POST['discount_text'] ?? '');
        $start_date = $_POST['start_date'] ?: null;
        $end_date = $_POST['end_date'] ?: null;
        $terms = trim($_POST['terms'] ?? '');
        $terms_en = trim($_POST['terms_en'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $display_order = intval($_POST['display_order'] ?? 0);
        
        // Xử lý upload hình ảnh mới
        $image_sql = '';
        $params = [$title, $title_en, $description, $description_en, $promo_type, $discount_text, $start_date, $end_date, $terms, $terms_en, $is_featured, $is_active, $display_order];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/promotions/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'promo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
            $image_sql = ', image = ?';
            $params[] = $image;
        }
        $params[] = $id;
