<?php
/**
 * Sync SePay - Tự động duyệt các yêu cầu nạp tiền khớp với giao dịch SePay
 * Admin chỉ cần bấm 1 nút để sync tất cả
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

define('SEPAY_API_KEY', 'Q6LCGEPYOCBCFR14URSGATDR2BM8GZHPUJA3D0YOX1GAZWDKWHFMCIQILOKH9VQX');

$results = [];
$error = '';

// Xử lý sync
if (isset($_POST['sync_now'])) {
    // Lấy giao dịch từ SePay
    $transactions = getSePayTransactions();
    
    if ($transactions === false) {
        $error = 'Không thể kết nối SePay API';
    } else {
        // Lấy các yêu cầu nạp tiền đang chờ
        $stmt = $conn->query("
            SELECT tr.*, mc.balance 
            FROM topup_requests tr
            JOIN member_cards mc ON tr.card_id = mc.id
            WHERE tr.status IN ('pending', 'waiting')
            ORDER BY tr.created_at DESC
        ");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($requests as $request) {
            $matched = findMatchingTransaction($transactions, $request);
            
            if ($matched) {
                // Tự động duyệt
                $result = approveTopup($conn, $request, $matched);
                $results[] = [
                    'request' => $request,
                    'transaction' => $matched,
                    'success' => $result['success'],
                    'message' => $result['message']
                ];
            }
        }
    }
}

// Hàm lấy giao dịch từ SePay
function getSePayTransactions() {
    // Thử nhiều endpoint khác nhau
    $endpoints = [
        'https://my.sepay.vn/userapi/transactions/list',
        'https://my.sepay.vn/userapi/transactions/list?account_number=444418062004',
        'https://my.sepay.vn/userapi/transactions/list?limit=100',
    ];
    
    foreach ($endpoints as $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . SEPAY_API_KEY,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            $transactions = $data['transactions'] ?? $data['data'] ?? [];
            if (!empty($transactions)) {
                return $transactions;
            }
        }
    }
    
    return [];
}

// Hàm tìm giao dịch khớp
function findMatchingTransaction($transactions, $request) {
    $requestTime = strtotime($request['created_at']);
    
    foreach ($transactions as $trans) {
        $content = strtoupper($trans['content'] ?? $trans['description'] ?? '');
        $amount = floatval($trans['transferAmount'] ?? $trans['amount'] ?? 0);
        $type = $trans['transferType'] ?? 'in';
        $transTime = strtotime($trans['transactionDate'] ?? '');
        
        // Chỉ xét giao dịch tiền vào
        if ($type !== 'in') continue;
        
        // Chỉ xét giao dịch trong 24h
        if (time() - $transTime > 86400) continue;
        
        // Khớp mã giao dịch và số tiền
        if (stripos($content, $request['transaction_code']) !== false && $amount == $request['amount']) {
            return $trans;
        }
        
        // Hoặc chỉ khớp số tiền (giao dịch sau khi tạo yêu cầu)
        if ($amount == $request['amount'] && $transTime >= ($requestTime - 300)) {
            return $trans;
        }
    }
    
    return null;
}

// Hàm duyệt yêu cầu nạp tiền
function approveTopup($conn, $request, $transaction) {
    try {
        $conn->beginTransaction();
        
        $amount = floatval($request['amount']);
        
        // Cập nhật số dư thẻ
        $stmt = $conn->prepare("
            UPDATE member_cards 
            SET balance = balance + ?, total_deposited = total_deposited + ?
            WHERE id = ?
        ");
        $stmt->execute([$amount, $amount, $request['card_id']]);
        
        // Ghi lịch sử giao dịch
        $stmt = $conn->prepare("
            INSERT INTO card_transactions (card_id, type, amount, description, created_at)
            VALUES (?, 'deposit', ?, ?, NOW())
        ");
        $stmt->execute([
            $request['card_id'],
            $amount,
            'Nạp tiền qua SePay - ' . $request['transaction_code']
        ]);
        
        // Cập nhật trạng thái
        $stmt = $conn->prepare("
            UPDATE topup_requests 
            SET status = 'completed', completed_at = NOW(), payment_info = ?
            WHERE id = ?
        ");
        $stmt->execute([
            json_encode([
                'sepay_id' => $transaction['id'] ?? '',
                'sepay_content' => $transaction['content'] ?? '',
                'sepay_amount' => $transaction['transferAmount'] ?? $transaction['amount'] ?? 0,
                'auto_synced' => true,
                'synced_at' => date('Y-m-d H:i:s')
            ]),
            $request['id']
        ]);
        
        $conn->commit();
        return ['success' => true, 'message' => 'Đã duyệt thành công'];
        
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Lấy thống kê
$pending_count = $conn->query("SELECT COUNT(*) FROM topup_requests WHERE status IN ('pending', 'waiting')")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync SePay - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body { background: #f8fafc !important; }
    .main-content { background: #f8fafc !important; padding: 2rem; }
    .page-header { margin-bottom: 2rem; }
    .page-header h1 { color: #1f2937; font-size: 1.75rem; display: flex; align-items: center; gap: 0.75rem; }
    .page-header h1 i { color: #3b82f6; }
    
    .sync-card {
        background: white; border-radius: 16px; padding: 2rem;
        border: 1px solid #e5e7eb; margin-bottom: 1.5rem;
    }
    .sync-card h3 { margin: 0 0 1rem; color: #1f2937; }
    
    .sync-btn {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white; border: none; padding: 1rem 2rem;
        border-radius: 12px; font-size: 1.1rem; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: 0.75rem;
        transition: all 0.2s;
    }
    .sync-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.4); }
    .sync-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
    
    .pending-badge {
        background: #fef3c7; color: #92400e; padding: 0.5rem 1rem;
        border-radius: 20px; font-weight: 600; display: inline-block; margin-bottom: 1rem;
    }
    
    .results-list { margin-top: 1.5rem; }
    .result-item {
        background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px;
        padding: 1rem; margin-bottom: 0.75rem;
    }
    .result-item.failed { background: #fef2f2; border-color: #fecaca; }
    .result-item strong { color: #15803d; }
    .result-item.failed strong { color: #dc2626; }
    
    .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }
    .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .alert-info { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
    
    .info-box {
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
        padding: 1rem; margin-top: 1rem;
    }
    .info-box h4 { margin: 0 0 0.5rem; color: #1d4ed8; }
    .info-box p { margin: 0; color: #1e40af; font-size: 0.9rem; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-sync-alt"></i> Sync SePay</h1>
            <p style="color:#6b7280;">Tự động duyệt các yêu cầu nạp tiền khớp với giao dịch SePay</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="sync-card">
            <h3><i class="fas fa-clock" style="color:#f59e0b;"></i> Yêu cầu đang chờ</h3>
            
            <?php if ($pending_count > 0): ?>
            <div class="pending-badge">
                <i class="fas fa-hourglass-half"></i> <?php echo $pending_count; ?> yêu cầu đang chờ duyệt
            </div>
            <?php else: ?>
            <p style="color:#22c55e;"><i class="fas fa-check-circle"></i> Không có yêu cầu nào đang chờ</p>
            <?php endif; ?>
            
            <form method="POST">
                <button type="submit" name="sync_now" class="sync-btn" <?php echo $pending_count == 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-sync-alt"></i> Sync với SePay ngay
                </button>
            </form>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Cách hoạt động</h4>
                <p>Khi bấm Sync, hệ thống sẽ lấy danh sách giao dịch từ SePay và tự động duyệt các yêu cầu nạp tiền có số tiền và mã giao dịch khớp.</p>
            </div>
        </div>
        
        <?php if (!empty($results)): ?>
        <div class="sync-card">
            <h3><i class="fas fa-list-check" style="color:#22c55e;"></i> Kết quả Sync</h3>
            <div class="results-list">
                <?php foreach ($results as $r): ?>
                <div class="result-item <?php echo $r['success'] ? '' : 'failed'; ?>">
                    <strong><?php echo $r['success'] ? '✓' : '✗'; ?></strong>
                    Mã: <code><?php echo $r['request']['transaction_code']; ?></code> - 
                    <?php echo number_format($r['request']['amount']); ?>đ - 
                    <?php echo $r['message']; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="sync-card">
            <h3><i class="fas fa-cog" style="color:#6b7280;"></i> Cấu hình Webhook (Tự động hoàn toàn)</h3>
            <p>Để hệ thống tự động duyệt ngay khi SePay nhận tiền, bạn cần cấu hình Webhook:</p>
            <ol style="color:#374151; line-height:1.8;">
                <li>Đăng nhập vào <a href="https://my.sepay.vn" target="_blank" style="color:#3b82f6;">my.sepay.vn</a></li>
                <li>Vào <strong>Tích hợp & Thông báo</strong> → <strong>Webhook</strong></li>
                <li>Thêm URL: <code style="background:#f3f4f6;padding:2px 8px;border-radius:4px;">https://your-domain.com/DUYENCN/api/sepay-webhook.php</code></li>
                <li>Chọn sự kiện: <strong>Giao dịch mới</strong></li>
                <li>Lưu cấu hình</li>
            </ol>
            <p style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> <strong>Lưu ý:</strong> Webhook chỉ hoạt động khi website được deploy lên hosting thật (không phải localhost)</p>
        </div>
    </div>
</body>
</html>
