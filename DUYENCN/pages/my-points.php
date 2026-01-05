<?php
/**
 * Trang Điểm tích lũy của khách hàng
 */
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();
$customer_id = $_SESSION['customer_id'];

// Lấy thông tin điểm
$stmt = $conn->prepare("SELECT * FROM customer_points WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$points = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$points) {
    $points = ['total_points' => 0, 'available_points' => 0, 'used_points' => 0, 'tier' => 'bronze'];
}

// Lấy cấu hình
$settings = $conn->query("SELECT setting_key, setting_value FROM point_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$points_to_money = intval($settings['points_to_money'] ?? 100);
$points_per_order = intval($settings['points_per_order'] ?? 1000);
$max_redeem_percent = intval($settings['max_redeem_percent'] ?? 50);

// Lọc lịch sử
$filter_type = $_GET['filter'] ?? 'all';
$page = max(1, intval($_GET['history_page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Đếm tổng số giao dịch
$count_sql = "SELECT COUNT(*) FROM point_transactions WHERE customer_id = ?";
$count_params = [$customer_id];
if ($filter_type !== 'all') {
    $count_sql .= " AND type = ?";
    $count_params[] = $filter_type;
}
$stmt = $conn->prepare($count_sql);
$stmt->execute($count_params);
$total_transactions = $stmt->fetchColumn();
$total_pages = ceil($total_transactions / $per_page);

// Lấy lịch sử giao dịch với phân trang
$history_sql = "
    SELECT pt.*, o.order_number 
    FROM point_transactions pt
    LEFT JOIN orders o ON pt.order_id = o.id
    WHERE pt.customer_id = ?
";
$history_params = [$customer_id];
if ($filter_type !== 'all') {
    $history_sql .= " AND pt.type = ?";
    $history_params[] = $filter_type;
}
$history_sql .= " ORDER BY pt.created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($history_sql);
$stmt->execute($history_params);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê theo tháng
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN type = 'earn' THEN points ELSE 0 END) as earned_this_month,
        SUM(CASE WHEN type = 'redeem' THEN points ELSE 0 END) as redeemed_this_month
    FROM point_transactions 
    WHERE customer_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$stmt->execute([$customer_id]);
$monthly_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Tính tier info
$tier_thresholds = [
    'silver' => intval($settings['tier_silver'] ?? 1000),
    'gold' => intval($settings['tier_gold'] ?? 5000),
    'platinum' => intval($settings['tier_platinum'] ?? 15000),
    'diamond' => intval($settings['tier_diamond'] ?? 50000),
];

$tier_colors = [
    'bronze' => '#b45309',
    'silver' => '#9ca3af',
    'gold' => '#fbbf24',
    'platinum' => '#a78bfa',
    'diamond' => '#06b6d4'
];

$tier_bonuses = [
    'bronze' => 0,
    'silver' => intval($settings['bonus_silver'] ?? 5),
    'gold' => intval($settings['bonus_gold'] ?? 10),
    'platinum' => intval($settings['bonus_platinum'] ?? 15),
    'diamond' => intval($settings['bonus_diamond'] ?? 25)
];

// Tính next tier
$next_tier = null;
$progress = 100;
foreach ($tier_thresholds as $tier => $threshold) {
    if ($points['total_points'] < $threshold) {
        $next_tier = ['tier' => $tier, 'points_needed' => $threshold - $points['total_points'], 'threshold' => $threshold];
        $prev_threshold = 0;
        $tiers = array_keys($tier_thresholds);
        $idx = array_search($tier, $tiers);
        if ($idx > 0) {
            $prev_threshold = $tier_thresholds[$tiers[$idx - 1]];
        }
        $progress = (($points['total_points'] - $prev_threshold) / ($threshold - $prev_threshold)) * 100;
        break;
    }
}
?>

<style>
.points-page { 
    padding: 2rem 1.5rem 4rem; 
    max-width: 900px; 
    margin: 0 auto; 
    min-height: 100vh;
    background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 100%);
}

.points-header { text-align: center; margin-bottom: 2rem; }
.points-header h1 { 
    font-size: 1.75rem; 
    color: #1f2937; 
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}
.points-header h1 i { color: #22c55e; }
.points-header p { color: #4b5563; margin: 0; }

.points-card { 
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 50%, #15803d 100%); 
    border-radius: 20px; 
    padding: 30px; 
    margin-bottom: 24px; 
    border: none;
    position: relative; 
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(34, 197, 94, 0.3);
}
.points-card::before { 
    content: ''; 
    position: absolute; 
    top: -50%; 
    right: -50%; 
    width: 100%; 
    height: 100%; 
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%); 
}

.tier-badge-large { 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    padding: 8px 20px; 
    border-radius: 30px; 
    font-size: 14px; 
    font-weight: 700; 
    text-transform: uppercase; 
    margin-bottom: 20px;
    background: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}
.tier-badge-large i { font-size: 18px; }

.points-value-large { font-size: 56px; font-weight: 800; color: #fff; line-height: 1; }
.points-value-large span { font-size: 24px; color: rgba(255,255,255,0.8); font-weight: 400; }
.points-equivalent { color: #fef08a; font-size: 18px; margin-top: 8px; font-weight: 600; }

.points-stats { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 16px; 
    margin-top: 24px; 
    padding-top: 24px; 
    border-top: 1px solid rgba(255,255,255,0.2); 
}
.stat-item { text-align: center; }
.stat-item .value { font-size: 24px; font-weight: 700; color: #fff; }
.stat-item .label { font-size: 13px; color: #ffffff; margin-top: 4px; font-weight: 500; }

/* Progress to next tier */
.tier-progress { 
    background: #ffffff; 
    border-radius: 16px; 
    padding: 24px; 
    margin-bottom: 24px; 
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
.tier-progress h3 { color: #1f2937; font-size: 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.tier-progress h3 i { color: #22c55e; }
.progress-bar { height: 12px; background: #f3f4f6; border-radius: 6px; overflow: hidden; margin-bottom: 12px; }
.progress-fill { height: 100%; border-radius: 6px; transition: width 0.5s ease; }
.progress-info { display: flex; justify-content: space-between; font-size: 14px; color: #374151; }
.progress-info strong { color: #111827; }

/* Tier benefits */
.tier-benefits { 
    background: #ffffff; 
    border-radius: 16px; 
    padding: 24px; 
    margin-bottom: 24px; 
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
.tier-benefits h3 { color: #1f2937; font-size: 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.tier-benefits h3 i { color: #f59e0b; }
.tiers-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
.tier-item { 
    padding: 16px; 
    border-radius: 12px; 
    text-align: center; 
    border: 2px solid #f3f4f6; 
    transition: all 0.3s;
    background: #fafafa;
}
.tier-item.current { 
    border-color: currentColor; 
    background: linear-gradient(135deg, rgba(34,197,94,0.08) 0%, rgba(34,197,94,0.03) 100%);
    box-shadow: 0 4px 15px rgba(34,197,94,0.15);
}
.tier-item .tier-icon { font-size: 24px; margin-bottom: 8px; }
.tier-item .tier-name { font-weight: 700; font-size: 14px; margin-bottom: 4px; color: #111827; }
.tier-item .tier-bonus { font-size: 12px; color: #374151; font-weight: 500; }
.tier-item .tier-threshold { font-size: 11px; color: #4b5563; margin-top: 4px; }

/* History */
.history-section { 
    background: #ffffff; 
    border-radius: 16px; 
    padding: 24px; 
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
.history-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    user-select: none;
}
.history-toggle h3 { 
    color: #1f2937; 
    font-size: 16px; 
    margin: 0;
    display: flex; 
    align-items: center; 
    gap: 10px; 
}
.history-toggle h3 i { color: #22c55e; }
.history-toggle .toggle-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #374151;
    transition: all 0.3s;
}
.history-toggle:hover .toggle-icon {
    background: #22c55e;
    color: #fff;
}
.history-toggle .toggle-icon i {
    transition: transform 0.3s;
}
.history-section.expanded .history-toggle .toggle-icon i {
    transform: rotate(180deg);
}
.history-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease, margin-top 0.3s ease;
}
.history-section.expanded .history-content {
    max-height: 2000px;
    margin-top: 16px;
}
.history-list { max-height: 400px; overflow-y: auto; }
.history-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #f3f4f6; }
.history-item:last-child { border-bottom: none; }
.history-info { flex: 1; }
.history-type { font-weight: 600; color: #111827; font-size: 14px; }
.history-desc { font-size: 13px; color: #374151; margin-top: 2px; }
.history-date { font-size: 12px; color: #4b5563; margin-top: 4px; }
.history-points { font-weight: 700; font-size: 16px; }
.history-points.earn { color: #16a34a; }
.history-points.redeem { color: #dc2626; }

.empty-history { text-align: center; padding: 40px; color: #374151; }
.empty-history i { font-size: 48px; color: #9ca3af; margin-bottom: 16px; display: block; }

/* How it works */
.how-it-works { 
    background: linear-gradient(135deg, rgba(34,197,94,0.08) 0%, rgba(34,197,94,0.05) 100%); 
    border-radius: 16px; 
    padding: 24px; 
    margin-bottom: 24px; 
    border: 1px solid rgba(34,197,94,0.2);
}
.how-it-works h3 { color: #16a34a; font-size: 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.how-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
.how-item { display: flex; gap: 12px; }
.how-icon { 
    width: 40px; 
    height: 40px; 
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); 
    border-radius: 10px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: #fff; 
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(34,197,94,0.3);
}
.how-text h4 { color: #111827; font-size: 14px; margin-bottom: 4px; font-weight: 600; }
.how-text p { color: #374151; font-size: 13px; line-height: 1.4; }

@media (max-width: 600px) {
    .points-value-large { font-size: 40px; }
    .points-stats { grid-template-columns: 1fr; gap: 12px; }
    .tiers-grid { grid-template-columns: repeat(2, 1fr); }
}

/* Quick Actions */
.quick-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.quick-action-btn {
    flex: 1;
    min-width: 200px;
    padding: 16px 20px;
    border-radius: 12px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s;
    text-decoration: none;
}
.quick-action-btn.primary {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(34,197,94,0.3);
}
.quick-action-btn.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34,197,94,0.4);
}
.quick-action-btn.secondary {
    background: #fff;
    color: #16a34a;
    border: 2px solid #22c55e;
}
.quick-action-btn.secondary:hover {
    background: rgba(34,197,94,0.1);
}

/* Monthly Stats */
.monthly-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.monthly-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.monthly-stat-card .stat-label {
    font-size: 13px;
    color: #374151;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}
.monthly-stat-card .stat-value {
    font-size: 24px;
    font-weight: 700;
}
.monthly-stat-card .stat-value.positive { color: #16a34a; }
.monthly-stat-card .stat-value.negative { color: #dc2626; }

/* History Filter */
.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}
.history-header h3 {
    margin: 0;
}
.history-filter {
    display: inline-flex !important;
    gap: 6px !important;
    flex-wrap: nowrap !important;
    background: #f3f4f6;
    padding: 4px;
    border-radius: 25px;
}
.filter-btn {
    padding: 6px 12px !important;
    border-radius: 20px !important;
    border: none !important;
    background: transparent !important;
    font-size: 12px !important;
    color: #374151 !important;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none !important;
    white-space: nowrap;
    display: inline-block !important;
    font-weight: 500 !important;
}
.filter-btn:hover {
    color: #16a34a !important;
}
.filter-btn.active {
    background: #22c55e !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(34,197,94,0.3);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
    flex-wrap: wrap;
}
.pagination a, .pagination span {
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s;
}
.pagination a {
    background: #fff;
    color: #374151;
    border: 1px solid #e5e7eb;
}
.pagination a:hover {
    border-color: #22c55e;
    color: #22c55e;
}
.pagination .current {
    background: #22c55e;
    color: #fff;
    border: 1px solid #22c55e;
}
.pagination .disabled {
    color: #d1d5db;
    cursor: not-allowed;
}

/* Tips Section */
.tips-section {
    background: linear-gradient(135deg, #fef3c7 0%, #fef9c3 100%);
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 24px;
    border: 1px solid #fcd34d;
}
.tips-section h4 {
    color: #b45309;
    font-size: 15px;
    margin: 0 0 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.tips-list li {
    font-size: 13px;
    color: #92400e;
    padding: 6px 0;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
.tips-list li i {
    color: #f59e0b;
    margin-top: 2px;
}
</style>

<div class="points-page">
    <div class="points-header">
        <h1><i class="fas fa-coins"></i> Điểm Tích Lũy</h1>
        <p>Tích điểm mỗi đơn hàng, đổi điểm lấy ưu đãi!</p>
    </div>
    
    <!-- Card điểm chính -->
    <div class="points-card">
        <span class="tier-badge-large" style="background: <?php echo $tier_colors[$points['tier']]; ?>20; color: <?php echo $tier_colors[$points['tier']]; ?>;">
            <i class="fas fa-crown"></i> <?php echo ucfirst($points['tier']); ?>
            <?php if ($tier_bonuses[$points['tier']] > 0): ?>
            <span style="font-size:12px;opacity:0.8;">+<?php echo $tier_bonuses[$points['tier']]; ?>% bonus</span>
            <?php endif; ?>
        </span>
        
        <div class="points-value-large">
            <?php echo number_format($points['available_points']); ?> <span>điểm</span>
        </div>
        <div class="points-equivalent">
            <i class="fas fa-equals"></i> <?php echo number_format($points['available_points'] * $points_to_money); ?>đ giảm giá
        </div>
        
        <div class="points-stats">
            <div class="stat-item">
                <div class="value"><?php echo number_format($points['total_points']); ?></div>
                <div class="label">Tổng tích lũy</div>
            </div>
            <div class="stat-item">
                <div class="value" style="color:#bbf7d0;"><?php echo number_format($points['available_points']); ?></div>
                <div class="label">Khả dụng</div>
            </div>
            <div class="stat-item">
                <div class="value" style="color:#d1fae5;"><?php echo number_format($points['used_points']); ?></div>
                <div class="label">Đã sử dụng</div>
            </div>
        </div>
    </div>
    
    <!-- Nút hành động nhanh -->
    <div class="quick-actions">
        <a href="?page=menu" class="quick-action-btn primary">
            <i class="fas fa-utensils"></i> Đặt món tích điểm
        </a>
        <a href="?page=vouchers" class="quick-action-btn secondary">
            <i class="fas fa-ticket-alt"></i> Đổi điểm lấy voucher
        </a>
    </div>
    
    <!-- Thống kê tháng này -->
    <div class="monthly-stats">
        <div class="monthly-stat-card">
            <div class="stat-label"><i class="fas fa-arrow-up"></i> Tích được tháng này</div>
            <div class="stat-value positive">+<?php echo number_format($monthly_stats['earned_this_month'] ?? 0); ?></div>
        </div>
        <div class="monthly-stat-card">
            <div class="stat-label"><i class="fas fa-arrow-down"></i> Đã đổi tháng này</div>
            <div class="stat-value negative">-<?php echo number_format($monthly_stats['redeemed_this_month'] ?? 0); ?></div>
        </div>
    </div>
    
    <!-- Progress đến tier tiếp theo -->
    <?php if ($next_tier): ?>
    <div class="tier-progress">
        <h3><i class="fas fa-chart-line"></i> Tiến độ lên hạng <?php echo ucfirst($next_tier['tier']); ?></h3>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo min(100, $progress); ?>%; background: <?php echo $tier_colors[$next_tier['tier']]; ?>;"></div>
        </div>
        <div class="progress-info">
            <span>Còn <strong><?php echo number_format($next_tier['points_needed']); ?></strong> điểm</span>
            <span><?php echo number_format($points['total_points']); ?> / <?php echo number_format($next_tier['threshold']); ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Mẹo tích điểm -->
    <div class="tips-section">
        <h4><i class="fas fa-lightbulb"></i> Mẹo tích điểm nhanh</h4>
        <ul class="tips-list">
            <li><i class="fas fa-check"></i> Đặt đơn hàng lớn để nhận nhiều điểm hơn (<?php echo number_format($points_per_order); ?>đ = 1 điểm)</li>
            <li><i class="fas fa-check"></i> Lên hạng cao hơn để nhận bonus điểm mỗi đơn hàng</li>
            <li><i class="fas fa-check"></i> Sử dụng điểm khi thanh toán để giảm giá tối đa <?php echo $max_redeem_percent; ?>%</li>
            <?php if ($tier_bonuses[$points['tier']] > 0): ?>
            <li><i class="fas fa-star"></i> Bạn đang được +<?php echo $tier_bonuses[$points['tier']]; ?>% bonus điểm mỗi đơn hàng!</li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Cách hoạt động -->
    <div class="how-it-works">
        <h3><i class="fas fa-info-circle"></i> Cách tích & đổi điểm</h3>
        <div class="how-grid">
            <div class="how-item">
                <div class="how-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="how-text">
                    <h4>Tích điểm</h4>
                    <p>Mỗi <?php echo number_format($points_per_order); ?>đ = 1 điểm</p>
                </div>
            </div>
            <div class="how-item">
                <div class="how-icon"><i class="fas fa-gift"></i></div>
                <div class="how-text">
                    <h4>Đổi điểm</h4>
                    <p>1 điểm = <?php echo number_format($points_to_money); ?>đ giảm giá</p>
                </div>
            </div>
            <div class="how-item">
                <div class="how-icon"><i class="fas fa-percent"></i></div>
                <div class="how-text">
                    <h4>Giới hạn</h4>
                    <p>Tối đa <?php echo $max_redeem_percent; ?>% đơn hàng</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Các hạng thành viên -->
    <div class="tier-benefits">
        <h3><i class="fas fa-crown"></i> Hạng thành viên & Ưu đãi</h3>
        <div class="tiers-grid">
            <div class="tier-item <?php echo $points['tier'] === 'bronze' ? 'current' : ''; ?>" style="color: <?php echo $tier_colors['bronze']; ?>;">
                <div class="tier-icon"><i class="fas fa-medal"></i></div>
                <div class="tier-name">Bronze</div>
                <div class="tier-bonus">Không bonus</div>
                <div class="tier-threshold">Mặc định</div>
            </div>
            <div class="tier-item <?php echo $points['tier'] === 'silver' ? 'current' : ''; ?>" style="color: <?php echo $tier_colors['silver']; ?>;">
                <div class="tier-icon"><i class="fas fa-medal"></i></div>
                <div class="tier-name">Silver</div>
                <div class="tier-bonus">+<?php echo $tier_bonuses['silver']; ?>% điểm</div>
                <div class="tier-threshold"><?php echo number_format($tier_thresholds['silver']); ?> điểm</div>
            </div>
            <div class="tier-item <?php echo $points['tier'] === 'gold' ? 'current' : ''; ?>" style="color: <?php echo $tier_colors['gold']; ?>;">
                <div class="tier-icon"><i class="fas fa-crown"></i></div>
                <div class="tier-name">Gold</div>
                <div class="tier-bonus">+<?php echo $tier_bonuses['gold']; ?>% điểm</div>
                <div class="tier-threshold"><?php echo number_format($tier_thresholds['gold']); ?> điểm</div>
            </div>
            <div class="tier-item <?php echo $points['tier'] === 'platinum' ? 'current' : ''; ?>" style="color: <?php echo $tier_colors['platinum']; ?>;">
                <div class="tier-icon"><i class="fas fa-gem"></i></div>
                <div class="tier-name">Platinum</div>
                <div class="tier-bonus">+<?php echo $tier_bonuses['platinum']; ?>% điểm</div>
                <div class="tier-threshold"><?php echo number_format($tier_thresholds['platinum']); ?> điểm</div>
            </div>
            <div class="tier-item <?php echo $points['tier'] === 'diamond' ? 'current' : ''; ?>" style="color: <?php echo $tier_colors['diamond']; ?>;">
                <div class="tier-icon"><i class="fas fa-gem"></i></div>
                <div class="tier-name">Diamond</div>
                <div class="tier-bonus">+<?php echo $tier_bonuses['diamond']; ?>% điểm</div>
                <div class="tier-threshold"><?php echo number_format($tier_thresholds['diamond']); ?> điểm</div>
            </div>
        </div>
    </div>
    
    <!-- Lịch sử giao dịch -->
    <div class="history-section" id="historySection">
        <div class="history-toggle" onclick="toggleHistory()">
            <h3><i class="fas fa-history"></i> Lịch sử giao dịch <span style="font-weight:500;color:#4b5563;font-size:13px;">(<?php echo $total_transactions; ?> giao dịch)</span></h3>
            <div class="toggle-icon">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
        
        <div class="history-content">
            <div class="history-filter" style="margin-bottom:16px;">
                <button type="button" class="filter-btn <?php echo $filter_type === 'all' ? 'active' : ''; ?>" onclick="filterHistory('all')">Tất cả</button>
                <button type="button" class="filter-btn <?php echo $filter_type === 'earn' ? 'active' : ''; ?>" onclick="filterHistory('earn')">Tích điểm</button>
                <button type="button" class="filter-btn <?php echo $filter_type === 'redeem' ? 'active' : ''; ?>" onclick="filterHistory('redeem')">Đổi điểm</button>
                <button type="button" class="filter-btn <?php echo $filter_type === 'bonus' ? 'active' : ''; ?>" onclick="filterHistory('bonus')">Bonus</button>
            </div>
            
            <div id="historyListContainer">
            <?php if (empty($history)): ?>
            <div class="empty-history">
                <i class="fas fa-receipt"></i>
                <p>Chưa có giao dịch điểm nào</p>
                <p style="font-size:13px;">Đặt hàng để bắt đầu tích điểm!</p>
                <a href="?page=menu" class="quick-action-btn primary" style="display:inline-flex;margin-top:16px;">
                    <i class="fas fa-utensils"></i> Đặt món ngay
                </a>
            </div>
            <?php else: ?>
            <div class="history-list">
                <?php foreach ($history as $h): 
                    $isEarn = in_array($h['type'], ['earn', 'bonus']);
                ?>
                <div class="history-item">
                    <div class="history-info">
                        <div class="history-type">
                            <?php 
                            $types = [
                                'earn' => '🛒 Tích điểm',
                                'redeem' => '🎁 Đổi điểm',
                                'bonus' => '🎉 Bonus',
                                'expire' => '⏰ Hết hạn',
                                'adjust' => '⚙️ Điều chỉnh'
                            ];
                            echo $types[$h['type']] ?? $h['type'];
                            ?>
                        </div>
                        <div class="history-desc"><?php echo htmlspecialchars($h['description'] ?? ''); ?></div>
                        <?php if (!empty($h['order_number'])): ?>
                        <div class="history-desc">
                            <a href="?page=orders" style="color:#22c55e;text-decoration:none;">
                                Đơn hàng: <?php echo $h['order_number']; ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="history-date"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></div>
                    </div>
                    <div class="history-points <?php echo $isEarn ? 'earn' : 'redeem'; ?>">
                        <?php echo $isEarn ? '+' : '-'; ?><?php echo number_format(abs($h['points'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination" id="historyPagination">
                <?php if ($page > 1): ?>
                <a href="javascript:void(0)" onclick="loadHistoryPage(<?php echo $page - 1; ?>)">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                <a href="javascript:void(0)" onclick="loadHistoryPage(1)">1</a>
                <?php if ($start_page > 2): ?><span class="disabled">...</span><?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="javascript:void(0)" onclick="loadHistoryPage(<?php echo $i; ?>)"><?php echo $i; ?></a>
                <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?><span class="disabled">...</span><?php endif; ?>
                <a href="javascript:void(0)" onclick="loadHistoryPage(<?php echo $total_pages; ?>)"><?php echo $total_pages; ?></a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="javascript:void(0)" onclick="loadHistoryPage(<?php echo $page + 1; ?>)">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let currentFilter = '<?php echo $filter_type; ?>';
let currentPage = <?php echo $page; ?>;

function toggleHistory() {
    const section = document.getElementById('historySection');
    section.classList.toggle('expanded');
    localStorage.setItem('pointsHistoryExpanded', section.classList.contains('expanded'));
}

// Lọc lịch sử bằng AJAX
function filterHistory(type) {
    currentFilter = type;
    currentPage = 1;
    
    // Cập nhật UI nút filter
    document.querySelectorAll('.history-filter .filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Mở phần lịch sử nếu đang đóng
    const section = document.getElementById('historySection');
    if (!section.classList.contains('expanded')) {
        section.classList.add('expanded');
        localStorage.setItem('pointsHistoryExpanded', 'true');
    }
    
    // Load dữ liệu
    loadHistory();
}

// Load trang lịch sử
function loadHistoryPage(page) {
    currentPage = page;
    loadHistory();
}

// Load lịch sử bằng AJAX
function loadHistory() {
    const container = document.getElementById('historyListContainer');
    container.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:#16a34a;"></i><p style="margin-top:10px;color:#374151;">Đang tải...</p></div>';
    
    fetch(`api/points.php?action=history&filter=${currentFilter}&page=${currentPage}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderHistory(data.data.history, data.data.total_pages, data.data.current_page);
                // Cập nhật số giao dịch
                document.querySelector('.history-toggle h3 span').textContent = `(${data.data.total} giao dịch)`;
            } else {
                container.innerHTML = '<div class="empty-history"><i class="fas fa-exclamation-circle"></i><p>Có lỗi xảy ra</p></div>';
            }
        })
        .catch(err => {
            container.innerHTML = '<div class="empty-history"><i class="fas fa-exclamation-circle"></i><p>Có lỗi xảy ra</p></div>';
        });
}

// Render danh sách lịch sử
function renderHistory(history, totalPages, currentPage) {
    const container = document.getElementById('historyListContainer');
    
    if (history.length === 0) {
        container.innerHTML = `
            <div class="empty-history">
                <i class="fas fa-receipt"></i>
                <p>Không có giao dịch nào</p>
                <p style="font-size:13px;">Thử chọn bộ lọc khác</p>
            </div>
        `;
        return;
    }
    
    const types = {
        'earn': '🛒 Tích điểm',
        'redeem': '🎁 Đổi điểm',
        'bonus': '🎉 Bonus',
        'expire': '⏰ Hết hạn',
        'adjust': '⚙️ Điều chỉnh'
    };
    
    let html = '<div class="history-list">';
    history.forEach(h => {
        const isEarn = ['earn', 'bonus'].includes(h.type);
        html += `
            <div class="history-item">
                <div class="history-info">
                    <div class="history-type">${types[h.type] || h.type}</div>
                    <div class="history-desc">${h.description || ''}</div>
                    ${h.order_number ? `<div class="history-desc"><a href="?page=orders" style="color:#22c55e;text-decoration:none;">Đơn hàng: ${h.order_number}</a></div>` : ''}
                    <div class="history-date">${h.created_at_formatted}</div>
                </div>
                <div class="history-points ${isEarn ? 'earn' : 'redeem'}">
                    ${isEarn ? '+' : '-'}${Math.abs(h.points).toLocaleString('vi-VN')}
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    // Phân trang
    if (totalPages > 1) {
        html += '<div class="pagination">';
        if (currentPage > 1) {
            html += `<a href="javascript:void(0)" onclick="loadHistoryPage(${currentPage - 1})"><i class="fas fa-chevron-left"></i></a>`;
        }
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            html += `<a href="javascript:void(0)" onclick="loadHistoryPage(1)">1</a>`;
            if (startPage > 2) html += '<span class="disabled">...</span>';
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                html += `<span class="current">${i}</span>`;
            } else {
                html += `<a href="javascript:void(0)" onclick="loadHistoryPage(${i})">${i}</a>`;
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) html += '<span class="disabled">...</span>';
            html += `<a href="javascript:void(0)" onclick="loadHistoryPage(${totalPages})">${totalPages}</a>`;
        }
        
        if (currentPage < totalPages) {
            html += `<a href="javascript:void(0)" onclick="loadHistoryPage(${currentPage + 1})"><i class="fas fa-chevron-right"></i></a>`;
        }
        html += '</div>';
    }
    
    container.innerHTML = html;
}

// Khôi phục trạng thái khi load trang
document.addEventListener('DOMContentLoaded', function() {
    const section = document.getElementById('historySection');
    const isExpanded = localStorage.getItem('pointsHistoryExpanded');
    
    if (isExpanded === 'true') {
        section.classList.add('expanded');
    }
});
</script>
