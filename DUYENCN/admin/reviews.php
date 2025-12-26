<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý các hành động
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $review_id = $_POST['review_id'] ?? 0;
    $comment_id = $_POST['comment_id'] ?? 0;
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE reviews SET is_approved = TRUE WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success_message'] = 'Đã duyệt đánh giá';
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE reviews SET is_approved = FALSE WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success_message'] = 'Đã từ chối đánh giá';
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success_message'] = 'Đã xóa đánh giá';
    } elseif ($action === 'delete_comment') {
        $stmt = $conn->prepare("DELETE FROM review_comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $_SESSION['success_message'] = 'Đã xóa bình luận';
    }
    
    header('Location: reviews.php');
    exit;
}

// Lấy thống kê
$stmt = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_approved = TRUE THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN is_approved = FALSE THEN 1 ELSE 0 END) as pending,
        AVG(rating) as avg_rating
    FROM reviews
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách đánh giá
$filter = $_GET['filter'] ?? 'all';
$rating_filter = $_GET['rating'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = "1=1";
if ($filter === 'approved') {
    $where .= " AND r.is_approved = TRUE";
} elseif ($filter === 'pending') {
    $where .= " AND r.is_approved = FALSE";
}

if ($rating_filter !== 'all') {
    $where .= " AND r.rating = " . intval($rating_filter);
}

if ($search) {
    $where .= " AND (c.full_name LIKE :search OR m.name LIKE :search OR r.comment LIKE :search)";
}

// Tạo bảng nếu chưa có
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS review_likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        review_id INT NOT NULL,
        customer_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (review_id, customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $conn->exec("CREATE TABLE IF NOT EXISTS review_comments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        review_id INT NOT NULL,
        customer_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

$stmt = $conn->prepare("
    SELECT 
        r.*,
        c.full_name as customer_name,
        c.email as customer_email,
        m.name as menu_item_name,
        m.image as menu_item_image,
        COALESCE((SELECT COUNT(*) FROM review_likes WHERE review_id = r.id), 0) as likes_count,
        COALESCE((SELECT COUNT(*) FROM review_comments WHERE review_id = r.id), 0) as comments_count
    FROM reviews r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN menu_items m ON r.menu_item_id = m.id
    WHERE {$where}
    ORDER BY r.created_at DESC
");

if ($search) {
    $stmt->bindValue(':search', "%{$search}%");
}

$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đánh giá - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc !important; }
        .main-content { background: #f8fafc !important; padding: 1.5rem 2rem !important; }
        
        .page-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .page-header h1 {
            color: #1f2937 !important;
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0 0 0.25rem 0;
        }
        .page-header h1 i { color: #22c55e; }
        .page-header p { color: #6b7280; margin: 0; font-size: 0.875rem; }
        
        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            border: 2px solid #e5e7eb;
        }
        .filter-row {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-btn {
            padding: 0.4rem 0.85rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: #4b5563;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .filter-btn:hover { border-color: #22c55e; color: #22c55e; }
        .filter-btn.active {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border-color: transparent;
        }
        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 0.4rem 0.85rem 0.4rem 2rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.8rem;
            background: white;
            color: #1f2937;
        }
        .search-box input:focus { outline: none; border-color: #22c55e; }
        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.8rem;
        }

        /* Reviews Table */
        .reviews-table {
            background: white;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            overflow: hidden;
        }
        .table-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 140px;
            padding: 0.85rem 1.25rem;
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 700;
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .review-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 140px;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
            transition: all 0.15s;
        }
        .review-row:last-child { border-bottom: none; }
        .review-row:hover { background: #fafffe; }
        
        /* Product Cell */
        .product-cell {
            display: flex;
            gap: 0.85rem;
            align-items: center;
        }
        .product-img {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }
        .product-info h4 {
            font-size: 0.875rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 0.2rem 0;
            line-height: 1.3;
        }
        .product-info .customer {
            font-size: 0.75rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .product-info .customer i { color: #22c55e; font-size: 0.65rem; }
        
        /* Rating Cell */
        .rating-cell {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .stars {
            display: flex;
            gap: 0.1rem;
        }
        .stars i { color: #f59e0b; font-size: 0.75rem; }
        .stars i.empty { color: #e5e7eb; }
        .rating-date {
            font-size: 0.7rem;
            color: #9ca3af;
        }
        
        /* Content Cell */
        .content-cell {
            font-size: 0.85rem;
            color: #1f2937;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            padding-right: 0.5rem;
            font-weight: 500;
        }
        
        /* Status Cell */
        .status-cell {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .status-badge {
            padding: 0.3rem 0.65rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            width: fit-content;
        }
        .status-badge.approved { background: #dcfce7; color: #15803d; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .engagement {
            display: flex;
            gap: 0.6rem;
            font-size: 0.7rem;
            color: #6b7280;
        }
        .engagement span {
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }
        .engagement .fa-heart { color: #ef4444; }
        .engagement .fa-comment { color: #22c55e; }

        /* Actions Cell */
        .actions-cell {
            display: flex;
            gap: 0.35rem;
            justify-content: flex-end;
        }
        .btn-sm {
            padding: 0.35rem 0.6rem;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .btn-sm:hover { transform: translateY(-1px); }
        .btn-approve { background: #22c55e; color: white; }
        .btn-reject { background: #f59e0b; color: white; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-view { background: #e5e7eb; color: #4b5563; }
        .btn-view:hover { background: #d1d5db; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #9ca3af;
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.5; }
        .empty-state h3 { color: #6b7280; font-size: 1rem; margin-bottom: 0.25rem; }
        
        /* Alert */
        .alert-success {
            background: #dcfce7;
            color: #15803d;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            font-size: 0.85rem;
            border: 1px solid #86efac;
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #9ca3af;
            cursor: pointer;
        }
        .modal-close:hover { color: #ef4444; }
        .modal-body { padding: 1.5rem; }
        .detail-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.8rem;
            min-width: 100px;
        }
        .detail-value {
            color: #1f2937;
            font-size: 0.875rem;
            flex: 1;
        }
        .full-comment {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid #22c55e;
            line-height: 1.6;
        }
        .comments-list { margin-top: 1rem; }
        .comment-item {
            background: #f8fafc;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .comment-author { font-weight: 700; color: #1f2937; font-size: 0.8rem; }
        .comment-date { color: #9ca3af; font-size: 0.7rem; margin-left: 0.5rem; }
        .comment-text { color: #4b5563; font-size: 0.8rem; margin-top: 0.25rem; }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-star"></i> Quản lý đánh giá</h1>
            <p>Quản lý và kiểm duyệt đánh giá từ khách hàng</p>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem;">
            <div style="background: white; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f97316';" onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='#d1d5db';">
                <div style="width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);"><i class="fas fa-comments"></i></div>
                <div><h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo number_format($stats['total'] ?? 0); ?></h3><p style="color: #6b7280; margin: 0; font-size: 0.8rem;">Tổng đánh giá</p></div>
            </div>
            <div style="background: white; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='#d1d5db';">
                <div style="width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"><i class="fas fa-check-circle"></i></div>
                <div><h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo number_format($stats['approved'] ?? 0); ?></h3><p style="color: #6b7280; margin: 0; font-size: 0.8rem;">Đã duyệt</p></div>
            </div>
            <div style="background: white; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f59e0b';" onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='#d1d5db';">
                <div style="width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-clock"></i></div>
                <div><h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo number_format($stats['pending'] ?? 0); ?></h3><p style="color: #6b7280; margin: 0; font-size: 0.8rem;">Chờ duyệt</p></div>
            </div>
            <div style="background: white; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f97316';" onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='#d1d5db';">
                <div style="width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);"><i class="fas fa-star"></i></div>
                <div><h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0;"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?> <i class="fas fa-star" style="font-size: 0.9rem; color: #f59e0b;"></i></h3><p style="color: #6b7280; margin: 0; font-size: 0.8rem;">Đánh giá TB</p></div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-card">
            <div class="filter-row">
                <a href="?filter=all&rating=<?php echo $rating_filter; ?>" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Tất cả</a>
                <a href="?filter=approved&rating=<?php echo $rating_filter; ?>" class="filter-btn <?php echo $filter === 'approved' ? 'active' : ''; ?>"><i class="fas fa-check"></i> Đã duyệt</a>
                <a href="?filter=pending&rating=<?php echo $rating_filter; ?>" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Chờ duyệt</a>
                <span style="color: #d1d5db; margin: 0 0.25rem;">|</span>
                <a href="?filter=<?php echo $filter; ?>&rating=all" class="filter-btn <?php echo $rating_filter === 'all' ? 'active' : ''; ?>">Tất cả sao</a>
                <a href="?filter=<?php echo $filter; ?>&rating=5" class="filter-btn <?php echo $rating_filter === '5' ? 'active' : ''; ?>">⭐ 5</a>
                <a href="?filter=<?php echo $filter; ?>&rating=4" class="filter-btn <?php echo $rating_filter === '4' ? 'active' : ''; ?>">⭐ 4</a>
                <a href="?filter=<?php echo $filter; ?>&rating=3" class="filter-btn <?php echo $rating_filter === '3' ? 'active' : ''; ?>">⭐ 3</a>
                <a href="?filter=<?php echo $filter; ?>&rating=2" class="filter-btn <?php echo $rating_filter === '2' ? 'active' : ''; ?>">⭐ 2</a>
                <a href="?filter=<?php echo $filter; ?>&rating=1" class="filter-btn <?php echo $rating_filter === '1' ? 'active' : ''; ?>">⭐ 1</a>
                <form method="GET" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <input type="hidden" name="rating" value="<?php echo htmlspecialchars($rating_filter); ?>">
                    <input type="text" name="search" placeholder="Tìm kiếm..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
        </div>

        <!-- Reviews Table -->
        <div class="reviews-table">
            <div class="table-header">
                <span>Món ăn / Khách hàng</span>
                <span>Đánh giá</span>
                <span>Nội dung</span>
                <span>Trạng thái</span>
                <span style="text-align: right;">Thao tác</span>
            </div>
            
            <?php if (empty($reviews)): ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash"></i>
                    <h3>Không có đánh giá nào</h3>
                    <p>Chưa có đánh giá phù hợp với bộ lọc</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-row">
                        <div class="product-cell">
                            <?php if ($review['menu_item_image']): ?>
                                <img src="../<?php echo htmlspecialchars($review['menu_item_image']); ?>" class="product-img" alt="">
                            <?php else: ?>
                                <div class="product-img"><i class="fas fa-utensils"></i></div>
                            <?php endif; ?>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($review['menu_item_name'] ?? 'Không xác định'); ?></h4>
                                <div class="customer"><i class="fas fa-user"></i> <?php echo htmlspecialchars($review['customer_name'] ?? 'Ẩn danh'); ?></div>
                            </div>
                        </div>
                        
                        <div class="rating-cell">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star <?php echo $i > $review['rating'] ? 'empty' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        
                        <div class="content-cell">
                            <?php echo htmlspecialchars(mb_substr($review['comment'] ?? '', 0, 80)); ?><?php echo mb_strlen($review['comment'] ?? '') > 80 ? '...' : ''; ?>
                        </div>
                        
                        <div class="status-cell">
                            <span class="status-badge <?php echo $review['is_approved'] ? 'approved' : 'pending'; ?>">
                                <?php echo $review['is_approved'] ? '✓ Đã duyệt' : '⏳ Chờ duyệt'; ?>
                            </span>
                            <div class="engagement">
                                <?php if ($review['likes_count'] > 0): ?><span><i class="fas fa-heart"></i> <?php echo $review['likes_count']; ?></span><?php endif; ?>
                                <?php if ($review['comments_count'] > 0): ?><span><i class="fas fa-comment"></i> <?php echo $review['comments_count']; ?></span><?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="actions-cell">
                            <button class="btn-sm btn-view" onclick="viewReview(<?php echo $review['id']; ?>)" title="Xem chi tiết"><i class="fas fa-eye"></i></button>
                            <?php if (!$review['is_approved']): ?>
                                <form method="POST" style="display:inline;"><input type="hidden" name="action" value="approve"><input type="hidden" name="review_id" value="<?php echo $review['id']; ?>"><button type="submit" class="btn-sm btn-approve" title="Duyệt"><i class="fas fa-check"></i></button></form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;"><input type="hidden" name="action" value="reject"><input type="hidden" name="review_id" value="<?php echo $review['id']; ?>"><button type="submit" class="btn-sm btn-reject" title="Từ chối"><i class="fas fa-times"></i></button></form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa đánh giá này?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="review_id" value="<?php echo $review['id']; ?>"><button type="submit" class="btn-sm btn-delete" title="Xóa"><i class="fas fa-trash"></i></button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal xem chi tiết -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-star" style="color: #22c55e; margin-right: 0.5rem;"></i> Chi tiết đánh giá</h3>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="modalBody">
                <p style="text-align: center; color: #9ca3af;">Đang tải...</p>
            </div>
        </div>
    </div>
    
    <script>
    const reviewsData = <?php echo json_encode($reviews); ?>;
    
    function viewReview(id) {
        const review = reviewsData.find(r => r.id == id);
        if (!review) return;
        
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<i class="${i <= review.rating ? 'fas' : 'far'} fa-star" style="color: ${i <= review.rating ? '#f59e0b' : '#e5e7eb'};"></i>`;
        }
        
        document.getElementById('modalBody').innerHTML = `
            <div class="detail-row">
                <span class="detail-label">Món ăn:</span>
                <span class="detail-value" style="font-weight: 700;">${review.menu_item_name || 'Không xác định'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Khách hàng:</span>
                <span class="detail-value">${review.customer_name || 'Ẩn danh'} (${review.customer_email || ''})</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Đánh giá:</span>
                <span class="detail-value">${stars} (${review.rating}/5)</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Ngày:</span>
                <span class="detail-value">${new Date(review.created_at).toLocaleString('vi-VN')}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Trạng thái:</span>
                <span class="detail-value"><span class="status-badge ${review.is_approved ? 'approved' : 'pending'}">${review.is_approved ? '✓ Đã duyệt' : '⏳ Chờ duyệt'}</span></span>
            </div>
            <div class="detail-row" style="flex-direction: column; gap: 0.5rem;">
                <span class="detail-label">Nội dung:</span>
                <div class="full-comment">${review.comment || 'Không có nội dung'}</div>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tương tác:</span>
                <span class="detail-value"><i class="fas fa-heart" style="color: #ef4444;"></i> ${review.likes_count} lượt thích &nbsp; <i class="fas fa-comment" style="color: #22c55e;"></i> ${review.comments_count} bình luận</span>
            </div>
        `;
        
        document.getElementById('reviewModal').classList.add('active');
    }
    
    function closeModal() {
        document.getElementById('reviewModal').classList.remove('active');
    }
    
    document.getElementById('reviewModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    </script>
</body>
</html>
