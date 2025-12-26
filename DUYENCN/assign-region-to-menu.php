<?php
/**
 * Gán vùng miền cho các món ăn dựa trên tên món
 * Chạy file này để tự động phân loại món ăn theo vùng miền
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🗺️ Gán Vùng Miền Cho Món Ăn</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #22c55e; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .badge { padding: 4px 10px; border-radius: 15px; color: white; font-size: 0.85rem; }
    .mien_bac { background: #3b82f6; }
    .mien_trung { background: #f59e0b; }
    .mien_nam { background: #22c55e; }
    .quoc_te { background: #8b5cf6; }
    .btn { display: inline-block; padding: 12px 24px; background: #22c55e; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; }
    .btn:hover { background: #16a34a; }
</style>";

// Danh sách từ khóa để nhận diện vùng miền
$region_keywords = [
    'mien_bac' => [
        'phở', 'pho', 'bún chả', 'bun cha', 'bánh cuốn', 'banh cuon', 
        'chả cá', 'cha ca', 'nem rán', 'nem ran', 'bún đậu', 'bun dau',
        'bún thang', 'bun thang', 'bánh tôm', 'banh tom', 'xôi xéo', 'xoi xeo',
        'bún ốc', 'bun oc', 'miến', 'mien', 'giò', 'gio', 'chả', 'cha',
        'bánh giò', 'banh gio', 'bánh đúc', 'banh duc', 'cốm', 'com',
        'hà nội', 'ha noi', 'hanoi', 'bắc', 'bac'
    ],
    'mien_trung' => [
        'bún bò huế', 'bun bo hue', 'mì quảng', 'mi quang', 'bánh bèo', 'banh beo',
        'bánh nậm', 'banh nam', 'bánh lọc', 'banh loc', 'bánh ít', 'banh it',
        'nem lụi', 'nem lui', 'cao lầu', 'cao lau', 'cơm hến', 'com hen',
        'bánh tráng', 'banh trang', 'bánh xèo miền trung', 'huế', 'hue',
        'đà nẵng', 'da nang', 'quảng', 'quang', 'trung', 'trung bộ'
    ],
    'mien_nam' => [
        'hủ tiếu', 'hu tieu', 'bánh mì', 'banh mi', 'cơm tấm', 'com tam',
        'bánh xèo', 'banh xeo', 'gỏi cuốn', 'goi cuon', 'bún mắm', 'bun mam',
        'lẩu mắm', 'lau mam', 'bánh canh', 'banh canh', 'bún riêu', 'bun rieu',
        'cháo lòng', 'chao long', 'bánh tét', 'banh tet', 'bánh ú', 'banh u',
        'sài gòn', 'sai gon', 'saigon', 'nam', 'nam bộ', 'miền nam'
    ],
    'quoc_te' => [
        'pizza', 'burger', 'pasta', 'spaghetti', 'steak', 'bít tết', 'bit tet',
        'sushi', 'sashimi', 'ramen', 'tempura', 'kimchi', 'bibimbap',
        'pad thai', 'tom yum', 'curry', 'cà ri', 'ca ri', 'sandwich',
        'salad', 'soup', 'hotdog', 'taco', 'burrito', 'noodle',
        'fried rice', 'spring roll', 'dumpling', 'dim sum',
        'chocolate', 'cake', 'tiramisu', 'cheesecake', 'mousse',
        'latte', 'cappuccino', 'espresso', 'smoothie', 'milkshake'
    ]
];

try {
    // Kiểm tra cột region đã tồn tại chưa
    $stmt = $conn->query("SHOW COLUMNS FROM menu_items LIKE 'region'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE menu_items ADD COLUMN region VARCHAR(50) DEFAULT NULL AFTER category_id");
        echo "<p>✅ Đã thêm cột 'region' vào bảng menu_items</p>";
    }
    
    // Lấy tất cả món ăn
    $stmt = $conn->query("SELECT id, name, name_en, region FROM menu_items ORDER BY name");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    $results = [];
    
    foreach ($items as $item) {
        $item_name = strtolower($item['name'] . ' ' . ($item['name_en'] ?? ''));
        $detected_region = null;
        
        // Tìm vùng miền phù hợp
        foreach ($region_keywords as $region => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($item_name, strtolower($keyword)) !== false) {
                    $detected_region = $region;
                    break 2;
                }
            }
        }
        
        // Nếu chưa có region hoặc region khác với detected
        if ($detected_region && $item['region'] !== $detected_region) {
            $update = $conn->prepare("UPDATE menu_items SET region = ? WHERE id = ?");
            $update->execute([$detected_region, $item['id']]);
            $updated++;
        }
        
        $results[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'old_region' => $item['region'],
            'new_region' => $detected_region ?? $item['region']
        ];
    }
    
    echo "<p>✅ Đã cập nhật <strong>{$updated}</strong> món ăn</p>";
    
    // Hiển thị kết quả
    $region_labels = [
        'mien_bac' => '🏔️ Miền Bắc',
        'mien_trung' => '🏖️ Miền Trung',
        'mien_nam' => '🌴 Miền Nam',
        'quoc_te' => '🌍 Quốc tế'
    ];
    
    echo "<h3>📋 Danh sách món ăn và vùng miền:</h3>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Tên món</th><th>Vùng miền</th></tr>";
    
    foreach ($results as $r) {
        $region_display = $r['new_region'] ? 
            "<span class='badge {$r['new_region']}'>{$region_labels[$r['new_region']]}</span>" : 
            "<span style='color: #999;'>Chưa phân loại</span>";
        echo "<tr>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['name']}</td>";
        echo "<td>{$region_display}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Thống kê
    echo "<h3>📊 Thống kê:</h3>";
    $stmt = $conn->query("
        SELECT 
            COALESCE(region, 'chua_phan_loai') as region,
            COUNT(*) as count 
        FROM menu_items 
        GROUP BY region
        ORDER BY count DESC
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table style='width: 400px;'>";
    echo "<tr><th>Vùng miền</th><th>Số món</th></tr>";
    foreach ($stats as $stat) {
        $name = $region_labels[$stat['region']] ?? '❓ Chưa phân loại';
        echo "<tr><td>{$name}</td><td><strong>{$stat['count']}</strong></td></tr>";
    }
    echo "</table>";
    
    echo "<br><a href='index.php?page=menu' class='btn'>→ Xem trang Menu</a>";
    echo " <a href='admin/menu-manage.php' class='btn' style='background: #3b82f6;'>→ Quản lý Menu (Admin)</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>
