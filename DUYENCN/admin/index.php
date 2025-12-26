<?php
session_start();
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Dashboard chỉ hiển thị thống kê tổng quan, không xử lý actions

// Lấy thống kê tổng quan
$stmt = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'");
$pending_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM reservations");
$total_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM contacts WHERE status = 'new'");
$new_contacts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM contacts");
$total_contacts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM menu_items");
$total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM customers");
$total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Thống kê doanh thu hôm nay
$stmt = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
$today_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

// Thống kê doanh thu 7 ngày gần nhất cho biểu đồ
$revenue_7days = [];
$labels_7days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d/m', strtotime("-$i days"));
    $labels_7days[] = $label;
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'");
    $stmt->execute([$date]);
    $revenue_7days[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
}

// Thống kê đơn hàng 7 ngày gần nhất
$orders_7days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $orders_7days[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Thống kê trạng thái đơn hàng cho biểu đồ tròn
$stmt = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$order_status = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $order_status[$row['status']] = (int)$row['count'];
}

// Thống kê top 5 món ăn bán chạy
$stmt = $conn->query("
    SELECT mi.name, SUM(oi.quantity) as total_sold 
    FROM order_items oi 
    JOIN menu_items mi ON oi.menu_item_id = mi.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE o.status != 'cancelled'
    GROUP BY mi.id, mi.name 
    ORDER BY total_sold DESC 
    LIMIT 5
");
$top_dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ngon Gallery Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Dashboard
            </h1>
            <div class="header-actions">
                <a href="../index.php" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt"></i>
                    Xem Website
                </a>
            </div>
        </div>
        
        <!-- Charts Section - Đầu trang -->
        <div class="charts-grid">
            <!-- Biểu đồ doanh thu 7 ngày -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-chart-area"></i>
                        Doanh thu 7 ngày gần nhất
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <!-- Biểu đồ đơn hàng 7 ngày -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-chart-bar"></i>
                        Đơn hàng 7 ngày gần nhất
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
            
            <!-- Biểu đồ trạng thái đơn hàng -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-chart-pie"></i>
                        Trạng thái đơn hàng
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            
            <!-- Top món ăn bán chạy -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-trophy"></i>
                        Top 5 món bán chạy
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="topDishesChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Stats Grid - Style giống trang giảm giá -->
        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <!-- Đơn hàng -->
            <a href="orders.php" style="text-decoration: none;">
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total_orders; ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.85rem; font-weight: 500;">Tổng đơn hàng</p>
                    </div>
                </div>
            </a>
            
            <!-- Đặt bàn -->
            <a href="reservations.php" style="text-decoration: none;">
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f59e0b';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); flex-shrink: 0;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total_reservations; ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.85rem; font-weight: 500;">Tổng đặt bàn</p>
                    </div>
                </div>
            </a>
            
            <!-- Liên hệ -->
            <a href="contacts.php" style="text-decoration: none;">
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#ef4444';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); flex-shrink: 0;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total_contacts; ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.85rem; font-weight: 500;">Tổng liên hệ</p>
                    </div>
                </div>
            </a>
            
            <!-- Khách hàng -->
            <a href="customers.php" style="text-decoration: none;">
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#8b5cf6';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); flex-shrink: 0;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total_customers; ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.85rem; font-weight: 500;">Khách hàng</p>
                    </div>
                </div>
            </a>
            
            <!-- Thực đơn -->
            <a href="menu-manage.php" style="text-decoration: none;">
                <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#3b82f6';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                    <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); flex-shrink: 0;">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $total_items; ?></h3>
                        <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.85rem; font-weight: 500;">Món ăn</p>
                    </div>
                </div>
            </a>
            
            <!-- Doanh thu hôm nay -->
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#10b981';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #10b981 0%, #059669 100%); flex-shrink: 0;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo number_format($today_revenue); ?>đ</h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.85rem; font-weight: 500;">Doanh thu hôm nay</p>
                </div>
            </div>
        </div>
        
        <?php if (isset($dashboard_message) && $dashboard_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($dashboard_message); ?>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-bolt"></i>
                    Thao tác nhanh
                </h3>
            </div>
            <div class="quick-actions-grid">
                <a href="orders.php" class="quick-action-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Quản lý đơn hàng</span>
                </a>
                <a href="reservations.php" class="quick-action-btn">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Quản lý đặt bàn</span>
                </a>
                <a href="contacts.php" class="quick-action-btn">
                    <i class="fas fa-envelope"></i>
                    <span>Quản lý liên hệ</span>
                </a>
                <a href="menu-manage.php" class="quick-action-btn">
                    <i class="fas fa-utensils"></i>
                    <span>Quản lý thực đơn</span>
                </a>
                <a href="customers.php" class="quick-action-btn">
                    <i class="fas fa-users"></i>
                    <span>Quản lý khách hàng</span>
                </a>
                <a href="reviews.php" class="quick-action-btn">
                    <i class="fas fa-star"></i>
                    <span>Quản lý đánh giá</span>
                </a>
            </div>
    </div>
    
    <script>
    // Cấu hình chung cho Chart.js - Màu đậm và rõ ràng hơn
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.font.size = 13;
    Chart.defaults.font.weight = '500';
    Chart.defaults.color = '#1f2937';
    
    // 1. Biểu đồ doanh thu - Line chart với gradient đậm
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueGradient = revenueCtx.createLinearGradient(0, 0, 0, 280);
    revenueGradient.addColorStop(0, 'rgba(34, 197, 94, 0.4)');
    revenueGradient.addColorStop(1, 'rgba(34, 197, 94, 0.05)');
    
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels_7days); ?>,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?php echo json_encode($revenue_7days); ?>,
                borderColor: '#16a34a',
                backgroundColor: revenueGradient,
                borderWidth: 4,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#16a34a',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 3,
                pointRadius: 8,
                pointHoverRadius: 12,
                pointHoverBackgroundColor: '#15803d',
                pointHoverBorderWidth: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#1f2937',
                        font: { size: 13, weight: '600' },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'rectRounded'
                    }
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleColor: '#ffffff',
                    titleFont: { size: 14, weight: '700' },
                    bodyColor: '#ffffff',
                    bodyFont: { size: 13, weight: '500' },
                    padding: 16,
                    cornerRadius: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return '💰 ' + new Intl.NumberFormat('vi-VN').format(context.raw) + ' VNĐ';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.08)',
                        lineWidth: 1
                    },
                    border: {
                        color: '#e5e7eb',
                        width: 2
                    },
                    ticks: {
                        color: '#374151',
                        font: { size: 12, weight: '600' },
                        padding: 10,
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    border: {
                        color: '#e5e7eb',
                        width: 2
                    },
                    ticks: {
                        color: '#374151',
                        font: { size: 12, weight: '600' },
                        padding: 10
                    }
                }
            }
        }
    });
    
    // 2. Biểu đồ đơn hàng - Bar chart với màu gradient
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordersCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels_7days); ?>,
            datasets: [{
                label: 'Số đơn hàng',
                data: <?php echo json_encode($orders_7days); ?>,
                backgroundColor: [
                    'rgba(34, 197, 94, 0.9)',
                    'rgba(59, 130, 246, 0.9)',
                    'rgba(245, 158, 11, 0.9)',
                    'rgba(139, 92, 246, 0.9)',
                    'rgba(236, 72, 153, 0.9)',
                    'rgba(6, 182, 212, 0.9)',
                    'rgba(34, 197, 94, 0.9)'
                ],
                borderColor: [
                    '#16a34a',
                    '#2563eb',
                    '#d97706',
                    '#7c3aed',
                    '#db2777',
                    '#0891b2',
                    '#16a34a'
                ],
                borderWidth: 3,
                borderRadius: 10,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#1f2937',
                        font: { size: 13, weight: '600' },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'rectRounded'
                    }
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleFont: { size: 14, weight: '700' },
                    bodyFont: { size: 13, weight: '500' },
                    padding: 16,
                    cornerRadius: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return '📦 ' + context.raw + ' đơn hàng';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.08)',
                        lineWidth: 1
                    },
                    border: {
                        color: '#e5e7eb',
                        width: 2
                    },
                    ticks: {
                        color: '#374151',
                        font: { size: 12, weight: '600' },
                        padding: 10,
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    border: {
                        color: '#e5e7eb',
                        width: 2
                    },
                    ticks: {
                        color: '#374151',
                        font: { size: 12, weight: '600' },
                        padding: 10
                    }
                }
            }
        }
    });
    
    // 3. Biểu đồ trạng thái đơn hàng - Doughnut với border rõ ràng
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusLabels = {
        'pending': '⏳ Chờ xử lý',
        'confirmed': '✅ Đã xác nhận',
        'preparing': '👨‍🍳 Đang chuẩn bị',
        'ready': '🍽️ Sẵn sàng',
        'delivered': '🚚 Đã giao',
        'completed': '✔️ Hoàn thành',
        'cancelled': '❌ Đã hủy'
    };
    const statusColors = {
        'pending': '#f59e0b',
        'confirmed': '#3b82f6',
        'preparing': '#8b5cf6',
        'ready': '#06b6d4',
        'delivered': '#22c55e',
        'completed': '#10b981',
        'cancelled': '#ef4444'
    };
    const statusBorderColors = {
        'pending': '#d97706',
        'confirmed': '#2563eb',
        'preparing': '#7c3aed',
        'ready': '#0891b2',
        'delivered': '#16a34a',
        'completed': '#059669',
        'cancelled': '#dc2626'
    };
    const orderStatus = <?php echo json_encode($order_status); ?>;
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(orderStatus).map(key => statusLabels[key] || key),
            datasets: [{
                data: Object.values(orderStatus),
                backgroundColor: Object.keys(orderStatus).map(key => statusColors[key] || '#6b7280'),
                borderColor: Object.keys(orderStatus).map(key => statusBorderColors[key] || '#4b5563'),
                borderWidth: 4,
                hoverOffset: 15,
                hoverBorderWidth: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#1f2937',
                        font: { size: 12, weight: '600' },
                        padding: 12,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 12
                    }
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleFont: { size: 14, weight: '700' },
                    bodyFont: { size: 13, weight: '500' },
                    padding: 16,
                    cornerRadius: 12,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.raw / total) * 100).toFixed(1);
                            return context.raw + ' đơn (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '55%',
            radius: '90%'
        }
    });
    
    // 4. Biểu đồ top món ăn - Horizontal bar với màu sắc đa dạng
    const topDishesCtx = document.getElementById('topDishesChart').getContext('2d');
    const topDishes = <?php echo json_encode($top_dishes); ?>;
    const dishColors = [
        { bg: 'rgba(34, 197, 94, 0.9)', border: '#16a34a' },
        { bg: 'rgba(59, 130, 246, 0.9)', border: '#2563eb' },
        { bg: 'rgba(245, 158, 11, 0.9)', border: '#d97706' },
        { bg: 'rgba(139, 92, 246, 0.9)', border: '#7c3aed' },
        { bg: 'rgba(236, 72, 153, 0.9)', border: '#db2777' }
    ];
    
    new Chart(topDishesCtx, {
        type: 'bar',
        data: {
            labels: topDishes.map((d, i) => '🏆 #' + (i+1) + ' ' + (d.name.length > 12 ? d.name.substring(0, 12) + '...' : d.name)),
            datasets: [{
                label: 'Số lượng bán',
                data: topDishes.map(d => d.total_sold),
                backgroundColor: dishColors.map(c => c.bg),
                borderColor: dishColors.map(c => c.border),
                borderWidth: 3,
                borderRadius: 10,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleFont: { size: 14, weight: '700' },
                    bodyFont: { size: 13, weight: '500' },
                    padding: 16,
                    cornerRadius: 12,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return '🍽️ ' + topDishes[context[0].dataIndex].name;
                        },
                        label: function(context) {
                            return '📊 Đã bán: ' + context.raw + ' phần';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.08)',
                        lineWidth: 1
                    },
                    border: {
                        color: '#e5e7eb',
                        width: 2
                    },
                    ticks: {
                        color: '#374151',
                        font: { size: 12, weight: '600' },
                        padding: 10
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    border: {
                        color: '#e5e7eb',
                        width: 2
                    },
                    ticks: {
                        color: '#1f2937',
                        font: { size: 11, weight: '700' },
                        padding: 10
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
