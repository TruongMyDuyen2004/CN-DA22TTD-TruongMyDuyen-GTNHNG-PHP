<?php
$db = new Database();
$conn = $db->connect();
$current_lang = getCurrentLanguage();

// Lấy tham số lọc
$filter_rating = $_GET['rating'] ?? '';
$filter_menu_item = $_GET['menu_item'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';

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

<style>
/* ========================================
   REVIEWS PAGE - MODERN WHITE THEME
   ======================================== */

/* Main Section */
body.dark-theme .modern-reviews-section,
.modern-reviews-section {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
    background-image: none !important;
    min-height: auto;
    padding: 3rem 2rem 5rem;
}

.reviews-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* ========================================
   STATS OVERVIEW - MODERN GLASSMORPHISM
   ======================================== */
body.dark-theme .stats-overview,
.stats-overview {
    display: grid !important;
    grid-template-columns: 320px 1fr !important;
    gap: 1.5rem !important;
    margin-bottom: 2rem !important;
    background: #ffffff !important;
    border: none !important;
    border-radius: 28px !important;
    padding: 1.5rem !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08) !important;
}

/* Rating Summary Card - Left Side */
body.dark-theme .stats-card.main-stat,
body.dark-theme .main-stat,
.stats-card.main-stat,
.main-stat {
    background: linear-gradient(145deg, #22c55e 0%, #16a34a 50%, #15803d 100%) !important;
    border: none !important;
    border-radius: 20px !important;
    padding: 2rem !important;
    text-align: center !important;
    box-shadow: 0 8px 32px rgba(34, 197, 94, 0.35) !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    align-items: center !important;
    position: relative !important;
    overflow: hidden !important;
}

body.dark-theme .stats-card.main-stat::before,
.stats-card.main-stat::before {
    content: '' !important;
    position: absolute !important;
    top: -50% !important;
    right: -50% !important;
    width: 100% !important;
    height: 100% !important;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%) !important;
    pointer-events: none !important;
}

body.dark-theme .big-rating,
.big-rating {
    font-size: 4.5rem !important;
    font-weight: 900 !important;
    color: #ffffff !important;
    line-height: 1 !important;
    margin-bottom: 0.5rem !important;
    text-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

body.dark-theme .stars-row,
.stars-row {
    display: flex !important;
    justify-content: center !important;
    gap: 0.35rem !important;
    margin-bottom: 0.75rem !important;
}

body.dark-theme .stars-row i,
.stars-row i {
    color: #fbbf24 !important;
    font-size: 1.4rem !important;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)) !important;
}

body.dark-theme .rating-count,
.rating-count {
    color: rgba(255,255,255,0.9) !important;
    font-size: 0.95rem !important;
    font-weight: 500 !important;
}

/* Rating Bars Card - Right Side */
body.dark-theme .stats-card.rating-bars,
body.dark-theme .rating-bars,
.stats-card.rating-bars,
.rating-bars {
    background: transparent !important;
    border: none !important;
    border-radius: 20px !important;
    padding: 1rem 1.5rem !important;
    box-shadow: none !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    gap: 0.6rem !important;
}

body.dark-theme .bar-row,
.bar-row {
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
    margin-bottom: 0 !important;
}

body.dark-theme .bar-label,
.bar-label {
    min-width: 40px !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.25rem !important;
    color: #6b7280 !important;
    font-weight: 600 !important;
    font-size: 0.9rem !important;
}

body.dark-theme .bar-label i,
.bar-label i {
    color: #fbbf24 !important;
    font-size: 0.85rem !important;
}

body.dark-theme .bar-bg,
.bar-bg {
    flex: 1 !important;
    height: 10px !important;
    background: #f3f4f6 !important;
    border-radius: 10px !important;
    overflow: hidden !important;
}

body.dark-theme .bar-fill,
.bar-fill {
    height: 100% !important;
    background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%) !important;
    border-radius: 10px !important;
    transition: width 0.6s ease !important;
}

body.dark-theme .bar-count,
.bar-count {
    min-width: 28px !important;
    text-align: right !important;
    color: #9ca3af !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
}

/* ========================================
   FILTER SECTION - MODERN COMPACT
   ======================================== */
body.dark-theme .filter-section,
body.dark-theme .filter-card,
.filter-section,
.filter-card {
    background: #ffffff !important;
    border: none !important;
    border-radius: 20px !important;
    padding: 1.5rem 2rem !important;
    margin-bottom: 2rem !important;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08) !important;
    display: flex !important;
    gap: 1.25rem !important;
    flex-wrap: wrap !important;
    align-items: center !important;
}

body.dark-theme .filter-group,
.filter-group {
    display: flex !important;
    align-items: center !important;
    gap: 0.85rem !important;
    flex: 1 !important;
    min-width: 250px !important;
    max-width: 350px !important;
}

body.dark-theme .filter-group i,
.filter-group i {
    width: 40px !important;
    height: 40px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: linear-gradient(135deg, #fef3c7, #fde68a) !important;
    color: #f59e0b !important;
    border-radius: 12px !important;
    font-size: 1rem !important;
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.25) !important;
}

body.dark-theme .filter-section select,
body.dark-theme .filter-group select,
.filter-section select,
.filter-group select {
    flex: 1 !important;
    padding: 1rem 1.5rem !important;
    background: #fafafa !important;
    border: 2px solid #e8e8e8 !important;
    border-radius: 14px !important;
    color: #374151 !important;
    font-size: 1rem !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    -webkit-appearance: none !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2322c55e' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 1rem center !important;
    background-size: 1.25rem !important;
    padding-right: 3rem !important;
    min-height: 50px !important;
}

body.dark-theme .filter-section select:hover,
body.dark-theme .filter-group select:hover,
.filter-section select:hover,
.filter-group select:hover {
    border-color: #22c55e !important;
    background-color: #f0fdf4 !important;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.12) !important;
}

body.dark-theme .filter-section select:focus,
.filter-section select:focus {
    outline: none !important;
    border-color: #22c55e !important;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1) !important;
}

/* ========================================
   REVIEWS GRID - MODERN CARDS
   ======================================== */
body.dark-theme .reviews-grid,
.reviews-grid {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 1.5rem !important;
    background: transparent !important;
}

@media (max-width: 1024px) {
    body.dark-theme .reviews-grid,
    .reviews-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    body.dark-theme .stats-overview,
    .stats-overview {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 640px) {
    body.dark-theme .reviews-grid,
    .reviews-grid {
        grid-template-columns: 1fr !important;
    }
}

/* Individual Review Card */
body.dark-theme .review-card,
body.dark-theme .review-item,
.review-card,
.review-item {
    background: #ffffff !important;
    border: 2px solid #22c55e !important;
    border-radius: 20px !important;
    padding: 1.5rem !important;
    box-shadow: 0 4px 16px rgba(34, 197, 94, 0.1) !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    overflow: hidden !important;
}

body.dark-theme .review-card::before,
.review-card::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    height: 4px !important;
    background: linear-gradient(90deg, #22c55e, #16a34a) !important;
}

body.dark-theme .review-card:hover,
.review-card:hover {
    transform: translateY(-5px) !important;
    border-color: #22c55e !important;
    box-shadow: 0 12px 35px rgba(34, 197, 94, 0.15) !important;
}

/* Review Header */
body.dark-theme .review-header,
.review-header {
    display: flex !important;
    align-items: center !important;
    gap: 1rem !important;
    margin-bottom: 1.25rem !important;
}

body.dark-theme .reviewer-avatar,
.reviewer-avatar {
    width: 52px !important;
    height: 52px !important;
    border-radius: 16px !important;
    background: linear-gradient(135deg, #22c55e, #16a34a) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: white !important;
    font-weight: 700 !important;
    font-size: 1.2rem !important;
    flex-shrink: 0 !important;
}

body.dark-theme .reviewer-avatar img,
.reviewer-avatar img {
    width: 100% !important;
    height: 100% !important;
    border-radius: 16px !important;
    object-fit: cover !important;
}

body.dark-theme .reviewer-info,
.reviewer-info {
    flex: 1 !important;
}

body.dark-theme .reviewer-name,
body.dark-theme .review-author,
.reviewer-name,
.review-author {
    color: #1f2937 !important;
    font-weight: 700 !important;
    font-size: 1.05rem !important;
    margin-bottom: 0.2rem !important;
}

body.dark-theme .review-date,
.review-date {
    color: #9ca3af !important;
    font-size: 0.85rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.4rem !important;
}

body.dark-theme .review-date i,
.review-date i {
    color: #22c55e !important;
}

/* Rating Badge */
body.dark-theme .rating-badge,
.rating-badge {
    background: #f0fdf4 !important;
    color: #15803d !important;
    padding: 0.5rem 0.9rem !important;
    border-radius: 12px !important;
    font-weight: 700 !important;
    font-size: 0.9rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.3rem !important;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.15) !important;
    border: 1px solid #86efac !important;
}

body.dark-theme .rating-badge i,
.rating-badge i {
    color: #fbbf24 !important;
}

/* Menu Tag */
body.dark-theme .menu-tag,
body.dark-theme .review-menu-tag,
.menu-tag,
.review-menu-tag {
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.4rem !important;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7) !important;
    color: #15803d !important;
    padding: 0.5rem 1rem !important;
    border-radius: 50px !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    margin-bottom: 1rem !important;
    border: 1px solid #86efac !important;
}

/* Review Card Structure */
body.dark-theme .review-card .card-header,
.review-card .card-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: flex-start !important;
    margin-bottom: 1rem !important;
}

body.dark-theme .review-card .user-section,
.review-card .user-section {
    display: flex !important;
    align-items: center !important;
    gap: 0.85rem !important;
}

body.dark-theme .review-card .avatar,
.review-card .avatar {
    width: 52px !important;
    height: 52px !important;
    border-radius: 14px !important;
    background: linear-gradient(135deg, #22c55e, #16a34a) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
    flex-shrink: 0 !important;
}

body.dark-theme .review-card .avatar img,
.review-card .avatar img {
    width: 100% !important;
    height: 100% !important;
    border-radius: 14px !important;
    object-fit: cover !important;
}

body.dark-theme .review-card .avatar-letter,
.review-card .avatar-letter {
    color: white !important;
    font-weight: 700 !important;
    font-size: 1.3rem !important;
}

body.dark-theme .review-card .verified-badge,
.review-card .verified-badge {
    position: absolute !important;
    bottom: -4px !important;
    right: -4px !important;
    width: 20px !important;
    height: 20px !important;
    background: #22c55e !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: 2px solid white !important;
}

body.dark-theme .review-card .verified-badge i,
.review-card .verified-badge i {
    color: white !important;
    font-size: 0.6rem !important;
}

body.dark-theme .review-card .user-info,
.review-card .user-info {
    display: flex !important;
    flex-direction: column !important;
    gap: 0.2rem !important;
}

body.dark-theme .review-card .user-name,
.review-card .user-name {
    color: #0f172a !important;
    font-weight: 800 !important;
    font-size: 1.1rem !important;
    margin: 0 !important;
    letter-spacing: -0.01em;
}

body.dark-theme .review-card .review-date,
.review-card .review-date {
    color: #64748b !important;
    font-size: 0.85rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.4rem !important;
    font-weight: 600 !important;
}

body.dark-theme .review-card .review-date i,
.review-card .review-date i {
    color: #22c55e !important;
    font-size: 0.8rem !important;
}

body.dark-theme .review-card .rating-badge,
.review-card .rating-badge {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
    color: #ffffff !important;
    padding: 0.6rem 1rem !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.4rem !important;
    font-weight: 800 !important;
    font-size: 1.1rem !important;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.35) !important;
    border: none !important;
}

body.dark-theme .review-card .rating-badge i,
.review-card .rating-badge i {
    color: #fbbf24 !important;
    font-size: 1rem !important;
}

body.dark-theme .review-card .rating-badge.rating-1,
body.dark-theme .review-card .rating-badge.rating-2,
.review-card .rating-badge.rating-1,
.review-card .rating-badge.rating-2 {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    color: #ffffff !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35) !important;
}

body.dark-theme .review-card .rating-badge.rating-3,
.review-card .rating-badge.rating-3 {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    color: #ffffff !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35) !important;
}

/* Dish Tag */
body.dark-theme .review-card .dish-tag,
.review-card .dish-tag {
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%) !important;
    color: #15803d !important;
    padding: 0.6rem 1.25rem !important;
    border-radius: 25px !important;
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    margin-bottom: 1rem !important;
    border: 2px solid #86efac !important;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.15) !important;
}

body.dark-theme .review-card .dish-tag a,
.review-card .dish-tag a {
    color: #15803d !important;
    text-decoration: none !important;
    font-weight: 700 !important;
}

body.dark-theme .review-card .dish-tag a:hover,
.review-card .dish-tag a:hover {
    color: #166534 !important;
    text-decoration: underline !important;
}

body.dark-theme .review-card .dish-tag i,
.review-card .dish-tag i {
    color: #22c55e !important;
    font-size: 1rem !important;
}

/* Review Content Area - Text + Small Image */
body.dark-theme .review-card .review-content-area,
.review-card .review-content-area {
    display: flex !important;
    gap: 1rem !important;
    margin-bottom: 1rem !important;
}

/* Review Text - IMPORTANT: Make comment visible */
body.dark-theme .review-card .review-text,
.review-card .review-text {
    flex: 1 !important;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
    padding: 1.25rem 1.5rem !important;
    border-radius: 14px !important;
    border-left: 4px solid #22c55e !important;
    position: relative !important;
    min-height: 80px !important;
}

body.dark-theme .review-card .review-text p,
.review-card .review-text p {
    color: #1e293b !important;
    font-size: 1rem !important;
    line-height: 1.7 !important;
    margin: 0 !important;
    font-weight: 500 !important;
}

body.dark-theme .review-card .review-text::before,
.review-card .review-text::before {
    content: '"' !important;
    position: absolute !important;
    top: 0.25rem !important;
    left: 0.5rem !important;
    font-size: 2.5rem !important;
    color: #22c55e !important;
    opacity: 0.3 !important;
    font-family: Georgia, serif !important;
    line-height: 1 !important;
}

/* Review Image - Small thumbnail */
body.dark-theme .review-card .review-image,
.review-card .review-image {
    width: 100px !important;
    height: 100px !important;
    flex-shrink: 0 !important;
    border-radius: 12px !important;
    overflow: hidden !important;
}

body.dark-theme .review-card .review-image img,
.review-card .review-image img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 12px !important;
    transition: transform 0.3s ease !important;
}

body.dark-theme .review-card:hover .review-image img,
.review-card:hover .review-image img {
    transform: scale(1.08) !important;
}

/* Card Footer */
body.dark-theme .review-card .card-footer,
.review-card .card-footer {
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
    padding-top: 1rem !important;
    border-top: 2px solid #e2e8f0 !important;
}

body.dark-theme .review-card .like-btn,
.review-card .like-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    padding: 0.65rem 1.25rem !important;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%) !important;
    border: 2px solid #fecaca !important;
    border-radius: 25px !important;
    color: #ef4444 !important;
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    transition: all 0.25s ease !important;
}

body.dark-theme .review-card .like-btn i,
.review-card .like-btn i {
    color: #ef4444 !important;
    font-size: 1.1rem !important;
}

body.dark-theme .review-card .like-btn:hover,
.review-card .like-btn:hover {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    border-color: #f87171 !important;
    color: #dc2626 !important;
    transform: scale(1.05) !important;
}

body.dark-theme .review-card .like-btn:hover i,
.review-card .like-btn:hover i {
    color: #dc2626 !important;
}

body.dark-theme .review-card .like-btn.liked,
.review-card .like-btn.liked {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    border-color: #ef4444 !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35) !important;
}

body.dark-theme .review-card .like-btn.liked i,
.review-card .like-btn.liked i {
    color: white !important;
}

body.dark-theme .review-card .helpful-text,
.review-card .helpful-text {
    color: #475569 !important;
    font-size: 0.95rem !important;
    font-weight: 600 !important;
}

/* Review Comment - Legacy support */
body.dark-theme .review-comment,
body.dark-theme .review-text,
.review-comment,
.review-text {
    background: #f8fafc !important;
    color: #374151 !important;
    padding: 1.25rem !important;
    border-radius: 16px !important;
    border-left: 4px solid #22c55e !important;
    font-size: 0.95rem !important;
    line-height: 1.7 !important;
    margin-bottom: 1rem !important;
    position: relative !important;
}

body.dark-theme .review-comment::before,
.review-comment::before {
    content: '"' !important;
    position: absolute !important;
    top: 0.5rem !important;
    left: 1rem !important;
    font-size: 2rem !important;
    color: #22c55e !important;
    opacity: 0.3 !important;
    font-family: Georgia, serif !important;
}

/* Review Actions */
body.dark-theme .review-actions,
.review-actions {
    display: flex !important;
    justify-content: flex-end !important;
    padding-top: 0.5rem !important;
}

body.dark-theme .like-btn,
.like-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    padding: 0.6rem 1.2rem !important;
    background: #f0fdf4 !important;
    border: 2px solid #86efac !important;
    border-radius: 50px !important;
    color: #22c55e !important;
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.25s ease !important;
}

body.dark-theme .like-btn:hover,
.like-btn:hover {
    background: #22c55e !important;
    border-color: #22c55e !important;
    color: white !important;
    transform: scale(1.05) !important;
}

body.dark-theme .like-btn.liked,
.like-btn.liked {
    background: #22c55e !important;
    border-color: #22c55e !important;
    color: white !important;
}

/* Empty State */
body.dark-theme .no-reviews,
.no-reviews {
    text-align: center !important;
    padding: 5rem 2rem !important;
    background: #ffffff !important;
    border-radius: 24px !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06) !important;
}

body.dark-theme .no-reviews i,
.no-reviews i {
    font-size: 4rem !important;
    color: #22c55e !important;
    margin-bottom: 1.5rem !important;
}

body.dark-theme .no-reviews h3,
.no-reviews h3 {
    color: #1f2937 !important;
    font-size: 1.5rem !important;
    margin-bottom: 0.5rem !important;
}

body.dark-theme .no-reviews p,
.no-reviews p {
    color: #6b7280 !important;
}
    margin: 0.5rem 0;
}
.rating-summary-card .total-reviews {
    color: #6b7280 !important;
    font-size: 0.95rem;
}
.rating-bars-card {
    background: #ffffff !important;
    border: 2px solid #22c55e !important;
    border-radius: 20px;
    padding: 1.5rem 2rem;
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1);
}
.rating-bar-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}
.rating-bar-row .star-label {
    min-width: 50px;
    color: #4b5563 !important;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.rating-bar-row .star-label i {
    color: #fbbf24;
}
.rating-bar-track {
    flex: 1;
    height: 12px;
    background: #e5e7eb !important;
    border-radius: 10px;
    overflow: hidden;
}
.rating-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #fbbf24) !important;
    border-radius: 10px;
    transition: width 0.5s ease;
}
.rating-bar-row .count {
    min-width: 30px;
    text-align: right;
    color: #6b7280 !important;
    font-weight: 500;
}
/* Filter Section */
.filter-section {
    background: #ffffff !important;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 250px;
}
.filter-group i {
    color: #22c55e;
}
.filter-group select {
    flex: 1;
    padding: 1rem 1.5rem;
    border: 2px solid #e5e7eb !important;
    border-radius: 12px;
    background: #f9fafb !important;
    color: #1f2937 !important;
    font-size: 1rem;
    cursor: pointer;
    min-height: 52px;
    min-width: 200px;
}
.filter-group select:focus {
    border-color: #22c55e !important;
    outline: none;
}
/* Review Cards */
.reviews-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}
@media (max-width: 992px) {
    .reviews-grid { grid-template-columns: repeat(2, 1fr); }
    .stats-overview { grid-template-columns: 1fr; }
}
@media (max-width: 576px) {
    .reviews-grid { grid-template-columns: 1fr; }
}
.review-card {
    background: #ffffff !important;
    border: 2px solid #22c55e !important;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.12);
    transition: all 0.3s ease;
}
.review-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(34, 197, 94, 0.2);
    border-color: #16a34a !important;
}
.review-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}
.reviewer-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
    border: 3px solid #dcfce7;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
}
.reviewer-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
.reviewer-info .name {
    font-weight: 700;
    color: #0f172a !important;
    font-size: 1rem;
}
.reviewer-info .date {
    font-size: 0.85rem;
    color: #475569 !important;
    font-weight: 500;
}
.review-rating {
    margin-left: auto;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    font-weight: 800;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.review-menu-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%) !important;
    color: #15803d !important;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 1rem;
    border: 2px solid #86efac;
}
.review-menu-tag i {
    color: #22c55e;
}
.review-comment {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
    padding: 1.25rem;
    border-radius: 12px;
    border-left: 4px solid #22c55e;
    color: #1e293b !important;
    font-size: 1rem;
    line-height: 1.7;
    margin-bottom: 1rem;
    font-weight: 500;
}
.review-comment::before {
    content: '"';
    font-size: 2rem;
    color: #22c55e;
    opacity: 0.5;
    line-height: 0;
    vertical-align: -0.4em;
    margin-right: 0.25rem;
}
.review-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    justify-content: flex-start;
}
.like-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.25rem;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%) !important;
    border: 2px solid #fecaca;
    border-radius: 25px;
    color: #ef4444 !important;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.like-btn:hover {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    transform: scale(1.05);
}
.like-btn.liked {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    border-color: #ef4444;
    color: white !important;
}
.like-btn i {
    font-size: 1rem;
}
.helpful-text {
    color: #64748b !important;
    font-size: 0.9rem;
    font-weight: 600;
}
/* Empty State */
.no-reviews {
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border: 2px dashed #22c55e;
    border-radius: 20px;
    color: #475569;
}
.no-reviews i {
    font-size: 3rem;
    color: #22c55e;
    margin-bottom: 1rem;
}
</style>

<!-- Reviews Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge"><?php echo $current_lang === 'en' ? 'Reviews' : 'Đánh giá'; ?></span>
        <h1 class="about-hero-title"><?php echo $current_lang === 'en' ? 'Reviews Ngon Gallery' : 'Đánh Giá Ngon Gallery'; ?></h1>
        <p class="about-hero-subtitle"><?php echo $current_lang === 'en' 
            ? 'Real experiences from our valued customers' 
            : 'Trải nghiệm thực tế từ khách hàng của chúng tôi'; ?></p>
    </div>
</section>

<section class="modern-reviews-section">
    <div class="reviews-container">

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stats-card main-stat">
                <div class="rating-display">
                    <span class="big-rating"><?php echo number_format($stats['avg_rating'], 1); ?></span>
                    <div class="rating-details">
                        <div class="stars-row">
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
                        <span class="total-reviews">
                            <?php echo $stats['total_reviews']; ?> 
                            <?php echo $current_lang === 'en' ? 'reviews' : 'đánh giá'; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="stats-card breakdown-stat">
                <div class="breakdown-bars">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                    <?php 
                    $count_key = ['one_star', 'two_star', 'three_star', 'four_star', 'five_star'][$i-1];
                    $count = $stats[$count_key];
                    $percentage = $stats['total_reviews'] > 0 ? ($count / $stats['total_reviews']) * 100 : 0;
                    ?>
                    <div class="bar-row">
                        <span class="bar-label"><?php echo $i; ?></span>
                        <i class="fas fa-star star-icon"></i>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <span class="bar-count"><?php echo $count; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="page" value="all-reviews">
                
                <div class="filter-item">
                    <div class="filter-icon"><i class="fas fa-star"></i></div>
                    <div class="filter-content">
                        <span class="filter-label"><?php echo $current_lang === 'en' ? 'Rating' : 'Đánh giá'; ?></span>
                        <select name="rating" onchange="this.form.submit()">
                            <option value=""><?php echo $current_lang === 'en' ? 'All Ratings' : 'Tất cả sao'; ?></option>
                            <option value="5" <?php echo $filter_rating == '5' ? 'selected' : ''; ?>>5 ⭐ <?php echo $current_lang === 'en' ? 'Excellent' : 'Xuất sắc'; ?></option>
                            <option value="4" <?php echo $filter_rating == '4' ? 'selected' : ''; ?>>4 ⭐ <?php echo $current_lang === 'en' ? 'Good' : 'Tốt'; ?></option>
                            <option value="3" <?php echo $filter_rating == '3' ? 'selected' : ''; ?>>3 ⭐ <?php echo $current_lang === 'en' ? 'Average' : 'Trung bình'; ?></option>
                            <option value="2" <?php echo $filter_rating == '2' ? 'selected' : ''; ?>>2 ⭐ <?php echo $current_lang === 'en' ? 'Poor' : 'Kém'; ?></option>
                            <option value="1" <?php echo $filter_rating == '1' ? 'selected' : ''; ?>>1 ⭐ <?php echo $current_lang === 'en' ? 'Bad' : 'Tệ'; ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-item">
                    <div class="filter-icon dish-icon"><i class="fas fa-utensils"></i></div>
                    <div class="filter-content">
                        <span class="filter-label"><?php echo $current_lang === 'en' ? 'Dish' : 'Món ăn'; ?></span>
                        <select name="menu_item" onchange="this.form.submit()">
                            <option value=""><?php echo $current_lang === 'en' ? 'All Dishes' : 'Tất cả món'; ?></option>
                            <?php foreach($menu_items as $item): 
                                $item_name = $current_lang === 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
                            ?>
                            <option value="<?php echo $item['id']; ?>" <?php echo $filter_menu_item == $item['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($item_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-item">
                    <div class="filter-icon sort-icon"><i class="fas fa-sort-amount-down"></i></div>
                    <div class="filter-content">
                        <span class="filter-label"><?php echo $current_lang === 'en' ? 'Sort by' : 'Sắp xếp'; ?></span>
                        <select name="sort" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>
                                <?php echo $current_lang === 'en' ? 'Newest First' : 'Mới nhất'; ?>
                            </option>
                            <option value="oldest" <?php echo $sort_by == 'oldest' ? 'selected' : ''; ?>>
                                <?php echo $current_lang === 'en' ? 'Oldest First' : 'Cũ nhất'; ?>
                            </option>
                            <option value="highest" <?php echo $sort_by == 'highest' ? 'selected' : ''; ?>>
                                <?php echo $current_lang === 'en' ? 'Highest Rating' : 'Điểm cao nhất'; ?>
                            </option>
                            <option value="lowest" <?php echo $sort_by == 'lowest' ? 'selected' : ''; ?>>
                                <?php echo $current_lang === 'en' ? 'Lowest Rating' : 'Điểm thấp nhất'; ?>
                            </option>
                        </select>
                    </div>
                </div>
                
                <?php if ($filter_rating || $filter_menu_item || $sort_by != 'newest'): ?>
                <a href="?page=all-reviews" class="clear-filter-btn">
                    <i class="fas fa-times-circle"></i>
                    <?php echo $current_lang === 'en' ? 'Clear' : 'Xóa lọc'; ?>
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Reviews Grid -->
        <div class="reviews-grid">
            <?php if (count($reviews) > 0): ?>
                <?php foreach($reviews as $index => $review): 
                    $menu_name = $current_lang === 'en' && !empty($review['menu_item_name_en']) 
                        ? $review['menu_item_name_en'] 
                        : $review['menu_item_name'];
                    $delay = ($index % 6) * 0.1;
                    $is_owner = isset($_SESSION['customer_id']) && $review['customer_id'] == $_SESSION['customer_id'];
                ?>
                <div class="review-card" data-review-id="<?php echo $review['id']; ?>" style="animation-delay: <?php echo $delay; ?>s">
                    <div class="card-header">
                        <div class="user-section">
                            <div class="avatar">
                                <?php if (!empty($review['customer_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($review['customer_avatar']); ?>" alt="Avatar">
                                <?php else: ?>
                                    <span class="avatar-letter"><?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?></span>
                                <?php endif; ?>
                                <div class="verified-badge" title="<?php echo $current_lang === 'en' ? 'Verified Purchase' : 'Đã xác thực'; ?>">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="user-info">
                                <h4 class="user-name"><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                <span class="review-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="header-right">
                            <?php if($is_owner): ?>
                            <div class="review-owner-actions">
                                <button class="owner-action-btn edit-btn" onclick="openEditModal(<?php echo $review['id']; ?>, <?php echo $review['rating']; ?>, '<?php echo addslashes(htmlspecialchars($review['comment'])); ?>')" title="<?php echo $current_lang === 'en' ? 'Edit' : 'Sửa'; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="owner-action-btn delete-btn" onclick="openDeleteModal(<?php echo $review['id']; ?>)" title="<?php echo $current_lang === 'en' ? 'Delete' : 'Xóa'; ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                            <div class="rating-badge rating-<?php echo $review['rating']; ?>">
                                <span class="rating-num"><?php echo $review['rating']; ?></span>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($review['menu_item_id']): ?>
                    <div class="dish-tag">
                        <i class="fas fa-utensils"></i>
                        <a href="index.php?page=menu-item-detail&id=<?php echo $review['menu_item_id']; ?>">
                            <?php echo htmlspecialchars($menu_name); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="review-content-area">
                        <?php if ($review['comment']): ?>
                        <div class="review-text">
                            <p class="review-comment-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($review['menu_item_image']): ?>
                        <div class="review-image">
                            <img src="<?php echo htmlspecialchars($review['menu_item_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($menu_name); ?>"
                                 loading="lazy">
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <button class="like-btn <?php echo $review['is_liked'] ? 'liked' : ''; ?>" 
                                data-review-id="<?php echo $review['id']; ?>"
                                onclick="toggleLikeReview(this, <?php echo $review['id']; ?>)">
                            <i class="<?php echo $review['is_liked'] ? 'fas' : 'far'; ?> fa-heart"></i>
                            <span class="like-count"><?php echo $review['likes_count'] ?? 0; ?></span>
                        </button>
                        <span class="helpful-text">
                            <?php echo $current_lang === 'en' ? 'Helpful?' : 'Hữu ích?'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="far fa-comment-dots"></i>
                    </div>
                    <h3><?php echo $current_lang === 'en' ? 'No Reviews Yet' : 'Chưa có đánh giá'; ?></h3>
                    <p><?php echo $current_lang === 'en' 
                        ? 'Be the first to share your experience!' 
                        : 'Hãy là người đầu tiên chia sẻ trải nghiệm!'; ?></p>
                    <a href="?page=menu" class="explore-btn">
                        <i class="fas fa-utensils"></i>
                        <?php echo $current_lang === 'en' ? 'Explore Menu' : 'Khám phá thực đơn'; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<style>
/* =====================================================
   MODERN REVIEWS SECTION - CLEAN WHITE THEME
   ===================================================== */
.modern-reviews-section {
    min-height: 100vh;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
    padding: 0;
    position: relative;
    overflow: hidden;
}

.modern-reviews-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 30%, rgba(34, 197, 94, 0.08) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(34, 197, 94, 0.05) 0%, transparent 40%);
    pointer-events: none;
}

.reviews-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    position: relative;
    z-index: 1;
}

/* Hero Header */
.reviews-hero {
    text-align: center;
    padding: 3rem 0 2rem;
    position: relative;
}

.reviews-hero .hero-content {
    max-width: 700px;
    margin: 0 auto;
}

.reviews-hero .hero-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    box-shadow: 0 12px 40px rgba(34, 197, 94, 0.4);
}

.reviews-hero .hero-icon i {
    font-size: 2rem;
    color: #fff;
}

.reviews-hero h1 {
    font-size: clamp(2rem, 4vw, 2.8rem);
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.reviews-hero p {
    font-size: 1.1rem;
    color: #6b7280;
    margin: 0;
}

/* =====================================================
   STATS OVERVIEW - ENHANCED DESIGN
   ===================================================== */
.stats-overview {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 0;
    margin-bottom: 2rem;
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 24px;
    padding: 0;
    align-items: stretch;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.stats-card {
    background: transparent;
    border: none;
    padding: 0;
    transition: none;
}

.stats-card:hover {
    background: transparent;
    border-color: transparent;
    transform: none;
}

/* Main Rating Card - Left Side */
.stats-card.main-stat,
.main-stat {
    background: linear-gradient(145deg, #22c55e 0%, #16a34a 50%, #15803d 100%);
    padding: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-stat .rating-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    text-align: center;
}

.big-rating {
    font-size: 5rem;
    font-weight: 900;
    color: #ffffff;
    line-height: 1;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.rating-details {
    text-align: center;
}

.stars-row {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    gap: 4px;
    justify-content: center;
}

.stars-row i {
    color: #fbbf24;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.total-reviews {
    color: rgba(255, 255, 255, 0.95);
    font-size: 1rem;
    font-weight: 600;
}

/* Breakdown Bars - Right Side */
.stats-card.breakdown-stat,
.breakdown-stat {
    padding: 2rem 2.5rem;
    display: flex;
    align-items: center;
}

.breakdown-bars {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    width: 100%;
}

.bar-row {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.bar-label {
    width: 24px;
    font-weight: 800;
    color: #374151;
    text-align: center;
    font-size: 1rem;
}

.star-icon {
    color: #f59e0b;
    font-size: 1rem;
}

.bar-track {
    flex: 1;
    height: 14px;
    background: #f3f4f6;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
    border-radius: 10px;
    transition: width 0.8s ease;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.bar-count {
    width: 40px;
    text-align: right;
    color: #6b7280;
    font-weight: 700;
    font-size: 0.95rem;
}

/* =====================================================
   FILTER SECTION - REDESIGNED MODERN UI
   ===================================================== */
.filter-section {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 24px;
    padding: 2rem 2.5rem;
    margin-bottom: 2.5rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(34, 197, 94, 0.1);
    position: relative;
    overflow: hidden;
}

.filter-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #22c55e 0%, #16a34a 50%, #15803d 100%);
}

.filter-form {
    display: flex;
    gap: 1.5rem;
    align-items: stretch;
    flex-wrap: nowrap;
    justify-content: space-between;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1 1 0;
    min-width: 0;
    max-width: none;
    background: #ffffff;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    border: 2px solid #22c55e;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    min-height: 80px;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.1);
}

.filter-item:hover {
    border-color: #16a34a;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.2);
    transform: translateY(-3px);
}

.filter-item:focus-within {
    border-color: #16a34a;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15), 0 8px 25px rgba(34, 197, 94, 0.2);
}

.filter-icon {
    width: 52px;
    height: 52px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
    position: absolute;
    top: 50%;
    left: 1.5rem;
    transform: translateY(-50%);
}

.filter-icon i {
    font-size: 1.3rem;
    color: #f59e0b;
}

/* Icon màu khác cho từng filter */
.filter-item:nth-child(1) .filter-icon {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
}
.filter-item:nth-child(1) .filter-icon i {
    color: #f59e0b;
}

.filter-item:nth-child(2) .filter-icon {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}
.filter-item:nth-child(2) .filter-icon i {
    color: #22c55e;
}

.filter-item:nth-child(3) .filter-icon {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.filter-item:nth-child(3) .filter-icon i {
    color: #3b82f6;
}

.filter-content {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    padding-left: 72px;
    flex: 1;
}

.filter-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-item select {
    width: 100%;
    background: transparent;
    border: none;
    color: #1f2937;
    font-size: 1.15rem;
    font-weight: 600;
    padding: 0.35rem 2.5rem 0.35rem 0;
    cursor: pointer;
    outline: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2322c55e' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0 center;
    background-size: 1.75rem;
}

.filter-item select:focus {
    outline: none;
}

/* Giới hạn chiều cao dropdown để mở xuống dưới đồng nhất */
.filter-item select option {
    background: #ffffff;
    color: #374151;
    padding: 1rem;
    font-size: 1.1rem;
}

/* Đảm bảo filter section có đủ margin bottom cho dropdown */
.filter-section {
    margin-bottom: 3rem;
    overflow: visible !important;
}

.filter-form {
    overflow: visible !important;
}

.filter-item {
    overflow: visible !important;
}

.clear-filter-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 1rem 1.75rem;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 2px solid #fecaca;
    border-radius: 16px;
    color: #dc2626;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.95rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
    align-self: center;
    min-height: 56px;
}

.clear-filter-btn:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    border-color: #dc2626;
    color: #ffffff;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
}

.clear-filter-btn i {
    font-size: 1rem;
}

/* Responsive cho filter */
@media (max-width: 992px) {
    .filter-section {
        padding: 1.75rem 2rem;
    }
    
    .filter-form {
        gap: 1.25rem;
        flex-wrap: wrap;
    }
    
    .filter-item {
        flex: 1 1 calc(50% - 0.75rem);
        min-width: calc(50% - 0.75rem);
        max-width: calc(50% - 0.75rem);
    }
}

@media (max-width: 768px) {
    .filter-section {
        padding: 1.5rem;
        border-radius: 20px;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-item {
        flex: 1 1 100%;
        min-width: 100%;
        max-width: 100%;
    }
    .filter-form {
        gap: 1rem;
    }
    
    .filter-item {
        min-width: 100%;
        max-width: 100%;
    }
    
    .clear-filter-btn {
        width: 100%;
    }
    
    .clear-filter-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Reviews Grid */
.reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 1.5rem;
    padding-bottom: 4rem;
}

/* Review Card */
.review-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 1.5rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.review-card:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 193, 7, 0.2);
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.user-section {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.avatar {
    position: relative;
    width: 56px;
    height: 56px;
}

.avatar img, .avatar .avatar-letter {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.avatar .avatar-letter {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
}

.verified-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 22px;
    height: 22px;
    background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #1a1a2e;
}

.verified-badge i {
    font-size: 0.65rem;
    color: #fff;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-name {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
}

.review-date {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

/* Rating Badge */
.rating-badge {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    font-weight: 700;
    background: #f0fdf4 !important;
    color: #15803d !important;
    border: 1px solid #86efac !important;
}

.rating-badge.rating-5,
.rating-badge.rating-4 {
    background: #f0fdf4 !important;
    color: #15803d !important;
    border: 1px solid #86efac !important;
}

.rating-badge.rating-3 {
    background: #fffbeb !important;
    color: #d97706 !important;
    border: 1px solid #fde68a !important;
}

.rating-badge.rating-2,
.rating-badge.rating-1 {
    background: #fef2f2 !important;
    color: #dc2626 !important;
    border: 1px solid #fecaca !important;
}

.rating-num {
    font-size: 1.2rem;
}

.rating-badge i {
    font-size: 0.9rem;
}

/* Dish Tag */
.dish-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.2);
    border-radius: 30px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.dish-tag i {
    color: #ffc107;
}

.dish-tag a {
    color: #ffc107;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.dish-tag a:hover {
    color: #ff9800;
    text-decoration: underline;
}

/* Review Text */
.review-text {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    border-left: 3px solid rgba(255, 193, 7, 0.5);
}

.review-text p {
    margin: 0;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.7;
    font-size: 0.95rem;
}

/* Review Image */
.review-image {
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.review-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.review-card:hover .review-image img {
    transform: scale(1.05);
}

/* Card Footer */
.card-footer {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.like-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
}

.like-btn:hover {
    background: rgba(255, 107, 107, 0.15);
    border-color: rgba(255, 107, 107, 0.3);
    color: #ff6b6b;
    transform: scale(1.05);
}

.like-btn.liked {
    background: linear-gradient(135deg, rgba(255, 107, 107, 0.2) 0%, rgba(255, 82, 82, 0.2) 100%);
    border-color: rgba(255, 107, 107, 0.4);
    color: #ff6b6b;
}

.like-btn.liked i {
    animation: heartBeat 0.6s ease;
}

@keyframes heartBeat {
    0%, 100% { transform: scale(1); }
    25% { transform: scale(1.3); }
    50% { transform: scale(1); }
    75% { transform: scale(1.2); }
}

.like-count {
    font-size: 0.95rem;
}

.helpful-text {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.85rem;
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 5rem 2rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 24px;
    border: 1px dashed rgba(255, 255, 255, 0.1);
}

.empty-icon {
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.empty-icon i {
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.3);
}

.empty-state h3 {
    font-size: 1.5rem;
    color: #fff;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 2rem;
}

.explore-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #1a1a2e;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 700;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(255, 193, 7, 0.3);
}

.explore-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(255, 193, 7, 0.4);
}

/* Responsive */
@media (max-width: 992px) {
    .stats-overview {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .reviews-grid {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    }
}

@media (max-width: 768px) {
    .reviews-container {
        padding: 0 1rem;
    }
    
    .reviews-hero {
        padding: 2rem 0 1.5rem;
    }
    
    .reviews-hero .hero-icon {
        width: 50px;
        height: 50px;
    }
    
    .reviews-hero .hero-icon i {
        font-size: 1.5rem;
    }
    
    .big-rating {
        font-size: 2.5rem;
    }
    
    .stats-overview {
        padding: 1rem;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-item {
        width: 100%;
    }
    
    .reviews-grid {
        grid-template-columns: 1fr;
    }
    
    .review-card {
        padding: 1.25rem;
    }
    
    .card-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .rating-badge {
        align-self: flex-start;
    }
}

@media (max-width: 480px) {
    .stats-overview {
        padding: 1rem;
    }
    
    .main-stat .rating-display {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .rating-details {
        text-align: center;
    }
}
</style>

<script>
async function toggleLikeReview(button, reviewId) {
    <?php if (!isset($_SESSION['customer_id'])): ?>
    alert('<?php echo $current_lang === "en" ? "Please login to like reviews" : "Vui lòng đăng nhập để thích đánh giá"; ?>');
    window.location.href = 'auth/login.php';
    return;
    <?php endif; ?>
    
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
            const likeCount = button.querySelector('.like-count');
            const icon = button.querySelector('i');
            
            likeCount.textContent = data.likes_count;
            
            if (data.action === 'liked') {
                button.classList.add('liked');
                icon.className = 'fas fa-heart';
            } else {
                button.classList.remove('liked');
                icon.className = 'far fa-heart';
            }
        } else {
            alert(data.message || '<?php echo $current_lang === "en" ? "An error occurred" : "Có lỗi xảy ra"; ?>');
        }
    } catch (error) {
        console.error('Error toggling like:', error);
        alert('<?php echo $current_lang === "en" ? "An error occurred" : "Có lỗi xảy ra"; ?>');
    } finally {
        button.disabled = false;
    }
}

// Animate cards on scroll
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.review-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, { threshold: 0.1 });
    
    cards.forEach(card => {
        card.style.animationPlayState = 'paused';
        observer.observe(card);
    });
});

// ========== EDIT/DELETE REVIEW FUNCTIONS ==========
let editSelectedRating = 0;

// Open edit modal
function openEditModal(reviewId, rating, comment) {
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

// Close edit modal
function closeEditModal() {
    document.getElementById('editReviewModal').classList.remove('active');
}

// Initialize edit star rating
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#editStarInput i').forEach(star => {
        star.addEventListener('click', () => {
            editSelectedRating = parseInt(star.dataset.r);
            document.getElementById('editRatingVal').value = editSelectedRating;
            document.querySelectorAll('#editStarInput i').forEach((s, i) => {
                s.className = i < editSelectedRating ? 'fas fa-star' : 'far fa-star';
            });
        });
    });
});

// Submit edit review
async function submitEditReview(e) {
    e.preventDefault();
    
    if (!editSelectedRating) {
        alert('<?php echo $current_lang === "en" ? "Please select rating" : "Vui lòng chọn số sao"; ?>');
        return;
    }
    
    const formData = new FormData(e.target);
    
    try {
        const res = await fetch('api/update-review.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            showToast('<?php echo $current_lang === "en" ? "Review updated!" : "Đã cập nhật đánh giá!"; ?>', 'success');
            closeEditModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(data.message || '<?php echo $current_lang === "en" ? "An error occurred" : "Có lỗi xảy ra"; ?>');
        }
    } catch (err) {
        alert('<?php echo $current_lang === "en" ? "An error occurred" : "Có lỗi xảy ra"; ?>');
    }
}

// Open delete confirm modal
function openDeleteModal(reviewId) {
    document.getElementById('deleteReviewId').value = reviewId;
    document.getElementById('deleteConfirmModal').classList.add('active');
}

// Close delete confirm modal
function closeDeleteModal() {
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
            showToast('<?php echo $current_lang === "en" ? "Review deleted!" : "Đã xóa đánh giá!"; ?>', 'success');
            closeDeleteModal();
            
            // Remove review card from DOM
            const reviewCard = document.querySelector(`.review-card[data-review-id="${reviewId}"]`);
            if (reviewCard) {
                reviewCard.style.transition = 'all 0.3s';
                reviewCard.style.opacity = '0';
                reviewCard.style.transform = 'scale(0.9)';
                setTimeout(() => reviewCard.remove(), 300);
            }
        } else {
            alert(data.message || '<?php echo $current_lang === "en" ? "An error occurred" : "Có lỗi xảy ra"; ?>');
        }
    } catch (err) {
        alert('<?php echo $current_lang === "en" ? "An error occurred" : "Có lỗi xảy ra"; ?>');
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

// Close modals when clicking outside
document.getElementById('editReviewModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
document.getElementById('deleteConfirmModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

<!-- Edit Review Modal -->
<div id="editReviewModal" class="review-modal-overlay">
    <div class="review-modal-box">
        <button class="modal-close-btn" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-edit"></i> <?php echo $current_lang === 'en' ? 'Edit Review' : 'Sửa đánh giá'; ?></h3>
        
        <form id="editReviewForm" onsubmit="submitEditReview(event)">
            <input type="hidden" name="review_id" id="editReviewId">
            
            <div class="modal-form-field">
                <label><?php echo $current_lang === 'en' ? 'Rating' : 'Đánh giá'; ?></label>
                <div class="star-input-group" id="editStarInput">
                    <i class="far fa-star" data-r="1"></i>
                    <i class="far fa-star" data-r="2"></i>
                    <i class="far fa-star" data-r="3"></i>
                    <i class="far fa-star" data-r="4"></i>
                    <i class="far fa-star" data-r="5"></i>
                </div>
                <input type="hidden" name="rating" id="editRatingVal">
            </div>
            
            <div class="modal-form-field">
                <label><?php echo $current_lang === 'en' ? 'Comment' : 'Nhận xét'; ?></label>
                <textarea name="comment" id="editCommentVal" required placeholder="<?php echo $current_lang === 'en' ? 'Share your experience...' : 'Chia sẻ trải nghiệm...'; ?>"></textarea>
            </div>
            
            <div class="modal-form-btns">
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> <?php echo $current_lang === 'en' ? 'Save' : 'Lưu'; ?></button>
                <button type="button" onclick="closeEditModal()" class="btn-cancel-modal"><?php echo $current_lang === 'en' ? 'Cancel' : 'Hủy'; ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="deleteConfirmModal" class="review-modal-overlay">
    <div class="review-modal-box delete-confirm">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3><?php echo $current_lang === 'en' ? 'Delete Review?' : 'Xóa đánh giá?'; ?></h3>
        <p><?php echo $current_lang === 'en' ? 'This action cannot be undone.' : 'Hành động này không thể hoàn tác.'; ?></p>
        <input type="hidden" id="deleteReviewId">
        <div class="modal-form-btns">
            <button type="button" class="btn-delete" onclick="confirmDeleteReview()">
                <i class="fas fa-trash-alt"></i> <?php echo $current_lang === 'en' ? 'Delete' : 'Xóa'; ?>
            </button>
            <button type="button" onclick="closeDeleteModal()" class="btn-cancel-modal"><?php echo $current_lang === 'en' ? 'Cancel' : 'Hủy'; ?></button>
        </div>
    </div>
</div>

<style>
/* Review Owner Actions */
.header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-left: auto;
}
.review-owner-actions {
    display: flex;
    gap: 0.5rem;
}
.owner-action-btn {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 0.9rem;
}
.owner-action-btn.edit-btn {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}
.owner-action-btn.edit-btn:hover {
    background: rgba(59, 130, 246, 0.2);
    transform: scale(1.08);
}
.owner-action-btn.delete-btn {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}
.owner-action-btn.delete-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    transform: scale(1.08);
}

/* Review Modal Styles */
.review-modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.review-modal-overlay.active {
    display: flex;
}
.review-modal-box {
    background: #ffffff;
    border-radius: 24px;
    padding: 2.5rem;
    max-width: 480px;
    width: 100%;
    position: relative;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
    animation: modalSlideUp 0.3s ease;
    border: 1px solid #e2e8f0;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.review-modal-box h3 {
    color: #0f172a;
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0 0 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}
.review-modal-box h3 i {
    color: #22c55e;
    font-size: 1.1rem;
}
.modal-close-btn {
    position: absolute;
    top: 1.25rem; right: 1.25rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    color: #64748b;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 0.9rem;
}
.modal-close-btn:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #ef4444;
    transform: rotate(90deg);
}
.modal-form-field {
    margin-bottom: 1.5rem;
}
.modal-form-field label {
    display: block;
    color: #374151;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    letter-spacing: 0.01em;
}
.star-input-group {
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
.star-input-group:hover {
    border-color: #22c55e;
    background: #f0fdf4;
}
.star-input-group i {
    color: #cbd5e1;
    cursor: pointer;
    transition: all 0.2s ease;
}
.star-input-group i.fas {
    color: #f59e0b;
    filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.3));
}
.star-input-group i:hover {
    transform: scale(1.2);
    color: #fbbf24;
}
.modal-form-field textarea {
    width: 100%;
    padding: 1rem 1.25rem;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    color: #000000;
    min-height: 120px;
    resize: vertical;
    font-size: 1rem;
    font-family: inherit;
    line-height: 1.6;
    transition: all 0.2s ease;
}
.modal-form-field textarea:focus {
    outline: none;
    border-color: #22c55e;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
}
.modal-form-field textarea::placeholder {
    color: #94a3b8;
}
.modal-form-btns {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #f1f5f9;
}
.btn-save, .btn-delete, .btn-cancel-modal {
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
.btn-save {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
}
.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(34, 197, 94, 0.45);
}
.btn-save:active {
    transform: translateY(0);
}
.btn-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
}
.btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(239, 68, 68, 0.45);
}
.btn-cancel-modal {
    background: #f8fafc;
    color: #64748b;
    border: 2px solid #e2e8f0;
}
.btn-cancel-modal:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #475569;
}

/* Delete Confirm Modal */
.review-modal-box.delete-confirm {
    text-align: center;
    padding: 2.5rem 2rem;
}
.review-modal-box.delete-confirm h3 {
    justify-content: center;
    margin-bottom: 0.5rem;
}
.review-modal-box.delete-confirm p {
    color: #64748b;
    margin-bottom: 1.5rem;
}
.delete-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
}
.delete-icon i {
    font-size: 2rem;
    color: #ef4444;
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

/* Override Dark Theme for Edit Review Modal Textarea */
body.dark-theme .review-modal-overlay textarea,
body.dark-theme .review-modal-box textarea,
body.dark-theme #editReviewModal textarea,
body.dark-theme .modal-form-field textarea,
.review-modal-overlay textarea,
.review-modal-box textarea,
#editReviewModal textarea,
.modal-form-field textarea {
    background: #ffffff !important;
    color: #000000 !important;
    border: 2px solid #e2e8f0 !important;
    -webkit-text-fill-color: #000000 !important;
}

body.dark-theme .review-modal-overlay textarea:focus,
body.dark-theme .review-modal-box textarea:focus,
body.dark-theme #editReviewModal textarea:focus,
body.dark-theme .modal-form-field textarea:focus,
.review-modal-overlay textarea:focus,
.review-modal-box textarea:focus,
#editReviewModal textarea:focus,
.modal-form-field textarea:focus {
    background: #ffffff !important;
    color: #000000 !important;
    border-color: #22c55e !important;
    -webkit-text-fill-color: #000000 !important;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1) !important;
}

body.dark-theme .review-modal-overlay textarea::placeholder,
body.dark-theme .review-modal-box textarea::placeholder,
body.dark-theme #editReviewModal textarea::placeholder,
body.dark-theme .modal-form-field textarea::placeholder {
    color: #9ca3af !important;
    -webkit-text-fill-color: #9ca3af !important;
}
</style>
