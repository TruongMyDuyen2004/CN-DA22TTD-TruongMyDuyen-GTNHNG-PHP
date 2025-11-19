<?php
$db = new Database();
$conn = $db->connect();
$current_lang = getCurrentLanguage();

// Lấy tham số lọc
$filter_rating = $_GET['rating'] ?? '';
$filter_menu_item = $_GET['menu_item'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest'; // newest, oldest, highest, lowest

// Xây dựng query
$customer_id = $_SESSION['customer_id'] ?? 0;
$sql = "SELECT r.*, 
        c.full_name as customer_name,
        c.avatar as customer_avatar,
        m.name as menu_item_name,
        m.name_en as menu_item_name_en,
        m.image as menu_item_image,
        (SELECT COUNT(*) FROM review_likes WHERE review_id = r.id) as likes_count,
        CASE WHEN EXISTS(SELECT 1 FROM review_likes WHERE review_id = r.id AND customer_id = $customer_id) THEN 1 ELSE 0 END as is_liked
        FROM reviews r
        LEFT JOIN customers c ON r.customer_id = c.id
        LEFT JOIN menu_items m ON r.menu_item_id = m.id
        WHERE r.is_approved = TRUE";

$params = [];

if ($filter_rating) {
    $sql .= " AND r.rating = ?";
    $params[] = $filter_rating;
}

if ($filter_menu_item) {
    $sql .= " AND r.menu_item_id = ?";
    $params[] = $filter_menu_item;
}

// Sắp xếp
switch ($sort_by) {
    case 'oldest':
        $sql .= " ORDER BY r.created_at ASC";
        break;
    case 'highest':
        $sql .= " ORDER BY r.rating DESC, r.created_at DESC";
        break;
    case 'lowest':
        $sql .= " ORDER BY r.rating ASC, r.created_at DESC";
        break;
    default: // newest
        $sql .= " ORDER BY r.created_at DESC";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách món ăn để lọc
$stmt_items = $conn->prepare("
    SELECT DISTINCT m.id, m.name, m.name_en 
    FROM menu_items m
    INNER JOIN reviews r ON m.id = r.menu_item_id
    WHERE r.is_approved = TRUE
    ORDER BY m.name
");
$stmt_items->execute();
$menu_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Thống kê tổng quan
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM reviews 
    WHERE is_approved = TRUE
");
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<section class="all-reviews-section">
    <div class="container">
        <!-- Header -->
        <div class="reviews-header">
            <h1>
                <i class="fas fa-star"></i>
                <?php echo $current_lang === 'en' ? 'All Reviews' : 'Tất cả đánh giá'; ?>
            </h1>
            <p class="reviews-subtitle">
                <?php echo $current_lang === 'en' 
                    ? 'See what our customers say about us' 
                    : 'Xem khách hàng nói gì về chúng tôi'; ?>
            </p>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="reviews-stats-card">
            <div class="stats-main">
                <div class="stats-rating-big">
                    <div class="rating-number"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                    <div class="rating-stars-big">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= $stats['avg_rating']): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif($i - 0.5 <= $stats['avg_rating']): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-count">
                        <?php echo $stats['total_reviews']; ?> 
                        <?php echo $current_lang === 'en' ? 'reviews' : 'đánh giá'; ?>
                    </div>
                </div>
                
                <div class="stats-breakdown">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                    <div class="stat-row">
                        <span class="stat-label"><?php echo $i; ?> <i class="fas fa-star"></i></span>
                        <div class="stat-bar">
                            <?php 
                            $count_key = ['one_star', 'two_star', 'three_star', 'four_star', 'five_star'][$i-1];
                            $count = $stats[$count_key];
                            $percentage = $stats['total_reviews'] > 0 ? ($count / $stats['total_reviews']) * 100 : 0;
                            ?>
                            <div class="stat-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <span class="stat-count"><?php echo $count; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="reviews-filters">
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="page" value="all-reviews">
                
                <div class="filter-group">
                    <label>
                        <i class="fas fa-star"></i>
                        <?php echo $current_lang === 'en' ? 'Rating' : 'Số sao'; ?>
                    </label>
                    <select name="rating" onchange="this.form.submit()">
                        <option value=""><?php echo $current_lang === 'en' ? 'All ratings' : 'Tất cả'; ?></option>
                        <option value="5" <?php echo $filter_rating == '5' ? 'selected' : ''; ?>>5 ⭐</option>
                        <option value="4" <?php echo $filter_rating == '4' ? 'selected' : ''; ?>>4 ⭐</option>
                        <option value="3" <?php echo $filter_rating == '3' ? 'selected' : ''; ?>>3 ⭐</option>
                        <option value="2" <?php echo $filter_rating == '2' ? 'selected' : ''; ?>>2 ⭐</option>
                        <option value="1" <?php echo $filter_rating == '1' ? 'selected' : ''; ?>>1 ⭐</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>
                        <i class="fas fa-utensils"></i>
                        <?php echo $current_lang === 'en' ? 'Dish' : 'Món ăn'; ?>
                    </label>
                    <select name="menu_item" onchange="this.form.submit()">
                        <option value=""><?php echo $current_lang === 'en' ? 'All dishes' : 'Tất cả món'; ?></option>
                        <?php foreach($menu_items as $item): 
                            $item_name = $current_lang === 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
                        ?>
                        <option value="<?php echo $item['id']; ?>" <?php echo $filter_menu_item == $item['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item_name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>
                        <i class="fas fa-sort"></i>
                        <?php echo $current_lang === 'en' ? 'Sort by' : 'Sắp xếp'; ?>
                    </label>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>
                            <?php echo $current_lang === 'en' ? 'Newest first' : 'Mới nhất'; ?>
                        </option>
                        <option value="oldest" <?php echo $sort_by == 'oldest' ? 'selected' : ''; ?>>
                            <?php echo $current_lang === 'en' ? 'Oldest first' : 'Cũ nhất'; ?>
                        </option>
                        <option value="highest" <?php echo $sort_by == 'highest' ? 'selected' : ''; ?>>
                            <?php echo $current_lang === 'en' ? 'Highest rating' : 'Điểm cao nhất'; ?>
                        </option>
                        <option value="lowest" <?php echo $sort_by == 'lowest' ? 'selected' : ''; ?>>
                            <?php echo $current_lang === 'en' ? 'Lowest rating' : 'Điểm thấp nhất'; ?>
                        </option>
                    </select>
                </div>
                
                <?php if ($filter_rating || $filter_menu_item || $sort_by != 'newest'): ?>
                <a href="?page=all-reviews" class="btn-reset-filter">
                    <i class="fas fa-times"></i>
                    <?php echo $current_lang === 'en' ? 'Clear filters' : 'Xóa bộ lọc'; ?>
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Danh sách đánh giá -->
        <div class="reviews-list">
            <?php if (count($reviews) > 0): ?>
                <?php foreach($reviews as $review): 
                    $menu_name = $current_lang === 'en' && !empty($review['menu_item_name_en']) 
                        ? $review['menu_item_name_en'] 
                        : $review['menu_item_name'];
                ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-user">
                            <div class="user-avatar">
                                <?php if (!empty($review['customer_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($review['customer_avatar']); ?>" alt="Avatar">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                <div class="review-meta">
                                    <span class="review-date">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                    </span>
                                    <?php if ($review['menu_item_id']): ?>
                                    <span class="review-dish">
                                        <i class="fas fa-utensils"></i>
                                        <a href="index.php?page=menu-item-detail&id=<?php echo $review['menu_item_id']; ?>">
                                            <?php echo htmlspecialchars($menu_name); ?>
                                        </a>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="review-rating">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <?php if ($review['comment']): ?>
                    <div class="review-content">
                        <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($review['menu_item_image']): ?>
                    <div class="review-dish-image">
                        <img src="<?php echo htmlspecialchars($review['menu_item_image']); ?>" 
                             alt="<?php echo htmlspecialchars($menu_name); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="review-footer">
                        <button class="btn-like <?php echo $review['is_liked'] ? 'liked' : ''; ?>" 
                                data-review-id="<?php echo $review['id']; ?>"
                                onclick="toggleLikeReview(this, <?php echo $review['id']; ?>)">
                            <i class="<?php echo $review['is_liked'] ? 'fas' : 'far'; ?> fa-thumbs-up"></i>
                            <span class="like-count"><?php echo $review['likes_count'] ?? 0; ?></span>
                            <span class="like-text"><?php echo $current_lang === 'en' ? 'Like' : 'Thích'; ?></span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reviews">
                    <i class="fas fa-star"></i>
                    <h3><?php echo $current_lang === 'en' ? 'No reviews yet' : 'Chưa có đánh giá'; ?></h3>
                    <p><?php echo $current_lang === 'en' 
                        ? 'Be the first to leave a review!' 
                        : 'Hãy là người đầu tiên để lại đánh giá!'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.all-reviews-section {
    padding: 4rem 0;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 100vh;
}

.reviews-header {
    text-align: center;
    margin-bottom: 3rem;
}

.reviews-header h1 {
    font-size: 2.5rem;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.reviews-header h1 i {
    color: #f59e0b;
    margin-right: 0.5rem;
}

.reviews-subtitle {
    font-size: 1.1rem;
    color: #64748b;
}

.reviews-stats-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.stats-main {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 3rem;
    align-items: center;
}

.stats-rating-big {
    text-align: center;
}

.rating-number {
    font-size: 4rem;
    font-weight: 700;
    color: #f59e0b;
    line-height: 1;
}

.rating-stars-big {
    font-size: 1.5rem;
    color: #f59e0b;
    margin: 0.5rem 0;
}

.rating-count {
    font-size: 1rem;
    color: #64748b;
}

.stats-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.stat-row {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-label {
    min-width: 60px;
    font-weight: 600;
    color: #475569;
}

.stat-label i {
    color: #f59e0b;
}

.stat-bar {
    flex: 1;
    height: 8px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}

.stat-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
    transition: width 0.3s ease;
}

.stat-count {
    min-width: 40px;
    text-align: right;
    color: #64748b;
    font-weight: 600;
}

.reviews-filters {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-form {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #475569;
}

.filter-group label i {
    margin-right: 0.3rem;
    color: #f59e0b;
}

.filter-group select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.filter-group select:focus {
    outline: none;
    border-color: #f59e0b;
}

.btn-reset-filter {
    padding: 0.75rem 1.5rem;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-reset-filter:hover {
    background: #dc2626;
    transform: translateY(-2px);
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.review-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.review-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.review-user {
    display: flex;
    gap: 1rem;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info h4 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
}

.review-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    color: #64748b;
}

.review-meta i {
    margin-right: 0.3rem;
}

.review-dish a {
    color: #f59e0b;
    text-decoration: none;
    font-weight: 600;
}

.review-dish a:hover {
    text-decoration: underline;
}

.review-rating {
    font-size: 1.2rem;
    color: #f59e0b;
}

.review-content {
    margin: 1rem 0;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 10px;
}

.review-content p {
    margin: 0;
    color: #475569;
    line-height: 1.6;
}

.review-dish-image {
    margin: 1rem 0;
    border-radius: 10px;
    overflow: hidden;
}

.review-dish-image img {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
}

.review-footer {
    display: flex;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.btn-like {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #64748b;
    font-weight: 600;
}

.btn-like:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.btn-like.liked {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-color: #60a5fa;
    color: #2563eb;
}

.btn-like.liked i {
    color: #2563eb;
}

.btn-like i {
    font-size: 1.1rem;
    transition: transform 0.3s;
}

.btn-like:hover i {
    transform: scale(1.15);
}

.btn-like.liked i {
    animation: heartBeat 0.5s;
}

.like-text {
    font-size: 0.9rem;
}

@keyframes heartBeat {
    0%, 100% { transform: scale(1); }
    25% { transform: scale(1.3); }
    50% { transform: scale(1.1); }
    75% { transform: scale(1.2); }
}

.no-reviews {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
}

.no-reviews i {
    font-size: 4rem;
    color: #e2e8f0;
    margin-bottom: 1rem;
}

.no-reviews h3 {
    color: #475569;
    margin-bottom: 0.5rem;
}

.no-reviews p {
    color: #94a3b8;
}

@media (max-width: 768px) {
    .stats-main {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .review-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .reviews-header h1 {
        font-size: 2rem;
    }
}
</style>

<script>
async function toggleLikeReview(button, reviewId) {
    // Kiểm tra đăng nhập
    <?php if (!isset($_SESSION['customer_id'])): ?>
    alert('<?php echo $current_lang === "en" ? "Please login to like reviews" : "Vui lòng đăng nhập để thích đánh giá"; ?>');
    window.location.href = 'auth/login.php';
    return;
    <?php endif; ?>
    
    // Disable button
    button.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('review_id', reviewId);
        
        const response = await fetch('api/review-like.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update UI
            const likeCount = button.querySelector('.like-count');
            const icon = button.querySelector('i');
            
            likeCount.textContent = data.likes_count;
            
            if (data.action === 'liked') {
                button.classList.add('liked');
                icon.className = 'fas fa-thumbs-up';
            } else {
                button.classList.remove('liked');
                icon.className = 'far fa-thumbs-up';
            }
        } else {
            alert(data.message || '<?php echo $current_lang === "en" ? "An error occurred" : "Có lỗi xảy ra"; ?>');
        }
    } catch (error) {
        console.error('Error toggling like:', error);
        alert('<?php echo $current_lang === "en" ? "An error occurred while liking the review" : "Có lỗi xảy ra khi thích đánh giá"; ?>');
    } finally {
        button.disabled = false;
    }
}
</script>
