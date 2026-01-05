<?php
/**
 * API kiểm tra giao dịch từ SePay và TỰ ĐỘNG CỘNG TIỀN
 * Khi tìm thấy giao dịch khớp sẽ tự động xác nhận và cộng tiền vào thẻ
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// SePay API Configuration
define('SEPAY_API_KEY', 'Q6LCGEPYOCBCFR14URSGATDR2BM8GZHPUJA3D0YOX1GAZWDKWHFMCIQILOKH9VQX');

$transaction_code = $_GET['transaction_code'] ?? '';
$amount = floatval($_GET['amount'] ?? 0);
$auto_confirm = ($_GET['auto_confirm'] ?? '1') === '1'; // Mặc định tự động xác nhận

if (empty($transaction_code) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->connect();

// Kiểm tra yêu cầu nạp tiền
$stmt = $conn->prepare("
    SELECT tr.*, mc.balance 
    FROM topup_requests tr
    JOIN member_cards mc ON tr.card_id = mc.id
    WHERE tr.transaction_code = ?
");
$stmt->execute([$transaction_code]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu nạp tiền']);
    exit;
}

// Nếu đã hoàn thành rồi
if ($request['status'] === 'completed') {
    echo json_encode([
        'success' => true,
        'found' => true,
        'already_completed' => true,
        'message' => 'Giao dịch đã được xác nhận trước đó',
        'transaction_code' => $transaction_code,
        'amount' => $request['amount']
    ]);
    exit;
}

$requestTime = strtotime($request['created_at']);

// Gọi API SePay lấy danh sách giao dịch
$transactions = getSePayTransactions();

if ($transactions === false) {
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối SePay API']);
    exit;
}

// Tìm giao dịch khớp
$found = false;
$matchedTransaction = null;
$debugInfo = [];

foreach ($transactions as $trans) {
    // Lấy nội dung giao dịch - thử nhiều field names
    $content = strtoupper($trans['content'] ?? $trans['description'] ?? $trans['transactionContent'] ?? '');
    
    // Lấy số tiền - thử nhiều field names
    $transAmount = floatval($trans['transferAmount'] ?? $trans['amount'] ?? $trans['amount_in'] ?? 0);
    
    // Lấy loại giao dịch
    $transferType = $trans['transferType'] ?? $trans['type'] ?? 'in';
    
    // Lấy ngày giao dịch - thử nhiều field names
    $transDate = $trans['transactionDate'] ?? $trans['when'] ?? $trans['created_at'] ?? '';
    $transTime = strtotime($transDate);
    
    // Chỉ xét giao dịch tiền vào
    if ($transferType !== 'in') continue;
    
    // Chỉ xét giao dịch trong vòng 24 giờ gần đây (nới lỏng từ 2 giờ)
    if (time() - $transTime > 86400) continue;
    
    // Debug info
    $debugInfo[] = [
        'id' => $trans['id'] ?? '',
        'content' => $content,
        'amount' => $transAmount,
        'date' => $transDate,
        'code_match' => stripos($content, $transaction_code) !== false,
        'amount_match' => $transAmount == $amount
    ];
    
    // Ưu tiên 1: Khớp cả mã và số tiền
    if (stripos($content, $transaction_code) !== false && $transAmount == $amount) {
        $found = true;
        $matchedTransaction = $trans;
        break;
    }
    
    // Ưu tiên 2: Chỉ khớp số tiền chính xác (giao dịch sau khi tạo yêu cầu)
    if ($transAmount == $amount && !$found) {
        // Giao dịch phải sau thời điểm tạo yêu cầu (trừ 5 phút buffer cho sai lệch đồng hồ)
        if ($transTime >= ($requestTime - 300)) {
            $found = true;
            $matchedTransaction = $trans;
            // Không break, tiếp tục tìm xem có giao dịch nào khớp mã không
        }
    }
}

// Nếu tìm thấy và auto_confirm = true, tự động cộng tiền
if ($found && $auto_confirm && $request['status'] !== 'completed') {
    try {
        $conn->beginTransaction();
        
        // Cập nhật số dư thẻ
        $stmt = $conn->prepare("
            UPDATE member_cards 
            SET balance = balance + ?, 
                total_deposited = total_deposited + ?
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
            'Nạp tiền qua SePay - ' . $transaction_code
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
                'sepay_id' => $matchedTransaction['id'] ?? '',
                'sepay_content' => $matchedTransaction['content'] ?? $matchedTransaction['description'] ?? '',
                'sepay_amount' => $matchedTransaction['transferAmount'] ?? $matchedTransaction['amount'] ?? 0,
                'auto_confirmed' => true,
                'confirmed_at' => date('Y-m-d H:i:s')
            ]),
            $request['id']
        ]);
        
        $conn->commit();
        
        // Lấy số dư mới
        $stmt = $conn->prepare("SELECT balance FROM member_cards WHERE id = ?");
        $stmt->execute([$request['card_id']]);
        $newBalance = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'found' => true,
            'confirmed' => true,
            'message' => 'Nạp tiền thành công!',
            'transaction_code' => $transaction_code,
            'amount' => $amount,
            'new_balance' => $newBalance,
            'sepay_transaction' => $matchedTransaction
        ]);
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'found' => true,
            'message' => 'Lỗi khi cộng tiền: ' . $e->getMessage()
        ]);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'found' => $found,
    'confirmed' => false,
    'message' => $found ? 'Tìm thấy giao dịch' : 'Chưa tìm thấy giao dịch khớp',
    'transaction_code' => $transaction_code,
    'checked_amount' => $amount,
    'request_time' => $request['created_at'],
    'transaction' => $matchedTransaction,
    'debug_transactions_count' => count($transactions),
    'debug_checked' => array_slice($debugInfo, 0, 5) // Chỉ trả về 5 giao dịch đầu để debug
]);

/**
 * Lấy danh sách giao dịch từ SePay API
 */
function getSePayTransactions() {
    if (empty(SEPAY_API_KEY)) {
        return false;
    }
    
    // Lấy giao dịch trong 24h gần nhất
    $url = 'https://my.sepay.vn/userapi/transactions/list';
    
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
    
    if ($httpCode != 200) {
        return false;
    }
    
    $data = json_decode($response, true);
    return $data['transactions'] ?? $data['data'] ?? [];
}
