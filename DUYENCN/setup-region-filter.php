<?php
/**
 * Setup Region Filter - Thêm bộ lọc món ăn theo vùng miền
 * Phân loại chính xác dựa trên tên món ăn
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🗺️ Setup Bộ Lọc Theo Vùng Miền</h2>";

try {
    // 1. Thêm cột region vào bảng menu_items
    echo "<h3>1. Thêm cột region vào bảng menu_items</h3>";
    
    $stmt = $conn->query("SHOW COLUMNS FROM menu_items LIKE 'region'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE menu_items ADD COLUMN region VARCHAR(50) DEFAULT NULL AFTER category_id");
        echo "✅ Đã thêm cột 'region' vào bảng menu_items<br>";
    } else {
        echo "ℹ️ Cột 'region' đã tồn tại<br>";
    }
    
    // 2. Danh sách từ khóa phân loại vùng miền CHÍNH XÁC
    echo "<h3>2. Phân loại món ăn theo vùng miền</h3>";
    
    // Món Miền Bắc
    $mien_bac_keywords = [
        'phở', 'pho', 'bún chả', 'bun cha', 'chả cá', 'cha ca', 'bánh cuốn', 'banh cuon',
        'bún thang', 'bun thang', 'bún ốc', 'bun oc', 'bún riêu', 'bun rieu', 
        'nem rán', 'nem ran', 'chả giò', 'cha gio', 'bánh tôm', 'banh tom',
        'xôi xéo', 'xoi xeo', 'cốm', 'com', 'bánh cốm', 'banh com',
        'giò chả', 'gio cha', 'giò lụa', 'gio lua', 'chả quế', 'cha que',
        'bún đậu', 'bun dau', 'mắm tôm', 'mam tom', 'bánh đa', 'banh da',
        'miến', 'mien', 'canh cua', 'rau muống', 'rau muong',
        'thịt đông', 'thit dong', 'dưa hành', 'dua hanh', 'bánh chưng', 'banh chung',
        'hà nội', 'ha noi', 'bắc', 'bac'
    ];
    
    // Món Miền Trung
    $mien_trung_keywords = [
        'mì quảng', 'mi quang', 'cao lầu', 'cao lau', 'bánh bèo', 'banh beo',
        'bánh nậm', 'banh nam', 'bánh lọc', 'banh loc', 'bánh ít', 'banh it',
        'bún bò huế', 'bun bo hue', 'bún bò', 'bun bo', 'cơm hến', 'com hen',
        'bánh xèo miền trung', 'nem lụi', 'nem lui', 'bánh tráng cuốn', 'banh trang cuon',
        'chả bò', 'cha bo', 'tré', 'tre', 'bánh ướt', 'banh uot',
        'bánh canh', 'banh canh', 'bánh đập', 'banh dap', 'hến xào', 'hen xao',
        'huế', 'hue', 'đà nẵng', 'da nang', 'quảng', 'quang', 'hội an', 'hoi an',
        'miền trung', 'mien trung'
    ];
    
    // Món Miền Nam
    $mien_nam_keywords = [
        'hủ tiếu', 'hu tieu', 'bánh mì', 'banh mi', 'cơm tấm', 'com tam',
        'bánh xèo', 'banh xeo', 'gỏi cuốn', 'goi cuon', 'bì cuốn', 'bi cuon',
        'bún mắm', 'bun mam', 'lẩu mắm', 'lau mam', 'cá kho tộ', 'ca kho to',
        'thịt kho', 'thit kho', 'canh chua', 'cá lóc', 'ca loc',
        'bánh tét', 'banh tet', 'bánh ít trần', 'banh it tran',
        'chè', 'che', 'sương sáo', 'suong sao', 'rau câu', 'rau cau',
        'sài gòn', 'sai gon', 'nam bộ', 'nam bo', 'miền nam', 'mien nam',
        'mekong', 'cần thơ', 'can tho', 'tây nam', 'tay nam'
    ];
    
    // Món Quốc tế
    $quoc_te_keywords = [
        'pizza', 'pasta', 'spaghetti', 'burger', 'hamburger', 'steak', 'beefsteak',
        'sushi', 'sashimi', 'ramen', 'tempura', 'takoyaki', 'udon',
        'kimchi', 'bibimbap', 'korean', 'hàn quốc', 'han quoc',
        'dim sum', 'dimsum', 'há cảo', 'ha cao', 'xíu mại', 'xiu mai',
        'pad thai', 'tom yum', 'thái', 'thai',
        'salad', 'sandwich', 'hotdog', 'hot dog', 'french fries',
        'chocolate', 'tiramisu', 'cheesecake', 'mousse', 'macaron',
        'lava', 'crepe', 'croissant', 'waffle',
        'nhật', 'nhat', 'ý', 'y', 'pháp', 'phap', 'mỹ', 'my',
        'tây', 'tay', 'âu', 'au', 'western', 'international'
    ];
    
    // Lấy tất cả món ăn
    $stmt = $conn->query("SELECT id, name, name_en FROM menu_items");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated_count = ['mien_bac' => 0, 'mien_trung' => 0, 'mien_nam' => 0, 'quoc_te' => 0];
    
    foreach ($items as $item) {
        $name_lower = mb_strtolower($item['name'], 'UTF-8');
        $name_en_lower = mb_strtolower($item['name_en'] ?? '', 'UTF-8');
        $combined = $name_lower . ' ' . $name_en_lower;
        
        $region = null;
        
        // Kiểm tra từng vùng miền
        foreach ($mien_bac_keywords as $keyword) {
            if (mb_strpos($combined, $keyword) !== false) {
                $region = 'mien_bac';
                break;
            }
        }
        
        if (!$region) {
            foreach ($mien_trung_keywords as $keyword) {
                if (mb_strpos($combined, $keyword) !== false) {
                    $region = 'mien_trung';
                    break;
                }
            }
        }
        
        if (!$region) {
            foreach ($mien_nam_keywords as $keyword) {
                if (mb_strpos($combined, $keyword) !== false) {
                    $region = 'mien_nam';
                    break;
                }
            }
        }
        
        if (!$region) {
            foreach ($quoc_te_keywords as $keyword) {
                if (mb_strpos($combined, $keyword) !== false) {
                    $region = 'quoc_te';
                    break;
                }
            }
        }
        
        // Cập nhật vào database
        if ($region) {
            $update = $conn->prepare("UPDATE menu_items SET region = ? WHERE id = ?");
            $update->execute([$region, $item['id']]);
            $updated_count[$region]++;
            
            $region_names = [
                'mien_bac' => '🏔️ Miền Bắc',
                'mien_trung' => '🏖️ Miền Trung',
                'mien_nam' => '🌴 Miền Nam',
                'quoc_te' => '🌍 Quốc tế'
            ];
            echo "✅ <strong>{$item['name']}</strong> → {$region_names[$region]}<br>";
        }
    }
    
    // 3. Hiển thị thống kê
    echo "<h3>3. Thống kê món ăn theo vùng miền</h3>";
    
    $stmt = $conn->query("
        SELECT 
            COALESCE(region, 'chua_phan_loai') as region,
            COUNT(*) as count 
        FROM menu_items 
        GROUP BY region
        ORDER BY FIELD(region, 'mien_bac', 'mien_trung', 'mien_nam', 'quoc_te', NULL)
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #22c55e; color: white;'><th>Vùng miền</th><th>Số món</th></tr>";
    
    $region_names = [
        'mien_bac' => '🏔️ Miền Bắc',
        'mien_trung' => '🏖️ Miền Trung',
        'mien_nam' => '🌴 Miền Nam',
        'quoc_te' => '🌍 Quốc tế',
        'chua_phan_loai' => '❓ Chưa phân loại'
    ];
    
    foreach ($stats as $stat) {
        $name = $region_names[$stat['region']] ?? $stat['region'];
        $bg = $stat['region'] == 'chua_phan_loai' ? 'background: #fef3c7;' : '';
        echo "<tr style='{$bg}'><td>{$name}</td><td style='text-align: center;'><strong>{$stat['count']}</strong></td></tr>";
    }
    echo "</table>";
    
    // 4. Hiển thị danh sách món chưa phân loại
    $stmt = $conn->query("SELECT id, name FROM menu_items WHERE region IS NULL ORDER BY name");
    $unclassified = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($unclassified) > 0) {
        echo "<h3>4. Món ăn chưa phân loại (cần cập nhật thủ công)</h3>";
        echo "<div style='background: #fef3c7; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        foreach ($unclassified as $item) {
            echo "• {$item['name']} (ID: {$item['id']})<br>";
        }
        echo "</div>";
        
        echo "<p><strong>Để cập nhật thủ công, chạy SQL:</strong></p>";
        echo "<pre style='background: #1e293b; color: #22c55e; padding: 15px; border-radius: 8px;'>";
        echo "UPDATE menu_items SET region = 'mien_bac' WHERE id = [ID];\n";
        echo "UPDATE menu_items SET region = 'mien_trung' WHERE id = [ID];\n";
        echo "UPDATE menu_items SET region = 'mien_nam' WHERE id = [ID];\n";
        echo "UPDATE menu_items SET region = 'quoc_te' WHERE id = [ID];";
        echo "</pre>";
    }
    
    echo "<br><br>✅ <strong>Setup hoàn tất!</strong>";
    echo "<br><br><a href='index.php?page=menu' style='padding: 12px 24px; background: #22c55e; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>→ Xem trang Menu với bộ lọc vùng miền</a>";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
