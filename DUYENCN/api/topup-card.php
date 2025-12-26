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
    
    $valid_methods = ['momo', 'zalopay', 'bank'];
    if (!in_array($method, $valid_methods)) {
        echo json_encode(['success' => false, 'message' => 'Phương thức thanh toán không hợp lệ']);
        return;
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
 * Xác nhận đã thanh toán - chuyển sang trạng thái chờ admin duyệt
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
        echo json_encode(['success' => false, 'message' => 'Giao dịch này đã được xử lý']);
        return;
    }
    
    if ($request['status'] == 'expired') {
        echo json_encode(['success' => false, 'message' => 'Giao dịch đã hết hạn']);
        return;
    }
    
    if ($request['status'] == 'waiting') {
        echo json_encode(['success' => false, 'message' => 'Yêu cầu đang chờ admin xác nhận']);
        return;
    }
    
    try {
        // Cập nhật trạng thái sang "waiting" - chờ admin duyệt
        $stmt = $conn->prepare("UPDATE topup_requests SET status = 'waiting' WHERE id = ?");
        $stmt->execute([$request['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Yêu cầu nạp tiền đã được gửi! Vui lòng chờ admin xác nhận.',
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
 * Tạo thông tin thanh toán giả lập
 */
function generatePaymentInfo($method, $amount, $code) {
    switch ($method) {
        case 'momo':
            return [
                'type' => 'momo',
                'phone' => '0912345678',
                'name' => 'NGON GALLERY',
                'content' => $code,
                'qr_data' => "momo://pay?phone=0912345678&amount=$amount&comment=$code"
            ];
        case 'zalopay':
            return [
                'type' => 'zalopay',
                'phone' => '0912345678',
                'name' => 'NGON GALLERY',
                'content' => $code,
                'qr_data' => "zalopay://pay?amount=$amount&desc=$code"
            ];
        case 'bank':
            return [
                'type' => 'bank',
                'bank_name' => 'Vietcombank',
                'account_number' => '9384848127',
                'account_name' => 'TRUONG MY DUYEN',
                'content' => $code,
                'branch' => 'Chi nhánh Hà Nội'
            ];
        default:
            return [];
    }
}
