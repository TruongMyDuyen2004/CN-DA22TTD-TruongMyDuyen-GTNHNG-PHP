<?php
/**
 * Script để cài đặt database đặt bàn
 * Chạy file này để tạo các bảng và dữ liệu mẫu
 */

require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "=== BẮT ĐẦU CÀI ĐẶT DATABASE ĐẶT BÀN ===\n\n";
    
    // Đọc file SQL
    $sql = file_get_contents(__DIR__ . '/setup_reservations.sql');
    
    if ($sql === false) {
        throw new Exception("Không thể đọc file setup_reservations.sql");
    }
    
    // Tách các câu lệnh SQL
    $statements = explode(';', $sql);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Bỏ qua câu lệnh rỗng và comment
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $conn->exec($statement);
            $success_count++;
            
            // Hiển thị tiến trình
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Tạo bảng: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Thêm dữ liệu vào: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'CREATE VIEW') !== false || stripos($statement, 'CREATE OR REPLACE VIEW') !== false) {
                preg_match('/VIEW\s+`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Tạo view: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'CREATE PROCEDURE') !== false) {
                preg_match('/PROCEDURE\s+`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Tạo procedure: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'CREATE TRIGGER') !== false) {
                preg_match('/TRIGGER\s+`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Tạo trigger: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'CREATE EVENT') !== false) {
                preg_match('/EVENT.*?`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Tạo event: {$matches[1]}\n";
                }
            }
            
        } catch (PDOException $e) {
            $error_count++;
            // Bỏ qua một số lỗi không quan trọng
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "✗ Lỗi: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== KẾT QUẢ ===\n";
    echo "Thành công: $success_count câu lệnh\n";
    echo "Lỗi: $error_count câu lệnh\n\n";
    
    // Kiểm tra kết quả
    echo "=== KIỂM TRA DỮ LIỆU ===\n\n";
    
    // Đếm số bàn
    $stmt = $conn->query("SELECT COUNT(*) as count FROM tables");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Số bàn: {$count['count']}\n";
    
    // Đếm khung giờ
    $stmt = $conn->query("SELECT COUNT(*) as count FROM time_slots");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Số khung giờ: {$count['count']}\n";
    
    // Đếm đặt bàn mẫu
    $stmt = $conn->query("SELECT COUNT(*) as count FROM reservations");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Số đặt bàn mẫu: {$count['count']}\n";
    
    // Hiển thị phân loại bàn
    echo "\n=== PHÂN LOẠI BÀN ===\n";
    $stmt = $conn->query("
        SELECT 
            location,
            COUNT(*) as count,
            SUM(capacity) as total_capacity
        FROM tables
        GROUP BY location
    ");
    
    $locations = [
        'indoor' => 'Trong nhà',
        'outdoor' => 'Sân vườn',
        'vip' => 'VIP',
        'private_room' => 'Phòng riêng'
    ];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $location_name = $locations[$row['location']] ?? $row['location'];
        echo "- {$location_name}: {$row['count']} bàn, sức chứa {$row['total_capacity']} người\n";
    }
    
    // Hiển thị khung giờ
    echo "\n=== KHUNG GIỜ ===\n";
    $stmt = $conn->query("
        SELECT time_slot, max_reservations 
        FROM time_slots 
        ORDER BY time_slot
    ");
    
    $lunch = [];
    $dinner = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $time = substr($row['time_slot'], 0, 5);
        $hour = (int)substr($time, 0, 2);
        
        if ($hour < 15) {
            $lunch[] = $time;
        } else {
            $dinner[] = $time;
        }
    }
    
    echo "- Buổi trưa: " . implode(', ', $lunch) . "\n";
    echo "- Buổi tối: " . implode(', ', $dinner) . "\n";
    
    echo "\n=== HOÀN TẤT ===\n";
    echo "Database đặt bàn đã được cài đặt thành công!\n";
    echo "Xem file RESERVATION_DATABASE.md để biết thêm chi tiết.\n\n";
    
} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
    exit(1);
}
