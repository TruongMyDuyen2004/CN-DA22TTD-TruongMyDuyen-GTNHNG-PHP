<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();
$current_lang = getCurrentLanguage();
$customer_id = $_SESSION['customer_id'];

// Tự động tạo bảng favorites nếu chưa tồn tại
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favorite (customer_id, menu_item_id),
        INDEX idx_favorites_customer (customer_id),
        INDEX idx_favorites_menu_item (menu_item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    // Bảng đã tồn tại, bỏ qua
}

// Lấy danh sách món yêu thích
$stmt = $conn->prepare("
    SELECT m.*, c.name as category_name, f.created_at as favorited_at
    FROM favorites f
    JOIN menu_items m ON f.menu_item_id = m.id
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE f.customer_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$customer_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Favorites Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge"><i class="fas fa-heart"></i> <?php echo $current_lang === 'en' ? 'Favorites' : 'Yêu thích'; ?></span>
        <h1 class="about-hero-title"><?php echo $current_lang === 'en' ? 'My Favorite Dishes' : 'Món Ăn Yêu Thích'; ?></h1>
        <p class="about-hero-subtitle"><?php echo $current_lang === 'en' ? 'Your saved favorite dishes' : 'Các món ăn bạn đã lưu'; ?></p>
    </div>
</section>

<section class="favorites-page">
    <div class="favorites-container">
        <?php if (count($favorites) > 0): ?>
        <div class="favorites-count">
            <i class="fas fa-heart"></i>
            <span><?php echo count($favorites); ?> <?php echo $current_lang === 'en' ? 'favorite dishes' : 'món yêu thích'; ?></span>
        </div>
        
        <div class="favorites-grid">
            <?php foreach($favorites as $item):
                $item_name = $current_lang === 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
                $discount = $item['discount_percent'] ?? 0;
                $has_discount = $discount > 0;
                $original_price = $item['original_price'] ?? $item['price'];
                $is_available = $item['is_available'] == 1;
            ?>
            <div class="favorite-card <?php echo !$is_available ? 'out-of-stock' : ''; ?>" data-item-id="<?php echo $item['id']; ?>">
                <div class="favorite-card-image">
                    <?php if(!$is_available): ?>
                    <div class="out-of-stock-overlay">
                        <span class="out-of-stock-badge">Hết món</span>
                    </div>
                    <?php endif; ?>
                    <?php if($has_discount && $is_available): ?>
                    <span class="discount-badge">-<?php echo $discount; ?>%</span>
                    <?php endif; ?>
                    <button class="remove-favorite-btn" onclick="removeFavorite(<?php echo $item['id']; ?>, this)" title="Xóa khỏi yêu thích">
                        <i class="fas fa-heart"></i>
                    </button>
                    <?php if($item['image']): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80" alt="<?php echo htmlspecialchars($item_name); ?>">
                    <?php endif; ?>
                </div>
                <div class="favorite-card-content">
                    <span class="favorite-category"><?php echo htmlspecialchars($item['category_name'] ?? 'Khác'); ?></span>
                    <h3 class="favorite-card-title"><?php echo htmlspecialchars($item_name); ?></h3>
                    <div class="favorite-card-price">
                        <span class="current-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                        <?php if($has_discount && $is_available): ?>
                        <span class="old-price"><?php echo number_format($original_price, 0, ',', '.'); ?>đ</span>
                        <?php endif; ?>
                    </div>
                    <div class="favorite-card-actions">
                        <?php if($is_available): ?>
                        <a href="index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>" class="view-detail-btn">
                            <i class="fas fa-eye"></i> Xem chi tiết
                        </a>
                        <?php else: ?>
                        <span class="view-detail-btn disabled">
                            <i class="fas fa-ban"></i> Tạm hết
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="favorites-empty">
            <div class="empty-icon">
                <i class="far fa-heart"></i>
            </div>
            <h3><?php echo $current_lang === 'en' ? 'No favorite dishes yet' : 'Chưa có món yêu thích'; ?></h3>
            <p><?php echo $current_lang === 'en' ? 'Click the heart icon on dishes to add them to your favorites' : 'Nhấn vào biểu tượng trái tim trên các món ăn để thêm vào danh sách yêu thích'; ?></p>
            <a href="index.php?page=menu" class="browse-menu-btn">
                <i class="fas fa-utensils"></i>
                <?php echo $current_lang === 'en' ? 'Browse Menu' : 'Xem thực đơn'; ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.favorites-page {
    padding: 2rem;
    background: #ffffff;
    min-height: calc(100vh - 300px);
}

.favorites-container {
    max-width: 1200px;
    margin: 0 auto;
}

.favorites-count {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    color: #16a34a;
    font-weight: 600;
    font-size: 1.1rem;
}

.favorites-count i {
    color: #ef4444;
}

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.favorite-card {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.favorite-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.15);
    border-color: #22c55e;
}

.favorite-card.out-of-stock {
    opacity: 0.7;
    filter: grayscale(30%);
}

.favorite-card-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.favorite-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.favorite-card:hover .favorite-card-image img {
    transform: scale(1.05);
}

.remove-favorite-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ffffff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    z-index: 5;
}

.remove-favorite-btn i {
    color: #ef4444;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.remove-favorite-btn:hover {
    background: #ef4444;
    transform: scale(1.1);
}

.remove-favorite-btn:hover i {
    color: #ffffff;
}

.discount-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    z-index: 5;
}

.out-of-stock-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 4;
}

.out-of-stock-badge {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.favorite-card-content {
    padding: 1.25rem;
}

.favorite-category {
    display: inline-block;
    background: #dcfce7;
    color: #16a34a;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.favorite-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.75rem 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.favorite-card-price {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.favorite-card-price .current-price {
    font-size: 1.25rem;
    font-weight: 800;
    color: #22c55e;
}

.favorite-card-price .old-price {
    font-size: 0.9rem;
    color: #9ca3af;
    text-decoration: line-through;
}

.favorite-card-actions {
    display: flex;
    gap: 0.75rem;
}

.favorite-card-actions .view-detail-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.favorite-card-actions .view-detail-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
}

.favorite-card-actions .view-detail-btn.disabled {
    background: #9ca3af;
    cursor: not-allowed;
    pointer-events: none;
}

/* Empty State */
.favorites-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.empty-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-icon i {
    font-size: 3rem;
    color: #ef4444;
}

.favorites-empty h3 {
    font-size: 1.5rem;
    color: #1f2937;
    margin: 0 0 0.75rem 0;
}

.favorites-empty p {
    color: #6b7280;
    margin: 0 0 1.5rem 0;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.browse-menu-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.browse-menu-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
    color: #ffffff;
}

/* Dark Theme */
body.dark-theme .favorites-page {
    background: #ffffff;
}

body.dark-theme .favorite-card {
    background: #1e293b;
    border-color: rgba(34, 197, 94, 0.2);
}

body.dark-theme .favorite-card:hover {
    border-color: #22c55e;
}

body.dark-theme .favorite-card-title {
    color: #f1f5f9;
}

body.dark-theme .favorite-category {
    background: rgba(34, 197, 94, 0.2);
}

body.dark-theme .favorites-empty {
    background: #1e293b;
}

body.dark-theme .favorites-empty h3 {
    color: #f1f5f9;
}

body.dark-theme .favorites-empty p {
    color: #94a3b8;
}

body.dark-theme .favorites-count {
    color: #22c55e;
}

/* Responsive */
@media (max-width: 768px) {
    .favorites-page {
        padding: 1.5rem;
    }
    
    .favorites-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .favorite-card-image {
        height: 180px;
    }
}

@media (max-width: 480px) {
    .favorites-grid {
        grid-template-columns: 1fr;
    }
}

/* Animation khi xóa */
.favorite-card.removing {
    animation: fadeOutScale 0.3s ease forwards;
}

@keyframes fadeOutScale {
    to {
        opacity: 0;
        transform: scale(0.8);
    }
}
</style>

<script>
function removeFavorite(itemId, btn) {
    const card = btn.closest('.favorite-card');
    
    fetch('api/favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle&menu_item_id=' + itemId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.action === 'removed') {
            card.classList.add('removing');
            setTimeout(() => {
                card.remove();
                // Kiểm tra nếu không còn món nào
                const grid = document.querySelector('.favorites-grid');
                if (grid && grid.children.length === 0) {
                    location.reload();
                }
                // Cập nhật số lượng
                const countEl = document.querySelector('.favorites-count span');
                if (countEl) {
                    const currentCount = parseInt(countEl.textContent) - 1;
                    countEl.textContent = currentCount + ' món yêu thích';
                }
            }, 300);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra, vui lòng thử lại');
    });
}
</script>
