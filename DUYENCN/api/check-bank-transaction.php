<?php
/**
 * API kiểm tra giao dịch ngân hàng từ SePay
 * Dùng để tự động xác nhận nạp tiền khi webhook không hoạt động
 * 
 * Cách dùng: Gọi API này định kỳ hoặc khi user bấm "Kiểm tra giao dịch"
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

// SePay API Configuration
define('SEPAY_API_KEY', 'Q6LCGEPYOCBCFR14URSGATDR2BM8GZHPUJA3D0YOX1GAZWDKWHFMCIQILOKH9VQX');
define('SEPAY_ACCOUNT_NUMBER', '8892478854');

$db = new Database();
$conn = $db->connect();

$action = $_POST['action'] ?? $_GET['action'] ?? 'check';

switch ($action) {
    case 'check':
        checkPendingTransactions($conn);
        break;
    case 'check_single':
        checkSingleTransaction($conn);
        break;
    case 'manual_confirm':
        manualConfirmByContent($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Kiểm tra tất cả giao dịch đang chờ
 */
function checkPendingTransactions($conn) {
    // Lấy danh sách yêu cầu nạp tiền đang chờ
    $stmt = $conn->prepare("
        SELECT tr.*, mc.customer_id 
        FROM topup_requests tr
        JOIN member_cards mc ON tr.card_id = mc.id
        WHERE tr.status IN ('pending', 'waiting')
        AND tr.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY tr.created_at DESC
    ");
    $stmt->execute();
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pending)) {
        echo json_encode(['success' => true, 'message' => 'Không có giao dịch nào đang chờ', 'confirmed' => 0]);
        return;
    }
    
    // Lấy giao dịch từ SePay API
    $transactions = getSePayTransactions();
    
    if ($transactions === false) {
        // Nếu không có API key, thử kiểm tra thủ công
        echo json_encode([
            'success' => false, 
            'message' => 'Chưa cấu hình SePay API. Vui lòng liên hệ admin.',
            'pending_count' => count($pending)
        ]);
        return;
    }
    
    $confirmed = 0;
    $results = [];
    
    foreach ($pending as $request) {
        $code = $request['transaction_code'];
        $amount = floatval($request['amount']);
        
        // Tìm giao dịch khớp trong danh sách từ SePay
        foreach ($transactions as $trans) {
            $content = $trans['content'] ?? $trans['description'] ?? '';
            $transAmount = floatval($trans['transferAmount'] ?? $trans['amount'] ?? 0);
            
            // Kiểm tra mã giao dịch và số tiền
            if (stripos($content, $code) !== false && $transAmount == $amount) {
                // Xác nhận giao dịch
                if (confirmTopupRequest($conn, $request, $trans)) {
                    $confirmed++;
                    $results[] = [
                        'code' => $code,
                        'amount' => $amount,
                        'status' => 'confirmed'
                    ];
                }
                break;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Đã xác nhận $confirmed giao dịch",
        'confirmed' => $confirmed,
        'results' => $results
    ]);
}

/**
 * Kiểm tra một giao dịch cụ thể
 */
function checkSingleTransaction($conn) {
    $transaction_code = $_POST['transaction_code'] ?? $_GET['transaction_code'] ?? '';
    
    if (empty($transaction_code)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã giao dịch']);
        return;
    }
    
    // Lấy thông tin yêu cầu
    $stmt = $conn->prepare("
        SELECT tr.*, mc.customer_id, mc.balance
        FROM topup_requests tr
        JOIN member_cards mc ON tr.card_id = mc.id
        WHERE tr.transaction_code = ?
    ");
    $stmt->execute([$transaction_code]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu nạp tiền']);
        return;
    }
    
    if ($request['status'] == 'completed') {
        echo json_encode(['success' => true, 'message' => 'Giao dịch đã được xác nhận trước đó', 'status' => 'completed']);
        return;
    }
    
    // Lấy giao dịch từ SePay
    $transactions = getSePayTransactions();
    
    if ($transactions === false) {
        echo json_encode(['success' => false, 'message' => 'Không thể kết nối SePay API']);
        return;
    }
    
    $code = $request['transaction_code'];
    $amount = floatval($request['amount']);
    
    foreach ($transactions as $trans) {
        $content = $trans['content'] ?? $trans['description'] ?? '';
        $transAmount = floatval($trans['transferAmount'] ?? $trans['amount'] ?? 0);
        
        if (stripos($content, $code) !== false && $transAmount == $amount) {
            if (confirmTopupRequest($conn, $request, $trans)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã xác nhận nạp tiền thành công!',
                    'amount' => $amount,
                    'status' => 'completed'
                ]);
                return;
            }
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Chưa tìm thấy giao dịch khớp. Vui lòng đợi vài phút và thử lại.',
        'status' => $request['status']
    ]);
}

/**
 * Xác nhận thủ công bằng nội dung chuyển khoản (cho admin hoặc khi không có API)
 */
function manualConfirmByContent($conn) {
    $transaction_code = $_POST['transaction_code'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    
    if (empty($transaction_code)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu mã giao dịch']);
        return;
    }
    
    // Lấy thông tin yêu cầu
    $stmt = $conn->prepare("
        SELECT tr.*, mc.customer_id, mc.balance
        FROM topup_requests tr
        JOIN member_cards mc ON tr.card_id = mc.id
        WHERE tr.transaction_code = ?
        AND tr.status IN ('pending', 'waiting')
    ");
    $stmt->execute([$transaction_code]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu nạp tiền hoặc đã được xử lý']);
        return;
    }
    
    // Kiểm tra số tiền nếu có
    if ($amount > 0 && $amount != $request['amount']) {
        echo json_encode(['success' => false, 'message' => 'Số tiền không khớp']);
        return;
    }
    
    // Xác nhận
    $trans = ['manual' => true, 'confirmed_at' => date('Y-m-d H:i:s')];
    if (confirmTopupRequest($conn, $request, $trans)) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã xác nhận nạp tiền thành công!',
            'amount' => $request['amount']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xác nhận giao dịch']);
    }
}

/**
 * Lấy danh sách giao dịch từ SePay API
 */
function getSePayTransactions() {
    if (empty(SEPAY_API_KEY)) {
        return false;
    }
    
    $url = 'https://my.sepay.vn/userapi/transactions/list';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SEPAY_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30
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

/**
 * Xác nhận yêu cầu nạp tiền
 */
function confirmTopupRequest($conn, $request, $transInfo) {
    try {
        $conn->beginTransaction();
        
        $amount = floatval($request['amount']);
        
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
            'Nạp tiền tự động - ' . $request['transaction_code']
        ]);
        
        // Cập nhật trạng thái yêu cầu
        $stmt = $conn->prepare("
            UPDATE topup_requests 
            SET status = 'completed', 
                completed_at = NOW(),
                payment_info = ?
            WHERE id = ?
        ");
        $stmt->execute([json_encode($transInfo), $request['id']]);
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Confirm topup error: " . $e->getMessage());
        return false;
    }
}
