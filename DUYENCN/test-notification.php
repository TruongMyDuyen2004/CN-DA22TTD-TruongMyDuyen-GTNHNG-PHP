<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Notification</title></head><body style='background:#1e293b;color:#fff;padding:20px;font-family:Arial;'>";

echo "<h2>🔔 Test Thông Báo Tin Nhắn</h2>";

// 1. Kiểm tra session
echo "<h3>1. Session Info:</h3>";
echo "<pre style='background:#0f172a;padding:15px;border-radius:8px;'>";
echo "customer_id: " . ($_SESSION['customer_id'] ?? 'CHƯA ĐĂNG NHẬP') . "\n";
echo "customer_email: " . ($_SESSION['customer_email'] ?? 'KHÔNG CÓ') . "\n";
echo "</pre>";

if (empty($_SESSION['customer_email'])) {
    echo "<p style='color:#ef4444;'>⚠️ Không có email trong session! Cần đăng nhập lại.</p>";
}

// 2. Test API check-new-messages
echo "<h3>2. Test API check-new-messages.php:</h3>";
echo "<div id='apiResult' style='background:#0f172a;padding:15px;border-radius:8px;'>Đang test...</div>";

// 3. Kiểm tra bảng contacts
echo "<h3>3. Dữ liệu trong bảng contacts:</h3>";
echo "<pre style='background:#0f172a;padding:15px;border-radius:8px;max-height:300px;overflow:auto;'>";

if (!empty($_SESSION['customer_email'])) {
    $stmt = $conn->prepare("SELECT id, email, message, status, admin_reply, user_read_at, replied_at FROM contacts WHERE email = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$_SESSION['customer_email']]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Tìm thấy " . count($contacts) . " tin nhắn cho email: " . $_SESSION['customer_email'] . "\n\n";
    
    foreach ($contacts as $c) {
        echo "ID: {$c['id']}\n";
        echo "  Status: {$c['status']}\n";
        echo "  Message: " . substr($c['message'], 0, 50) . "...\n";
        echo "  Admin Reply: " . ($c['admin_reply'] ? substr($c['admin_reply'], 0, 50) . "..." : "CHƯA CÓ") . "\n";
        echo "  User Read At: " . ($c['user_read_at'] ?: "CHƯA ĐỌC") . "\n";
        echo "  Replied At: " . ($c['replied_at'] ?: "N/A") . "\n";
        echo "---\n";
    }
    
    // Đếm tin chưa đọc
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM contacts WHERE email = ? AND status = 'replied' AND (user_read_at IS NULL OR user_read_at = '')");
    $stmt->execute([$_SESSION['customer_email']]);
    $unread = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "\n📬 Tin nhắn chưa đọc: $unread\n";
} else {
    echo "Không có email trong session\n";
}
echo "</pre>";

// 4. Test thông báo
echo "<h3>4. Test Thông Báo:</h3>";
echo "<button onclick='testNotification()' style='background:#d4a574;color:#000;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:bold;margin-right:10px;'>Test Toast</button>";
echo "<button onclick='testSound()' style='background:#3b82f6;color:#fff;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:bold;margin-right:10px;'>Test Âm Thanh</button>";
echo "<button onclick='checkMessages()' style='background:#10b981;color:#fff;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:bold;'>Check Messages</button>";

?>

<script>
// Test API ngay khi load
fetch('api/check-new-messages.php')
    .then(res => res.text())
    .then(text => {
        document.getElementById('apiResult').innerHTML = '<pre>' + text + '</pre>';
        console.log('API Response:', text);
    })
    .catch(err => {
        document.getElementById('apiResult').innerHTML = '<span style="color:#ef4444;">Lỗi: ' + err.message + '</span>';
    });

function testSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
        
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.3);
        
        alert('Âm thanh đã phát! Nếu không nghe được, kiểm tra loa.');
    } catch(e) {
        alert('Lỗi phát âm thanh: ' + e.message);
    }
}

function testNotification() {
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:100px;right:20px;background:linear-gradient(135deg,#15803d,#166534);border:1px solid rgba(34,197,94,0.3);border-radius:12px;padding:1rem;display:flex;align-items:center;gap:1rem;box-shadow:0 10px 40px rgba(34,197,94,0.3);z-index:10001;max-width:350px;';
    toast.innerHTML = `
        <div style="width:40px;height:40px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-bell" style="color:#fff;"></i>
        </div>
        <div>
            <strong style="color:#22c55e;display:block;">Tin nhắn mới!</strong>
            <p style="color:rgba(255,255,255,0.8);margin:0;font-size:0.85rem;">Đây là test thông báo</p>
        </div>
        <button onclick="this.parentElement.remove()" style="background:rgba(255,255,255,0.1);border:none;width:28px;height:28px;border-radius:50%;color:#fff;cursor:pointer;">×</button>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 5000);
}

function checkMessages() {
    fetch('api/check-new-messages.php')
        .then(res => res.json())
        .then(data => {
            alert('Kết quả:\n' + JSON.stringify(data, null, 2));
            if (data.count > 0) {
                testSound();
                testNotification();
            }
        })
        .catch(err => alert('Lỗi: ' + err.message));
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body></html>
