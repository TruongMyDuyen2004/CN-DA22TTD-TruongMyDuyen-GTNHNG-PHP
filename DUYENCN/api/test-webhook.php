<?php
/**
 * Test webhook SePay - Giả lập giao dịch để test
 * Truy cập: /DUYENCN/api/test-webhook.php?code=NAP251229135118629&amount=20000
 */

session_start();
require_once '../config/database.php';

$transaction_code = $_GET['code'] ?? '';
$amount = floatval($_GET['amount'] ?? 0);

if (empty($transaction_code) || $amount <= 0) {
    die("Sử dụng: ?code=NAP...&amount=20000");
}

// Giả lập dữ liệu webhook từ SePay
$fake_webhook = [
    'id' => rand(1000, 9999),
    'gateway' => 'BIDV',
    'transactionDate' => date('Y-m-d H:i:s'),
    'accountNumber' => '8892478854',
    'code' => null,
    'content' => $transaction_code,
    'transferType' => 'in',
    'transferAmount' => $amount,
    'accumulated' => 0,
    'subAccount' => null,
    'referenceCode' => 'TEST' . date('YmdHis'),
    'description' => $transaction_code
];

echo "<h2>Test Webhook SePay</h2>";
echo "<p><strong>Mã giao dịch:</strong> $transaction_code</p>";
echo "<p><strong>Số tiền:</strong> " . number_format($amount) . "đ</p>";
echo "<hr>";

// Gọi webhook
$ch = curl_init();
$webhook_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/webhook-bank.php';

curl_setopt_array($ch, [
    CURLOPT_URL => $webhook_url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($fake_webhook),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Kết quả:</h3>";
echo "<p><strong>HTTP Code:</strong> $http_code</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$result = json_decode($response, true);
if ($result && $result['success']) {
    echo "<p style='color: green; font-size: 1.5em;'>✅ NẠP TIỀN THÀNH CÔNG!</p>";
} else {
    echo "<p style='color: red;'>❌ Thất bại: " . ($result['message'] ?? 'Unknown error') . "</p>";
}
