<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> Cài đặt hệ thống</h1>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>✅ Trang cài đặt đang hoạt động!</h3>
            </div>
            <div class="card-body">
                <p>Nếu bạn thấy trang này, nghĩa là file settings.php đã load thành công.</p>
                
                <h4>Thông tin session:</h4>
                <ul>
                    <li>Admin ID: <?php echo $_SESSION['admin_id']; ?></li>
                    <li>Admin Username: <?php echo $_SESSION['admin_username'] ?? 'N/A'; ?></li>
                </ul>
                
                <h4>Thông tin database:</h4>
                <ul>
                    <li>Kết nối: <?php echo $conn ? '✅ Thành công' : '❌ Thất bại'; ?></li>
                    <li>PHP Version: <?php echo phpversion(); ?></li>
                </ul>
                
                <hr>
                
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Về Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
