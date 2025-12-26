<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Notification Simple</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background:#1e293b; color:#fff; padding:20px; font-family:Arial; }
        .box { background:#0f172a; padding:20px; border-radius:10px; margin:20px 0; }
        button { background:#d4a574; color:#000; padding:12px 24px; border:none; border-radius:8px; cursor:pointer; font-weight:bold; margin:5px; }
        button:hover { background:#c89456; }
        .notification-toast {
            position: fixed; top: 100px; right: 20px;
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 12px; padding: 1rem 1.25rem;
            display: flex; align-items: center; gap: 1rem;
            box-shadow: 0 10px 40px rgba(34, 197, 94, 0.3);
            z-index: 10001; transform: translateX(120%);
            transition: transform 0.3s ease; max-width: 350px;
        }
        .notification-toast.show { transform: translateX(0); }
        .toast-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
        }
        .toast-icon i { color: #1a1a1a; }
        .toast-content strong { color: #d4a574; display: block; margin-bottom: 5px; }
        .toast-content p { color: rgba(255,255,255,0.8); margin: 0; font-size: 0.9rem; }
        .toast-close { background: rgba(255,255,255,0.1); border: none; width: 28px; height: 28px; border-radius: 50%; color: #fff; cursor: pointer; }
    </style>
</head>
<body>

<h2>🔔 Test Thông Báo - Đơn Giản</h2>

<div class="box">
    <h3>Session:</h3>
    <p>customer_id: <?php echo $_SESSION['customer_id'] ?? 'CHƯA ĐĂNG NHẬP'; ?></p>
    <p>customer_email: <?php echo $_SESSION['customer_email'] ?? 'KHÔNG CÓ'; ?></p>
</div>

<div class="box">
    <h3>Test API:</h3>
    <div id="apiResult">Đang load...</div>
</div>

<div class="box">
    <h3>Actions:</h3>
    <button onclick="testToast()">🔔 Test Toast</button>
    <button onclick="testSound()">🔊 Test Âm Thanh</button>
    <button onclick="checkNow()">📬 Check Messages</button>
    <button onclick="clearStorage()">🗑️ Clear LocalStorage</button>
    <button onclick="startPolling()">▶️ Start Polling (5s)</button>
</div>

<div class="box">
    <h3>Log:</h3>
    <div id="log" style="max-height:200px;overflow:auto;font-family:monospace;font-size:12px;"></div>
</div>

<script>
let lastNotifiedId = localStorage.getItem('lastNotifiedMessageId') || 0;
let pollingInterval = null;

function log(msg) {
    const logDiv = document.getElementById('log');
    const time = new Date().toLocaleTimeString();
    logDiv.innerHTML = `[${time}] ${msg}<br>` + logDiv.innerHTML;
}

function testSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 800;
        osc.type = 'sine';
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.3);
        log('✓ Âm thanh đã phát');
    } catch(e) {
        log('✗ Lỗi âm thanh: ' + e.message);
    }
}

function testToast() {
    showToast('Đây là tin nhắn test từ admin!');
    log('✓ Toast đã hiện');
}

function showToast(message) {
    const old = document.querySelector('.notification-toast');
    if (old) old.remove();
    
    const toast = document.createElement('div');
    toast.className = 'notification-toast';
    toast.innerHTML = `
        <div class="toast-icon"><i class="fas fa-bell"></i></div>
        <div class="toast-content">
            <strong>Tin nhắn mới!</strong>
            <p>${message}</p>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 5000);
}

function checkNow() {
    log('Đang check messages...');
    fetch('api/check-new-messages.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('apiResult').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            log('API trả về: count=' + data.count + ', latest_id=' + (data.latest?.id || 'null'));
            
            if (data.success && data.count > 0 && data.latest) {
                const latestId = parseInt(data.latest.id);
                const savedId = parseInt(lastNotifiedId) || 0;
                
                log('So sánh: latestId=' + latestId + ' vs savedId=' + savedId);
                
                if (latestId > savedId) {
                    log('🔔 CÓ TIN NHẮN MỚI! Hiện thông báo...');
                    testSound();
                    showToast(data.latest.admin_reply);
                    lastNotifiedId = latestId;
                    localStorage.setItem('lastNotifiedMessageId', latestId);
                } else {
                    log('Không có tin nhắn mới (đã thông báo rồi)');
                }
            } else {
                log('Không có tin nhắn chưa đọc');
            }
        })
        .catch(err => {
            log('✗ Lỗi: ' + err.message);
        });
}

function clearStorage() {
    localStorage.removeItem('lastNotifiedMessageId');
    lastNotifiedId = 0;
    log('✓ Đã xóa localStorage. lastNotifiedId = 0');
}

function startPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
        log('⏹️ Đã dừng polling');
    } else {
        pollingInterval = setInterval(checkNow, 5000);
        log('▶️ Bắt đầu polling mỗi 5 giây');
        checkNow();
    }
}

// Load API khi mở trang
fetch('api/check-new-messages.php')
    .then(res => res.json())
    .then(data => {
        document.getElementById('apiResult').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        log('Loaded. lastNotifiedId từ localStorage: ' + lastNotifiedId);
    })
    .catch(err => {
        document.getElementById('apiResult').innerHTML = 'Lỗi: ' + err.message;
    });
</script>

</body>
</html>
