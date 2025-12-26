<?php
/**
 * Script để reset và tạo lại khuyến mãi phù hợp với website giới thiệu nhà hàng
 * Chạy file này một lần để cập nhật dữ liệu
 */

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>🔄 Reset Khuyến Mãi Nhà Hàng</h2>";

try {
    // Xóa tất cả khuyến mãi cũ
    $conn->exec("DELETE FROM restaurant_promotions");
    echo "<p>✅ Đã xóa tất cả khuyến mãi cũ</p>";
    
    // Thêm cột link_page nếu chưa có
    try {
        $conn->query("SELECT link_page FROM restaurant_promotions LIMIT 1");
    } catch (PDOException $e) {
        $conn->exec("ALTER TABLE restaurant_promotions ADD COLUMN link_page VARCHAR(50) DEFAULT 'reservation'");
        echo "<p>✅ Đã thêm cột link_page</p>";
    }
    
    // Thêm khuyến mãi mới - mỗi cái có link_page riêng
    $promotions = [
        [
            'title' => 'Combo Gia Đình Cuối Tuần',
            'title_en' => 'Weekend Family Combo',
            'description' => 'Thưởng thức bữa ăn ấm cúng cùng gia đình với combo đặc biệt gồm 4 món chính, 2 món phụ và đồ uống.',
            'description_en' => 'Enjoy a warm family meal with our special combo including 4 main dishes, 2 side dishes and drinks.',
            'promo_type' => 'combo',
            'discount_text' => 'Tiết kiệm 20%',
            'discount_percent' => 20,
            'link_page' => 'menu',
            'is_featured' => 1,
            'display_order' => 1
        ],
        [
            'title' => 'Happy Hour - Giờ Vàng',
            'title_en' => 'Happy Hour - Golden Time',
            'description' => 'Giảm giá đặc biệt 30% cho tất cả đồ uống từ 14:00 - 17:00 hàng ngày.',
            'description_en' => 'Special 30% discount on all beverages from 2PM - 5PM daily.',
            'promo_type' => 'discount',
            'discount_text' => 'Giảm 30% đồ uống',
            'discount_percent' => 30,
            'link_page' => 'menu',
            'is_featured' => 1,
            'display_order' => 2
        ],
        [
            'title' => 'Ưu Đãi Sinh Nhật',
            'title_en' => 'Birthday Special',
            'description' => 'Tổ chức sinh nhật tại Ngon Gallery: Tặng bánh sinh nhật và giảm 15% hóa đơn!',
            'description_en' => 'Celebrate your birthday at Ngon Gallery: Free birthday cake and 15% off!',
            'promo_type' => 'event',
            'discount_text' => 'Tặng bánh + Giảm 15%',
            'discount_percent' => 15,
            'link_page' => 'reservation',
            'is_featured' => 0,
            'display_order' => 3
        ],
        [
            'title' => 'Set Menu Tiệc Công Ty',
            'title_en' => 'Corporate Party Set Menu',
            'description' => 'Dành cho các buổi họp mặt, tiệc công ty từ 10 người. Liên hệ để được tư vấn menu.',
            'description_en' => 'For meetings and corporate parties of 10+ people. Contact us for menu consultation.',
            'promo_type' => 'event',
            'discount_text' => 'Ưu đãi đặc biệt',
            'discount_percent' => 10,
            'link_page' => 'contact',
            'is_featured' => 0,
            'display_order' => 4
        ],
        [
            'title' => 'Đặt Bàn Online - Ưu Đãi 5%',
            'title_en' => 'Online Reservation - 5% Off',
            'description' => 'Đặt bàn trực tuyến qua website và nhận ngay ưu đãi giảm 5% cho hóa đơn.',
            'description_en' => 'Book online through our website and get 5% off your bill instantly!',
            'promo_type' => 'member',
            'discount_text' => 'Giảm 5%',
            'discount_percent' => 5,
            'link_page' => 'reservation',
            'is_featured' => 0,
            'display_order' => 5
        ],
        [
            'title' => 'Buffet Trưa Văn Phòng',
            'title_en' => 'Office Lunch Buffet',
            'description' => 'Buffet trưa đa dạng món Việt với giá chỉ từ 99.000đ/người. Thứ 2 - Thứ 6, 11:00 - 14:00.',
            'description_en' => 'Diverse Vietnamese lunch buffet from only 99,000 VND/person. Mon-Fri, 11AM-2PM.',
            'promo_type' => 'combo',
            'discount_text' => 'Chỉ 99K/người',
            'discount_percent' => 0,
            'link_page' => 'menu',
            'is_featured' => 0,
            'display_order' => 6
        ]
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO restaurant_promotions 
        (title, title_en, description, description_en, promo_type, discount_text, discount_percent, 
         link_page, start_date, end_date, is_featured, is_active, display_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, '2024-01-01', '2025-12-31', ?, 1, ?)
    ");
    
    foreach ($promotions as $promo) {
        $stmt->execute([
            $promo['title'],
            $promo['title_en'],
            $promo['description'],
            $promo['description_en'],
            $promo['promo_type'],
            $promo['discount_text'],
            $promo['discount_percent'],
            $promo['link_page'],
            $promo['is_featured'],
            $promo['display_order']
        ]);
        echo "<p>✅ {$promo['title']} → <strong>{$promo['link_page']}</strong></p>";
    }
    
    echo "<h3>🎉 Hoàn tất! Đã tạo " . count($promotions) . " khuyến mãi.</h3>";
    echo "<p><a href='index.php?page=promotions' style='color:#22c55e;font-weight:bold;'>👉 Xem trang khuyến mãi</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>
