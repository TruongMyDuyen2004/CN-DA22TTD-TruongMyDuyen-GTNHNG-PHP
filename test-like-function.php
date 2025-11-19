<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    die("Vui lòng đăng nhập trước: <a href='auth/login.php'>Đăng nhập</a>");
}

$db = new Database();
$conn = $db->connect();

$customer_id = $_SESSION['customer_id'];

// Lấy thông tin customer
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy một số reviews để test
$stmt = $conn->query("SELECT * FROM reviews LIMIT 5");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Like Function</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .user-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .review-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-like {
            display: inline-flex;
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
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #fca5a5;
            color: #dc2626;
        }
        .btn-like.liked i {
            color: #dc2626;
        }
        .btn-like i {
            font-size: 1.1rem;
        }
        .log {
            background: #1e293b;
            color: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #3b82f6;
            padding-left: 10px;
        }
        .log-success { border-left-color: #10b981; }
        .log-error { border-left-color: #ef4444; }
        h1 { color: #1e293b; }
        h2 { color: #475569; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>🧪 Test Like Function</h1>
    
    <div class="user-info">
        <h3>👤 Thông tin người dùng đã đăng nhập:</h3>
        <p><strong>ID:</strong> <?php echo $customer['id']; ?></p>
        <p><strong>Tên:</strong> <?php echo htmlspecialchars($customer['full_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
    </div>

    <h2>📝 Danh sách đánh giá để test:</h2>
    
    <?php foreach($reviews as $review): 
        // Kiểm tra đã like chưa
        $stmt = $conn->prepare("SELECT id FROM review_likes WHERE review_id = ? AND customer_id = ?");
        $stmt->execute([$review['id'], $customer_id]);
        $is_liked = $stmt->rowCount() > 0;
        
        // Đếm likes
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM review_likes WHERE review_id = ?");
        $stmt->execute([$review['id']]);
        $likes_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    ?>
    <div class="review-card">
        <p><strong>Review ID:</strong> <?php echo $review['id']; ?></p>
        <p><strong>Rating:</strong> <?php echo str_repeat('⭐', $review['rating']); ?></p>
        <p><strong>Comment:</strong> <?php echo htmlspecialchars($review['comment']); ?></p>
        <p><strong>Status:</strong> <?php echo $is_liked ? '❤️ Đã like' : '🤍 Chưa like'; ?></p>
        
        <button class="btn-like <?php echo $is_liked ? 'liked' : ''; ?>" 
                data-review-id="<?php echo $review['id']; ?>"
                onclick="testLike(this, <?php echo $review['id']; ?>)">
            <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i>
            <span class="like-count"><?php echo $likes_count; ?></span>
        </button>
    </div>
    <?php endforeach; ?>

    <h2>📊 Console Log:</h2>
    <div class="log" id="logContainer">
        <div class="log-entry">Sẵn sàng để test...</div>
    </div>

    <script>
        function addLog(message, type = 'info') {
            const logContainer = document.getElementById('logContainer');
            const entry = document.createElement('div');
            entry.className = 'log-entry log-' + type;
            entry.textContent = new Date().toLocaleTimeString() + ' - ' + message;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        async function testLike(button, reviewId) {
            addLog('🔄 Bắt đầu like/unlike review ID: ' + reviewId);
            
            // Disable button
            button.disabled = true;
            addLog('⏸️ Đã disable button');
            
            try {
                const formData = new FormData();
                formData.append('review_id', reviewId);
                
                addLog('📤 Gửi request đến api/review-like.php');
                
                const response = await fetch('api/review-like.php', {
                    method: 'POST',
                    body: formData
                });
                
                addLog('📥 Nhận response, status: ' + response.status);
                
                const data = await response.json();
                addLog('📦 Data nhận được: ' + JSON.stringify(data));
                
                if (data.success) {
                    addLog('✅ Thành công! Action: ' + data.action, 'success');
                    
                    // Update UI
                    const likeCount = button.querySelector('.like-count');
                    const icon = button.querySelector('i');
                    
                    likeCount.textContent = data.likes_count;
                    addLog('🔢 Cập nhật số likes: ' + data.likes_count);
                    
                    if (data.action === 'liked') {
                        button.classList.add('liked');
                        icon.className = 'fas fa-heart';
                        addLog('❤️ Đã thêm class "liked"', 'success');
                    } else {
                        button.classList.remove('liked');
                        icon.className = 'far fa-heart';
                        addLog('🤍 Đã xóa class "liked"', 'success');
                    }
                } else {
                    addLog('❌ Lỗi: ' + data.message, 'error');
                    alert(data.message);
                }
            } catch (error) {
                addLog('💥 Exception: ' + error.message, 'error');
                console.error('Error:', error);
                alert('Có lỗi xảy ra: ' + error.message);
            } finally {
                button.disabled = false;
                addLog('▶️ Đã enable button lại');
            }
        }

        // Log khi trang load xong
        window.addEventListener('load', function() {
            addLog('✅ Trang đã load xong', 'success');
            addLog('👤 Customer ID: <?php echo $customer_id; ?>');
            addLog('📝 Số reviews: <?php echo count($reviews); ?>');
        });
    </script>
</body>
</html>
