<?php
$db = new Database();
$conn = $db->connect();
$current_lang = getCurrentLanguage();

$item_id = $_GET['id'] ?? 0;

// Lấy thông tin món ăn
$stmt = $conn->prepare("
    SELECT m.*, c.name as category_name, c.name_en as category_name_en
    FROM menu_items m 
    JOIN categories c ON m.category_id = c.id 
    WHERE m.id = ?
");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo '<div class="container"><p>Không tìm thấy món ăn</p></div>';
    return;
}

// Lấy món ăn liên quan
$stmt = $conn->prepare("SELECT * FROM menu_items WHERE category_id = ? AND id != ? AND is_available = 1 ORDER BY RAND() LIMIT 4");
$stmt->execute([$item['category_id'], $item_id]);
$related_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy đánh giá
$stmt = $conn->prepare("SELECT r.*, c.full_name as customer_name FROM reviews r JOIN customers c ON r.customer_id = c.id WHERE r.menu_item_id = ? ORDER BY r.created_at DESC LIMIT 3");
$stmt->execute([$item_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rating trung bình
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE menu_item_id = ?");
$stmt->execute([$item_id]);
$rating_data = $stmt->fetch(PDO::FETCH_ASSOC);
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = $rating_data['total'] ?? 0;

$item_name = $current_lang === 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
$item_desc = $current_lang === 'en' && !empty($item['description_en']) ? $item['description_en'] : $item['description'];
$cat_name = $current_lang === 'en' && !empty($item['category_name_en']) ? $item['category_name_en'] : $item['category_name'];
?>

<section class="item-detail-page">
    <div class="detail-wrapper">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php?page=home"><i class="fas fa-home"></i></a>
            <span>/</span>
            <a href="index.php?page=menu"><?php echo $current_lang === 'en' ? 'Menu' : 'Thực đơn'; ?></a>
            <span>/</span>
            <span class="current"><?php echo htmlspecialchars($item_name); ?></span>
        </nav>

        <!-- Main Content -->
        <div class="detail-main">
            <!-- Left: Image -->
            <div class="detail-image">
                <div class="image-box">
                    <?php if($item['image']): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
                    <?php else: ?>
                        <div class="no-image"><i class="fas fa-utensils"></i></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Info -->
            <div class="detail-content">
                <span class="category-badge"><?php echo htmlspecialchars($cat_name); ?></span>
                
                <h1><?php echo htmlspecialchars($item_name); ?></h1>
                
                <!-- Rating -->
                <div class="rating-row">
                    <div class="stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="<?php echo $i <= $avg_rating ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-num"><?php echo $avg_rating; ?></span>
                    <span class="rating-count">(<?php echo $total_reviews; ?> <?php echo $current_lang === 'en' ? 'reviews' : 'đánh giá'; ?>)</span>
                </div>

                <p class="description"><?php echo htmlspecialchars($item_desc); ?></p>

                <!-- Price Box -->
                <?php 
                $discount = $item['discount_percent'] ?? 0;
                $has_discount = $discount > 0;
                $original_price = $item['original_price'] ?? $item['price'];
                ?>
                <div class="price-box">
                    <div class="price-info">
                        <span class="price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                        <?php if($has_discount): ?>
                        <span class="original-price"><?php echo number_format($original_price, 0, ',', '.'); ?>đ</span>
                        <span class="discount-tag">-<?php echo $discount; ?>%</span>
                        <?php endif; ?>
                    </div>
                    <?php if($item['is_available']): ?>
                        <span class="status available"><i class="fas fa-check"></i> <?php echo $current_lang === 'en' ? 'Available' : 'Còn món'; ?></span>
                    <?php else: ?>
                        <span class="status sold-out"><i class="fas fa-times"></i> <?php echo $current_lang === 'en' ? 'Sold out' : 'Hết món'; ?></span>
                    <?php endif; ?>
                </div>

                <?php if($item['is_available']): ?>
                <!-- Quantity -->
                <div class="quantity-row">
                    <span><?php echo $current_lang === 'en' ? 'Quantity' : 'Số lượng'; ?>:</span>
                    <div class="qty-control">
                        <button onclick="changeQty(-1)"><i class="fas fa-minus"></i></button>
                        <input type="text" id="qty" value="1" readonly>
                        <button onclick="changeQty(1)"><i class="fas fa-plus"></i></button>
                    </div>
                    <span class="subtotal" id="subtotal"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                </div>

                <!-- Notes -->
                <div class="notes-row">
                    <label><i class="fas fa-edit"></i> <?php echo $current_lang === 'en' ? 'Note' : 'Ghi chú'; ?>:</label>
                    <input type="text" id="note" placeholder="<?php echo $current_lang === 'en' ? 'E.g: Less spicy...' : 'VD: Ít cay...'; ?>">
                </div>

                <!-- Buttons -->
                <div class="btn-row">
                    <button class="btn-cart" onclick="addToCartWithQty(<?php echo $item['id']; ?>)">
                        <i class="fas fa-cart-plus"></i> <?php echo $current_lang === 'en' ? 'Add to cart' : 'Thêm vào giỏ'; ?>
                    </button>
                    <button class="btn-order" onclick="buyNow(<?php echo $item['id']; ?>)">
                        <i class="fas fa-bolt"></i> <?php echo $current_lang === 'en' ? 'Order now' : 'Đặt ngay'; ?>
                    </button>
                    <button class="btn-review" onclick="showReviewModal()">
                        <i class="fas fa-star"></i>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Info Tags -->
                <div class="info-tags">
                    <span><i class="fas fa-clock"></i> 15-20 <?php echo $current_lang === 'en' ? 'mins' : 'phút'; ?></span>
                    <span><i class="fas fa-truck"></i> <?php echo $current_lang === 'en' ? 'Free ship >200k' : 'Free ship >200k'; ?></span>
                </div>
            </div>
        </div>

        <!-- Reviews - Ẩn mặc định, hiện khi bấm ngôi sao -->
        <div class="reviews-block" id="reviewsBlock" style="display: none;">
            <div class="reviews-header">
                <h2><i class="fas fa-star"></i> <?php echo $current_lang === 'en' ? 'Reviews' : 'Đánh giá'; ?></h2>
                <div class="rating-big">
                    <span class="num"><?php echo $avg_rating; ?></span>
                    <div class="stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="<?php echo $i <= $avg_rating ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="count"><?php echo $total_reviews; ?> <?php echo $current_lang === 'en' ? 'reviews' : 'đánh giá'; ?></span>
                </div>
                <button class="btn-close-reviews" onclick="toggleReviews()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Nút viết đánh giá - luôn hiển thị -->
            <div class="write-review-btn">
                <button onclick="showReviewModal()" class="btn-write-review">
                    <i class="fas fa-pen"></i> <?php echo $current_lang === 'en' ? 'Write a review' : 'Viết đánh giá'; ?>
                </button>
            </div>

            <?php if(count($reviews) > 0): ?>
            <div class="reviews-list">
                <?php foreach($reviews as $r): ?>
                <div class="review-item">
                    <div class="avatar"><?php echo strtoupper(substr($r['customer_name'], 0, 1)); ?></div>
                    <div class="review-body">
                        <div class="review-top">
                            <strong><?php echo htmlspecialchars($r['customer_name']); ?></strong>
                            <div class="review-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= $r['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="date"><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($r['comment']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-reviews">
                <p><?php echo $current_lang === 'en' ? 'No reviews yet' : 'Chưa có đánh giá'; ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Related -->
        <?php if(count($related_items) > 0): ?>
        <div class="related-block">
            <h2><i class="fas fa-utensils"></i> <?php echo $current_lang === 'en' ? 'Related dishes' : 'Món tương tự'; ?></h2>
            <div class="related-list">
                <?php foreach($related_items as $rel): 
                    $rel_name = $current_lang === 'en' && !empty($rel['name_en']) ? $rel['name_en'] : $rel['name'];
                ?>
                <a href="index.php?page=menu-item-detail&id=<?php echo $rel['id']; ?>" class="related-item">
                    <div class="rel-img">
                        <?php if($rel['image']): ?>
                            <img src="<?php echo htmlspecialchars($rel['image']); ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-utensils"></i>
                        <?php endif; ?>
                    </div>
                    <div class="rel-info">
                        <h4><?php echo htmlspecialchars($rel_name); ?></h4>
                        <span><?php echo number_format($rel['price'], 0, ',', '.'); ?>đ</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Back -->
        <div class="back-row">
            <a href="index.php?page=menu"><i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'en' ? 'Back to menu' : 'Quay lại thực đơn'; ?></a>
        </div>
    </div>
</section>

<!-- Review Modal -->
<div id="reviewModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" onclick="closeReviewModal()"><i class="fas fa-times"></i></button>
        <h3><?php echo $current_lang === 'en' ? 'Write a review' : 'Viết đánh giá'; ?></h3>
        <p class="modal-subtitle"><?php echo htmlspecialchars($item_name); ?></p>
        
        <form id="reviewForm" onsubmit="submitReview(event)">
            <input type="hidden" name="menu_item_id" value="<?php echo $item['id']; ?>">
            
            <div class="form-field">
                <label><?php echo $current_lang === 'en' ? 'Rating' : 'Đánh giá'; ?></label>
                <div class="star-input" id="starInput">
                    <i class="far fa-star" data-r="1"></i>
                    <i class="far fa-star" data-r="2"></i>
                    <i class="far fa-star" data-r="3"></i>
                    <i class="far fa-star" data-r="4"></i>
                    <i class="far fa-star" data-r="5"></i>
                </div>
                <input type="hidden" name="rating" id="ratingVal">
            </div>
            
            <div class="form-field">
                <label><?php echo $current_lang === 'en' ? 'Comment' : 'Nhận xét'; ?></label>
                <textarea name="comment" required placeholder="<?php echo $current_lang === 'en' ? 'Share your experience...' : 'Chia sẻ trải nghiệm...'; ?>"></textarea>
            </div>
            
            <div class="form-btns">
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> <?php echo $current_lang === 'en' ? 'Submit' : 'Gửi đánh giá'; ?></button>
                <button type="button" onclick="closeReviewModal()" class="btn-cancel"><?php echo $current_lang === 'en' ? 'Cancel' : 'Hủy'; ?></button>
            </div>
        </form>
    </div>
</div>


<style>
/* Clean Modern Detail Page - Light Theme */
.item-detail-page {
    padding: 1.5rem 0 3rem;
    min-height: 100vh;
    background: #ffffff;
}

.detail-wrapper {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 2rem;
}
.breadcrumb a { color: #475569; text-decoration: none; }
.breadcrumb a:hover { color: #22c55e; }
.breadcrumb .current { color: #22c55e; font-weight: 600; }

/* Main Layout */
.detail-main {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 3rem;
    margin-bottom: 3rem;
    background: #ffffff;
    border-radius: 20px;
    padding: 2rem;
    border: 3px solid #22c55e;
    box-shadow: 0 10px 40px rgba(34, 197, 94, 0.15);
}

/* Image */
.detail-image { position: sticky; top: 100px; }
.image-box {
    border-radius: 16px;
    overflow: hidden;
    background: #f8fafc;
    border: 3px solid #22c55e;
}
.image-box img {
    width: 100%;
    height: auto;
    display: block;
}
.no-image {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: #cbd5e1;
}

/* Content */
.detail-content h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0.75rem 0;
    line-height: 1.3;
}

.category-badge {
    display: inline-block;
    padding: 0.4rem 1rem;
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #22c55e;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Rating */
.rating-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.rating-row .stars { color: #fbbf24; font-size: 0.9rem; }
.rating-num { color: #f59e0b; font-weight: 700; }
.rating-count { color: #64748b; font-size: 0.85rem; }

.description {
    color: #475569;
    line-height: 1.7;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

/* Price Box */
.price-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 12px;
    margin-bottom: 1.25rem;
    border: 1px solid rgba(34, 197, 94, 0.2);
}
.price-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.price {
    font-size: 1.75rem;
    font-weight: 800;
    color: #22c55e;
}
.original-price {
    font-size: 1.1rem;
    color: #94a3b8;
    text-decoration: line-through;
}
.discount-tag {
    padding: 0.3rem 0.6rem;
    background: #22c55e;
    color: white;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 700;
}
.status {
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
}
.status.available { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
.status.sold-out { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

/* Quantity */
.quantity-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    color: #475569;
    font-size: 0.9rem;
}
.qty-control {
    display: flex;
    align-items: center;
    background: #f1f5f9;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
.qty-control button {
    width: 36px;
    height: 36px;
    background: transparent;
    border: none;
    color: #22c55e;
    cursor: pointer;
    font-weight: 600;
}
.qty-control button:hover { background: rgba(34, 197, 94, 0.1); }
.qty-control input {
    width: 50px;
    height: 36px;
    text-align: center;
    background: transparent;
    border: none;
    color: #1e293b;
    font-weight: 600;
}
.subtotal { color: #22c55e; font-weight: 700; font-size: 1.1rem; }

/* Notes */
.notes-row {
    margin-bottom: 1.25rem;
}
.notes-row label {
    display: block;
    color: #475569;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}
.notes-row input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #1e293b;
    font-size: 0.9rem;
}
.notes-row input:focus {
    outline: none;
    border-color: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}
.notes-row input::placeholder { color: #94a3b8; }

/* Buttons */
.btn-row {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.btn-cart, .btn-order, .btn-review {
    padding: 0.9rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    font-size: 0.9rem;
}
.btn-cart {
    flex: 1;
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border: 2px solid rgba(34, 197, 94, 0.3);
}
.btn-cart:hover { background: rgba(34, 197, 94, 0.2); border-color: #22c55e; }
.btn-order {
    flex: 1;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}
.btn-order:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4); }
.btn-review {
    background: #fef3c7;
    color: #f59e0b;
    padding: 0.9rem;
    border: 1px solid #fcd34d;
}
.btn-review:hover { background: #fde68a; }

/* Info Tags */
.info-tags {
    display: flex;
    gap: 1.5rem;
    color: #64748b;
    font-size: 0.8rem;
}
.info-tags span { display: flex; align-items: center; gap: 0.4rem; }
.info-tags i { color: #22c55e; }

/* Reviews Block */
.reviews-block {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 3px solid #22c55e;
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.12);
}
.reviews-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.reviews-header h2 {
    font-size: 1.1rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.reviews-header h2 i { color: #fbbf24; }
.rating-big {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #fef3c7;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    border: 1px solid #fcd34d;
}
.rating-big .num { font-size: 1.5rem; font-weight: 800; color: #f59e0b; }
.rating-big .stars { color: #fbbf24; font-size: 0.85rem; }
.rating-big .count { color: #64748b; font-size: 0.8rem; }

.reviews-list { display: flex; flex-direction: column; gap: 1rem; }
.review-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}
.review-body { flex: 1; }
.review-top {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}
.review-top strong { color: #1e293b; font-size: 0.9rem; }
.review-stars { color: #fbbf24; font-size: 0.75rem; }
.date { color: #94a3b8; font-size: 0.75rem; }
.review-body p { color: #475569; font-size: 0.9rem; line-height: 1.6; margin: 0; }

/* Nút viết đánh giá */
.write-review-btn {
    margin-bottom: 1.5rem;
}
.btn-write-review {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-write-review:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
}
.btn-write-review i {
    font-size: 0.9rem;
}

.no-reviews {
    text-align: center;
    padding: 2rem;
    color: #94a3b8;
}

/* Nút đóng reviews */
.btn-close-reviews {
    background: #f1f5f9;
    border: none;
    color: #64748b;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.btn-close-reviews:hover {
    background: #e2e8f0;
    color: #1e293b;
}

/* Related */
.related-block {
    margin-bottom: 2rem;
}
.related-block h2 {
    font-size: 1.1rem;
    color: #1e293b;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.related-block h2 i { color: #22c55e; }
.related-list {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
.related-item {
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    text-decoration: none;
    transition: all 0.2s;
    border: 3px solid #86efac;
    box-shadow: 0 2px 10px rgba(34, 197, 94, 0.1);
}
.related-item:hover {
    transform: translateY(-4px);
    border-color: #22c55e;
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.2);
}
.rel-img {
    aspect-ratio: 1;
    overflow: hidden;
    background: #f8fafc;
}
.rel-img img { width: 100%; height: 100%; object-fit: cover; }
.rel-img i { display: flex; align-items: center; justify-content: center; height: 100%; color: #cbd5e1; font-size: 2rem; }
.rel-info {
    padding: 0.75rem;
}
.rel-info h4 {
    color: #1e293b;
    font-size: 0.85rem;
    margin: 0 0 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rel-info span { color: #22c55e; font-weight: 600; font-size: 0.85rem; }

/* Back */
.back-row {
    text-align: center;
}
.back-row a {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}
.back-row a:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}

/* Modal - Modern Design */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: #ffffff;
    border-radius: 24px;
    padding: 0;
    max-width: 420px;
    width: 100%;
    position: relative;
    border: 2px solid rgba(34, 197, 94, 0.2);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: slideUp 0.4s ease;
    overflow: hidden;
}
.modal-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #22c55e, #16a34a, #22c55e);
}
.modal-close {
    position: absolute;
    top: 1.25rem; right: 1.25rem;
    background: #f1f5f9;
    border: none;
    color: #64748b;
    font-size: 1rem;
    cursor: pointer;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    z-index: 10;
}
.modal-close:hover {
    background: #fee2e2;
    color: #ef4444;
    transform: rotate(90deg);
}
.modal-box h3 {
    color: #1e293b;
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    padding: 2rem 2rem 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.modal-box h3::before {
    content: '✨';
    font-size: 1.25rem;
}
.modal-subtitle {
    color: #22c55e;
    margin: 0;
    padding: 0 2rem 1.5rem;
    font-size: 0.95rem;
    font-weight: 500;
    border-bottom: 1px solid #e2e8f0;
}
#reviewForm {
    padding: 1.5rem 2rem 2rem;
}
.form-field {
    margin-bottom: 1.5rem;
}
.form-field label {
    display: block;
    color: #1e293b;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    letter-spacing: 0.5px;
}
.star-input {
    display: flex;
    gap: 0.5rem;
    font-size: 2.25rem;
    padding: 1rem;
    background: #fef3c7;
    border-radius: 16px;
    justify-content: center;
    border: 2px solid #fcd34d;
    transition: all 0.3s;
}
.star-input:hover {
    border-color: #f59e0b;
}
.star-input i {
    color: #fcd34d;
    cursor: pointer;
    transition: all 0.2s;
}
.star-input i.fas {
    color: #f59e0b;
    text-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
}
.star-input i:hover {
    transform: scale(1.3) rotate(-10deg);
    color: #f59e0b;
}
.form-field textarea {
    width: 100%;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    color: #1e293b;
    min-height: 120px;
    resize: vertical;
    font-size: 0.95rem;
    line-height: 1.6;
    transition: all 0.3s;
}
.form-field textarea:focus {
    outline: none;
    border-color: #22c55e;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
}
.form-field textarea::placeholder {
    color: #94a3b8;
}
.form-btns {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
}
.btn-submit, .btn-cancel {
    flex: 1;
    padding: 1rem 1.5rem;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    font-size: 0.95rem;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.btn-submit {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
}
.btn-submit:active {
    transform: translateY(0);
}
.btn-cancel {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
.btn-cancel:hover {
    background: #e2e8f0;
    color: #1e293b;
}

/* Responsive */
@media (max-width: 900px) {
    .detail-main { grid-template-columns: 1fr; gap: 1.5rem; }
    .detail-image { position: static; }
    .related-list { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .btn-row { flex-wrap: wrap; }
    .btn-cart, .btn-order { flex: 1 1 100%; }
    .related-list { grid-template-columns: repeat(2, 1fr); }
}

/* Toast Notification */
.toast-notification {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    box-shadow: 0 10px 40px rgba(34, 197, 94, 0.3);
    z-index: 99999;
    opacity: 0;
    transition: all 0.3s ease;
}
.toast-notification.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}
.toast-notification.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
.toast-notification.success i {
    color: #fff;
}
</style>

<script>
const itemPrice = <?php echo $item['price']; ?>;
let qty = 1;

function changeQty(delta) {
    qty = Math.max(1, Math.min(99, qty + delta));
    document.getElementById('qty').value = qty;
    document.getElementById('subtotal').textContent = (itemPrice * qty).toLocaleString('vi-VN') + 'đ';
}

function toggleReviews() {
    const reviewsBlock = document.getElementById('reviewsBlock');
    if (reviewsBlock.style.display === 'none') {
        reviewsBlock.style.display = 'block';
        reviewsBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        reviewsBlock.style.display = 'none';
    }
}

function showReviewModal() {
    <?php if(!isset($_SESSION['customer_id'])): ?>
    alert('<?php echo $current_lang === 'en' ? 'Please login to review' : 'Vui lòng đăng nhập để đánh giá'; ?>');
    window.location.href = 'auth/login.php';
    return;
    <?php endif; ?>
    document.getElementById('reviewModal').classList.add('active');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.remove('active');
}

// Star rating
let selectedRating = 0;
document.querySelectorAll('#starInput i').forEach(star => {
    star.addEventListener('click', () => {
        selectedRating = parseInt(star.dataset.r);
        document.getElementById('ratingVal').value = selectedRating;
        document.querySelectorAll('#starInput i').forEach((s, i) => {
            s.className = i < selectedRating ? 'fas fa-star' : 'far fa-star';
        });
    });
});

async function submitReview(e) {
    e.preventDefault();
    if (!selectedRating) { alert('Vui lòng chọn số sao'); return; }
    const formData = new FormData(e.target);
    try {
        const res = await fetch('api/submit-review.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert('Cảm ơn bạn đã đánh giá!');
            closeReviewModal();
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    } catch (err) {
        alert('Có lỗi xảy ra');
    }
}

async function addToCartWithQty(itemId, showAlert = true) {
    const note = document.getElementById('note')?.value || '';
    const formData = new FormData();
    formData.append('menu_item_id', itemId);
    formData.append('quantity', qty);
    formData.append('note', note);
    try {
        const res = await fetch('api/cart.php?action=add', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (showAlert) {
                // Hiện toast thay vì alert
                showToast('Đã thêm vào giỏ hàng!', 'success');
            }
            if (document.querySelector('.cart-badge')) {
                document.querySelector('.cart-badge').textContent = data.cart_count || '';
            }
            return true;
        } else {
            if (showAlert) alert(data.message || 'Có lỗi xảy ra');
            return false;
        }
    } catch (err) {
        if (showAlert) alert('Có lỗi xảy ra');
        return false;
    }
}

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'toast-notification ' + type;
    toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'info-circle') + '"></i> ' + message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

function buyNow(itemId) {
    const note = document.getElementById('note')?.value || '';
    // Lưu thông tin mua ngay vào sessionStorage
    const buyNowData = {
        item_id: itemId,
        item_name: '<?php echo addslashes($item_name); ?>',
        item_image: '<?php echo addslashes($item['image'] ?? ''); ?>',
        price: <?php echo $item['price']; ?>,
        quantity: qty,
        note: note
    };
    sessionStorage.setItem('buyNowItem', JSON.stringify(buyNowData));
    window.location.href = 'index.php?page=checkout&mode=buynow';
}
</script>
