<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'View Order';
$userRole = $_SESSION['role'];
$username = htmlspecialchars($_SESSION['username']);

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = (int)$_GET['id'];

// Get order details
$order_query = "SELECT o.*, u.username as cashier_name 
                FROM orders o 
                JOIN users u ON o.cashier_id = u.id 
                WHERE o.id = :id";
$stmt = $pdo->prepare($order_query);
$stmt->execute(['id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name as product_name 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = :order_id";
$stmt = $pdo->prepare($items_query);
$stmt->execute(['order_id' => $order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment details if exists
$payment_query = "SELECT * FROM payments WHERE order_id = :order_id";
$stmt = $pdo->prepare($payment_query);
$stmt->execute(['order_id' => $order_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

// Function to get status badge
function getStatusBadge($status) {
    $statusClasses = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'in-progress' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800'
    ];
    $class = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
    $displayStatus = ucfirst(str_replace('-', ' ', $status));
    return "<span class='px-3 py-1 text-sm font-medium rounded-full $class'>$displayStatus</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?> - CafD Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-base-200">
    <div class="min-h-screen flex flex-col md:flex-row">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-base-200">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <div class="flex items-center space-x-2">
                            <a href="orders.php" class="btn btn-ghost btn-sm">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <h1 class="text-2xl font-bold">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h1>
                            <?php echo getStatusBadge($order['status']); ?>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            <span>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
                            <?php if ($order['completed_at']): ?>
                                <span>• Completed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['completed_at'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="orders.php" class="btn btn-ghost">Back to Orders</a>
                        <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                            <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn btn-primary">
                                    <span>Update Status</span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 ml-1"></i>
                                </label>
                                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                                    <li><a href="update-order.php?id=<?php echo $order['id']; ?>&status=in-progress">Mark as In Progress</a></li>
                                    <li><a href="update-order.php?id=<?php echo $order['id']; ?>&status=completed">Mark as Completed</a></li>
                                    <li><a href="update-order.php?id=<?php echo $order['id']; ?>&status=cancelled" class="text-red-600">Cancel Order</a></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Order Items -->
                    <div class="lg:col-span-2">
                        <div class="bg-base-100 rounded-lg shadow overflow-hidden">
                            <div class="p-6 border-b border-base-300">
                                <h2 class="text-lg font-semibold">Order Items</h2>
                            </div>
                            <div class="divide-y divide-base-200">
                                <?php foreach ($items as $item): ?>
                                <div class="p-4 flex justify-between items-center">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-16 h-16 bg-base-200 rounded-lg flex items-center justify-center">
                                            <i data-lucide="coffee" class="w-6 h-6 text-gray-400"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-medium"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                            <p class="text-sm text-gray-500">Quantity: <?php echo $item['quantity']; ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium">₱<?php echo number_format($item['price'], 2); ?></p>
                                        <p class="text-sm text-gray-500">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?> total</p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="space-y-6">
                        <!-- Order Details -->
                        <div class="bg-base-100 rounded-lg shadow overflow-hidden">
                            <div class="p-6 border-b border-base-300">
                                <h2 class="text-lg font-semibold">Order Details</h2>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Order Number</span>
                                    <span>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Placed On</span>
                                    <span><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status</span>
                                    <?php echo getStatusBadge($order['status']); ?>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Cashier</span>
                                    <span><?php echo htmlspecialchars($order['cashier_name']); ?></span>
                                </div>
                                <?php if (!empty($order['notes'])): ?>
                                <div class="pt-4 mt-4 border-t border-base-200">
                                    <p class="text-sm text-gray-600 mb-1">Notes:</p>
                                    <p class="text-sm bg-base-200 p-3 rounded"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="bg-base-100 rounded-lg shadow overflow-hidden">
                            <div class="p-6 border-b border-base-300">
                                <h2 class="text-lg font-semibold">Payment Information</h2>
                            </div>
                            <div class="p-6 space-y-4">
                                <?php if ($payment): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payment Method</span>
                                        <span class="capitalize"><?php echo str_replace('_', ' ', $payment['payment_method']); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payment Status</span>
                                        <span class="capitalize"><?php echo $payment['status']; ?></span>
                                    </div>
                                    <?php if (!empty($payment['transaction_id'])): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Transaction ID</span>
                                        <span class="font-mono"><?php echo $payment['transaction_id']; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="pt-4 mt-4 border-t border-base-200">
                                        <div class="flex justify-between text-lg font-semibold">
                                            <span>Total Amount</span>
                                            <span>₱<?php echo number_format($order['total'], 2); ?></span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-4">No payment information available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const closeMobileMenu = document.getElementById('close-mobile-menu');
        
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenuOverlay.classList.remove('hidden');
                setTimeout(() => {
                    mobileMenuOverlay.classList.add('opacity-100');
                }, 10);
            });
        }
        
        if (closeMobileMenu) {
            closeMobileMenu.addEventListener('click', () => {
                mobileMenuOverlay.classList.remove('opacity-100');
                setTimeout(() => {
                    mobileMenuOverlay.classList.add('hidden');
                }, 200);
            });
        }
    </script>
</body>
</html>
