<?php
/**
 * API Nạp tiền thẻ thành viên
 * Hỗ trợ các phương thức: Momo, ZaloPay, Ngân hàng (giả lập)
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$db = new Database();
$conn = $db->connect();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create_topup':
        createTopupRequest($conn);
        break;
    case 'confirm_topup':
        confirmTopup($conn);
        break;
    case 'check_status':
        checkTopupStatus($conn);
        break;
    case 'get_history':
        getTopupHistory($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

/**
 * Tạo yêu cầu nạp tiền
 */
function createTopupRequest($conn) {
    $amount = floatval($_POST['amount'] ?? 0);
    $method = $_POST['method'] ?? 'bank';
    
    // Validate
    if ($amount < 10000) {
        echo json_encode(['success' => false, 'message' => 'Số tiền nạp tối thiểu là 10,000đ']);
        return;
    }
    
    if ($amount > 10000000) {
        echo json_encode(['success' => false, 'message' => 'Số tiền nạp tối đa là 10,000,000đ']);
        return;
    }
    
    $valid_methods = ['bank'];
    if (!in_array($method, $valid_methods)) {
        $method = 'bank'; // Mặc định là chuyển khoản ngân hàng
    }
    
    // Kiểm tra thẻ thành viên
    $stmt = $conn->prepare("SELECT id, card_number, balance, status FROM member_cards WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$card) {
        echo json_encode(['success' => false, 'message' => 'Bạn chưa có thẻ thành viên!']);
        return;
    }
    
    if ($card['status'] != 'active') {
        echo json_encode(['success' => false, 'message' => 'Thẻ của bạn đã bị khóa!']);
        return;
    }
    
    // Tạo mã giao dịch
    $transaction_code = 'NAP' . date('ymdHis') . rand(100, 999);
    
    // Lưu yêu cầu nạp tiền vào bảng topup_requests
    try {
        // Kiểm tra bảng tồn tại, nếu chưa thì tạo
        $conn->exec("CREATE TABLE IF NOT EXISTS topup_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            card_id INT NOT NULL,
            transaction_code VARCHAR(30) NOT NULL UNIQUE,
            amount DECIMAL(12,0) NOT NULL,
            method VARCHAR(20) NOT NULL,
            status ENUM('pending', 'waiting', 'completed', 'failed', 'expired') DEFAULT 'pending',
            payment_info TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            INDEX idx_customer (customer_id),
            INDEX idx_code (transaction_code),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $conn->prepare("INSERT INTO topup_requests (customer_id, card_id, transaction_code, amount, method) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['customer_id'], $card['id'], $transaction_code, $amount, $method]);
        $request_id = $conn->lastInsertId();
        
        // Tạo thông tin thanh toán giả lập
        $payment_info = generatePaymentInfo($method, $amount, $transaction_code);
        
        echo json_encode([
            'success' => true,
            'message' => 'Tạo yêu cầu nạp tiền thành công!',
            'data' => [
                'request_id' => $request_id,
                'transaction_code' => $transaction_code,
                'amount' => $amount,
                'method' => $method,
                'payment_info' => $payment_info
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

/**
 * Xác nhận đã thanh toán - Chuyển sang trạng thái CHỜ ADMIN DUYỆT
 */
function confirmTopup($conn) {
    $transaction_code = $_POST['transaction_code'] ?? '';
    
    if (empty($transaction_code)) {
        echo json_encode(['success' => false, 'message' => 'Mã giao dịch không hợp lệ']);
        return;
    }
    
    // Lấy thông tin yêu cầu nạp tiền
    $stmt = $conn->prepare("
        SELECT tr.*, mc.balance 
        FROM topup_requests tr
        JOIN member_cards mc ON tr.card_id = mc.id
        WHERE tr.transaction_code = ? AND tr.customer_id = ?
    ");
    $stmt->execute([$transaction_code, $_SESSION['customer_id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu nạp tiền']);
        return;
    }
    
    if ($request['status'] == 'completed') {
        // Lấy số dư hiện tại
        $stmt = $conn->prepare("SELECT balance FROM member_cards WHERE id = ?");
        $stmt->execute([$request['card_id']]);
        $currentBalance = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'message' => 'Giao dịch này đã được xử lý trước đó',
            'data' => [
                'status' => 'completed',
                'transaction_code' => $transaction_code,
                'amount' => $request['amount'],
                'new_balance' => $currentBalance
            ]
        ]);
        return;
    }
    
    if ($request['status'] == 'expired') {
        echo json_encode(['success' => false, 'message' => 'Giao dịch đã hết hạn']);
        return;
    }
    
    if ($request['status'] == 'waiting') {
        echo json_encode([
            'success' => true,
            'message' => 'Yêu cầu đã được gửi, vui lòng chờ Admin xác nhận',
            'data' => [
                'status' => 'waiting',
                'transaction_code' => $transaction_code,
                'amount' => $request['amount']
            ]
        ]);
        return;
    }
    
    try {
        // Chuyển trạng thái sang "waiting" (chờ admin duyệt)
        $stmt = $conn->prepare("
            UPDATE topup_requests 
            SET status = 'waiting',
                payment_info = ?
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([
            json_encode([
                'user_confirmed' => true,
                'confirmed_at' => date('Y-m-d H:i:s')
            ]),
            $request['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Yêu cầu đã được gửi! Vui lòng chờ Admin xác nhận giao dịch.',
            'data' => [
                'status' => 'waiting',
                'transaction_code' => $transaction_code,
                'amount' => $request['amount']
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

/**
 * Kiểm tra trạng thái nạp tiền
 */
function checkTopupStatus($conn) {
    $transaction_code = $_GET['transaction_code'] ?? $_POST['transaction_code'] ?? '';
    
    $stmt = $conn->prepare("SELECT status, amount, method, created_at, completed_at FROM topup_requests WHERE transaction_code = ? AND customer_id = ?");
    $stmt->execute([$transaction_code, $_SESSION['customer_id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy giao dịch']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $request
    ]);
}

/**
 * Lấy lịch sử nạp tiền
 */
function getTopupHistory($conn) {
    $stmt = $conn->prepare("
        SELECT transaction_code, amount, method, status, created_at, completed_at
        FROM topup_requests
        WHERE customer_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$_SESSION['customer_id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $history
    ]);
}

/**
 * Tạo thông tin thanh toán ngân hàng MB Bank với VietQR
 */
function generatePaymentInfo($method, $amount, $code) {
    // Thông tin ngân hàng MB Bank
    $bank_id = 'MB';
    $account_number = '444418062004';
    $account_name = 'TRUONG MY DUYEN';
    
    // Tạo URL mã QR VietQR (template print - đẹp hơn)
    $qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_number}-print.png?amount={$amount}&addInfo={$code}&accountName=" . urlencode($account_name);
    
    return [
        'type' => 'bank',
        'bank_name' => 'MB Bank',
        'bank_full_name' => 'Ngân hàng TMCP Quân đội',
        'bank_branch' => '',
        'account_number' => $account_number,
        'account_name' => $account_name,
        'content' => $code,
        'qr_url' => $qr_url
    ];
}
