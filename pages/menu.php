<?php
$db = new Database();
$conn = $db->connect();
$current_lang = getCurrentLanguage();

// Kiểm tra nếu có tham số admin_view=1, chuyển hướng đến trang admin
if (isset($_GET['admin_view']) && $_GET['admin_view'] == '1') {
    // Kiểm tra xem user có phải admin không
    if (isset($_SESSION['admin_id'])) {
        header('Location: admin/menu.php');
        exit;
    }
}

// Xử lý tìm kiếm
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Lấy danh mục
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY display_order");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="menu-section">
    <div class="container">
        <div class="menu-header-wrapper">
            <div class="menu-header-content">
                <h2><?php echo __('menu_title'); ?></h2>
                <p class="menu-intro"><?php echo __('menu_subtitle'); ?></p>
            </div>
        </div>
        
        <!-- Tìm kiếm -->
        <div class="menu-search">
            <form method="GET" action="">
                <input type="hidden" name="page" value="menu">
                <input type="text" name="search" placeholder="<?php echo __('search'); ?>..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="category">
                    <option value=""><?php echo __('all_categories'); ?></option>
                    <?php foreach($categories as $cat): 
                        $cat_name = $current_lang === 'en' && !empty($cat['name_en']) ? $cat['name_en'] : $cat['name'];
                    ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary"><?php echo __('search'); ?></button>
            </form>
        </div>
        
        <?php 
        // Nếu có tìm kiếm hoặc lọc
        if ($search || $category_filter) {
            $sql = "SELECT * FROM menu_items WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND name LIKE ?";
                $params[] = "%$search%";
            }
            
            if ($category_filter) {
                $sql .= " AND category_id = ?";
                $params[] = $category_filter;
            }
            
            $sql .= " ORDER BY name";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($all_items) > 0):
        ?>
        <div class="menu-category">
            <h3><?php echo __('search_results'); ?> (<?php echo count($all_items); ?> <?php echo __('dishes'); ?>)</h3>
            <div class="menu-grid">
                <?php foreach($all_items as $item): 
                    $item_name = $current_lang === 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
                    $item_desc = $current_lang === 'en' && !empty($item['description_en']) ? $item['description_en'] : $item['description'];
                    
                    // Lấy thống kê đánh giá
                    $stmt_review = $conn->prepare("
                        SELECT 
                            COUNT(*) as total_reviews,
                            AVG(rating) as avg_rating
                        FROM reviews 
                        WHERE menu_item_id = ? AND is_approved = TRUE
                    ");
                    $stmt_review->execute([$item['id']]);
                    $review_stats = $stmt_review->fetch(PDO::FETCH_ASSOC);
                    $avg_rating = $review_stats['avg_rating'] ? round($review_stats['avg_rating'], 1) : 0;
                    $total_reviews = $review_stats['total_reviews'];
                ?>
                <div class="menu-item">
                    <a href="index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>" class="menu-item-link">
                        <div class="menu-item-image">
                            <?php if($item['image']): ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
                            <?php else: ?>
                                <div class="menu-item-icon">
                                    <?php 
                                    $icons = ['🍜', '🍲', '🥘', '🍱', '🥗', '🍛', '🥙', '🍢', '☕', '🥤'];
                                    echo $icons[array_rand($icons)];
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="menu-item-content">
                        <div class="menu-item-header">
                            <h4>
                                <a href="index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item_name); ?>
                                </a>
                            </h4>
                            <span class="price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                        </div>
                        
                        <div class="menu-item-rating-link" onclick="openReviewsModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item_name, ENT_QUOTES); ?>')" title="Xem <?php echo $total_reviews; ?> đánh giá">
                            <div class="menu-item-rating">
                                <div class="rating-stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $avg_rating): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif($i - 0.5 <= $avg_rating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-text">
                                    <strong><?php echo $avg_rating; ?></strong>
                                    (<?php echo $total_reviews; ?> đánh giá)
                                </span>
                            </div>
                        </div>
                        
                        <p class="menu-item-desc"><?php echo htmlspecialchars($item_desc); ?></p>
                        
                        <div class="menu-item-footer">
                            <span class="availability <?php echo $item['is_available'] ? 'available' : 'unavailable'; ?>">
                                <?php echo $item['is_available'] ? __('available') : __('unavailable'); ?>
                            </span>
                            
                            <div class="menu-item-actions">
                                <button onclick="window.location.href='index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>'" class="btn btn-small btn-secondary">
                                    <i class="fas fa-eye"></i> Xem
                                </button>
                                
                                <?php if (isset($_SESSION['customer_id'])): ?>
                                <button onclick="openReviewModalDirect(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item_name, ENT_QUOTES); ?>')" class="btn btn-small btn-warning">
                                    <i class="fas fa-star"></i> Đánh giá
                                </button>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['customer_id']) && $item['is_available']): ?>
                                <button onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item_name); ?>')" class="btn btn-small btn-primary">
                                    <i class="fas fa-shopping-cart"></i> Thêm
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
            <p class="no-results"><?php echo __('no_results'); ?></p>
        <?php endif; ?>
        
        <?php } else { ?>
        
        <?php foreach($categories as $category): ?>
        <?php
            // Lấy món ăn theo danh mục
            $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category_id = ? ORDER BY name");
            $stmt->execute([$category['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($items) > 0):
        ?>
        <div class="menu-category">
            <h3><?php 
                $cat_name = $current_lang === 'en' && !empty($category['name_en']) ? $category['name_en'] : $category['name'];
                echo htmlspecialchars($cat_name); 
            ?></h3>
            <div class="menu-grid">
                <?php foreach($items as $item): 
                    $item_name = $current_lang === 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
                    $item_desc = $current_lang === 'en' && !empty($item['description_en']) ? $item['description_en'] : $item['description'];
                    
                    // Lấy thống kê đánh giá
                    $stmt_review = $conn->prepare("
                        SELECT 
                            COUNT(*) as total_reviews,
                            AVG(rating) as avg_rating
                        FROM reviews 
                        WHERE menu_item_id = ? AND is_approved = TRUE
                    ");
                    $stmt_review->execute([$item['id']]);
                    $review_stats = $stmt_review->fetch(PDO::FETCH_ASSOC);
                    $avg_rating = $review_stats['avg_rating'] ? round($review_stats['avg_rating'], 1) : 0;
                    $total_reviews = $review_stats['total_reviews'];
                ?>
                <div class="menu-item">
                    <a href="index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>" class="menu-item-link">
                        <div class="menu-item-image">
                            <?php if($item['image']): ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
                            <?php else: ?>
                                <div class="menu-item-icon">
                                    <?php 
                                    $icons = ['🍜', '🍲', '🥘', '🍱', '🥗', '🍛', '🥙', '🍢', '☕', '🥤'];
                                    echo $icons[array_rand($icons)];
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="menu-item-content">
                        <div class="menu-item-header">
                            <h4>
                                <a href="index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item_name); ?>
                                </a>
                            </h4>
                            <span class="price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                        </div>
                        
                        <div class="menu-item-rating" onclick="openReviewsModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item_name, ENT_QUOTES); ?>')" title="Xem <?php echo $total_reviews; ?> đánh giá">
                            <div class="rating-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $avg_rating): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif($i - 0.5 <= $avg_rating): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">
                                <strong><?php echo $avg_rating; ?></strong>
                                (<?php echo $total_reviews; ?> đánh giá)
                            </span>
                        </div>
                        
                        <p class="menu-item-desc"><?php echo htmlspecialchars($item_desc); ?></p>
                        
                        <div class="menu-item-footer">
                            <span class="availability <?php echo $item['is_available'] ? 'available' : 'unavailable'; ?>">
                                <?php echo $item['is_available'] ? __('available') : __('unavailable'); ?>
                            </span>
                            
                            <div class="menu-item-actions">
                                <button onclick="window.location.href='index.php?page=menu-item-detail&id=<?php echo $item['id']; ?>'" class="btn btn-small btn-secondary">
                                    <i class="fas fa-eye"></i> Xem
                                </button>
                                
                                <?php if (isset($_SESSION['customer_id'])): ?>
                                <button onclick="openReviewModalDirect(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item_name, ENT_QUOTES); ?>')" class="btn btn-small btn-warning">
                                    <i class="fas fa-star"></i> Đánh giá
                                </button>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['customer_id']) && $item['is_available']): ?>
                                <button onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item_name); ?>')" class="btn btn-small btn-primary">
                                    <i class="fas fa-shopping-cart"></i> Thêm
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        
        <?php } ?>
    </div>
</section>



<!-- Include Reviews Modal CSS & JS -->
<link rel="stylesheet" href="assets/css/reviews-modal.css">
<script src="assets/js/reviews-modal.js"></script>

<script>
// Function to open review modal directly for writing review
function openReviewModalDirect(menuItemId, menuItemName) {
    // Redirect to menu item detail page with review modal open
    window.location.href = `index.php?page=menu-item-detail&id=${menuItemId}&action=review`;
}
</script>

<style>
.menu-item-link {
    display: block;
    text-decoration: none;
}

.menu-item-image {
    position: relative;
    overflow: hidden;
    border-radius: 16px 16px 0 0;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.menu-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.menu-item:hover .menu-item-image img {
    transform: scale(1.1);
}

.menu-item-icon {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    transition: transform 0.3s ease;
}

.menu-item:hover .menu-item-icon {
    transform: scale(1.2) rotate(5deg);
}

.menu-item-header h4 a {
    color: inherit;
    text-decoration: none;
    transition: color 0.3s ease;
}

.menu-item-header h4 a:hover {
    color: #667eea;
}

.menu-item-rating-link {
    text-decoration: none;
    display: block;
    margin: 0.75rem 0;
}

.menu-item-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.menu-item-rating-link:hover .menu-item-rating {
    background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.menu-item-rating .rating-stars {
    display: flex;
    gap: 0.15rem;
}

.menu-item-rating .rating-stars i {
    color: #f59e0b;
    font-size: 0.9rem;
}

.menu-item-rating .rating-stars i.far {
    color: #e2e8f0;
}

.menu-item-rating .rating-text {
    font-size: 0.85rem;
    color: #92400e;
    font-weight: 600;
}

.menu-item-rating .rating-text strong {
    color: #78350f;
    font-weight: 700;
    font-size: 0.95rem;
}

.menu-item-footer {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}

.menu-item-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.menu-item-actions .btn {
    text-align: center;
    padding: 0.7rem 0.5rem;
    font-size: 0.85rem;
    white-space: nowrap;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
}

.menu-item-actions .btn i {
    font-size: 0.9rem;
}

.btn-warning {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    border: none;
    font-weight: 600;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

@media (max-width: 768px) {
    .menu-item-actions {
        grid-template-columns: 1fr;
    }
    
    .menu-item-actions .btn {
        width: 100%;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .menu-item-actions {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.4rem;
    }
    
    .menu-item-actions .btn {
        font-size: 0.8rem;
        padding: 0.6rem 0.3rem;
    }
}
</style>
