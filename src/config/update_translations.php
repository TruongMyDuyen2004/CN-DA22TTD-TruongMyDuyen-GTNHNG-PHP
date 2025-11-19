<?php
require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Update categories with English translations
    $categories_updates = [
        'Món chính' => 'Main Dishes',
        'Món phụ' => 'Side Dishes',
        'Đồ uống' => 'Beverages',
        'Tráng miệng' => 'Desserts'
    ];
    
    foreach ($categories_updates as $vi => $en) {
        $stmt = $conn->prepare("UPDATE categories SET name_en = ? WHERE name = ?");
        $stmt->execute([$en, $vi]);
    }
    
    echo "✅ Categories updated successfully!\n";
    
    // Update menu items with English translations
    $items_updates = [
        'Phở bò đặc biệt' => ['name' => 'Special Beef Pho', 'desc' => 'Broth simmered for 12 hours'],
        'Bún chả Hà Nội' => ['name' => 'Hanoi Grilled Pork with Noodles', 'desc' => 'Delicious charcoal grilled pork'],
        'Cơm tấm sườn' => ['name' => 'Broken Rice with Grilled Pork Chop', 'desc' => 'Signature tender grilled pork chop'],
        'Bánh mì thịt' => ['name' => 'Vietnamese Pork Sandwich', 'desc' => 'Crispy baguette with savory pork'],
        'Gỏi cuốn' => ['name' => 'Fresh Spring Rolls', 'desc' => 'Fresh vegetables wrapped in rice paper'],
        'Nem rán' => ['name' => 'Fried Spring Rolls', 'desc' => 'Crispy fried rolls with pork and vegetables'],
        'Cà phê sữa đá' => ['name' => 'Vietnamese Iced Coffee', 'desc' => 'Strong coffee with condensed milk'],
        'Trà đá' => ['name' => 'Iced Tea', 'desc' => 'Refreshing Vietnamese iced tea']
    ];
    
    foreach ($items_updates as $vi => $en) {
        $stmt = $conn->prepare("UPDATE menu_items SET name_en = ?, description_en = ? WHERE name = ?");
        $stmt->execute([$en['name'], $en['desc'], $vi]);
    }
    
    echo "✅ Menu items updated successfully!\n";
    echo "\nDatabase has been updated with English translations.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
