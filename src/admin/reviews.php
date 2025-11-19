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
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
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

// Lọc theo rating
if ($rating_filter !== 'all') {
    $where .= " AND r.rating = " . intval($rating_filter);
}

if ($search) {
    $where .= " AND (c.full_name LIKE :search OR m.name LIKE :search OR r.comment LIKE :search)";
}

// Kiểm tra và tạo bảng nếu chưa có
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS review_likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        review_id INT NOT NULL,
        customer_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (review_id, customer_id),
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $conn->exec("CREATE TABLE IF NOT EXISTS review_comments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        review_id INT NOT NULL,
        customer_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Tables might already exist
}

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
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-luxury.css">
    <link rel="stylesheet" href="../assets/css/admin-fix.css">
    <link rel="stylesheet" href="../assets/css/admin-orange-theme.css">
    <style>
        .reviews-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 4px solid;
        }
        
        .stat-card.total { border-left-color: #f97316; }
        .stat-card.approved { border-left-color: #10b981; }
        .stat-card.pending { border-left-color: #f59e0b; }
        .stat-card.rating { border-left-color: #f97316; }
        
        .stat-card h3 {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
        }
        
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.7rem 1.5rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            border-color: #f97316;
            color: #f97316;
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            border-color: transparent;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.7rem 1rem 0.7rem 2.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .review-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s;
        }
        
        .review-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .review-info {
            display: flex;
            gap: 1rem;
            flex: 1;
        }
        
        .menu-item-thumb {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        
        .review-details h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.3rem;
        }
        
        .review-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        
        .review-rating {
            display: flex;
            gap: 0.2rem;
        }
        
        .review-rating i {
            color: #f59e0b;
        }
        
        .review-comment {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
            color: #475569;
            line-height: 1.6;
        }
        
        .review-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .review-actions button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .review-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        
        .status-badge.approved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        
        .status-badge.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }
        
        .likes-count {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .likes-count i {
            color: #ef4444;
        }
        
        .comments-count {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .comments-count i {
            color: #f97316;
        }
        
        .review-comments-toggle {
            margin-top: 1rem;
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            color: #475569;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .review-comments-toggle:hover {
            background: white;
            border-color: #f97316;
            color: #f97316;
        }
        
        .review-comments-section {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid #f97316;
            display: none;
        }
        
        .review-comments-section.active {
            display: block;
        }
        
        .comment-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .comment-content {
            flex: 1;
        }
        
        .comment-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .comment-author {
            font-weight: 700;
            color: #1e293b;
        }
        
        .comment-date {
            color: #94a3b8;
            font-size: 0.85rem;
        }
        
        .comment-text {
            color: #475569;
            line-height: 1.6;
        }
        
        .comment-actions {
            margin-left: 1rem;
        }
        
        .btn-delete-comment {
            padding: 0.4rem 0.8rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-delete-comment:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .no-comments {
            text-align: center;
            padding: 1.5rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-star"></i> Quản lý đánh giá</h1>
                <p>Quản lý và kiểm duyệt đánh giá từ khách hàng</p>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="reviews-stats">
            <div class="stat-card total">
                <h3>Tổng đánh giá</h3>
                <div class="value"><?php echo number_format($stats['total']); ?></div>
            </div>
            <div class="stat-card approved">
                <h3>Đã duyệt</h3>
                <div class="value"><?php echo number_format($stats['approved']); ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Chờ duyệt</h3>
                <div class="value"><?php echo number_format($stats['pending']); ?></div>
            </div>
            <div class="stat-card rating">
                <h3>Đánh giá trung bình</h3>
                <div class="value">
                    <?php echo number_format($stats['avg_rating'], 1); ?>
                    <i class="fas fa-star" style="font-size: 1.2rem; color: #f59e0b;"></i>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <a href="?filter=all&rating=<?php echo $rating_filter; ?>" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> Tất cả
            </a>
            <a href="?filter=approved&rating=<?php echo $rating_filter; ?>" class="filter-btn <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                <i class="fas fa-check"></i> Đã duyệt
            </a>
            <a href="?filter=pending&rating=<?php echo $rating_filter; ?>" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Chờ duyệt
            </a>
            
            <form method="GET" class="search-box">
                <i class="fas fa-search"></i>
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="hidden" name="rating" value="<?php echo htmlspecialchars($rating_filter); ?>">
                <input type="text" name="search" placeholder="Tìm kiếm theo tên, món ăn, nội dung..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>
        
        <!-- Rating Filters -->
        <div class="filters" style="margin-top: -1rem;">
            <a href="?filter=<?php echo $filter; ?>&rating=all" class="filter-btn <?php echo $rating_filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Tất cả đánh giá
            </a>
            <a href="?filter=<?php echo $filter; ?>&rating=5" class="filter-btn <?php echo $rating_filter === '5' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> 5 sao (<?php echo $stats['rating_5']; ?>)
            </a>
            <a href="?filter=<?php echo $filter; ?>&rating=4" class="filter-btn <?php echo $rating_filter === '4' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> 4 sao (<?php echo $stats['rating_4']; ?>)
            </a>
            <a href="?filter=<?php echo $filter; ?>&rating=3" class="filter-btn <?php echo $rating_filter === '3' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> 3 sao (<?php echo $stats['rating_3']; ?>)
            </a>
            <a href="?filter=<?php echo $filter; ?>&rating=2" class="filter-btn <?php echo $rating_filter === '2' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> 2 sao (<?php echo $stats['rating_2']; ?>)
            </a>
            <a href="?filter=<?php echo $filter; ?>&rating=1" class="filter-btn <?php echo $rating_filter === '1' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> 1 sao (<?php echo $stats['rating_1']; ?>)
            </a>
        </div>
        
        <!-- Reviews List -->
        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash"></i>
                    <h3>Không có đánh giá nào</h3>
                    <p>Chưa có đánh giá nào trong hệ thống</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-info">
                                <?php if ($review['menu_item_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($review['menu_item_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($review['menu_item_name']); ?>" 
                                         class="menu-item-thumb">
                                <?php else: ?>
                                    <div class="menu-item-thumb" style="display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="review-details">
                                    <h4><?php echo htmlspecialchars($review['menu_item_name']); ?></h4>
                                    <div class="review-meta">
                                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($review['customer_name']); ?></span>
                                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($review['customer_email']); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                                <span class="status-badge <?php echo $review['is_approved'] ? 'approved' : 'pending'; ?>">
                                    <?php echo $review['is_approved'] ? 'Đã duyệt' : 'Chờ duyệt'; ?>
                                </span>
                                <div style="display: flex; gap: 1rem;">
                                    <?php if ($review['likes_count'] > 0): ?>
                                        <span class="likes-count">
                                            <i class="fas fa-heart"></i>
                                            <?php echo $review['likes_count']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($review['comments_count'] > 0): ?>
                                        <span class="comments-count">
                                            <i class="fas fa-comment"></i>
                                            <?php echo $review['comments_count']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="review-comment">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                        
                        <?php if ($review['comments_count'] > 0): ?>
                        <button class="review-comments-toggle" onclick="toggleComments(<?php echo $review['id']; ?>)">
                            <i class="fas fa-comment"></i>
                            Xem <?php echo $review['comments_count']; ?> bình luận
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="review-comments-section" id="comments-<?php echo $review['id']; ?>">
                            <?php
                            $stmt_comments = $conn->prepare("
                                SELECT 
                                    rc.*,
                                    c.full_name,
                                    c.email
                                FROM review_comments rc
                                JOIN customers c ON rc.customer_id = c.id
                                WHERE rc.review_id = ?
                                ORDER BY rc.created_at ASC
                            ");
                            $stmt_comments->execute([$review['id']]);
                            $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (empty($comments)): ?>
                                <div class="no-comments">
                                    <i class="far fa-comment"></i>
                                    <p>Chưa có bình luận</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <div class="comment-content">
                                            <div class="comment-header">
                                                <span class="comment-author"><?php echo htmlspecialchars($comment['full_name']); ?></span>
                                                <span class="comment-date">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                                </span>
                                            </div>
                                            <div class="comment-text">
                                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                            </div>
                                        </div>
                                        <div class="comment-actions">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_comment">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <button type="submit" class="btn-delete-comment" onclick="return confirm('Xóa bình luận này?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="review-actions">
                            <?php if (!$review['is_approved']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" class="btn-approve" onclick="return confirm('Duyệt đánh giá này?')">
                                        <i class="fas fa-check"></i> Duyệt
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" class="btn-reject" onclick="return confirm('Từ chối đánh giá này?')">
                                        <i class="fas fa-times"></i> Từ chối
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" class="btn-delete" onclick="return confirm('Xóa đánh giá này? Hành động này không thể hoàn tác!')">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
    function toggleComments(reviewId) {
        const commentsSection = document.getElementById('comments-' + reviewId);
        const button = event.target.closest('.review-comments-toggle');
        const icon = button.querySelector('.fa-chevron-down, .fa-chevron-up');
        
        if (commentsSection.classList.contains('active')) {
            commentsSection.classList.remove('active');
            icon.className = 'fas fa-chevron-down';
        } else {
            commentsSection.classList.add('active');
            icon.className = 'fas fa-chevron-up';
        }
    }
    </script>
</body>
</html>
