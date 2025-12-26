<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Kiểm tra và thêm cột order_id nếu chưa có
try {
    $conn->query("SELECT order_id FROM reviews LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE reviews ADD COLUMN order_id INT AFTER customer_id");
    $conn->exec("ALTER TABLE reviews ADD INDEX idx_order_id (order_id)");
}

$order_id = $_GET['order_id'] ?? 0;

// Kiểm tra đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ? AND status = 'completed'");
$stmt->execute([$order_id, $_SESSION['customer_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<script>window.location.href = "?page=orders";</script>';
    exit;
}

// Lấy món ăn trong đơn
$stmt = $conn->prepare("
    SELECT oi.*, m.name, m.image 
    FROM order_items oi 
    JOIN menu_items m ON oi.menu_item_id = m.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra đã đánh giá chưa
$existing_review = null;
if (!empty($items)) {
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE customer_id = ? AND menu_item_id = ?");
    $stmt->execute([$_SESSION['customer_id'], $items[0]['menu_item_id']]);
    $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success = '';
$error = '';

// Xử lý đánh giá
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $menu_item_id = !empty($_POST['menu_item_id']) ? $_POST['menu_item_id'] : null;
    
    if (!$menu_item_id && !empty($items)) {
        $menu_item_id = $items[0]['menu_item_id'];
    }
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Vui lòng chọn số sao từ 1-5';
    } elseif (!$menu_item_id) {
        $error = 'Không tìm thấy món ăn để đánh giá';
    } else {
        $check = $conn->prepare("SELECT id FROM reviews WHERE customer_id = ? AND menu_item_id = ?");
        $check->execute([$_SESSION['customer_id'], $menu_item_id]);
        
        if ($check->fetch()) {
            $error = 'Bạn đã đánh giá món này rồi!';
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews (customer_id, menu_item_id, rating, comment) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$_SESSION['customer_id'], $menu_item_id, $rating, $comment])) {
                $success = 'Cảm ơn bạn đã đánh giá!';
                echo '<script>setTimeout(function(){ location.reload(); }, 1500);</script>';
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại';
            }
        }
    }
}
?>

<section class="rv-page">
    <div class="rv-wrapper">
        <!-- Header Card -->
        <div class="rv-header-card">
            <a href="?page=orders" class="rv-back">
                <i class="fas fa-chevron-left"></i>
            </a>
            <div class="rv-header-info">
                <div class="rv-header-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <h1>Đánh giá đơn hàng</h1>
                    <span class="rv-order-id">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="rv-alert error">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="rv-alert success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
        <?php endif; ?>

        <!-- Order Items Preview -->
        <?php if (!empty($items)): ?>
        <div class="rv-items-preview" onclick="toggleItemsDetail()" style="cursor: pointer;">
            <div class="rv-items-label">
                <i class="fas fa-utensils"></i>
                <span>Món đã đặt (<?php echo count($items); ?>)</span>
                <i class="fas fa-chevron-down rv-toggle-icon" style="margin-left: auto; font-size: 0.8rem; color: #9ca3af; transition: transform 0.3s;"></i>
            </div>
            <div class="rv-items-list">
                <?php foreach (array_slice($items, 0, 4) as $item): 
                    // Xử lý đường dẫn hình ảnh
                    $img_src = '';
                    if (!empty($item['image'])) {
                        // Nếu đường dẫn đã có uploads/ thì giữ nguyên, nếu không thì thêm
                        if (strpos($item['image'], 'uploads/') === 0) {
                            $img_src = $item['image'];
                        } else {
                            $img_src = 'uploads/menu/' . $item['image'];
                        }
                    }
                ?>
                <div class="rv-item-thumb">
                    <?php if (!empty($img_src)): ?>
                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-utensils\'></i>';">
                    <?php else: ?>
                    <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (count($items) > 4): ?>
                <div class="rv-item-more">+<?php echo count($items) - 4; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Items Detail (Hidden by default) -->
        <div class="rv-items-detail" id="itemsDetail" style="display: none;">
            <?php foreach ($items as $item): 
                $img_src = '';
                if (!empty($item['image'])) {
                    if (strpos($item['image'], 'uploads/') === 0) {
                        $img_src = $item['image'];
                    } else {
                        $img_src = 'uploads/menu/' . $item['image'];
                    }
                }
            ?>
            <div class="rv-item-detail-row">
                <div class="rv-item-detail-img">
                    <?php if (!empty($img_src)): ?>
                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php else: ?>
                    <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </div>
                <div class="rv-item-detail-info">
                    <span class="rv-item-detail-name"><?php echo htmlspecialchars($item['name']); ?></span>
                    <span class="rv-item-detail-qty">x<?php echo $item['quantity']; ?></span>
                </div>
                <div class="rv-item-detail-price">
                    <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Main Review Card -->
        <div class="rv-main-card">
            <?php if ($existing_review): ?>
            <!-- Already Reviewed -->
            <div class="rv-done">
                <div class="rv-done-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h2>Cảm ơn bạn!</h2>
                <p>Đánh giá của bạn đã được ghi nhận</p>
                
                <div class="rv-done-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star <?php echo $i <= $existing_review['rating'] ? 'active' : ''; ?>"></i>
                    <?php endfor; ?>
                </div>
                
                <?php if ($existing_review['comment']): ?>
                <div class="rv-done-comment">
                    <i class="fas fa-quote-left"></i>
                    <?php echo htmlspecialchars($existing_review['comment']); ?>
                </div>
                <?php endif; ?>
                
                <div class="rv-done-time">
                    <i class="far fa-clock"></i>
                    <?php echo date('d/m/Y - H:i', strtotime($existing_review['created_at'])); ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Review Form -->
            <form method="POST" class="rv-form">
                <div class="rv-form-header">
                    <div class="rv-emoji" id="rvEmoji">🤔</div>
                    <h2 id="rvTitle">Bạn thấy đơn hàng thế nào?</h2>
                </div>

                <!-- Star Rating -->
                <div class="rv-stars-wrap">
                    <div class="rv-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                        <label for="star<?php echo $i; ?>" data-value="<?php echo $i; ?>">
                            <i class="fas fa-star" style="color: #fde68a;"></i>
                        </label>
                        <?php endfor; ?>
                    </div>
                    <div class="rv-rating-text" id="rvText">Chọn số sao</div>
                </div>

                <div class="rv-comment-wrap">
                    <label>
                        <i class="fas fa-pen"></i>
                        <span>Chia sẻ trải nghiệm của bạn</span>
                    </label>
                    <textarea name="comment" rows="3" placeholder="Món ăn ngon, giao hàng nhanh..."></textarea>
                </div>

                <!-- Quick Tags -->
                <div class="rv-tags">
                    <span class="rv-tag" data-text="Món ăn ngon">😋 Ngon</span>
                    <span class="rv-tag" data-text="Giao hàng nhanh">🚀 Nhanh</span>
                    <span class="rv-tag" data-text="Đóng gói cẩn thận">📦 Đẹp</span>
                    <span class="rv-tag" data-text="Giá hợp lý">💰 Rẻ</span>
                    <span class="rv-tag" data-text="Sẽ đặt lại">❤️ Quay lại</span>
                </div>

                <button type="submit" class="rv-submit">
                    <i class="fas fa-paper-plane"></i>
                    Gửi đánh giá
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Back Link -->
        <a href="?page=orders" class="rv-back-link">
            <i class="fas fa-arrow-left"></i>
            Quay lại đơn hàng
        </a>
    </div>
</section>

<style>
.rv-page {
    min-height: 100vh;
    padding: 1.5rem 0 3rem;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}
.rv-wrapper {
    max-width: 500px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Header */
.rv-header-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 20px;
    margin-bottom: 1rem;
    box-shadow: 0 8px 30px rgba(34, 197, 94, 0.3);
}
.rv-back {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.2s;
}
.rv-back:hover { background: rgba(255,255,255,0.3); }
.rv-header-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.rv-header-icon {
    width: 44px;
    height: 44px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fbbf24;
    font-size: 1.25rem;
}
.rv-header-info h1 {
    margin: 0;
    font-size: 1.2rem;
    color: white;
    font-weight: 700;
}
.rv-order-id {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.85);
}

/* Alert */
.rv-alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.85rem 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}
.rv-alert.error {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.rv-alert.success {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

/* Items Preview */
.rv-items-preview {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1rem;
    background: white;
    border-radius: 14px;
    margin-bottom: 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}
.rv-items-preview:hover {
    border-color: #22c55e;
}
.rv-items-preview.expanded {
    border-radius: 14px 14px 0 0;
    border-bottom: none;
}
.rv-items-preview.expanded .rv-toggle-icon {
    transform: rotate(180deg);
}

/* Items Detail */
.rv-items-detail {
    background: white;
    border: 2px solid #e5e7eb;
    border-top: 1px dashed #e5e7eb;
    border-radius: 0 0 14px 14px;
    padding: 0.75rem;
    margin-bottom: 1rem;
    animation: slideDown 0.3s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.rv-item-detail-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem;
    border-radius: 10px;
    transition: background 0.2s;
}
.rv-item-detail-row:hover {
    background: #f9fafb;
}
.rv-item-detail-img {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    overflow: hidden;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.rv-item-detail-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rv-item-detail-img i {
    color: #9ca3af;
    font-size: 1.2rem;
}
.rv-item-detail-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.rv-item-detail-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f2937;
}
.rv-item-detail-qty {
    font-size: 0.8rem;
    color: #6b7280;
}
.rv-item-detail-price {
    font-size: 0.9rem;
    font-weight: 700;
    color: #22c55e;
}
.rv-items-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
}
.rv-items-label i { color: #22c55e; }
.rv-items-list {
    display: flex;
    gap: 0.35rem;
}
.rv-item-thumb {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    overflow: hidden;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 0.9rem;
}
.rv-item-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rv-item-more {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #22c55e;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
}

/* Main Card */
.rv-main-card {
    background: white;
    border-radius: 24px;
    padding: 2rem 1.75rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 2px solid #22c55e;
}

/* Done State */
.rv-done {
    text-align: center;
}
.rv-done-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.75rem;
    color: #f59e0b;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
.rv-done h2 {
    margin: 0 0 0.25rem;
    font-size: 1.35rem;
    color: #1f2937;
}
.rv-done > p {
    margin: 0 0 1.25rem;
    color: #6b7280;
    font-size: 0.9rem;
}
.rv-done-stars {
    display: flex;
    justify-content: center;
    gap: 0.4rem;
    margin-bottom: 1rem;
}
.rv-page .rv-done-stars i.fa-star,
.rv-done-stars i.fa-star {
    font-size: 1.5rem;
    color: #fde68a !important; /* Vàng nhạt */
}
.rv-page .rv-done-stars i.fa-star.active,
.rv-done-stars i.fa-star.active {
    color: #f59e0b !important; /* Vàng đậm */
}
.rv-done-comment {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 12px;
    color: #374151;
    font-style: italic;
    margin-bottom: 1rem;
    position: relative;
}
.rv-done-comment i {
    color: #d1d5db;
    margin-right: 0.5rem;
}
.rv-done-time {
    font-size: 0.8rem;
    color: #9ca3af;
}
.rv-done-time i { margin-right: 0.3rem; }

/* Form */
.rv-form-header {
    text-align: center;
    margin-bottom: 1.5rem;
}
.rv-emoji {
    font-size: 3rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s;
}
.rv-form-header h2 {
    margin: 0;
    font-size: 1.1rem;
    color: #1f2937;
    font-weight: 600;
    transition: all 0.3s;
}

/* Stars */
.rv-stars-wrap {
    text-align: center;
    margin-bottom: 1.5rem;
}
.rv-stars {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.6rem;
}
.rv-stars input { display: none; }
.rv-stars label {
    cursor: pointer;
    font-size: 2.25rem;
    transition: all 0.2s;
}
.rv-page .rv-stars label i.fa-star,
.rv-stars label i.fa-star {
    color: #fde68a !important; /* Vàng nhạt cho sao chưa chọn */
}
.rv-page .rv-stars label:hover i.fa-star,
.rv-page .rv-stars label.active i.fa-star,
.rv-stars label:hover i.fa-star,
.rv-stars label.active i.fa-star {
    color: #f59e0b !important; /* Vàng đậm cho sao đã chọn */
}
.rv-stars label:hover,
.rv-stars label.active {
    transform: scale(1.15);
}
.rv-rating-text {
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
}

/* Comment */
.rv-comment-wrap {
    margin-bottom: 1rem;
}
.rv-comment-wrap label {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
    color: #1f2937;
    font-weight: 600;
    margin-bottom: 0.6rem;
    gap: 0.5rem;
}
.rv-comment-wrap label i {
    color: #22c55e;
    font-size: 0.85rem;
    width: 18px;
    text-align: center;
}
.rv-comment-wrap textarea {
    width: 100%;
    padding: 0.9rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 14px;
    font-size: 0.9rem;
    resize: none;
    transition: all 0.2s;
    font-family: inherit;
    background: #f9fafb;
}
.rv-comment-wrap textarea:focus {
    outline: none;
    border-color: #22c55e;
    background: white;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

/* Tags */
.rv-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    justify-content: center;
}
.rv-tag {
    padding: 0.5rem 0.9rem;
    background: #f3f4f6;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.rv-tag:hover {
    background: #dcfce7;
    color: #15803d;
}
.rv-tag.selected {
    background: #22c55e;
    color: white;
}

/* Submit */
.rv-submit {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s;
}
.rv-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
}

/* Back Link */
.rv-back-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.85rem;
    background: white;
    border-radius: 12px;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}
.rv-back-link:hover {
    color: #22c55e;
}

/* Responsive */
@media (max-width: 480px) {
    .rv-stars label { font-size: 1.85rem; }
    .rv-emoji { font-size: 2.5rem; }
}

/* Override Dark Theme - Force Light Theme for Review Page */
body.dark-theme .rv-page,
.dark-theme .rv-page {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
}

body.dark-theme .rv-main-card,
.dark-theme .rv-main-card {
    background: white !important;
    border: 2px solid #22c55e !important;
}

body.dark-theme .rv-items-preview,
.dark-theme .rv-items-preview {
    background: white !important;
    border: 2px solid #e5e7eb !important;
}

body.dark-theme .rv-form-header h2,
.dark-theme .rv-form-header h2 {
    color: #1f2937 !important;
}

body.dark-theme .rv-comment-wrap textarea,
.dark-theme .rv-comment-wrap textarea {
    background: #f9fafb !important;
    color: #1f2937 !important;
    border-color: #e5e7eb !important;
}

body.dark-theme .rv-tag,
.dark-theme .rv-tag {
    background: #f3f4f6 !important;
    color: #4b5563 !important;
}

body.dark-theme .rv-tag.selected,
.dark-theme .rv-tag.selected {
    background: #22c55e !important;
    color: white !important;
}

body.dark-theme .rv-back-link,
.dark-theme .rv-back-link {
    background: white !important;
    color: #6b7280 !important;
    border: 2px solid #e5e7eb !important;
}
</style>

<script>
// Toggle items detail
function toggleItemsDetail() {
    const detail = document.getElementById('itemsDetail');
    const preview = document.querySelector('.rv-items-preview');
    
    if (detail.style.display === 'none') {
        detail.style.display = 'block';
        preview.classList.add('expanded');
    } else {
        detail.style.display = 'none';
        preview.classList.remove('expanded');
    }
}

const ratingData = {
    1: { emoji: '😞', text: 'Rất tệ', title: 'Rất tiếc bạn không hài lòng' },
    2: { emoji: '😕', text: 'Tệ', title: 'Chúng tôi sẽ cải thiện' },
    3: { emoji: '😐', text: 'Bình thường', title: 'Cảm ơn phản hồi của bạn' },
    4: { emoji: '😊', text: 'Tốt', title: 'Rất vui bạn hài lòng!' },
    5: { emoji: '😍', text: 'Tuyệt vời!', title: 'Cảm ơn bạn rất nhiều!' }
};

const stars = document.querySelectorAll('.rv-stars label');
const inputs = document.querySelectorAll('.rv-stars input');

stars.forEach(star => {
    star.addEventListener('click', function() {
        const val = this.dataset.value;
        
        // Update stars - đổi màu
        stars.forEach(s => {
            const icon = s.querySelector('i');
            if (s.dataset.value <= val) {
                s.classList.add('active');
                icon.style.color = '#f59e0b'; // Vàng đậm khi chọn
            } else {
                s.classList.remove('active');
                icon.style.color = '#fde68a'; // Vàng nhạt khi chưa chọn
            }
        });
        
        // Update emoji & text
        const data = ratingData[val];
        document.getElementById('rvEmoji').textContent = data.emoji;
        document.getElementById('rvTitle').textContent = data.title;
        document.getElementById('rvText').textContent = data.text;
        document.getElementById('rvText').style.color = '#f59e0b';
        
        // Check radio
        document.getElementById('star' + val).checked = true;
    });
});

// Quick tags
document.querySelectorAll('.rv-tag').forEach(tag => {
    tag.addEventListener('click', function() {
        this.classList.toggle('selected');
        const textarea = document.querySelector('.rv-comment-wrap textarea');
        const text = this.dataset.text;
        
        if (this.classList.contains('selected')) {
            textarea.value = textarea.value ? textarea.value + ', ' + text : text;
        } else {
            textarea.value = textarea.value.replace(text + ', ', '').replace(', ' + text, '').replace(text, '');
        }
    });
});
</script>
