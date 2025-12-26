<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Review API</title></head><body style='background:#1e293b;color:#fff;padding:20px;font-family:Arial;'>";

echo "<h2>🧪 Test Review API</h2>";

// Kiểm tra session
echo "<h3>1. Session Info:</h3>";
echo "<pre style='background:#0f172a;padding:15px;border-radius:8px;'>";
echo "customer_id: " . ($_SESSION['customer_id'] ?? 'CHƯA ĐĂNG NHẬP') . "\n";
if (isset($_SESSION['customer_id'])) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Tên: " . ($customer['full_name'] ?? 'N/A') . "\n";
    echo "Email: " . ($customer['email'] ?? 'N/A') . "\n";
}
echo "</pre>";

// Form test
$menu_item_id = $_GET['item'] ?? 26;
echo "<h3>2. Test Form Đánh Giá (Món #$menu_item_id):</h3>";

// Kiểm tra đã đánh giá chưa
if (isset($_SESSION['customer_id'])) {
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE customer_id = ? AND menu_item_id = ?");
    $stmt->execute([$_SESSION['customer_id'], $menu_item_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "<p style='color:#fbbf24;'>⚠️ Bạn đã đánh giá món này rồi (ID: {$existing['id']}, Rating: {$existing['rating']})</p>";
        echo "<p>Nếu gửi lại sẽ CẬP NHẬT đánh giá cũ.</p>";
    } else {
        echo "<p style='color:#10b981;'>✓ Bạn chưa đánh giá món này. Có thể thêm mới.</p>";
    }
}

echo "<form id='testForm' style='background:#0f172a;padding:20px;border-radius:8px;max-width:400px;'>";
echo "<input type='hidden' name='menu_item_id' value='$menu_item_id'>";
echo "<p><label>Rating:</label><br>";
echo "<select name='rating' style='padding:10px;width:100%;background:#1e293b;color:#fff;border:1px solid #334155;border-radius:5px;'>";
for ($i = 1; $i <= 5; $i++) echo "<option value='$i'>$i sao</option>";
echo "</select></p>";
echo "<p><label>Comment:</label><br>";
echo "<textarea name='comment' style='padding:10px;width:100%;height:80px;background:#1e293b;color:#fff;border:1px solid #334155;border-radius:5px;'>Test đánh giá từ " . ($_SESSION['customer_id'] ?? 'guest') . "</textarea></p>";
echo "<button type='submit' style='background:#d4a574;color:#000;padding:12px 24px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;'>Gửi đánh giá</button>";
echo "</form>";

echo "<div id='result' style='margin-top:20px;'></div>";

// Hiển thị tất cả đánh giá cho món này
echo "<h3>3. Tất cả đánh giá cho món #$menu_item_id:</h3>";
$stmt = $conn->prepare("SELECT r.*, c.full_name FROM reviews r JOIN customers c ON r.customer_id = c.id WHERE r.menu_item_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$menu_item_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre style='background:#0f172a;padding:15px;border-radius:8px;'>";
echo "Tổng: " . count($reviews) . " đánh giá\n\n";
foreach ($reviews as $r) {
    echo "ID: {$r['id']} | User: {$r['full_name']} (ID:{$r['customer_id']}) | Rating: {$r['rating']} | {$r['comment']}\n";
}
echo "</pre>";

?>

<script>
document.getElementById('testForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const resultDiv = document.getElementById('result');
    
    resultDiv.innerHTML = '<p style="color:#fbbf24;">Đang gửi...</p>';
    
    try {
        const res = await fetch('api/submit-review.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await res.text();
        console.log('Raw response:', text);
        
        try {
            const data = JSON.parse(text);
            if (data.success) {
                resultDiv.innerHTML = '<p style="color:#10b981;">✓ ' + data.message + '</p>';
                setTimeout(() => location.reload(), 1500);
            } else {
                resultDiv.innerHTML = '<p style="color:#ef4444;">✗ ' + data.message + '</p>';
            }
        } catch (e) {
            resultDiv.innerHTML = '<p style="color:#ef4444;">Response không phải JSON:</p><pre>' + text + '</pre>';
        }
    } catch (err) {
        resultDiv.innerHTML = '<p style="color:#ef4444;">Lỗi: ' + err.message + '</p>';
    }
});
</script>

</body></html>
