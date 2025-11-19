<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

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
    SELECT oi.*, m.name 
    FROM order_items oi 
    JOIN menu_items m ON oi.menu_item_id = m.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

// Xử lý đánh giá
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $menu_item_id = $_POST['menu_item_id'] ?? null;
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Vui lòng chọn số sao từ 1-5';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO reviews (customer_id, order_id, menu_item_id, rating, comment) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$_SESSION['customer_id'], $order_id, $menu_item_id, $rating, $comment])) {
            $success = 'Cảm ơn bạn đã đánh giá!';
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}
?>

<section class="review-section">
    <div class="container">
        <h2>Đánh giá đơn hàng</h2>
        <p class="order-info">Đơn hàng #<?php echo htmlspecialchars($order['order_number']); ?></p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <a href="?page=orders" class="btn btn-primary">Về danh sách đơn hàng</a>
        <?php else: ?>
        
        <div class="review-form">
            <h3>Đánh giá tổng thể</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Đánh giá *</label>
                    <div class="star-rating">
                        <input type="radio" name="rating" value="5" id="star5" required>
                        <label for="star5">★</label>
                        <input type="radio" name="rating" value="4" id="star4">
                        <label for="star4">★</label>
                        <input type="radio" name="rating" value="3" id="star3">
                        <label for="star3">★</label>
                        <input type="radio" name="rating" value="2" id="star2">
                        <label for="star2">★</label>
                        <input type="radio" name="rating" value="1" id="star1">
                        <label for="star1">★</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Nhận xét</label>
                    <textarea name="comment" rows="4" placeholder="Chia sẻ trải nghiệm của bạn..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
            </form>
        </div>
        
        <div class="review-items">
            <h3>Đánh giá từng món</h3>
            <?php foreach ($items as $item): ?>
            <div class="review-item-card">
                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                <form method="POST">
                    <input type="hidden" name="menu_item_id" value="<?php echo $item['menu_item_id']; ?>">
                    
                    <div class="form-group">
                        <div class="star-rating">
                            <input type="radio" name="rating" value="5" id="item<?php echo $item['id']; ?>star5" required>
                            <label for="item<?php echo $item['id']; ?>star5">★</label>
                            <input type="radio" name="rating" value="4" id="item<?php echo $item['id']; ?>star4">
                            <label for="item<?php echo $item['id']; ?>star4">★</label>
                            <input type="radio" name="rating" value="3" id="item<?php echo $item['id']; ?>star3">
                            <label for="item<?php echo $item['id']; ?>star3">★</label>
                            <input type="radio" name="rating" value="2" id="item<?php echo $item['id']; ?>star2">
                            <label for="item<?php echo $item['id']; ?>star2">★</label>
                            <input type="radio" name="rating" value="1" id="item<?php echo $item['id']; ?>star1">
                            <label for="item<?php echo $item['id']; ?>star1">★</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="comment" rows="2" placeholder="Nhận xét về món này..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary btn-small">Đánh giá món này</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</section>
