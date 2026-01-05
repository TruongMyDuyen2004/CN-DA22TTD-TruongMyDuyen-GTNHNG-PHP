<?php
/**
 * SePay Webhook - Tự động xác nhận nạp tiền khi nhận được giao dịch
 * 
 * Cấu hình trong SePay:
 * 1. Vào SePay → Tích hợp & Thông báo → Webhook
 * 2. Thêm URL: https://yourdomain.com/DUYENCN/api/sepay-webhook.php
 * 3. Chọn sự kiện: Giao dịch mới
 */

// Log webhook để debug
$logFile = '../logs/sepay-webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Lấy dữ liệu từ webhook
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Log request
$logEntry = date('Y-m-d H:i:s') . " - Webhook received\n";
$logEntry .= "Raw: " . $rawInput . "\n";
$logEntry .= "---\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Trả về 200 OK ngay để SePay không retry
header('Content-Type: application/json');
http_response_code(200);

if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'No data']);
    exit;
}

// Kết nối database
require_once '../config/database.php';
$db = new Database();
$conn = $db->connect();

// Lấy thông tin giao dịch từ webhook
// SePay webhook format có thể khác nhau, thử nhiều field names
$transferType = $data['transferType'] ?? $data['type'] ?? $data['gateway'] ?? '';
$amount = floatval($data['transferAmount'] ?? $data['amount'] ?? $data['money'] ?? 0);
$content = $data['content'] ?? $data['description'] ?? $data['additionData'] ?? '';
$transactionId = $data['id'] ?? $data['transactionId'] ?? $data['referenceCode'] ?? '';
$transactionDate = $data['transactionDate'] ?? $data['when'] ?? date('Y-m-d H:i:s');

// Log parsed data
$logEntry = date('Y-m-d H:i:s') . " - Parsed data\n";
$logEntry .= "Type: $transferType, Amount: $amount, Content: $content, ID: $transactionId\n";
$logEntry .= "---\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Chỉ xử lý giao dịch tiền VÀO
if ($transferType !== 'in' && $transferType !== 'IN') {
    echo json_encode(['success' => true, 'message' => 'Ignored - not incoming transfer']);
    exit;
}

// Tìm yêu cầu nạp tiền khớp
$found = false;
$matchedRequest = null;

// Ưu tiên 1: Tìm theo mã giao dịch trong nội dung (NAP...)
if (preg_match('/NAP\d{12,}/i', $content, $matches)) {
    $transactionCode = strtoupper($matches[0]);
    
    $stmt = $conn->prepare("
        SELECT tr.*, mc.balance 
        FROM topup_requests tr
        JOIN member_cards mc ON tr.card_id = mc.id
        WHERE tr.transaction_code = ? AND tr.status IN ('pending', 'waiting')
    ");
    $stmt->execute([$transactionCode]);
    $matchedRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($matchedRequest && floatval($matchedRequest['amount']) == $amount) {
        $found = true;
    }
}

// Ưu tiên 2: Tìm theo số tiền chính xác (yêu cầu trong 2 giờ gần đây)
if (!$found && $amount > 0) {
    $stmt = $conn->prepare("
        SELECT tr.*, mc.balance 
        FROM topup_requests tr
        JOIN member_cards mc ON tr.card_id = mc.id
        WHERE tr.amount = ? 
        AND tr.status IN ('pending', 'waiting')
        AND tr.created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ORDER BY tr.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$amount]);
    $matchedRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($matchedRequest) {
        $found = true;
    }
}

// Nếu tìm thấy, tự động cộng tiền
if ($found && $matchedRequest) {
    try {
        $conn->beginTransaction();
        
        // Cập nhật số dư thẻ
        $stmt = $conn->prepare("
            UPDATE member_cards 
            SET balance = balance + ?, 
                total_deposited = total_deposited + ?
            WHERE id = ?
        ");
        $stmt->execute([$amount, $amount, $matchedRequest['card_id']]);
        
        // Ghi lịch sử giao dịch
        $stmt = $conn->prepare("
            INSERT INTO card_transactions (card_id, type, amount, description, created_at)
            VALUES (?, 'deposit', ?, ?, NOW())
        ");
        $stmt->execute([
            $matchedRequest['card_id'],
            $amount,
            'Nạp tiền qua SePay - ' . $matchedRequest['transaction_code']
        ]);
        
        // Cập nhật trạng thái yêu cầu
        $stmt = $conn->prepare("
            UPDATE topup_requests 
            SET status = 'completed', 
                completed_at = NOW(),
                payment_info = ?
            WHERE id = ?
        ");
        $stmt->execute([
            json_encode([
                'sepay_id' => $transactionId,
                'sepay_content' => $content,
                'sepay_amount' => $amount,
                'auto_confirmed' => true,
                'confirmed_via' => 'webhook',
                'confirmed_at' => date('Y-m-d H:i:s')
            ]),
            $matchedRequest['id']
        ]);
        
        $conn->commit();
        
        // Log success
        $logEntry = date('Y-m-d H:i:s') . " - SUCCESS\n";
        $logEntry .= "Matched request ID: {$matchedRequest['id']}, Code: {$matchedRequest['transaction_code']}, Amount: $amount\n";
        $logEntry .= "---\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        echo json_encode([
            'success' => true,
            'message' => 'Topup confirmed',
            'request_id' => $matchedRequest['id'],
            'amount' => $amount
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        
        $logEntry = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n---\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // Không tìm thấy yêu cầu khớp
    $logEntry = date('Y-m-d H:i:s') . " - No matching request found for amount: $amount, content: $content\n---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo json_encode(['success' => true, 'message' => 'No matching topup request']);
}
