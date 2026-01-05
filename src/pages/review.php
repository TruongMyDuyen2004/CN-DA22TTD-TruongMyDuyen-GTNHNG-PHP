<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

try {
    $conn->query("SELECT order_id FROM reviews LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE reviews ADD COLUMN order_id INT AFTER customer_id");
    $conn->exec("ALTER TABLE reviews ADD INDEX idx_order_id (order_id)");
}

$order_id = $_GET['order_id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ? AND status = 'completed'");
$stmt->execute([$order_id, $_SESSION['customer_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<script>window.location.href = "?page=orders";</script>';
    exit;
}

$stmt = $conn->prepare("
    SELECT oi.*, m.name, m.image 
    FROM order_items oi 
    JOIN menu_items m ON oi.menu_item_id = m.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$existing_review = null;
if (!empty($items)) {
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE customer_id = ? AND menu_item_id = ?");
    $stmt->execute([$_SESSION['customer_id'], $items[0]['menu_item_id']]);
    $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success = '';
$error = '';

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
        <div class="rv-header">
            <a href="?page=orders" class="rv-back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="rv-header-content">
                <h1>Đánh giá đơn hàng</h1>
                <span class="rv-order-code"><?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="rv-alert rv-alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="rv-alert rv-alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($items)): ?>
        <div class="rv-order-items">
            <div class="rv-section-title">
                <span>Món đã đặt</span>
                <span class="rv-item-count"><?php echo count($items); ?> món</span>
            </div>
            <div class="rv-items-grid">
                <?php foreach ($items as $item): 
                    $img_src = '';
                    if (!empty($item['image'])) {
                        $img_src = strpos($item['image'], 'uploads/') === 0 ? $item['image'] : 'uploads/menu/' . $item['image'];
                    }
                ?>
                <div class="rv-item-card">
                    <div class="rv-item-img">
                        <?php if (!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                        <i class="fas fa-utensils"></i>
                        <?php endif; ?>
                    </div>
                    <div class="rv-item-info">
                        <span class="rv-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="rv-item-qty">SL: <?php echo $item['quantity']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="rv-review-card">
            <?php if ($existing_review): ?>
            <div class="rv-completed">
                <div class="rv-completed-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2>Đã đánh giá</h2>
                <p>Cảm ơn bạn đã chia sẻ trải nghiệm</p>
                
                <div class="rv-completed-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star <?php echo $i <= $existing_review['rating'] ? 'filled' : ''; ?>"></i>
                    <?php endfor; ?>
                    <span><?php echo $existing_review['rating']; ?>/5</span>
                </div>
                
                <?php if ($existing_review['comment']): ?>
                <div class="rv-completed-comment">
                    "<?php echo htmlspecialchars($existing_review['comment']); ?>"
                </div>
                <?php endif; ?>
                
                <div class="rv-completed-date">
                    <?php echo date('d/m/Y, H:i', strtotime($existing_review['created_at'])); ?>
                </div>
            </div>
            <?php else: ?>
            <form method="POST" class="rv-form">
                <div class="rv-form-title">
                    <h2>Trải nghiệm của bạn thế nào?</h2>
                    <p>Đánh giá giúp chúng tôi phục vụ tốt hơn</p>
                </div>

                <div class="rv-rating-section">
                    <div class="rv-stars-container">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                        <label for="star<?php echo $i; ?>" class="rv-star" data-value="<?php echo $i; ?>">
                            <i class="fas fa-star"></i>
                        </label>
                        <?php endfor; ?>
                    </div>
                    <div class="rv-rating-label" id="ratingLabel">Chọn đánh giá</div>
                </div>

                <div class="rv-comment-section">
                    <label>Nhận xét của bạn</label>
                    <textarea name="comment" placeholder="Chia sẻ trải nghiệm của bạn về món ăn, dịch vụ..." rows="4"></textarea>
                </div>

                <div class="rv-quick-tags">
                    <span class="rv-quick-tag" data-text="Món ăn ngon">Ngon</span>
                    <span class="rv-quick-tag" data-text="Giao hàng nhanh">Nhanh</span>
                    <span class="rv-quick-tag" data-text="Đóng gói đẹp">Đóng gói tốt</span>
                    <span class="rv-quick-tag" data-text="Giá hợp lý">Giá tốt</span>
                    <span class="rv-quick-tag" data-text="Sẽ quay lại">Sẽ quay lại</span>
                </div>

                <button type="submit" class="rv-submit-btn">Gửi đánh giá</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.rv-page { min-height: 100vh; background: #e9ecef; padding: 0; }
.rv-wrapper { max-width: 520px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }

.rv-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #dee2e6; }
.rv-back-btn { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid #dee2e6; border-radius: 10px; color: #495057; text-decoration: none; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.06); }
.rv-back-btn:hover { background: #f8f9fa; border-color: #adb5bd; }
.rv-header-content h1 { margin: 0; font-size: 1.25rem; font-weight: 600; color: #212529; }
.rv-order-code { font-size: 0.8rem; color: #6c757d; font-family: 'SF Mono', Monaco, monospace; }

.rv-alert { display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; }
.rv-alert-error { background: #fff5f5; color: #c92a2a; border: 1px solid #ffc9c9; }
.rv-alert-success { background: #ebfbee; color: #2b8a3e; border: 1px solid #b2f2bb; }

.rv-order-items { background: #fff; border-radius: 12px; padding: 1rem; margin-bottom: 1rem; border: 2px solid #22c55e; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.rv-section-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; font-size: 0.875rem; font-weight: 600; color: #495057; }
.rv-item-count { font-weight: 400; color: #868e96; }
.rv-items-grid { display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.25rem; }
.rv-item-card { flex-shrink: 0; display: flex; align-items: center; gap: 0.625rem; padding: 0.5rem; background: #f1f3f5; border-radius: 8px; min-width: 140px; border: 1px solid #e9ecef; }
.rv-item-img { width: 40px; height: 40px; border-radius: 8px; overflow: hidden; background: #e9ecef; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.rv-item-img img { width: 100%; height: 100%; object-fit: cover; }
.rv-item-img i { color: #adb5bd; font-size: 1rem; }
.rv-item-info { display: flex; flex-direction: column; gap: 0.125rem; min-width: 0; }
.rv-item-name { font-size: 0.8rem; font-weight: 500; color: #212529; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rv-item-qty { font-size: 0.7rem; color: #868e96; }

.rv-review-card { background: #fff; border-radius: 16px; padding: 1.5rem; border: 2px solid #22c55e; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

.rv-completed { text-align: center; padding: 1rem 0; }
.rv-completed-icon { width: 56px; height: 56px; background: #ebfbee; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.rv-completed-icon i { font-size: 1.5rem; color: #2b8a3e; }
.rv-completed h2 { margin: 0 0 0.25rem; font-size: 1.125rem; font-weight: 600; color: #212529; }
.rv-completed > p { margin: 0 0 1.25rem; font-size: 0.875rem; color: #868e96; }
.rv-completed-rating { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-bottom: 1rem; }
.rv-completed-rating i { font-size: 1.25rem; color: #dee2e6; }
.rv-completed-rating i.filled { color: #fab005; }
.rv-completed-rating span { margin-left: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #495057; }
.rv-completed-comment { background: #f8f9fa; padding: 1rem; border-radius: 10px; font-size: 0.875rem; color: #495057; font-style: italic; margin-bottom: 1rem; line-height: 1.5; }
.rv-completed-date { font-size: 0.75rem; color: #adb5bd; }

.rv-form-title { text-align: center; margin-bottom: 1.5rem; }
.rv-form-title h2 { margin: 0 0 0.25rem; font-size: 1.125rem; font-weight: 600; color: #212529; }
.rv-form-title p { margin: 0; font-size: 0.875rem; color: #868e96; }

.rv-rating-section { text-align: center; margin-bottom: 1.5rem; padding: 1.25rem; background: #f1f3f5; border-radius: 12px; border: 1px solid #dee2e6; }
.rv-stars-container { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 0.75rem; }
.rv-stars-container input { display: none; }
.rv-star { cursor: pointer; font-size: 2rem; transition: all 0.15s ease; display: inline-block; }
.rv-star i { color: #86efac !important; transition: color 0.15s ease; -webkit-text-fill-color: #86efac !important; }
.rv-star:hover i { color: #22c55e !important; -webkit-text-fill-color: #22c55e !important; }
.rv-star.active i { color: #22c55e !important; -webkit-text-fill-color: #22c55e !important; }
.rv-star:hover { transform: scale(1.1); }
.rv-rating-label { font-size: 0.875rem; color: #868e96; font-weight: 500; transition: all 0.2s; }

.rv-comment-section { margin-bottom: 1rem; }
.rv-comment-section label { display: block; font-size: 0.875rem; font-weight: 500; color: #495057; margin-bottom: 0.5rem; }
.rv-comment-section textarea { width: 100%; padding: 0.875rem 1rem; border: 1px solid #dee2e6; border-radius: 10px; font-size: 0.875rem; font-family: inherit; resize: none; transition: all 0.2s; background: #fff; color: #212529; }
.rv-comment-section textarea:focus { outline: none; border-color: #228be6; box-shadow: 0 0 0 3px rgba(34, 139, 230, 0.1); }
.rv-comment-section textarea::placeholder { color: #adb5bd; }

.rv-quick-tags { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem; }
.rv-quick-tag { padding: 0.5rem 0.875rem; background: #f1f3f4; border: 1px solid #e9ecef; border-radius: 20px; font-size: 0.8rem; color: #495057; cursor: pointer; transition: all 0.2s; user-select: none; }
.rv-quick-tag:hover { background: #e9ecef; border-color: #dee2e6; }
.rv-quick-tag.selected { background: #228be6; border-color: #228be6; color: #fff; }

.rv-submit-btn { width: 100%; padding: 0.875rem 1.5rem; background: #22c55e; border: none; border-radius: 10px; color: #fff; font-size: 0.9375rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.rv-submit-btn:hover { background: #16a34a; }
.rv-submit-btn:active { transform: scale(0.98); }

@media (max-width: 480px) {
    .rv-wrapper { padding: 1rem 0.875rem 2rem; }
    .rv-star { font-size: 1.75rem; }
    .rv-review-card { padding: 1.25rem; }
}

body.dark-theme .rv-page, .dark-theme .rv-page { background: #e9ecef !important; }
body.dark-theme .rv-review-card, .dark-theme .rv-review-card { background: #fff !important; border-color: #22c55e !important; }
body.dark-theme .rv-order-items, .dark-theme .rv-order-items { background: #fff !important; border-color: #dee2e6 !important; }
body.dark-theme .rv-header-content h1, .dark-theme .rv-header-content h1, body.dark-theme .rv-form-title h2, .dark-theme .rv-form-title h2 { color: #212529 !important; }
body.dark-theme .rv-comment-section textarea, .dark-theme .rv-comment-section textarea { background: #fff !important; color: #212529 !important; border-color: #dee2e6 !important; }
body.dark-theme .rv-quick-tag, .dark-theme .rv-quick-tag { background: #f1f3f4 !important; color: #495057 !important; border-color: #dee2e6 !important; }
body.dark-theme .rv-quick-tag.selected, .dark-theme .rv-quick-tag.selected { background: #228be6 !important; color: #fff !important; border-color: #228be6 !important; }
body.dark-theme .rv-rating-section, .dark-theme .rv-rating-section { background: #f1f3f5 !important; border-color: #dee2e6 !important; }
body.dark-theme .rv-item-card, .dark-theme .rv-item-card { background: #f1f3f5 !important; border-color: #e9ecef !important; }
</style>

<script>
const ratingLabels = { 1: 'Rất không hài lòng', 2: 'Không hài lòng', 3: 'Bình thường', 4: 'Hài lòng', 5: 'Rất hài lòng' };
const stars = document.querySelectorAll('.rv-star');
const ratingLabel = document.getElementById('ratingLabel');

stars.forEach(star => {
    star.addEventListener('click', function() {
        const val = parseInt(this.dataset.value);
        stars.forEach(s => {
            const sVal = parseInt(s.dataset.value);
            s.classList.toggle('active', sVal <= val);
        });
        if (ratingLabel) { ratingLabel.textContent = ratingLabels[val]; ratingLabel.style.color = '#212529'; }
        document.getElementById('star' + val).checked = true;
    });
    
    star.addEventListener('mouseenter', function() {
        const val = parseInt(this.dataset.value);
        stars.forEach(s => { if (parseInt(s.dataset.value) <= val) s.querySelector('i').style.color = '#22c55e'; });
    });
    
    star.addEventListener('mouseleave', function() {
        stars.forEach(s => { 
            if (!s.classList.contains('active')) {
                s.querySelector('i').style.color = '#86efac';
            } else {
                s.querySelector('i').style.color = '#22c55e';
            }
        });
    });
});

document.querySelectorAll('.rv-quick-tag').forEach(tag => {
    tag.addEventListener('click', function() {
        this.classList.toggle('selected');
        const textarea = document.querySelector('.rv-comment-section textarea');
        const text = this.dataset.text;
        if (this.classList.contains('selected')) {
            textarea.value = textarea.value ? textarea.value + '. ' + text : text;
        } else {
            textarea.value = textarea.value.replace(text + '. ', '').replace('. ' + text, '').replace(text, '');
        }
    });
});
</script>
