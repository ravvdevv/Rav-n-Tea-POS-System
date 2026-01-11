<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Dashboard - Rav\'n\'Tea POS';
$userRole = $_SESSION['role'];
$username = htmlspecialchars($_SESSION['username']);

// Get counts and data for the dashboard
try {
    // Basic counts
    $productsCount = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $inventoryItemsCount = $pdo->query("SELECT COUNT(*) FROM inventory_items")->fetchColumn();
    $ordersCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    // Sales data for the chart (last 7 days)
    $salesData = $pdo->query("
        SELECT 
            DATE(created_at) as date, 
            COUNT(*) as order_count,
            SUM(total) as total_sales
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Recent orders
    $recentOrders = $pdo->query("
        SELECT o.id, o.id as order_number, u.username, o.total as total_amount, o.status, o.created_at
        FROM orders o
        JOIN users u ON o.cashier_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Low stock items (inventory items with quantity below threshold)
    $lowStockItems = $pdo->query("
        SELECT id, name, quantity, threshold, unit
        FROM inventory_items 
        WHERE quantity <= threshold
        ORDER BY quantity ASC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get recent activities from database
    try {
        // Get recent orders for activity log
        $recentActivities = [];
        
        // Add system update activity
        $recentActivities[] = [
            'type' => 'system', 
            'description' => 'System updated to v1.0.0', 
            'date' => 'Just now',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Add recent orders to activity log
        if (!empty($recentOrders)) {
            foreach ($recentOrders as $order) {
                $recentActivities[] = [
                    'type' => 'order',
                    'description' => 'New order #' . str_pad($order['id'], 3, '0', STR_PAD_LEFT),
                    'date' => timeAgo($order['created_at']),
                    'timestamp' => $order['created_at']
                ];
            }
        }
        
        // Add low stock alerts to activity log
        if (!empty($lowStockItems)) {
            foreach ($lowStockItems as $item) {
                $recentActivities[] = [
                    'type' => 'inventory',
                    'description' => 'Low stock alert for ' . htmlspecialchars($item['name']),
                    'date' => timeAgo(date('Y-m-d H:i:s')), // Current time since we don't have a timestamp for when stock went low
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        // Sort activities by timestamp (newest first)
        usort($recentActivities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Limit to 5 most recent activities
        $recentActivities = array_slice($recentActivities, 0, 5);
        
    } catch (Exception $e) {
        error_log("Error getting recent activities: " . $e->getMessage());
        $recentActivities = [];
    }

} catch (PDOException $e) {
    // Handle database errors
    error_log("Database error: " . $e->getMessage());
    $productsCount = $usersCount = $inventoryItemsCount = $ordersCount = 0;
    $salesData = [];
    $recentOrders = [];
    $lowStockItems = [];
    $recentActivities = [];
}

// Function to calculate time ago
function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    // Convert difference to array
    $diffArray = (array)$diff;
    $diffArray['w'] = floor($diff->d / 7);  // Calculate weeks from days
    $diff->d = $diff->d % 7;  // Update days to be remainder after weeks
    
    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Prepare chart data
$chartLabels = [];
$chartOrderData = [];
$chartSalesData = [];

$today = new DateTime();
for ($i = 6; $i >= 0; $i--) {
    $date = clone $today;
    $date->modify("-$i days");
    $dateStr = $date->format('Y-m-d');
    $chartLabels[] = $date->format('M j');
    
    $found = false;
    foreach ($salesData as $sale) {
        if ($sale['date'] === $dateStr) {
            $chartOrderData[] = (int)$sale['order_count'];
            $chartSalesData[] = (float)$sale['total_sales'];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $chartOrderData[] = 0;
        $chartSalesData[] = 0;
    }
}
?>
<!DOCTYPE html>
<html data-theme="cupcake" class="min-h-full">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
    <title><?php echo $pageTitle; ?> - CafD</title>
</head>
<body class="min-h-screen bg-base-200 flex flex-col">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <div class="flex flex-1">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 p-6 overflow-x-hidden overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Admin Dashboard</h1>
                <div class="text-sm breadcrumbs">
                    <ul>
                        <li>Dashboard</li>
                    </ul>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Products Card -->
                <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-primary/10 text-primary">
                                <i data-lucide="coffee" class="w-6 h-6"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Active Products</h3>
                                <p class="text-2xl font-bold"><?php echo number_format($productsCount); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Total products in store</p>
                            </div>
                        </div>
                        <div class="card-actions justify-end mt-4">
                            <a href="products.php" class="btn btn-sm btn-primary btn-outline gap-2">
                                <i data-lucide="external-link" class="w-4 h-4"></i>
                                Manage
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Users Card -->
                <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-secondary/10 text-secondary">
                                <i data-lucide="users" class="w-6 h-6"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Total Users</h3>
                                <p class="text-2xl font-bold"><?php echo number_format($usersCount); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Registered users</p>
                            </div>
                        </div>
                        <div class="card-actions justify-end mt-4">
                            <a href="users.php" class="btn btn-sm btn-secondary btn-outline gap-2">
                                <i data-lucide="users" class="w-4 h-4"></i>
                                Manage Users
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Inventory Card -->
                <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-accent/10 text-accent">
                                <i data-lucide="package" class="w-6 h-6"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Inventory Items</h3>
                                <p class="text-2xl font-bold"><?php echo number_format($inventoryItemsCount); ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo count($lowStockItems); ?> low on stock</p>
                            </div>
                        </div>
                        <div class="card-actions justify-end mt-4">
                            <a href="inventory.php" class="btn btn-sm btn-accent btn-outline gap-2">
                                <i data-lucide="package-plus" class="w-4 h-4"></i>
                                Manage Inventory
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Orders Card -->
                <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-info/10 text-info">
                                <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Today's Orders</h3>
                                <p class="text-2xl font-bold"><?php echo number_format($ordersCount); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Total orders today</p>
                            </div>
                        </div>
                        <div class="card-actions justify-end mt-4">
                            <a href="orders.php" class="btn btn-sm btn-info btn-outline gap-2">
                                <i data-lucide="list-ordered" class="w-4 h-4"></i>
                                View Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Sales Overview -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="card-title">Sales Overview</h2>
                            <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn btn-sm btn-ghost">
                                    <span>Last 7 days</span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 ml-1"></i>
                                </label>
                                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-40">
                                    <li><a>Today</a></li>
                                    <li><a>Last 7 days</a></li>
                                    <li><a>This month</a></li>
                                    <li><a>This year</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="h-80">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="card-title">Recent Orders</h2>
                            <a href="orders.php" class="btn btn-sm btn-ghost">View All</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentOrders) > 0): ?>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = [
                                                        'pending' => 'badge-warning',
                                                        'processing' => 'badge-info',
                                                        'completed' => 'badge-success',
                                                        'cancelled' => 'badge-error'
                                                    ][$order['status']] ?? 'badge-ghost';
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?> badge-sm">
                                                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-gray-500">No recent orders found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Low Stock Items -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="card-title">Low Stock Items</h2>
                            <a href="inventory.php?filter=low_stock" class="btn btn-sm btn-ghost">View All</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Current Stock</th>
                                        <th>Min. Required</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($lowStockItems) > 0): ?>
                                        <?php foreach ($lowStockItems as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo number_format($item['quantity']); ?></td>
                                                <td><?php echo number_format($item['min_quantity']); ?></td>
                                                <td>
                                                    <span class="badge badge-warning badge-sm">
                                                        <?php 
                                                        $percentage = ($item['quantity'] / $item['min_quantity']) * 100;
                                                        echo 'Low (' . number_format($percentage, 0) . '%)';
                                                        ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-gray-500">No low stock items</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Recent Activity</h2>
                        <div class="space-y-4">
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0">
                                            <?php if ($activity['type'] === 'system'): ?>
                                                <div class="p-2 rounded-full bg-info/10 text-info">
                                                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                                                </div>
                                            <?php elseif ($activity['type'] === 'order'): ?>
                                                <div class="p-2 rounded-full bg-success/10 text-success">
                                                    <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="p-2 rounded-full bg-warning/10 text-warning">
                                                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-medium text-sm"><?php echo htmlspecialchars($activity['description']); ?></p>
                                            <p class="text-xs text-gray-500 time-ago" data-time="<?php echo $activity['timestamp'] ?? date('Y-m-d H:i:s'); ?>">
                                                <?php echo $activity['date']; ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i data-lucide="activity" class="w-8 h-8 mx-auto mb-2 opacity-30"></i>
                                    <p>No recent activities</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize lucide icons
            lucide.createIcons();
            
            // Initialize tooltips
            const tooltipTriggers = document.querySelectorAll('[data-tip]');
            tooltipTriggers.forEach(trigger => {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = trigger.getAttribute('data-tip');
                
                trigger.addEventListener('mouseenter', () => {
                    const rect = trigger.getBoundingClientRect();
                    tooltip.style.position = 'fixed';
                    tooltip.style.left = `${rect.left + window.scrollX}px`;
                    tooltip.style.top = `${rect.bottom + window.scrollY + 5}px`;
                    tooltip.style.zIndex = '50';
                    tooltip.className = 'tooltip tooltip-open bg-base-200 text-base-content px-2 py-1 rounded text-sm shadow-lg';
                    document.body.appendChild(tooltip);
                });
                
                trigger.addEventListener('mouseleave', () => {
                    if (document.body.contains(tooltip)) {
                        document.body.removeChild(tooltip);
                    }
                });
            });

            // Sales Chart
            const ctx = document.getElementById('salesChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chartLabels); ?>,
                        datasets: [{
                            label: 'Orders',
                            data: <?php echo json_encode($chartOrderData); ?>,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y'
                        }, {
                            label: 'Sales (₱)',
                            data: <?php echo json_encode($chartSalesData); ?>,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            if (label.includes('₱')) {
                                                label += '₱' + context.parsed.y.toFixed(2);
                                            } else {
                                                label += context.parsed.y;
                                            }
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Number of Orders'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                                title: {
                                    display: true,
                                    text: 'Total Sales (₱)'
                                }
                            }
                        }
                    }
                });
            }

            // Update time ago for recent activities
            function updateTimeAgo() {
                document.querySelectorAll('.time-ago').forEach(element => {
                    const timestamp = element.getAttribute('data-time');
                    if (timestamp) {
                        const date = new Date(timestamp);
                        const now = new Date();
                        const seconds = Math.floor((now - date) / 1000);
                        
                        let interval = Math.floor(seconds / 31536000);
                        if (interval >= 1) {
                            element.textContent = interval + ' year' + (interval === 1 ? '' : 's') + ' ago';
                            return;
                        }
                        interval = Math.floor(seconds / 2592000);
                        if (interval >= 1) {
                            element.textContent = interval + ' month' + (interval === 1 ? '' : 's') + ' ago';
                            return;
                        }
                        interval = Math.floor(seconds / 86400);
                        if (interval >= 1) {
                            element.textContent = interval + ' day' + (interval === 1 ? '' : 's') + ' ago';
                            return;
                        }
                        interval = Math.floor(seconds / 3600);
                        if (interval >= 1) {
                            element.textContent = interval + ' hour' + (interval === 1 ? '' : 's') + ' ago';
                            return;
                        }
                        interval = Math.floor(seconds / 60);
                        if (interval >= 1) {
                            element.textContent = interval + ' minute' + (interval === 1 ? '' : 's') + ' ago';
                            return;
                        }
                        element.textContent = 'Just now';
                    }
                });
            }

            // Initial call
            updateTimeAgo();
            // Update every minute
            setInterval(updateTimeAgo, 60000);
        });
    </script>
</body>
</html>
