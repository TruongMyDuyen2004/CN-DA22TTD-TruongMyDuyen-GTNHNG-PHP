<?php
/**
 * Admin - Quản lý Điểm tích lũy
 */
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

$message = '';
$error = '';

// Xử lý điều chỉnh điểm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'adjust') {
        $customer_id = intval($_POST['customer_id']);
        $points = intval($_POST['points']);
        $type = $_POST['type']; // add or subtract
        $reason = trim($_POST['reason']);
        
        try {
            $conn->beginTransaction();
            
            // Lấy thông tin điểm hiện tại
            $stmt = $conn->prepare("SELECT * FROM customer_points WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $cp = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cp) {
                // Tạo mới nếu chưa có
                $conn->exec("INSERT INTO customer_points (customer_id, total_points, available_points) VALUES ($customer_id, 0, 0)");
                $cp = ['total_points' => 0, 'available_points' => 0, 'used_points' => 0];
            }
            
            $balance_before = $cp['available_points'];
            
            if ($type === 'add') {
                $balance_after = $balance_before + $points;
                $new_total = $cp['total_points'] + $points;
                $stmt = $conn->prepare("UPDATE customer_points SET available_points = ?, total_points = ? WHERE customer_id = ?");
                $stmt->execute([$balance_after, $new_total, $customer_id]);
                $trans_type = 'bonus';
            } else {
                if ($points > $balance_before) {
                    throw new Exception("Không đủ điểm để trừ!");
                }
                $balance_after = $balance_before - $points;
                $stmt = $conn->prepare("UPDATE customer_points SET available_points = ? WHERE customer_id = ?");
                $stmt->execute([$balance_after, $customer_id]);
                $trans_type = 'adjust';
                $points = -$points;
            }
            
            // Ghi lịch sử
            $stmt = $conn->prepare("INSERT INTO point_transactions (customer_id, type, points, balance_before, balance_after, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$customer_id, $trans_type, $points, $balance_before, $balance_after, $reason ?: 'Admin điều chỉnh']);
            
            // Cập nhật tier
            updateCustomerTier($conn, $customer_id);
            
            $conn->commit();
            $message = "Điều chỉnh điểm thành công!";
        } catch (Exception $e) {
            $conn->rollBack();
            $error = $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'save_settings') {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $conn->prepare("UPDATE point_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        $message = "Lưu cấu hình thành công!";
    }
}

function updateCustomerTier($conn, $customer_id) {
    $stmt = $conn->prepare("SELECT total_points FROM customer_points WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $points = $stmt->fetchColumn() ?: 0;
    
    // Lấy ngưỡng tier
    $settings = $conn->query("SELECT setting_key, setting_value FROM point_settings WHERE setting_key LIKE 'tier_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $tier = 'bronze';
    if ($points >= ($settings['tier_diamond'] ?? 50000)) $tier = 'diamond';
    elseif ($points >= ($settings['tier_platinum'] ?? 15000)) $tier = 'platinum';
    elseif ($points >= ($settings['tier_gold'] ?? 5000)) $tier = 'gold';
    elseif ($points >= ($settings['tier_silver'] ?? 1000)) $tier = 'silver';
    
    $stmt = $conn->prepare("UPDATE customer_points SET tier = ? WHERE customer_id = ?");
    $stmt->execute([$tier, $customer_id]);
}

// Lấy filter từ URL
$search = trim($_GET['search'] ?? '');
$tier_filter = $_GET['tier'] ?? '';

// Lấy danh sách khách hàng có điểm
$sql = "
    SELECT c.id, c.full_name, c.email, c.phone,
           COALESCE(cp.total_points, 0) as total_points,
           COALESCE(cp.available_points, 0) as available_points,
           COALESCE(cp.used_points, 0) as used_points,
           COALESCE(cp.tier, 'bronze') as tier
    FROM customers c
    LEFT JOIN customer_points cp ON c.id = cp.customer_id
    WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($tier_filter) {
    $sql .= " AND cp.tier = ?";
    $params[] = $tier_filter;
}

$sql .= " ORDER BY cp.total_points DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy cấu hình
$settings = $conn->query("SELECT * FROM point_settings ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = $conn->query("SELECT 
    COUNT(DISTINCT customer_id) as total_members,
    SUM(available_points) as total_available,
    SUM(used_points) as total_used
FROM customer_points")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Điểm Tích Lũy - Admin</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Theme sáng với màu sắc rõ nét */
    .points-page { padding: 20px; background: #f8fafc; min-height: 100vh; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { font-size: 24px; font-weight: 700; color: #059669; margin: 0; }
    .page-title i { margin-right: 10px; color: #059669; }

    .tabs { display: flex; gap: 8px; margin-bottom: 24px; }
    .tab-btn { padding: 10px 20px; background: #ffffff; border: 2px solid #e2e8f0; border-radius: 8px; color: #64748b; cursor: pointer; font-weight: 600; transition: all 0.3s; }
    .tab-btn:hover { background: #f0fdf4; color: #059669; border-color: #86efac; }
    .tab-btn.active { background: #059669; color: white; border-color: #059669; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #ffffff; border-radius: 12px; padding: 20px; border: 2px solid #e2e8f0; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12); }
    .stat-card.clickable { cursor: pointer; }
    .stat-card.clickable:hover { border-color: #059669; }
    .stat-card.clickable:active { transform: scale(0.98); }
    .stat-card .stat-value { font-size: 32px; font-weight: 800; }
    .stat-card .stat-label { color: #475569; font-size: 14px; margin-top: 4px; font-weight: 500; display: flex; align-items: center; gap: 6px; }
    .stat-card.yellow .stat-value { color: #d97706; }
    .stat-card.yellow.clickable:hover { border-color: #d97706; background: #fffbeb; }
    .stat-card.green .stat-value { color: #059669; }
    .stat-card.green.clickable:hover { border-color: #059669; background: #ecfdf5; }
    .stat-card.blue .stat-value { color: #2563eb; }
    .stat-card.blue.clickable:hover { border-color: #2563eb; background: #eff6ff; }

    .customer-table { width: 100%; background: #ffffff; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .customer-table th { background: #f1f5f9; padding: 14px 16px; text-align: left; color: #334155; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #e2e8f0; }
    .customer-table td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
    .customer-table tr:hover { background: #f0fdf4; }

    .customer-name { font-weight: 700; color: #1e293b; }
    .customer-email { font-size: 13px; color: #64748b; }

    .tier-badge { padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
    .tier-badge.bronze { background: #fef3c7; color: #b45309; border: 2px solid #f59e0b; }
    .tier-badge.silver { background: #f1f5f9; color: #475569; border: 2px solid #94a3b8; }
    .tier-badge.gold { background: #fef9c3; color: #a16207; border: 2px solid #eab308; }
    .tier-badge.platinum { background: #ede9fe; color: #7c3aed; border: 2px solid #a78bfa; }
    .tier-badge.diamond { background: #cffafe; color: #0891b2; border: 2px solid #06b6d4; }

    .points-value { font-weight: 800; color: #059669; font-size: 17px; }
    .points-sub { font-size: 13px; color: #64748b; font-weight: 500; }

    .btn-adjust { padding: 7px 14px; background: #ecfdf5; color: #059669; border: 2px solid #34d399; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.3s; }
    .btn-adjust:hover { background: #059669; color: white; transform: translateY(-2px); }

    /* Search & Filter Bar */
    .search-filter-bar { background: #ffffff; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .filter-form { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .search-box { position: relative; flex: 1; min-width: 250px; }
    .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748b; }
    .search-box input { width: 100%; padding: 12px 14px 12px 42px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; color: #1e293b; font-size: 14px; box-sizing: border-box; transition: all 0.3s; }
    .search-box input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
    .search-box input::placeholder { color: #94a3b8; }
    .tier-filter select { padding: 12px 16px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; color: #1e293b; font-size: 14px; cursor: pointer; min-width: 150px; transition: all 0.3s; }
    .tier-filter select:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
    .btn-search { padding: 12px 20px; background: #059669; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); }
    .btn-search:hover { background: #047857; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4); }
    .btn-clear { padding: 12px 16px; background: #fef2f2; color: #dc2626; border: 2px solid #fca5a5; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all 0.3s; }
    .btn-clear:hover { background: #dc2626; color: white; }
    .filter-result { padding: 12px 16px; background: #ecfdf5; border: 2px solid #34d399; border-radius: 8px; margin-bottom: 16px; color: #059669; font-size: 14px; font-weight: 600; }
    .filter-result i { margin-right: 8px; }

    /* Settings */
    .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    .settings-card { background: #ffffff; border-radius: 12px; padding: 20px; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .settings-card h4 { color: #059669; margin: 0 0 16px 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
    .setting-row:last-child { border-bottom: none; }
    .setting-label { color: #334155; font-size: 14px; font-weight: 500; }
    .setting-input { width: 120px; padding: 8px 12px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 6px; color: #1e293b; text-align: right; font-weight: 600; transition: all 0.3s; }
    .setting-input:focus { border-color: #059669; outline: none; }

    .btn-save { padding: 12px 24px; background: #059669; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; margin-top: 20px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); }
    .btn-save:hover { background: #047857; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4); }

    /* Modal */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal-content { background: #ffffff; border-radius: 16px; width: 100%; max-width: 450px; border: 2px solid #e2e8f0; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2); }
    .modal-header { padding: 20px 24px; background: #059669; display: flex; justify-content: space-between; align-items: center; border-radius: 14px 14px 0 0; }
    .modal-header h3 { color: white; font-size: 18px; margin: 0; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .modal-close { background: rgba(255, 255, 255, 0.2); border: none; color: white; font-size: 20px; cursor: pointer; width: 32px; height: 32px; border-radius: 50%; transition: all 0.3s; }
    .modal-close:hover { background: rgba(255, 255, 255, 0.3); transform: rotate(90deg); }
    .modal-body { padding: 24px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; color: #334155; font-size: 13px; margin-bottom: 6px; font-weight: 700; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; color: #1e293b; box-sizing: border-box; transition: all 0.3s; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
    .btn-submit { width: 100%; padding: 14px; background: #059669; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); }
    .btn-submit:hover { background: #047857; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4); }

    .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600; }
    .alert.success { background: #ecfdf5; color: #059669; border: 2px solid #34d399; }
    .alert.error { background: #fef2f2; color: #dc2626; border: 2px solid #fca5a5; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="points-page">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-star"></i> Quản lý Điểm Tích Lũy</h1>
                </div>
                
                <?php if ($message): ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="tabs">
                    <button class="tab-btn active" onclick="showTab('members', this)"><i class="fas fa-users"></i> Thành viên</button>
                    <button class="tab-btn" onclick="showTab('settings', this)"><i class="fas fa-cog"></i> Cấu hình</button>
                </div>
                
                <!-- Tab Thành viên -->
                <div class="tab-content active" id="tab-members">
                    <div class="stats-row">
                        <div class="stat-card yellow clickable" onclick="showStatDetail('members')">
                            <div class="stat-value"><?php echo number_format($stats['total_members'] ?? 0); ?></div>
                            <div class="stat-label"><i class="fas fa-users"></i> Thành viên có điểm</div>
                        </div>
                        <div class="stat-card green clickable" onclick="showStatDetail('available')">
                            <div class="stat-value"><?php echo number_format($stats['total_available'] ?? 0); ?></div>
                            <div class="stat-label"><i class="fas fa-coins"></i> Tổng điểm khả dụng</div>
                        </div>
                        <div class="stat-card blue clickable" onclick="showStatDetail('used')">
                            <div class="stat-value"><?php echo number_format($stats['total_used'] ?? 0); ?></div>
                            <div class="stat-label"><i class="fas fa-gift"></i> Điểm đã sử dụng</div>
                        </div>
                    </div>
                    
                    <!-- Thanh tìm kiếm -->
                    <div class="search-filter-bar">
                        <form method="GET" class="filter-form">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Tìm theo tên, email, SĐT..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="tier-filter">
                                <select name="tier" onchange="this.form.submit()">
                                    <option value="">Tất cả hạng</option>
                                    <option value="bronze" <?php echo $tier_filter === 'bronze' ? 'selected' : ''; ?>>🥉 Bronze</option>
                                    <option value="silver" <?php echo $tier_filter === 'silver' ? 'selected' : ''; ?>>🥈 Silver</option>
                                    <option value="gold" <?php echo $tier_filter === 'gold' ? 'selected' : ''; ?>>🥇 Gold</option>
                                    <option value="platinum" <?php echo $tier_filter === 'platinum' ? 'selected' : ''; ?>>💎 Platinum</option>
                                    <option value="diamond" <?php echo $tier_filter === 'diamond' ? 'selected' : ''; ?>>👑 Diamond</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-search"><i class="fas fa-search"></i> Tìm kiếm</button>
                            <?php if ($search || $tier_filter): ?>
                            <a href="points.php" class="btn-clear"><i class="fas fa-times"></i> Xóa lọc</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <?php if ($search || $tier_filter): ?>
                    <div class="filter-result">
                        <i class="fas fa-filter"></i> 
                        Tìm thấy <strong><?php echo count($customers); ?></strong> kết quả
                        <?php if ($search): ?> cho "<strong><?php echo htmlspecialchars($search); ?></strong>"<?php endif; ?>
                        <?php if ($tier_filter): ?> - Hạng: <span class="tier-badge <?php echo $tier_filter; ?>"><?php echo ucfirst($tier_filter); ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <table class="customer-table">
                        <thead>
                            <tr>
                                <th>Khách hàng</th>
                                <th>Hạng</th>
                                <th>Tổng điểm</th>
                                <th>Khả dụng</th>
                                <th>Đã dùng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                            <tr>
                                <td>
                                    <div class="customer-name"><?php echo htmlspecialchars($c['full_name']); ?></div>
                                    <div class="customer-email"><?php echo htmlspecialchars($c['email']); ?></div>
                                </td>
                                <td><span class="tier-badge <?php echo $c['tier']; ?>"><?php echo ucfirst($c['tier']); ?></span></td>
                                <td><span class="points-value"><?php echo number_format($c['total_points']); ?></span></td>
                                <td>
                                    <span class="points-value" style="color:#059669;"><?php echo number_format($c['available_points']); ?></span>
                                </td>
                                <td>
                                    <span class="points-sub"><?php echo number_format($c['used_points']); ?></span>
                                </td>
                                <td>
                                    <button class="btn-adjust" onclick="openAdjustModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['full_name']); ?>', '<?php echo htmlspecialchars($c['email']); ?>', '<?php echo $c['tier']; ?>', <?php echo $c['available_points']; ?>)">
                                        <i class="fas fa-edit"></i> Điều chỉnh
                                    </button>
                                    <button class="btn-adjust" style="background:#eff6ff;color:#2563eb;border:2px solid #60a5fa;margin-left:6px;" onclick="openHistoryModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['full_name']); ?>')">
                                        <i class="fas fa-history"></i> Lịch sử
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Tab Cấu hình -->
                <div class="tab-content" id="tab-settings">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_settings">
                        
                        <div class="settings-grid">
                            <div class="settings-card">
                                <h4><i class="fas fa-coins"></i> Quy đổi điểm</h4>
                                <?php foreach ($settings as $s): ?>
                                <?php if (in_array($s['setting_key'], ['points_per_order', 'points_to_money', 'min_redeem_points', 'max_redeem_percent'])): ?>
                                <div class="setting-row">
                                    <span class="setting-label"><?php echo $s['description']; ?></span>
                                    <input type="number" name="settings[<?php echo $s['setting_key']; ?>]" value="<?php echo $s['setting_value']; ?>" class="setting-input">
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="settings-card">
                                <h4><i class="fas fa-crown"></i> Ngưỡng hạng thành viên</h4>
                                <?php foreach ($settings as $s): ?>
                                <?php if (strpos($s['setting_key'], 'tier_') === 0): ?>
                                <div class="setting-row">
                                    <span class="setting-label"><?php echo $s['description']; ?></span>
                                    <input type="number" name="settings[<?php echo $s['setting_key']; ?>]" value="<?php echo $s['setting_value']; ?>" class="setting-input">
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="settings-card">
                                <h4><i class="fas fa-gift"></i> Bonus điểm theo hạng</h4>
                                <?php foreach ($settings as $s): ?>
                                <?php if (strpos($s['setting_key'], 'bonus_') === 0): ?>
                                <div class="setting-row">
                                    <span class="setting-label"><?php echo $s['description']; ?></span>
                                    <input type="number" name="settings[<?php echo $s['setting_key']; ?>]" value="<?php echo $s['setting_value']; ?>" class="setting-input">
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Lưu cấu hình</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Điều chỉnh điểm -->
    <div class="modal-overlay" id="adjustModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Điều chỉnh điểm</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="adjust">
                    <input type="hidden" name="customer_id" id="adjustCustomerId">
                    
                    <!-- Thông tin khách hàng -->
                    <div class="customer-info-box" style="background:#f0fdf4;border:2px solid #34d399;border-radius:12px;padding:16px;margin-bottom:20px;">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                            <div style="width:48px;height:48px;background:#059669;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-user" style="color:white;font-size:20px;"></i>
                            </div>
                            <div>
                                <div id="adjustCustomerName" style="font-weight:700;color:#1e293b;font-size:16px;"></div>
                                <div id="adjustCustomerEmail" style="font-size:13px;color:#64748b;"></div>
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;">
                            <div style="flex:1;min-width:120px;background:white;border-radius:8px;padding:10px;text-align:center;">
                                <div style="font-size:12px;color:#64748b;margin-bottom:4px;">Hạng thành viên</div>
                                <span id="adjustCustomerTier" class="tier-badge"></span>
                            </div>
                            <div style="flex:1;min-width:120px;background:white;border-radius:8px;padding:10px;text-align:center;">
                                <div style="font-size:12px;color:#64748b;margin-bottom:4px;">Điểm hiện tại</div>
                                <div id="adjustCurrentPoints" style="font-weight:800;color:#059669;font-size:18px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Thao tác</label>
                        <select name="type" id="adjustType" onchange="updatePreview()">
                            <option value="add">➕ Cộng điểm</option>
                            <option value="subtract">➖ Trừ điểm</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Số điểm</label>
                        <input type="number" name="points" id="adjustPoints" required min="1" placeholder="Nhập số điểm" oninput="updatePreview()">
                    </div>
                    
                    <!-- Preview điểm sau điều chỉnh -->
                    <div id="previewBox" style="background:#eff6ff;border:2px solid #60a5fa;border-radius:8px;padding:12px;margin-bottom:16px;display:none;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:#2563eb;font-weight:600;"><i class="fas fa-calculator"></i> Điểm sau điều chỉnh:</span>
                            <span id="previewPoints" style="font-weight:800;font-size:20px;color:#2563eb;"></span>
                        </div>
                        <div id="previewChange" style="font-size:13px;margin-top:6px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Lý do</label>
                        <textarea name="reason" rows="2" placeholder="VD: Bonus sinh nhật, Điều chỉnh lỗi..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Xác nhận điều chỉnh</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Lịch sử giao dịch -->
    <div class="modal-overlay" id="historyModal">
        <div class="modal-content" style="max-width:550px;">
            <div class="modal-header">
                <h3><i class="fas fa-history"></i> Lịch sử điểm - <span id="historyCustomerName"></span></h3>
                <button class="modal-close" onclick="closeHistoryModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding:0;">
                <div id="historyList" class="history-list-container"></div>
            </div>
        </div>
    </div>

    <!-- Modal Chi tiết thống kê -->
    <div class="modal-overlay" id="statDetailModal">
        <div class="modal-content" style="max-width:600px;">
            <div class="modal-header" id="statDetailHeader">
                <h3><i class="fas fa-chart-bar"></i> <span id="statDetailTitle">Chi tiết</span></h3>
                <button class="modal-close" onclick="closeStatDetailModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding:0;">
                <div id="statDetailContent" class="stat-detail-container"></div>
            </div>
        </div>
    </div>

    <style>
    .history-list-container { max-height: 400px; overflow-y: auto; background: #ffffff; }
    .history-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; border-bottom: 1px solid #e2e8f0; }
    .history-item:last-child { border-bottom: none; }
    .history-info { flex: 1; }
    .history-type { font-weight: 700; color: #1e293b; font-size: 14px; }
    .history-desc { font-size: 13px; color: #64748b; margin-top: 2px; }
    .history-order { font-size: 12px; color: #7c3aed; font-weight: 600; margin-top: 2px; }
    .history-date { font-size: 12px; color: #94a3b8; margin-top: 4px; }
    .history-points { font-weight: 800; font-size: 17px; }
    .history-points.earn { color: #059669; }
    .history-points.redeem { color: #dc2626; }
    
    /* Stat Detail Modal */
    .stat-detail-container { max-height: 450px; overflow-y: auto; background: #ffffff; }
    .stat-detail-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; border-bottom: 1px solid #e2e8f0; transition: background 0.2s; }
    .stat-detail-item:hover { background: #f8fafc; }
    .stat-detail-item:last-child { border-bottom: none; }
    .stat-detail-info { flex: 1; }
    .stat-detail-name { font-weight: 700; color: #1e293b; font-size: 14px; }
    .stat-detail-email { font-size: 12px; color: #64748b; margin-top: 2px; }
    .stat-detail-value { font-weight: 800; font-size: 18px; }
    .stat-detail-value.yellow { color: #d97706; }
    .stat-detail-value.green { color: #059669; }
    .stat-detail-value.blue { color: #2563eb; }
    .stat-summary { padding: 16px 24px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .stat-summary-label { font-weight: 600; color: #64748b; }
    .stat-summary-value { font-weight: 800; font-size: 24px; }
    </style>

    <script>
    // Scroll đến nội dung chính khi trang load
    document.addEventListener('DOMContentLoaded', function() {
        const mainContent = document.querySelector('.points-page');
        if (mainContent) {
            mainContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
    
    let currentPoints = 0;
    
    function showTab(tab, btn) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        btn.classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
    }

    function openAdjustModal(id, name, email, tier, points) {
        currentPoints = points;
        document.getElementById('adjustCustomerId').value = id;
        document.getElementById('adjustCustomerName').textContent = name;
        document.getElementById('adjustCustomerEmail').textContent = email;
        document.getElementById('adjustCurrentPoints').textContent = points.toLocaleString() + ' điểm';
        
        // Hiển thị tier badge
        const tierBadge = document.getElementById('adjustCustomerTier');
        const tierNames = {
            'bronze': '🥉 Bronze',
            'silver': '🥈 Silver', 
            'gold': '🥇 Gold',
            'platinum': '💎 Platinum',
            'diamond': '👑 Diamond'
        };
        tierBadge.className = 'tier-badge ' + tier;
        tierBadge.textContent = tierNames[tier] || tier;
        
        // Reset form
        document.getElementById('adjustType').value = 'add';
        document.getElementById('adjustPoints').value = '';
        document.getElementById('previewBox').style.display = 'none';
        
        document.getElementById('adjustModal').classList.add('show');
    }
    
    function updatePreview() {
        const type = document.getElementById('adjustType').value;
        const pointsInput = document.getElementById('adjustPoints').value;
        const previewBox = document.getElementById('previewBox');
        
        if (!pointsInput || pointsInput <= 0) {
            previewBox.style.display = 'none';
            return;
        }
        
        const points = parseInt(pointsInput);
        let newPoints, changeText;
        
        if (type === 'add') {
            newPoints = currentPoints + points;
            changeText = '<span style="color:#059669;">+' + points.toLocaleString() + ' điểm</span>';
        } else {
            newPoints = currentPoints - points;
            changeText = '<span style="color:#dc2626;">-' + points.toLocaleString() + ' điểm</span>';
            
            if (newPoints < 0) {
                changeText += ' <span style="color:#dc2626;font-weight:600;">(⚠️ Không đủ điểm!)</span>';
                newPoints = 0;
            }
        }
        
        document.getElementById('previewPoints').textContent = newPoints.toLocaleString() + ' điểm';
        document.getElementById('previewChange').innerHTML = currentPoints.toLocaleString() + ' → ' + newPoints.toLocaleString() + ' (' + changeText + ')';
        previewBox.style.display = 'block';
    }

    function closeModal() {
        document.getElementById('adjustModal').classList.remove('show');
    }

    document.getElementById('adjustModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Modal lịch sử
    function openHistoryModal(customerId, customerName) {
        document.getElementById('historyCustomerName').textContent = customerName;
        document.getElementById('historyModal').classList.add('show');
        loadHistory(customerId);
    }

    function closeHistoryModal() {
        document.getElementById('historyModal').classList.remove('show');
    }

    document.getElementById('historyModal').addEventListener('click', function(e) {
        if (e.target === this) closeHistoryModal();
    });

    function loadHistory(customerId) {
        const container = document.getElementById('historyList');
        container.innerHTML = '<div style="text-align:center;padding:20px;color:#888;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
        
        fetch('../api/points.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=admin_history&customer_id=' + customerId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.history && data.history.length > 0) {
                let html = '';
                data.history.forEach(h => {
                    const isEarn = ['earn', 'bonus'].includes(h.type);
                    const types = {
                        'earn': '🛒 Tích điểm',
                        'redeem': '🎁 Đổi điểm',
                        'bonus': '🎉 Bonus',
                        'expire': '⏰ Hết hạn',
                        'adjust': '⚙️ Điều chỉnh'
                    };
                    html += `
                        <div class="history-item">
                            <div class="history-info">
                                <div class="history-type">${types[h.type] || h.type}</div>
                                <div class="history-desc">${h.description || ''}</div>
                                ${h.order_number ? `<div class="history-order">Đơn: ${h.order_number}</div>` : ''}
                                <div class="history-date">${h.created_at_formatted}</div>
                            </div>
                            <div class="history-points ${isEarn ? 'earn' : 'redeem'}">
                                ${isEarn ? '+' : ''}${h.points.toLocaleString()}
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div style="text-align:center;padding:40px;color:#64748b;"><i class="fas fa-inbox" style="font-size:32px;display:block;margin-bottom:12px;color:#94a3b8;"></i>Chưa có giao dịch nào</div>';
            }
        })
        .catch(err => {
            console.error('Error:', err);
            container.innerHTML = '<div style="text-align:center;padding:20px;color:#dc2626;">Lỗi tải dữ liệu</div>';
        });
    }
    
    // Dữ liệu khách hàng từ PHP
    const customersData = <?php echo json_encode($customers); ?>;
    
    // Hiển thị chi tiết thống kê
    function showStatDetail(type) {
        const modal = document.getElementById('statDetailModal');
        const header = document.getElementById('statDetailHeader');
        const title = document.getElementById('statDetailTitle');
        const container = document.getElementById('statDetailContent');
        
        let headerColor, titleText, valueField, valueClass, icon, totalValue = 0;
        
        switch(type) {
            case 'members':
                headerColor = '#d97706';
                titleText = 'Danh sách thành viên có điểm';
                valueField = 'total_points';
                valueClass = 'yellow';
                icon = 'fa-users';
                break;
            case 'available':
                headerColor = '#059669';
                titleText = 'Chi tiết điểm khả dụng';
                valueField = 'available_points';
                valueClass = 'green';
                icon = 'fa-coins';
                break;
            case 'used':
                headerColor = '#2563eb';
                titleText = 'Chi tiết điểm đã sử dụng';
                valueField = 'used_points';
                valueClass = 'blue';
                icon = 'fa-gift';
                break;
        }
        
        header.style.background = headerColor;
        title.innerHTML = '<i class="fas ' + icon + '"></i> ' + titleText;
        
        // Lọc và sắp xếp dữ liệu
        let filteredData = customersData.filter(c => c[valueField] > 0);
        filteredData.sort((a, b) => b[valueField] - a[valueField]);
        
        // Tính tổng
        filteredData.forEach(c => totalValue += parseInt(c[valueField]));
        
        let html = '';
        
        // Summary
        html += `
            <div class="stat-summary">
                <span class="stat-summary-label"><i class="fas ${icon}"></i> Tổng cộng (${filteredData.length} thành viên)</span>
                <span class="stat-summary-value" style="color:${headerColor}">${totalValue.toLocaleString()}</span>
            </div>
        `;
        
        if (filteredData.length > 0) {
            filteredData.forEach((c, index) => {
                html += `
                    <div class="stat-detail-item">
                        <div class="stat-detail-info">
                            <div class="stat-detail-name">${index + 1}. ${c.full_name}</div>
                            <div class="stat-detail-email">${c.email}</div>
                        </div>
                        <div class="stat-detail-value ${valueClass}">${parseInt(c[valueField]).toLocaleString()}</div>
                    </div>
                `;
            });
        } else {
            html += '<div style="text-align:center;padding:40px;color:#64748b;"><i class="fas fa-inbox" style="font-size:32px;display:block;margin-bottom:12px;color:#94a3b8;"></i>Không có dữ liệu</div>';
        }
        
        container.innerHTML = html;
        modal.classList.add('show');
    }
    
    function closeStatDetailModal() {
        document.getElementById('statDetailModal').classList.remove('show');
    }
    
    document.getElementById('statDetailModal').addEventListener('click', function(e) {
        if (e.target === this) closeStatDetailModal();
    });
    </script>
</body>
</html>
