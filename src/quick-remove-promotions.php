<?php
/**
 * Quick script - Xóa nhanh bảng promotions
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

try {
    // Xóa bảng promotions
    $conn->exec("DROP TABLE IF EXISTS promotions");
    
    echo "<!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Xóa Khuyến mãi</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .container {
                background: white;
                padding: 3rem;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 500px;
            }
            .icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }
            h1 {
                color: #10b981;
                margin-bottom: 1rem;
            }
            p {
                color: #6b7280;
                line-height: 1.6;
                margin-bottom: 2rem;
            }
            .btn {
                display: inline-block;
                padding: 1rem 2rem;
                background: #3b82f6;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.3s;
            }
            .btn:hover {
                background: #2563eb;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            }
            ul {
                text-align: left;
                margin: 1.5rem 0;
                padding-left: 1.5rem;
            }
            li {
                margin: 0.5rem 0;
                color: #374151;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='icon'>✅</div>
            <h1>Xóa thành công!</h1>
            <p>Tính năng khuyến mãi đã được xóa hoàn toàn khỏi website.</p>
            
            <div style='background: #f3f4f6; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;'>
                <strong style='color: #1f2937;'>Đã xóa:</strong>
                <ul>
                    <li>✅ Bảng promotions trong database</li>
                    <li>✅ Tất cả file PHP liên quan</li>
                    <li>✅ Menu trong admin panel</li>
                    <li>✅ Route trong index.php</li>
                </ul>
            </div>
            
            <a href='admin/index.php' class='btn'>
                🏠 Về trang Admin
            </a>
        </div>
    </body>
    </html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <title>Lỗi</title>
        <style>
            body {
                font-family: Arial;
                padding: 2rem;
                background: #fee2e2;
            }
            .error {
                background: white;
                padding: 2rem;
                border-radius: 8px;
                border-left: 4px solid #ef4444;
            }
        </style>
    </head>
    <body>
        <div class='error'>
            <h1 style='color: #ef4444;'>❌ Lỗi</h1>
            <p>" . $e->getMessage() . "</p>
            <p><a href='admin/index.php'>Về trang Admin</a></p>
        </div>
    </body>
    </html>";
}
?>
