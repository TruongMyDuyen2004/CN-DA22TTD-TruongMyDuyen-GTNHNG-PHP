<?php
/**
 * Google OAuth Callback Handler
 * Xử lý callback từ Google sau khi user đăng nhập
 */

session_start();
require_once '../config/database.php';
require_once '../config/google-oauth.php';

// Kiểm tra có code từ Google không
if (!isset($_GET['code'])) {
    header('Location: login.php?error=google_failed');
    exit;
}

$code = $_GET['code'];

// Đổi code lấy access token
$tokenData = getGoogleAccessToken($code);

if (isset($tokenData['error'])) {
    header('Location: login.php?error=google_token_failed');
    exit;
}

$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken) {
    header('Location: login.php?error=google_no_token');
    exit;
}

// Lấy thông tin user từ Google
$googleUser = getGoogleUserInfo($accessToken);

if (isset($googleUser['error']) || !isset($googleUser['email'])) {
    header('Location: login.php?error=google_userinfo_failed');
    exit;
}

// Thông tin từ Google
$googleId = $googleUser['id'];
$email = $googleUser['email'];
$fullName = $googleUser['name'] ?? '';
$avatar = $googleUser['picture'] ?? '';

// Kết nối database
$db = new Database();
$conn = $db->connect();

if (!$conn) {
    error_log('Google Login: Database connection failed');
    header('Location: login.php?error=database_error');
    exit;
}

try {
    // Kiểm tra user đã tồn tại chưa (theo google_id hoặc email)
    $stmt = $conn->prepare("SELECT * FROM customers WHERE google_id = ? OR email = ?");
    $stmt->execute([$googleId, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // User đã tồn tại - cập nhật google_id nếu chưa có
        if (empty($user['google_id'])) {
            $stmt = $conn->prepare("UPDATE customers SET google_id = ?, avatar = COALESCE(avatar, ?) WHERE id = ?");
            $stmt->execute([$googleId, $avatar, $user['id']]);
        }
        
        // Đăng nhập
        $_SESSION['customer_id'] = $user['id'];
        $_SESSION['customer_name'] = $user['full_name'];
        $_SESSION['customer_email'] = $user['email'];
        $_SESSION['customer_avatar'] = $user['avatar'] ?: $avatar;
        
    } else {
        // User mới - tạo tài khoản
        $stmt = $conn->prepare("
            INSERT INTO customers (full_name, email, google_id, avatar, password, created_at) 
            VALUES (?, ?, ?, ?, '', NOW())
        ");
        $stmt->execute([$fullName, $email, $googleId, $avatar]);
        
        $userId = $conn->lastInsertId();
        
        // Đăng nhập
        $_SESSION['customer_id'] = $userId;
        $_SESSION['customer_name'] = $fullName;
        $_SESSION['customer_email'] = $email;
        $_SESSION['customer_avatar'] = $avatar;
    }
    
    // Chuyển hướng về trang chủ
    header('Location: ../index.php');
    exit;
    
} catch (PDOException $e) {
    // Lỗi database
    error_log('Google Login Error: ' . $e->getMessage());
    header('Location: login.php?error=database_error');
    exit;
}
