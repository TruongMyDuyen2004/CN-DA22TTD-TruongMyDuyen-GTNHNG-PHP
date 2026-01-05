<?php
/**
 * Webhook nhận thông báo giao dịch từ SePay
 * Tự động xác nhận nạp tiền khi có giao dịch khớp
 * 
 * Cấu hình webhook URL: https://yourdomain.com/DUYENCN/api/webhook-bank.php
 * 
 * SePay webhook format:
 * {
 *   "id": 93,
 *   "gateway": "BIDV",
 *   "transactionDate": "2024-01-15 10:30:00",
 *   "accountNumber": "8892478854",
 *   "code": null,
 *   "content": "NAP251229131510765",
 *   "transferType": "in",
 *   "transferAmount": 50000,
 *   "accumulated": 1500000,
 *   "subAccount": null,
 *   "referenceCode": "FT24015XXXXX",
 *   "description": "NAP251229131510765"
 * }
 */

header('Content-Type: application/json; charset=utf-8');

// Tạo thư mục logs nếu chưa có
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Log webhook để debug
$log_file = $log_dir . '/webhook_' . date('Y-m-d') . '.log';
$raw_input = file_get_contents('php://input');
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Received: " . $raw_input . "\n", FILE_APPEND);

require_once '../config/database.php';

// Lấy dữ liệu từ webhook
$data = json_decode($raw_input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Xử lý webhook từ SePay
if (isset($data['transferType']) && $data['transferType'] === 'in') {
    $result = processSePayTransaction($data);
    echo json_encode($result);
    exit;
}

// Xử lý webhook từ Casso (backup)
if (isset($data['data']) && is_array($data['data'])) {
    foreach ($data['data'] as $transaction) {
        processTransaction($transaction);
    }
}

echo json_encode(['success' => true, 'message' => 'Webhook received']);

/**
 * Xử lý giao dịch từ SePay
 */
function processSePayTransaction($data) {
    global $log_file;
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        // Lấy thông tin từ SePay webhook
        $amount = floatval($data['transferAmount'] ?? 0);
        $content = $data['content'] ?? $data['description'] ?? '';
        $trans_id = $data['id'] ?? '';
        $reference_code = $data['referenceCode'] ?? '';
        $gateway = $data['gateway'] ?? '';
        $account_number = $data['accountNumber'] ?? '';
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - SePay: amount=$amount, content=$content, gateway=$gateway\n", FILE_APPEND);
        
        // Chỉ xử lý giao dịch tiền vào
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount'];
        }
        
        // Tìm mã giao dịch NAP trong nội dung chuyển khoản
        $request = null;
        
        if (preg_match('/NAP\d{12,20}/i', $content, $matches)) {
            $transaction_code = strtoupper($matches[0]);
            
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Found code: $transaction_code\n", FILE_APPEND);
            
            // Tìm yêu cầu nạp tiền tương ứng theo mã
            $stmt = $conn->prepare("
                SELECT tr.*, mc.balance, mc.customer_id
                FROM topup_requests tr
                JOIN member_cards mc ON tr.card_id = mc.id
                WHERE tr.transaction_code = ? 
                AND tr.status IN ('pending', 'waiting')
            ");
            $stmt->execute([$transaction_code]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Nếu không tìm thấy theo mã, thử tìm theo số tiền (trong vòng 30 phút)
        if (!$request) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - No code found, trying to match by amount: $amount\n", FILE_APPEND);
            
            $stmt = $conn->prepare("
                SELECT tr.*, mc.balance, mc.customer_id
                FROM topup_requests tr
                JOIN member_cards mc ON tr.card_id = mc.id
                WHERE tr.amount = ? 
                AND tr.status IN ('pending', 'waiting')
                AND tr.created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                ORDER BY tr.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$amount]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request) {
                $transaction_code = $request['transaction_code'];
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Matched by amount, code: $transaction_code\n", FILE_APPEND);
            }
        }
            
            if (!$request) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - No matching request found\n", FILE_APPEND);
                return ['success' => false, 'message' => 'No matching topup request'];
            }
            
            // Bắt đầu transaction
            $conn->beginTransaction();
            
            try {
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
                    'Nạp tiền tự động qua SePay - ' . $transaction_code
                ]);
                
                // Cập nhật trạng thái yêu cầu nạp tiền
                $stmt = $conn->prepare("
                    UPDATE topup_requests 
                    SET status = 'completed', 
                        completed_at = NOW(),
                        payment_info = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    json_encode([
                        'sepay_id' => $trans_id,
                        'reference_code' => $reference_code,
                        'gateway' => $gateway,
                        'auto_confirmed' => true,
                        'confirmed_at' => date('Y-m-d H:i:s')
                    ]),
                    $request['id']
                ]);
                
                $conn->commit();
                
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - SUCCESS: Auto confirmed $transaction_code, amount: $amount\n", FILE_APPEND);
                
                return [
                    'success' => true, 
                    'message' => 'Topup confirmed',
                    'transaction_code' => $transaction_code,
                    'amount' => $amount
                ];
                
            } catch (Exception $e) {
                $conn->rollBack();
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - DB ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                return ['success' => false, 'message' => 'Database error'];
            }
        } else {
            // Không tìm thấy mã NAP, thử tìm theo số tiền
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - No NAP code found in content: $content, trying amount match\n", FILE_APPEND);
            
            $stmt = $conn->prepare("
                SELECT tr.*, mc.balance, mc.customer_id
                FROM topup_requests tr
                JOIN member_cards mc ON tr.card_id = mc.id
                WHERE tr.amount = ? 
                AND tr.status IN ('pending', 'waiting')
                AND tr.created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                ORDER BY tr.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$amount]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request) {
                $transaction_code = $request['transaction_code'];
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Matched by amount only, code: $transaction_code\n", FILE_APPEND);
                
                // Bắt đầu transaction
                $conn->beginTransaction();
                
                try {
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
                        'Nạp tiền tự động qua SePay - ' . $transaction_code
                    ]);
                    
                    // Cập nhật trạng thái yêu cầu nạp tiền
                    $stmt = $conn->prepare("
                        UPDATE topup_requests 
                        SET status = 'completed', 
                            completed_at = NOW(),
                            payment_info = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        json_encode([
                            'sepay_id' => $trans_id,
                            'reference_code' => $reference_code,
                            'gateway' => $gateway,
                            'auto_confirmed' => true,
                            'matched_by' => 'amount_only',
                            'confirmed_at' => date('Y-m-d H:i:s')
                        ]),
                        $request['id']
                    ]);
                    
                    $conn->commit();
                    
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - SUCCESS (amount match): Auto confirmed $transaction_code, amount: $amount\n", FILE_APPEND);
                    
                    return [
                        'success' => true, 
                        'message' => 'Topup confirmed by amount match',
                        'transaction_code' => $transaction_code,
                        'amount' => $amount
                    ];
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - DB ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                    return ['success' => false, 'message' => 'Database error'];
                }
            }
            
            return ['success' => false, 'message' => 'No transaction code found and no amount match'];
        }
        
    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        return ['success' => false, 'message' => 'Server error'];
    }
}

/**
 * Xử lý giao dịch từ Casso (backup)
 */
function processTransaction($transaction) {
    global $log_file;
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $amount = $transaction['amount'] ?? 0;
        $description = $transaction['description'] ?? '';
        $trans_id = $transaction['id'] ?? '';
        
        if ($amount <= 0) return;
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Casso: amount=$amount, desc=$description\n", FILE_APPEND);
        
        if (preg_match('/NAP\d{12,20}/i', $description, $matches)) {
            $transaction_code = strtoupper($matches[0]);
            
            $stmt = $conn->prepare("
                SELECT tr.*, mc.balance 
                FROM topup_requests tr
                JOIN member_cards mc ON tr.card_id = mc.id
                WHERE tr.transaction_code = ? 
                AND tr.status IN ('pending', 'waiting')
                AND tr.amount = ?
            ");
            $stmt->execute([$transaction_code, $amount]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request) {
                $conn->beginTransaction();
                
                try {
                    $stmt = $conn->prepare("UPDATE member_cards SET balance = balance + ?, total_deposited = total_deposited + ? WHERE id = ?");
                    $stmt->execute([$amount, $amount, $request['card_id']]);
                    
                    $stmt = $conn->prepare("INSERT INTO card_transactions (card_id, type, amount, description, created_at) VALUES (?, 'deposit', ?, ?, NOW())");
                    $stmt->execute([$request['card_id'], $amount, 'Nạp tiền tự động - ' . $transaction_code]);
                    
                    $stmt = $conn->prepare("UPDATE topup_requests SET status = 'completed', completed_at = NOW(), payment_info = ? WHERE id = ?");
                    $stmt->execute([json_encode(['bank_trans_id' => $trans_id, 'auto_confirmed' => true]), $request['id']]);
                    
                    $conn->commit();
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - SUCCESS: $transaction_code confirmed\n", FILE_APPEND);
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
        }
    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
