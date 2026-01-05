<?php
// Component hiển thị reviews cho món ăn
// Sử dụng: include 'includes/menu-item-reviews.php' với biến $menu_item_id

if (!isset($menu_item_id)) {
    return;
}

$db = new Database();
$conn = $db->connect();

// Lấy thống kê đánh giá
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating
    FROM reviews 
    WHERE menu_item_id = ? AND is_approved = TRUE
");
$stmt->execute([$menu_item_id]);
$review_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$avg_rating = $review_stats['avg_rating'] ? round($review_stats['avg_rating'], 1) : 0;
$total_reviews = $review_stats['total_reviews'];
?>

<div class="menu-item-rating" onclick="scrollToReviews()" style="cursor: pointer;" title="Xem tất cả đánh giá">
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

<style>
.menu-item-rating {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0.75rem 0;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.menu-item-rating:hover {
    background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.rating-stars {
    display: flex;
    gap: 0.2rem;
}

.rating-stars i {
    color: #f59e0b;
    font-size: 1rem;
}

.rating-stars i.far {
    color: #e2e8f0;
}

.rating-text {
    font-size: 0.9rem;
    color: #92400e;
    font-weight: 600;
}

.rating-text strong {
    color: #78350f;
    font-weight: 700;
}
</style>

<script>
function scrollToReviews() {
    const reviewsSection = document.getElementById('reviews');
    if (reviewsSection) {
        reviewsSection.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
        
        // Thêm hiệu ứng highlight
        reviewsSection.style.animation = 'highlight 1s ease';
        setTimeout(() => {
            reviewsSection.style.animation = '';
        }, 1000);
    }
}
</script>

<style>
@keyframes highlight {
    0%, 100% {
        background-color: transparent;
    }
    50% {
        background-color: rgba(245, 158, 11, 0.1);
    }
}
</style>
