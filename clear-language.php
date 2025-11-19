<?php
session_start();

// Xóa session language
unset($_SESSION['language']);

// Xóa cookie language
setcookie('language', '', time() - 3600, '/');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Clear Language Cache</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 100px auto;
            padding: 20px;
            text-align: center;
        }
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        a {
            display: inline-block;
            padding: 10px 20px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        a:hover {
            background: #b91c1c;
        }
    </style>
</head>
<body>
    <div class='success'>
        <h2>✓ Language cache cleared!</h2>
        <p>Session và cookie ngôn ngữ đã được xóa.</p>
    </div>
    
    <a href='index.php'>← Về trang chủ</a>
    <a href='test-translation.php'>Test Translation</a>
</body>
</html>";
?>
