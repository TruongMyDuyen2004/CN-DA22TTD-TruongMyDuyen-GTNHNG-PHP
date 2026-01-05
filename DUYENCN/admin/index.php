<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Thống kê
$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$total_reservations = $conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$pending_reservations = $conn->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
$total_customers = $conn->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_items = $conn->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();

// Doanh thu
$today_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetchColumn();
$month_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled'")->fetchColumn();

// Dữ liệu biểu đồ
$revenue_7days = [];
$orders_7days = [];
$labels_7days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels_7days[] = date('d/m', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'");
    $stmt->execute([$date]);
    $revenue_7days[] = (int)$stmt->fetchColumn();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $orders_7days[] = (int)$stmt->fetchColumn();
}

$order_status = [];
$stmt = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $order_status[$row['status']] = (int)$row['count'];
}

$top_dishes = $conn->query("SELECT mi.name, SUM(oi.quantity) as total_sold FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id JOIN orders o ON oi.order_id = o.id WHERE o.status != 'cancelled' GROUP BY mi.id, mi.name ORDER BY total_sold DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .db-page { padding: 24px; background: #f8fafc; min-height: 100vh; max-width: 1400px; margin: 0 auto; }
    
    /* Header */
    .db-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .db-header h1 { font-size: 28px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 12px; }
    .db-header h1 i { color: #22c55e; }
    .btn-site { background: #22c55e; color: #ffffff !important; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 15px; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); }
    .btn-site:hover { background: #16a34a; box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4); transform: translateY(-2px); }

    /* Stats Grid */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
    .stat-card { 
        background: white; 
        border-radius: 16px; 
        padding: 28px 20px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
        text-decoration: none; 
        transition: all 0.3s ease; 
        border: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-height: 180px;
    }
    .stat-card:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        border-color: transparent;
    }
    .stat-card .icon { 
        width: 56px !important; 
        height: 56px !important; 
        min-width: 56px !important;
        max-width: 56px !important;
        border-radius: 14px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 24px !important; 
        color: white;
        margin: 0 auto 16px auto;
        flex-shrink: 0;
    }
    .stat-card .icon i {
        font-size: 24px !important;
    }
    .stat-card h3 { 
        font-size: 36px !important; 
        font-weight: 800; 
        color: #1e293b; 
        margin: 0 !important;
        line-height: 1;
        width: 100%;
        text-align: center !important;
    }
    .stat-card p { 
        color: #64748b; 
        font-size: 14px !important; 
        margin: 8px 0 0 !important; 
        font-weight: 600; 
        width: 100%;
        text-align: center !important;
    }
    .stat-card .badge { 
        background: #fef3c7; 
        color: #d97706; 
        font-size: 12px; 
        font-weight: 700; 
        padding: 5px 14px; 
        border-radius: 20px; 
        margin-top: 14px;
    }
    .stat-card .badge-placeholder {
        height: 26px;
        margin-top: 14px;
    }
    
    .stat-card.green .icon { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .stat-card.green:hover { border-color: #22c55e; }
    .stat-card.orange .icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .stat-card.orange:hover { border-color: #f59e0b; }
    .stat-card.purple .icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .stat-card.purple:hover { border-color: #8b5cf6; }
    .stat-card.blue .icon { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .stat-card.blue:hover { border-color: #3b82f6; }

    /* Revenue - Hiện đại */
    .revenue-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 28px; }
    .revenue-card { 
        border-radius: 16px; 
        padding: 28px 32px; 
        display: flex; 
        align-items: center; 
        gap: 24px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .revenue-card:hover { transform: translateY(-4px); }
    .revenue-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        opacity: 0.1;
        transform: translate(30%, -30%);
    }
    .revenue-card .icon { 
        width: 64px; 
        height: 64px; 
        border-radius: 16px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 28px; 
        color: white;
        flex-shrink: 0;
    }
    .revenue-card .info { position: relative; z-index: 1; }
    .revenue-card .label { font-size: 14px; font-weight: 600; margin-bottom: 8px; }
    .revenue-card .value { font-size: 32px; font-weight: 800; letter-spacing: -0.5px; }
    
    .revenue-card.today { 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        box-shadow: 0 10px 30px rgba(34, 197, 94, 0.3);
    }
    .revenue-card.today::before { background: white; }
    .revenue-card.today .icon { background: rgba(255,255,255,0.2); }
    .revenue-card.today .label { color: rgba(255,255,255,0.85); }
    .revenue-card.today .value { color: white; }
    
    .revenue-card.month { 
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
    }
    .revenue-card.month::before { background: white; }
    .revenue-card.month .icon { background: rgba(255,255,255,0.2); }
    .revenue-card.month .label { color: rgba(255,255,255,0.85); }
    .revenue-card.month .value { color: white; }

    /* Charts */
    .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .chart-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
    .chart-card .head { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; }
    .chart-card .head i { color: #22c55e; }
    .chart-card .head h3 { font-size: 15px; font-weight: 600; color: #1e293b; margin: 0; }
    .chart-card .body { padding: 20px; height: 300px; }

    @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .stats-grid, .revenue-grid, .charts-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="db-page">
            <div class="db-header">
                <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                <a href="../index.php" target="_blank" class="btn-site"><i class="fas fa-external-link-alt"></i> Xem Website</a>
            </div>

            <!-- Revenue Cards - Đầu trang -->
            <div class="revenue-grid">
                <div class="revenue-card today">
                    <div class="icon"><i class="fas fa-coins"></i></div>
                    <div class="info">
                        <div class="label">Doanh thu hôm nay</div>
                        <div class="value"><?= number_format($today_revenue) ?>đ</div>
                    </div>
                </div>
                <div class="revenue-card month">
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                    <div class="info">
                        <div class="label">Doanh thu tháng này</div>
                        <div class="value"><?= number_format($month_revenue) ?>đ</div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <a href="orders.php" class="stat-card green">
                    <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                    <h3><?= $total_orders ?></h3>
                    <p>Đơn hàng</p>
                    <?php if($pending_orders > 0): ?><span class="badge"><?= $pending_orders ?> chờ xử lý</span><?php else: ?><span class="badge-placeholder"></span><?php endif; ?>
                </a>
                <a href="reservations.php" class="stat-card orange">
                    <div class="icon"><i class="fas fa-calendar-check"></i></div>
                    <h3><?= $total_reservations ?></h3>
                    <p>Đặt bàn</p>
                    <?php if($pending_reservations > 0): ?><span class="badge"><?= $pending_reservations ?> chờ xử lý</span><?php else: ?><span class="badge-placeholder"></span><?php endif; ?>
                </a>
                <a href="customers.php" class="stat-card purple">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <h3><?= $total_customers ?></h3>
                    <p>Khách hàng</p>
                    <span class="badge-placeholder"></span>
                </a>
                <a href="menu-manage.php" class="stat-card blue">
                    <div class="icon"><i class="fas fa-utensils"></i></div>
                    <h3><?= $total_items ?></h3>
                    <p>Món ăn</p>
                    <span class="badge-placeholder"></span>
                </a>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <div class="head"><i class="fas fa-chart-area"></i><h3>Doanh thu 7 ngày</h3></div>
                    <div class="body"><canvas id="revenueChart"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="head"><i class="fas fa-chart-bar"></i><h3>Đơn hàng 7 ngày</h3></div>
                    <div class="body"><canvas id="ordersChart"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="head"><i class="fas fa-chart-pie"></i><h3>Trạng thái đơn hàng</h3></div>
                    <div class="body"><canvas id="statusChart"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="head"><i class="fas fa-trophy"></i><h3>Top 5 món bán chạy</h3></div>
                    <div class="body"><canvas id="topDishesChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    Chart.defaults.font.family = 'system-ui, sans-serif';
    Chart.defaults.color = '#64748b';

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($labels_7days) ?>,
            datasets: [{
                label: 'Doanh thu',
                data: <?= json_encode($revenue_7days) ?>,
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34,197,94,0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#22c55e'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: v => (v/1000000).toFixed(1) + 'Tr' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Orders Chart
    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_7days) ?>,
            datasets: [{ data: <?= json_encode($orders_7days) ?>, backgroundColor: '#22c55e', borderRadius: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
        }
    });

    // Status Chart
    const statusMap = { pending: 'Chờ xử lý', confirmed: 'Đã xác nhận', preparing: 'Đang làm', completed: 'Hoàn thành', cancelled: 'Đã hủy' };
    const colorMap = { pending: '#f59e0b', confirmed: '#3b82f6', preparing: '#8b5cf6', completed: '#22c55e', cancelled: '#ef4444' };
    const status = <?= json_encode($order_status) ?>;
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(status).map(k => statusMap[k] || k),
            datasets: [{ data: Object.values(status), backgroundColor: Object.keys(status).map(k => colorMap[k] || '#94a3b8'), borderWidth: 0 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right' } },
            cutout: '60%'
        }
    });

    // Top Dishes Chart
    const dishes = <?= json_encode($top_dishes) ?>;
    new Chart(document.getElementById('topDishesChart'), {
        type: 'bar',
        data: {
            labels: dishes.map((d,i) => '#'+(i+1)+' '+d.name.substring(0,15)),
            datasets: [{ data: dishes.map(d => d.total_sold), backgroundColor: ['#22c55e','#3b82f6','#f59e0b','#8b5cf6','#ec4899'], borderRadius: 6 }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, grid: { color: '#f1f5f9' } }, y: { grid: { display: false } } }
        }
    });
    </script>
</body>
</html>
