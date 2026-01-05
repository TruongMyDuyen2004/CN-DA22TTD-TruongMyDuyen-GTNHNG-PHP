<?php
/**
 * Test SePay Webhook - Giả lập giao dịch từ SePay
 * Truy cập: /DUYENCN/api/test-sepay-webhook.php?code=NAP251229131510765&amount=50000
 */

// Lấy tham số
$transaction_code = $_GET['code'] ?? 'NAP251229131510765';
$amount = intval($_GET['amount'] ?? 50000);

// Tạo dữ liệu giả lập SePay webhook
$sepay_data = [
    'id' => rand(1000, 9999),
    'gateway' => 'BIDV',
    'transactionDate' => date('Y-m-d H:i:s'),
    'accountNumber' => '8892478854',
    'code' => null,
    'content' => $transaction_code,
    'transferType' => 'in',
    'transferAmount' => $amount,
    'accumulated' => 1500000,
    'subAccount' => null,
    'referenceCode' => 'FT' . date('ymd') . rand(10000, 99999),
    'description' => $transaction_code
];

echo "<h2>🧪 Test SePay Webhook</h2>";
echo "<p><strong>Mã giao dịch:</strong> {$transaction_code}</p>";
echo "<p><strong>Số tiền:</strong> " . number_format($amount) . "đ</p>";
echo "<hr>";

// Gửi request đến webhook
$webhook_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/webhook-bank.php';

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sepay_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>📤 Dữ liệu gửi đi:</h3>";
echo "<pre>" . json_encode($sepay_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h3>📥 Phản hồi từ webhook:</h3>";
echo "<p><strong>HTTP Code:</strong> {$http_code}</p>";
echo "<pre>" . $response . "</pre>";

$result = json_decode($response, true);
if ($result && $result['success']) {
    echo "<p style='color: green; font-size: 1.2em;'>✅ Webhook xử lý thành công!</p>";
} else {
    echo "<p style='color: orange; font-size: 1.2em;'>⚠️ Kiểm tra log để xem chi tiết</p>";
}

echo "<hr>";
echo "<p><a href='../pages/?page=member-card'>← Quay lại trang thẻ thành viên</a></p>";
