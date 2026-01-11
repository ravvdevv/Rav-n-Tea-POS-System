<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a cashier or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['cashier', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Get order ID from URL
$orderId = $_GET['id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.username as cashier_name 
    FROM orders o 
    JOIN users u ON o.cashier_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.price as product_price 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = $_POST['status'];
    $validStatuses = ['pending', 'in-progress', 'completed'];
    
    if (in_array($newStatus, $validStatuses)) {
        $updateStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $orderId]);
        
        // If marking as completed, set completed_at timestamp
        if ($newStatus === 'completed') {
            $pdo->exec("UPDATE orders SET completed_at = CURRENT_TIMESTAMP WHERE id = $orderId");
        }
        
        // Redirect to refresh the page
        header("Location: view-order.php?id=$orderId");
        exit();
    }
}
?>
<!DOCTYPE html>
<html data-theme="cupcake">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> - Rav n tea Cashier</title>
    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <!-- Lucide Icons -->
    <script type="module">
        import { createIcons, Printer, ArrowLeft, CheckCircle, Clock, X } from 'https://cdn.jsdelivr.net/npm/lucide@0.562.0/+esm'
        
        // Initialize icons
        createIcons({
            icons: {
                Printer,
                ArrowLeft,
                CheckCircle,
                Clock,
                X
            }
        });
    </script>
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
                <div class="flex-1 px-2 mx-2 font-bold text-xl">Order #<?php echo $orderId; ?></div>
                <div class="flex-none">
                    <a href="orders.php" class="btn btn-ghost">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to Orders
                    </a>
                    <button onclick="window.print()" class="btn btn-ghost">
                        <i data-lucide="printer" class="w-4 h-4 mr-2"></i> Print
                    </button>
                </div>
            </div>

            <!-- Page content -->
            <div class="p-6">
                <div class="bg-base-100 rounded-lg shadow-lg p-6 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold">Order #<?php echo $orderId; ?></h1>
                            <div class="text-sm text-gray-500 mt-1">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="user" class="w-4 h-4"></i>
                                    <?php echo htmlspecialchars($order['cashier_name']); ?>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <i data-lucide="clock" class="w-4 h-4"></i>
                                    <?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 md:mt-0">
                            <div class="badge <?php 
                                echo $order['status'] === 'pending' ? 'badge-warning' : 
                                    ($order['status'] === 'in-progress' ? 'badge-info' : 'badge-success'); 
                            ?> text-lg p-3">
                                <?php echo ucfirst(str_replace('-', ' ', $order['status'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <!-- Order Items -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold mb-4">Items</h2>
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-right">Price</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="font-medium"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                            </td>
                                            <td class="text-right"><?php echo format_peso($item['product_price']); ?></td>
                                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="text-right"><?php echo format_peso($item['price'] * $item['quantity']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-right">Subtotal</th>
                                        <th class="text-right"><?php echo format_peso($order['total']); ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-right">Tax (10%)</th>
                                        <th class="text-right"><?php echo format_peso($order['total'] * 0.1); ?></th>
                                    </tr>
                                    <tr class="text-lg">
                                        <th colspan="3" class="text-right">Total</th>
                                        <th class="text-right"><?php echo format_peso($order['total'] * 1.1); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Order Actions -->
                    <?php if ($order['status'] !== 'completed'): ?>
                        <div class="divider"></div>
                        <div class="flex flex-col sm:flex-row gap-4 justify-end mt-6">
                            <form method="POST" class="w-full sm:w-auto">
                                <input type="hidden" name="status" value="<?php echo $order['status'] === 'pending' ? 'in-progress' : 'completed'; ?>">
                                <button type="submit" class="btn btn-primary w-full">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> Mark as In Progress
                                    <?php else: ?>
                                        <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> Mark as Completed
                                    <?php endif; ?>
                                </button>
                            </form>
                            
                            <?php if ($order['status'] === 'in-progress'): ?>
                                <form method="POST" class="w-full sm:w-auto">
                                    <input type="hidden" name="status" value="pending">
                                    <button type="submit" class="btn btn-outline w-full">
                                        <i data-lucide="clock" class="w-5 h-5 mr-2"></i> Revert to Pending
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                            <div>
                                <h3 class="font-bold">Order Completed</h3>
                                <div class="text-xs">
                                    <?php echo date('M j, Y h:i A', strtotime($order['completed_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Print Receipt Button (for mobile) -->
                <div class="lg:hidden fixed bottom-6 right-6">
                    <button onclick="window.print()" class="btn btn-primary btn-circle btn-lg shadow-lg">
                        <i data-lucide="printer" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="drawer-side">
            <label for="my-drawer-2" class="drawer-overlay"></label>
            <ul class="menu p-4 w-64 h-full bg-base-100 text-base-content">
                <li class="mb-4">
                    <h1 class="text-2xl font-bold p-4">Rav n tea</h1>
                </li>
                <li><a href="orders.php"><i data-lucide="list-ordered" class="w-5 h-5"></i> Orders</a></li>
                <li><a href="history.php"><i data-lucide="history" class="w-5 h-5"></i> Order History</a></li>
                <li><a href="../logout.php" class="text-error"><i data-lucide="log-out" class="w-5 h-5"></i> Logout</a></li>
            </ul>
        </div>
    </div>
    
    <!-- Print Styles -->
    <style>
        @media print {
            .drawer-side, .navbar, .btn, .divider, .no-print {
                display: none !important;
            }
            .drawer-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            body {
                background: white !important;
                color: black !important;
                font-size: 14px !important;
            }
            .badge {
                color: black !important;
                border: 1px solid black !important;
                background: white !important;
                padding: 0.25rem 0.5rem !important;
            }
            .table th, .table td {
                padding: 0.25rem 0.5rem !important;
            }
        }
    </style>
</body>
</html>
