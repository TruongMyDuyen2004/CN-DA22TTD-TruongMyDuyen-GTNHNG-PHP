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

                <p class="description"><?php 
                    // Tách mô tả và thành phần xuống dòng
                    $desc_formatted = $item_desc;
                    // Xuống dòng trước "Thành phần:" - chỉ 1 lần
                    $desc_formatted = preg_replace('/\.\s*(Thành phần:)/iu', ".<br><strong>$1</strong>", $desc_formatted);
                    // Nếu không có dấu chấm trước Thành phần
                    $desc_formatted = preg_replace('/([^<])(Thành phần:)/iu', "$1<br><strong>$2</strong>", $desc_formatted);
                    echo $desc_formatted; 
                ?></p>

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
                        <input type="number" id="qty" value="1" min="1" max="99" onchange="updateQty(this.value)" onkeyup="updateQty(this.value)">
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
                <?php foreach($reviews as $r): 
                    $is_owner = isset($_SESSION['customer_id']) && $r['customer_id'] == $_SESSION['customer_id'];
                ?>
                <div class="review-item" data-review-id="<?php echo $r['id']; ?>">
                    <div class="avatar"><?php echo strtoupper(substr($r['customer_name'], 0, 1)); ?></div>
                    <div class="review-body">
                        <div class="review-top">
                            <strong><?php echo htmlspecialchars($r['customer_name']); ?></strong>
                            <div class="review-stars" data-rating="<?php echo $r['rating']; ?>">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= $r['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="date"><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></span>
                            <?php if($is_owner): ?>
                            <div class="review-actions">
                                <button class="review-action-btn edit-btn" onclick="editReview(<?php echo $r['id']; ?>, <?php echo $r['rating']; ?>, '<?php echo addslashes(htmlspecialchars($r['comment'])); ?>')" title="Sửa đánh giá">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="review-action-btn delete-btn" onclick="deleteReview(<?php echo $r['id']; ?>)" title="Xóa đánh giá">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <p class="review-comment"><?php echo htmlspecialchars($r['comment']); ?></p>
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

<!-- Edit Review Modal -->
<div id="editReviewModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" onclick="closeEditReviewModal()"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-edit"></i> <?php echo $current_lang === 'en' ? 'Edit review' : 'Sửa đánh giá'; ?></h3>
        <p class="modal-subtitle"><?php echo htmlspecialchars($item_name); ?></p>
        
        <form id="editReviewForm" onsubmit="submitEditReview(event)">
            <input type="hidden" name="review_id" id="editReviewId">
            
            <div class="form-field">
                <label><?php echo $current_lang === 'en' ? 'Rating' : 'Đánh giá'; ?></label>
                <div class="star-input" id="editStarInput">
                    <i class="far fa-star" data-r="1"></i>
                    <i class="far fa-star" data-r="2"></i>
                    <i class="far fa-star" data-r="3"></i>
                    <i class="far fa-star" data-r="4"></i>
                    <i class="far fa-star" data-r="5"></i>
                </div>
                <input type="hidden" name="rating" id="editRatingVal">
            </div>
            
            <div class="form-field">
                <label><?php echo $current_lang === 'en' ? 'Comment' : 'Nhận xét'; ?></label>
                <textarea name="comment" id="editCommentVal" required placeholder="<?php echo $current_lang === 'en' ? 'Share your experience...' : 'Chia sẻ trải nghiệm...'; ?>"></textarea>
            </div>
            
            <div class="form-btns">
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> <?php echo $current_lang === 'en' ? 'Save' : 'Lưu thay đổi'; ?></button>
                <button type="button" onclick="closeEditReviewModal()" class="btn-cancel"><?php echo $current_lang === 'en' ? 'Cancel' : 'Hủy'; ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="deleteConfirmModal" class="modal-overlay">
    <div class="modal-box delete-confirm-box">
        <div class="delete-confirm-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3><?php echo $current_lang === 'en' ? 'Delete review?' : 'Xóa đánh giá?'; ?></h3>
        <p><?php echo $current_lang === 'en' ? 'This action cannot be undone.' : 'Hành động này không thể hoàn tác.'; ?></p>
        <input type="hidden" id="deleteReviewId">
        <div class="form-btns">
            <button type="button" class="btn-delete-confirm" onclick="confirmDeleteReview()">
                <i class="fas fa-trash-alt"></i> <?php echo $current_lang === 'en' ? 'Delete' : 'Xóa'; ?>
            </button>
            <button type="button" onclick="closeDeleteConfirmModal()" class="btn-cancel"><?php echo $current_lang === 'en' ? 'Cancel' : 'Hủy'; ?></button>
        </div>
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
    -moz-appearance: textfield;
}
.qty-control input::-webkit-outer-spin-button,
.qty-control input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.qty-control input:focus {
    outline: none;
    background: rgba(34, 197, 94, 0.1);
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

/* Review Actions - Edit/Delete buttons */
.review-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}
.review-action-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 0.85rem;
}
.review-action-btn.edit-btn {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}
.review-action-btn.edit-btn:hover {
    background: rgba(59, 130, 246, 0.2);
    transform: scale(1.05);
}
.review-action-btn.delete-btn {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}
.review-action-btn.delete-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    transform: scale(1.05);
}
.review-top {
    flex-wrap: wrap;
}

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

/* Modal - Premium Modern Design */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
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
    max-width: 480px;
    width: 100%;
    position: relative;
    border: 1px solid #e2e8f0;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    overflow: hidden;
}
.modal-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #22c55e, #16a34a);
}
.modal-close {
    position: absolute;
    top: 1.25rem; right: 1.25rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    color: #64748b;
    font-size: 0.9rem;
    cursor: pointer;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    z-index: 10;
}
.modal-close:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #ef4444;
    transform: rotate(90deg);
}
.modal-box h3 {
    color: #0f172a;
    margin: 0;
    font-size: 1.35rem;
    font-weight: 700;
    padding: 2rem 2rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    letter-spacing: -0.02em;
}
.modal-box h3 i {
    color: #22c55e;
    font-size: 1.1rem;
}
.modal-box h3::before {
    display: none;
}
.modal-subtitle {
    color: #22c55e;
    margin: 0;
    padding: 0 2rem 1.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    border-bottom: 2px solid #f1f5f9;
}
#reviewForm, #editReviewForm {
    padding: 1.5rem 2rem 2rem;
}
.form-field {
    margin-bottom: 1.5rem;
}
.form-field label {
    display: block;
    color: #374151;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    letter-spacing: 0.01em;
}
.star-input {
    display: flex;
    gap: 0.75rem;
    font-size: 1.75rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-radius: 12px;
    justify-content: center;
    border: 2px solid #e2e8f0;
    transition: all 0.2s ease;
}
.star-input:hover {
    border-color: #22c55e;
    background: #f0fdf4;
}
.star-input i {
    color: #cbd5e1;
    cursor: pointer;
    transition: all 0.2s ease;
}
.star-input i.fas {
    color: #f59e0b;
    filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.3));
}
.star-input i:hover {
    transform: scale(1.2);
    color: #fbbf24;
}
.form-field textarea {
    width: 100%;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    color: #1e293b !important;
    min-height: 120px;
    resize: vertical;
    font-size: 1rem;
    line-height: 1.6;
    transition: all 0.2s ease;
    font-family: inherit;
}
.form-field textarea:focus {
    outline: none;
    border-color: #22c55e;
    background: #ffffff !important;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
}
.form-field textarea::placeholder {
    color: #94a3b8 !important;
}
.form-btns {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.25rem;
    border-top: 2px solid #f1f5f9;
}
.btn-submit, .btn-cancel {
    flex: 1;
    padding: 1rem 1.5rem;
    border-radius: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-size: 1rem;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.btn-submit {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(34, 197, 94, 0.45);
}
.btn-submit:active {
    transform: translateY(0);
}
.btn-cancel {
    background: #f8fafc;
    color: #64748b;
    border: 2px solid #e2e8f0;
}
.btn-cancel:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #475569;
}

/* Delete Confirm Modal */
.delete-confirm-box {
    text-align: center;
    padding: 2rem 1.75rem !important;
}
.delete-confirm-box h3 {
    padding: 0 !important;
    margin-bottom: 0.5rem !important;
}
.delete-confirm-box h3::before {
    display: none !important;
}
.delete-confirm-box p {
    color: #64748b;
    margin-bottom: 1.5rem;
}
.delete-confirm-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
}
.delete-confirm-icon i {
    font-size: 2rem;
    color: #ef4444;
}
.btn-delete-confirm {
    flex: 1;
    padding: 0.875rem 1.25rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-size: 0.9rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}
.btn-delete-confirm:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4);
}

/* Override dark theme for review modal */
.modal-box .form-field textarea,
body.dark-theme .modal-box .form-field textarea {
    background: #ffffff !important;
    color: #1e293b !important;
    border-color: #e2e8f0 !important;
}
body.dark-theme .modal-box .form-field textarea::placeholder {
    color: #9ca3af !important;
}
body.dark-theme .modal-box .form-field textarea:focus {
    background: #ffffff !important;
    border-color: #22c55e !important;
}

/* Thank You Message - Premium Design */
.thank-you-message {
    padding: 2.5rem 2rem 2rem;
    text-align: center;
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 50%);
    min-height: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.thank-you-animation {
    position: relative;
    width: 100px;
    height: 100px;
    margin-bottom: 1.5rem;
}

.circle-bg {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(16, 185, 129, 0.1) 100%);
    border-radius: 50%;
    animation: pulseRing 2s ease-out infinite;
}

@keyframes pulseRing {
    0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(1.4); opacity: 0; }
}

.checkmark-circle {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 80px;
}

.checkmark {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: block;
    stroke-width: 3;
    stroke: #22c55e;
    stroke-miterlimit: 10;
    box-shadow: 0 8px 30px rgba(34, 197, 94, 0.3);
    animation: scaleUp 0.4s ease-in-out 0.2s both;
}

@keyframes scaleUp {
    0% { transform: scale(0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.checkmark-circle-bg {
    stroke: #22c55e;
    fill: #22c55e;
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    animation: strokeCircle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

@keyframes strokeCircle {
    100% { stroke-dashoffset: 0; }
}

.checkmark-check {
    stroke: #ffffff;
    stroke-width: 4;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: strokeCheck 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.5s forwards;
}

@keyframes strokeCheck {
    100% { stroke-dashoffset: 0; }
}

/* Confetti */
.confetti {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.confetti-piece {
    position: absolute;
    width: 10px;
    height: 10px;
    top: 50%;
    left: 50%;
    opacity: 0;
    animation: confettiFall 1s ease-out 0.6s forwards;
}

.confetti-piece:nth-child(1) { background: #f59e0b; border-radius: 50%; animation-delay: 0.6s; }
.confetti-piece:nth-child(2) { background: #22c55e; border-radius: 2px; animation-delay: 0.7s; }
.confetti-piece:nth-child(3) { background: #3b82f6; border-radius: 50%; animation-delay: 0.8s; }
.confetti-piece:nth-child(4) { background: #ef4444; border-radius: 2px; animation-delay: 0.65s; }
.confetti-piece:nth-child(5) { background: #8b5cf6; border-radius: 50%; animation-delay: 0.75s; }
.confetti-piece:nth-child(6) { background: #ec4899; border-radius: 2px; animation-delay: 0.85s; }

@keyframes confettiFall {
    0% { transform: translate(0, 0) rotate(0deg) scale(0); opacity: 1; }
    100% { opacity: 0; }
}

.confetti-piece:nth-child(1) { animation-name: confetti1; }
.confetti-piece:nth-child(2) { animation-name: confetti2; }
.confetti-piece:nth-child(3) { animation-name: confetti3; }
.confetti-piece:nth-child(4) { animation-name: confetti4; }
.confetti-piece:nth-child(5) { animation-name: confetti5; }
.confetti-piece:nth-child(6) { animation-name: confetti6; }

@keyframes confetti1 { 0% { transform: translate(0,0) scale(0); opacity:1; } 100% { transform: translate(-50px,-60px) rotate(180deg) scale(1); opacity:0; } }
@keyframes confetti2 { 0% { transform: translate(0,0) scale(0); opacity:1; } 100% { transform: translate(50px,-50px) rotate(-120deg) scale(1); opacity:0; } }
@keyframes confetti3 { 0% { transform: translate(0,0) scale(0); opacity:1; } 100% { transform: translate(-40px,50px) rotate(90deg) scale(1); opacity:0; } }
@keyframes confetti4 { 0% { transform: translate(0,0) scale(0); opacity:1; } 100% { transform: translate(45px,45px) rotate(-90deg) scale(1); opacity:0; } }
@keyframes confetti5 { 0% { transform: translate(0,0) scale(0); opacity:1; } 100% { transform: translate(-60px,20px) rotate(150deg) scale(1); opacity:0; } }
@keyframes confetti6 { 0% { transform: translate(0,0) scale(0); opacity:1; } 100% { transform: translate(55px,-30px) rotate(-150deg) scale(1); opacity:0; } }

.thank-you-message h3 {
    color: #0f172a;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    padding: 0;
    animation: fadeInUp 0.5s ease 0.3s both;
}

.thank-you-message h3::before {
    display: none;
}

.thank-you-message p {
    color: #64748b;
    font-size: 0.95rem;
    margin: 0 0 1.25rem;
    line-height: 1.5;
    animation: fadeInUp 0.5s ease 0.4s both;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.thank-you-stars {
    display: flex;
    gap: 0.35rem;
    justify-content: center;
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.5s ease 0.5s both;
}

.thank-you-stars i {
    color: #f59e0b;
    font-size: 1.25rem;
    animation: starPop 0.3s ease both;
}

.thank-you-stars i:nth-child(1) { animation-delay: 0.6s; }
.thank-you-stars i:nth-child(2) { animation-delay: 0.7s; }
.thank-you-stars i:nth-child(3) { animation-delay: 0.8s; }
.thank-you-stars i:nth-child(4) { animation-delay: 0.9s; }
.thank-you-stars i:nth-child(5) { animation-delay: 1s; }

@keyframes starPop {
    0% { transform: scale(0); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

.thank-you-footer {
    width: 100%;
    animation: fadeInUp 0.5s ease 0.6s both;
}

.countdown-bar {
    width: 100%;
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.75rem;
}

.countdown-progress {
    height: 100%;
    background: linear-gradient(90deg, #22c55e, #10b981);
    border-radius: 2px;
    animation: countdownBar 5s linear forwards;
}

@keyframes countdownBar {
    from { width: 100%; }
    to { width: 0%; }
}

.countdown-text {
    color: #94a3b8;
    font-size: 0.8rem;
}

.countdown-text strong {
    color: #22c55e;
    font-weight: 700;
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

function updateQty(value) {
    let newQty = parseInt(value) || 1;
    newQty = Math.max(1, Math.min(99, newQty));
    qty = newQty;
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
            // Hiện thông báo cảm ơn trong modal
            showThankYouMessage();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    } catch (err) {
        alert('Có lỗi xảy ra');
    }
}

// Hiện thông báo cảm ơn sau khi đánh giá
function showThankYouMessage() {
    const modalBox = document.querySelector('#reviewModal .modal-box');
    modalBox.innerHTML = `
        <div class="thank-you-message">
            <div class="thank-you-animation">
                <div class="circle-bg"></div>
                <div class="checkmark-circle">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark-circle-bg" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>
                <div class="confetti">
                    <div class="confetti-piece"></div>
                    <div class="confetti-piece"></div>
                    <div class="confetti-piece"></div>
                    <div class="confetti-piece"></div>
                    <div class="confetti-piece"></div>
                    <div class="confetti-piece"></div>
                </div>
            </div>
            <h3>Cảm ơn bạn!</h3>
            <p>Đánh giá của bạn rất có giá trị với chúng tôi</p>
            <div class="thank-you-stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
            </div>
            <div class="thank-you-footer">
                <div class="countdown-bar">
                    <div class="countdown-progress"></div>
                </div>
                <span class="countdown-text">Tự động đóng sau <strong id="countdownTimer">5</strong>s</span>
            </div>
        </div>
    `;
    
    // Đếm ngược 5 giây
    let countdown = 5;
    const timer = setInterval(() => {
        countdown--;
        const timerEl = document.getElementById('countdownTimer');
        if (timerEl) timerEl.textContent = countdown;
        
        if (countdown <= 0) {
            clearInterval(timer);
            closeReviewModal();
            location.reload();
        }
    }, 1000);
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

// ========== EDIT/DELETE REVIEW FUNCTIONS ==========

let editSelectedRating = 0;

// Initialize edit star rating
document.querySelectorAll('#editStarInput i').forEach(star => {
    star.addEventListener('click', () => {
        editSelectedRating = parseInt(star.dataset.r);
        document.getElementById('editRatingVal').value = editSelectedRating;
        document.querySelectorAll('#editStarInput i').forEach((s, i) => {
            s.className = i < editSelectedRating ? 'fas fa-star' : 'far fa-star';
        });
    });
});

// Open edit review modal
function editReview(reviewId, rating, comment) {
    document.getElementById('editReviewId').value = reviewId;
    document.getElementById('editRatingVal').value = rating;
    document.getElementById('editCommentVal').value = comment;
    
    // Set stars
    editSelectedRating = rating;
    document.querySelectorAll('#editStarInput i').forEach((s, i) => {
        s.className = i < rating ? 'fas fa-star' : 'far fa-star';
    });
    
    document.getElementById('editReviewModal').classList.add('active');
}

// Close edit review modal
function closeEditReviewModal() {
    document.getElementById('editReviewModal').classList.remove('active');
}

// Submit edit review
async function submitEditReview(e) {
    e.preventDefault();
    
    if (!editSelectedRating) {
        alert('Vui lòng chọn số sao');
        return;
    }
    
    const formData = new FormData(e.target);
    
    try {
        const res = await fetch('api/update-review.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            showToast('Đã cập nhật đánh giá!', 'success');
            closeEditReviewModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    } catch (err) {
        alert('Có lỗi xảy ra');
    }
}

// Open delete confirm modal
function deleteReview(reviewId) {
    document.getElementById('deleteReviewId').value = reviewId;
    document.getElementById('deleteConfirmModal').classList.add('active');
}

// Close delete confirm modal
function closeDeleteConfirmModal() {
    document.getElementById('deleteConfirmModal').classList.remove('active');
}

// Confirm delete review
async function confirmDeleteReview() {
    const reviewId = document.getElementById('deleteReviewId').value;
    
    const formData = new FormData();
    formData.append('review_id', reviewId);
    
    try {
        const res = await fetch('api/delete-review.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            showToast('Đã xóa đánh giá!', 'success');
            closeDeleteConfirmModal();
            
            // Remove review item from DOM
            const reviewItem = document.querySelector(`.review-item[data-review-id="${reviewId}"]`);
            if (reviewItem) {
                reviewItem.style.transition = 'all 0.3s';
                reviewItem.style.opacity = '0';
                reviewItem.style.transform = 'translateX(-20px)';
                setTimeout(() => reviewItem.remove(), 300);
            }
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    } catch (err) {
        alert('Có lỗi xảy ra');
    }
}

// Close modals when clicking outside
document.getElementById('editReviewModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditReviewModal();
});
document.getElementById('deleteConfirmModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteConfirmModal();
});
</script>
