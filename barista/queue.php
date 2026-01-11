<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a barista or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['barista', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Handle status updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['order_id'])) {
    $response = ['success' => false];
    
    try {
        $orderId = (int)$_POST['order_id'];
        $action = $_POST['action'];
        
        // Validate action
        $validActions = ['start', 'complete', 'cancel'];
        if (!in_array($action, $validActions)) {
            throw new Exception('Invalid action');
        }
        
        // Update order status based on action
        $statusMap = [
            'start' => 'in-progress',
            'complete' => 'completed',
            'cancel' => 'cancelled'
        ];
        
        $newStatus = $statusMap[$action];
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $result = $stmt->execute([$newStatus, $orderId]);
        
        if ($newStatus === 'completed') {
            $pdo->exec("UPDATE orders SET completed_at = CURRENT_TIMESTAMP WHERE id = $orderId");
        }
        
        $response['success'] = true;
        $response['newStatus'] = $newStatus;
        
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get all active orders
$stmt = $pdo->query("
    SELECT o.*, u.username as cashier_name,
           GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
    FROM orders o
    JOIN users u ON o.cashier_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.status IN ('pending', 'in-progress')
    GROUP BY o.id
    ORDER BY 
        CASE 
            WHEN o.status = 'in-progress' THEN 1
            WHEN o.status = 'pending' THEN 2
            ELSE 3
        END,
        o.created_at ASC
");
$activeOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html data-theme="cupcake">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Queue - CafD Barista</title>
    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <!-- Lucide Icons -->
    <script type="module">
        import { createIcons, Coffee, Clock, CheckCircle, X, RefreshCw } from 'https://cdn.jsdelivr.net/npm/lucide@0.562.0/+esm'
        
        // Initialize icons
        createIcons({
            icons: {
                Coffee,
                Clock,
                CheckCircle,
                X,
                RefreshCw
            }
        });
    </script>
    <style>
        .order-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .order-card.pending {
            border-left-color: #fbbf24; /* amber-400 */
        }
        .order-card.in-progress {
            border-left-color: #60a5fa; /* blue-400 */
        }
        .order-card.completed {
            border-left-color: #34d399; /* emerald-400 */
        }
        .order-card.cancelled {
            border-left-color: #f87171; /* red-400 */
        }
        .order-card:hover {
            transform: translateY(-2px);
        }
        .blink {
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body class="bg-base-200 min-h-screen">
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div class="navbar bg-base-100 shadow-lg sticky top-0 z-10">
                <div class="flex-none lg:hidden">
                    <label for="my-drawer-2" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </label>
                </div>
                <div class="flex-1 px-2 mx-2 font-bold text-xl">Order Queue</div>
                <div class="flex-none">
                    <button id="refreshBtn" class="btn btn-ghost">
                        <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Page content -->
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6" id="orderQueue">
                    <?php if (empty($activeOrders)): ?>
                        <div class="col-span-full">
                            <div class="alert alert-info">
                                <i data-lucide="coffee" class="w-6 h-6"></i>
                                <span>No active orders. All caught up!</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activeOrders as $order): ?>
                            <div class="card bg-base-100 shadow-xl order-card <?php echo $order['status']; ?>" id="order-<?php echo $order['id']; ?>">
                                <div class="card-body">
                                    <div class="flex justify-between items-start">
                                        <h2 class="card-title">Order #<?php echo $order['id']; ?></h2>
                                        <div class="badge <?php 
                                            echo $order['status'] === 'pending' ? 'badge-warning' : 
                                                ($order['status'] === 'in-progress' ? 'badge-info' : 'badge-success'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('-', ' ', $order['status'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="text-sm text-gray-500 mt-1">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="user" class="w-4 h-4"></i>
                                            <?php echo htmlspecialchars($order['cashier_name']); ?>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <i data-lucide="clock" class="w-4 h-4"></i>
                                            <?php 
                                                $orderTime = new DateTime($order['created_at']);
                                                $now = new DateTime();
                                                $interval = $now->diff($orderTime);
                                                $minutes = $interval->i + ($interval->h * 60);
                                                echo $minutes . ' min ago';
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="divider my-2"></div>
                                    
                                    <div class="space-y-2">
                                        <h3 class="font-semibold">Items:</h3>
                                        <p class="text-sm"><?php echo htmlspecialchars($order['items']); ?></p>
                                    </div>
                                    
                                    <div class="card-actions justify-end mt-4">
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-primary start-btn" data-order-id="<?php echo $order['id']; ?>">
                                                <i data-lucide="coffee" class="w-4 h-4 mr-1"></i> Start
                                            </button>
                                            <button class="btn btn-sm btn-ghost text-error cancel-btn" data-order-id="<?php echo $order['id']; ?>">
                                                <i data-lucide="x" class="w-4 h-4 mr-1"></i> Cancel
                                            </button>
                                        <?php elseif ($order['status'] === 'in-progress'): ?>
                                            <button class="btn btn-sm btn-success complete-btn" data-order-id="<?php echo $order['id']; ?>">
                                                <i data-lucide="check-circle" class="w-4 h-4 mr-1"></i> Complete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- No Orders Template (hidden by default) -->
                <div id="noOrdersTemplate" class="hidden">
                    <div class="col-span-full">
                        <div class="alert alert-info">
                            <i data-lucide="coffee" class="w-6 h-6"></i>
                            <span>No active orders. All caught up!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="drawer-side">
            <label for="my-drawer-2" class="drawer-overlay"></label>
            <ul class="menu p-4 w-64 h-full bg-base-100 text-base-content">
                <li class="mb-4">
                    <h1 class="text-2xl font-bold p-4">CafD</h1>
                </li>
                <li><a href="queue.php" class="active"><i data-lucide="list-ordered" class="w-5 h-5"></i> Order Queue</a></li>
                <li><a href="completed.php"><i data-lucide="check-circle" class="w-5 h-5"></i> Completed Orders</a></li>
                <li><a href="../logout.php" class="text-error"><i data-lucide="log-out" class="w-5 h-5"></i> Logout</a></li>
            </ul>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Refresh button
            document.getElementById('refreshBtn').addEventListener('click', function() {
                location.reload();
            });
            
            // Auto-refresh every 30 seconds
            setInterval(updateOrderStatus, 30000);
            
            // Handle order actions
            document.addEventListener('click', function(e) {
                if (e.target.closest('.start-btn')) {
                    const orderId = e.target.closest('.start-btn').dataset.orderId;
                    updateOrderStatus(orderId, 'start');
                } else if (e.target.closest('.complete-btn')) {
                    const orderId = e.target.closest('.complete-btn').dataset.orderId;
                    updateOrderStatus(orderId, 'complete');
                } else if (e.target.closest('.cancel-btn')) {
                    if (confirm('Are you sure you want to cancel this order?')) {
                        const orderId = e.target.closest('.cancel-btn').dataset.orderId;
                        updateOrderStatus(orderId, 'cancel');
                    }
                }
            });
            
            // Update order status via AJAX
            function updateOrderStatus(orderId, action) {
                if (orderId && action) {
                    const orderCard = document.getElementById(`order-${orderId}`);
                    if (orderCard) orderCard.classList.add('opacity-75', 'pointer-events-none');
                    
                    fetch('queue.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=${action}&order_id=${orderId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // If the order was completed or cancelled, remove it from the queue
                            if (action === 'complete' || action === 'cancel') {
                                orderCard.remove();
                                
                                // Show "no orders" message if no orders left
                                const orderQueue = document.getElementById('orderQueue');
                                if (orderQueue.children.length === 1) { // Only the template is left
                                    const noOrdersTemplate = document.getElementById('noOrdersTemplate').cloneNode(true);
                                    noOrdersTemplate.id = '';
                                    noOrdersTemplate.classList.remove('hidden');
                                    orderQueue.innerHTML = '';
                                    orderQueue.appendChild(noOrdersTemplate);
                                }
                            } else if (action === 'start') {
                                // Update the order card to show in-progress state
                                const badge = orderCard.querySelector('.badge');
                                const buttons = orderCard.querySelector('.card-actions');
                                
                                if (badge && buttons) {
                                    badge.className = 'badge badge-info';
                                    badge.textContent = 'In Progress';
                                    
                                    buttons.innerHTML = `
                                        <button class="btn btn-sm btn-success complete-btn" data-order-id="${orderId}">
                                            <i data-lucide="check-circle" class="w-4 h-4 mr-1"></i> Complete
                                        </button>
                                    `;
                                }
                            }
                        } else {
                            console.error('Error:', data.error || 'Unknown error');
                            alert('Failed to update order status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to update order status');
                    })
                    .finally(() => {
                        if (orderCard) orderCard.classList.remove('opacity-75', 'pointer-events-none');
                    });
                }
            }
            
            // Auto-refresh function
            function autoRefresh() {
                fetch('queue.php?partial=1')
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newQueue = doc.getElementById('orderQueue');
                        
                        if (newQueue) {
                            document.getElementById('orderQueue').innerHTML = newQueue.innerHTML;
                        }
                    })
                    .catch(error => console.error('Error refreshing orders:', error));
            }
        });
    </script>
</body>
</html>
