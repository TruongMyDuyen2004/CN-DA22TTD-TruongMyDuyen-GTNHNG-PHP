<?php
/**
 * API Gợi ý tìm kiếm món ăn (Autocomplete)
 * Trả về danh sách món ăn phù hợp với từ khóa tìm kiếm
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

// Lấy từ khóa tìm kiếm
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;

// Giới hạn limit
if ($limit > 20) $limit = 20;
if ($limit < 1) $limit = 8;

// Nếu từ khóa quá ngắn, trả về rỗng
if (strlen($query) < 1) {
    echo json_encode(['success' => true, 'suggestions' => []]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Tìm kiếm theo tên món (tiếng Việt và tiếng Anh)
    $search_term = "%{$query}%";
    
    $sql = "SELECT id, name, name_en, price, image, category_id, is_available,
                   (SELECT name FROM categories WHERE id = menu_items.category_id) as category_name
            FROM menu_items 
            WHERE (name LIKE ? OR name_en LIKE ?)
            ORDER BY 
                CASE 
                    WHEN name LIKE ? THEN 1
                    WHEN name_en LIKE ? THEN 2
                    ELSE 3
                END,
                is_available DESC,
                name ASC
            LIMIT ?";
    
    $start_term = "{$query}%";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $search_term, PDO::PARAM_STR);
    $stmt->bindValue(2, $search_term, PDO::PARAM_STR);
    $stmt->bindValue(3, $start_term, PDO::PARAM_STR);
    $stmt->bindValue(4, $start_term, PDO::PARAM_STR);
    $stmt->bindValue(5, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format kết quả
    $suggestions = [];
    foreach ($items as $item) {
        $suggestions[] = [
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'name_en' => $item['name_en'] ?? '',
            'price' => (int)$item['price'],
            'price_formatted' => number_format($item['price'], 0, ',', '.') . 'đ',
            'image' => $item['image'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80',
            'category' => $item['category_name'] ?? '',
            'is_available' => (bool)$item['is_available']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'count' => count($suggestions),
        'suggestions' => $suggestions
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi khi tìm kiếm: ' . $e->getMessage()
    ]);
}
