<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Thêm vào Giỏ Hàng</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 2rem;
            background: #f3f4f6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .status {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .status.success { background: #d1fae5; color: #065f46; }
        .status.error { background: #fee2e2; color: #991b1b; }
        .status.warning { background: #fef3c7; color: #92400e; }
        .btn {
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin: 0.5rem;
        }
        .btn:hover { background: #2563eb; }
        .console {
            background: #1f2937;
            color: #10b981;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 Test Thêm vào Giỏ Hàng</h1>
        
        <?php
        session_start();
        require_once 'config/database.php';
        
        $db = new Database();
        $conn = $db->connect();
        
        // Kiểm tra trạng thái đăng nhập
        echo "<div class='status " . (isset($_SESSION['customer_id']) ? "success" : "warning") . "'>";
        if (isset($_SESSION['customer_id'])) {
            echo "<strong>✅ Đã đăng nhập</strong><br>";
            echo "Customer ID: " . $_SESSION['customer_id'];
            
            // Lấy thông tin khách hàng
            $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$_SESSION['customer_id']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                echo "<br>Tên: " . htmlspecialchars($customer['name']);
                echo "<br>Email: " . htmlspecialchars($customer['email']);
            }
        } else {
            echo "<strong>⚠️ Chưa đăng nhập</strong><br>";
            echo "Bạn cần đăng nhập để test chức năng giỏ hàng<br>";
            echo "<a href='auth/login.php' style='color: #92400e; text-decoration: underline;'>Đăng nhập ngay</a>";
        }
        echo "</div>";
        
        // Lấy món ăn đầu tiên để test
        $stmt = $conn->query("SELECT * FROM menu_items WHERE is_available = 1 LIMIT 1");
        $test_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_item) {
            echo "<div class='status success'>";
            echo "<strong>🍽️ Món test:</strong><br>";
            echo "ID: " . $test_item['id'] . "<br>";
            echo "Tên: " . htmlspecialchars($test_item['name']) . "<br>";
            echo "Giá: " . number_format($test_item['price']) . "đ";
            echo "</div>";
        }
        ?>
        
        <div style="margin: 1.5rem 0;">
            <button onclick="testAddToCart()" class="btn" <?php echo !isset($_SESSION['customer_id']) ? 'disabled' : ''; ?>>
                ➕ Test Thêm vào Giỏ
            </button>
            <button onclick="testGetCart()" class="btn" <?php echo !isset($_SESSION['customer_id']) ? 'disabled' : ''; ?>>
                📊 Xem Giỏ Hàng
            </button>
            <button onclick="clearLog()" class="btn" style="background: #6b7280;">
                🗑️ Xóa Log
            </button>
        </div>
        
        <div class="console" id="console">
            <strong>Console Log:</strong><br>
            <div id="log">Chờ test...</div>
        </div>
    </div>

    <script>
        const testItemId = <?php echo $test_item['id'] ?? 0; ?>;
        
        function log(message) {
            const logDiv = document.getElementById('log');
            const time = new Date().toLocaleTimeString();
            logDiv.innerHTML += `<br>[${time}] ${message}`;
            console.log(message);
        }
        
        function clearLog() {
            document.getElementById('log').innerHTML = 'Log đã xóa...';
        }
        
        async function testAddToCart() {
            log('🔵 Bắt đầu test thêm vào giỏ...');
            log('Item ID: ' + testItemId);
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('menu_item_id', testItemId);
            formData.append('quantity', 1);
            
            try {
                log('🌐 Gửi request đến api/cart.php');
                
                const response = await fetch('api/cart.php', {
                    method: 'POST',
                    body: formData
                });
                
                log('📡 Response status: ' + response.status);
                log('📡 Response OK: ' + response.ok);
                
                const contentType = response.headers.get('content-type');
                log('📄 Content-Type: ' + contentType);
                
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    log('❌ Response không phải JSON!');
                    log('Response text: ' + text.substring(0, 300));
                    alert('❌ Lỗi: Server không trả về JSON\n\nXem Console log để biết chi tiết');
                    return;
                }
                
                const data = await response.json();
                log('✅ Nhận được JSON response');
                log('Response: ' + JSON.stringify(data, null, 2));
                
                if (data.success) {
                    log('✅ SUCCESS! ' + data.message);
                    log('Cart count: ' + data.cart_count);
                    alert('✅ ' + data.message);
                } else {
                    log('❌ ERROR: ' + data.message);
                    alert('❌ Lỗi: ' + data.message);
                }
                
            } catch (error) {
                log('❌ Catch error: ' + error.message);
                log('Error stack: ' + error.stack);
                alert('❌ Có lỗi xảy ra: ' + error.message);
            }
        }
        
        async function testGetCart() {
            log('🔵 Lấy thông tin giỏ hàng...');
            
            try {
                const response = await fetch('api/cart.php?action=get_items');
                const data = await response.json();
                
                log('Response: ' + JSON.stringify(data, null, 2));
                
                if (data.success) {
                    log('✅ Có ' + data.cart_count + ' món trong giỏ');
                    log('Tổng tiền: ' + data.subtotal + 'đ');
                    alert('Giỏ hàng có ' + data.cart_count + ' món\nTổng: ' + data.subtotal.toLocaleString() + 'đ');
                } else {
                    log('❌ ERROR: ' + data.message);
                }
                
            } catch (error) {
                log('❌ Error: ' + error.message);
            }
        }
        
        log('✅ Page loaded');
        log('Test item ID: ' + testItemId);
    </script>
</body>
</html>
