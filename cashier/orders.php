<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a cashier or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['cashier', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Get active orders
$stmt = $pdo->query("SELECT o.*, u.username as cashier_name 
                    FROM orders o 
                    JOIN users u ON o.cashier_id = u.id 
                    WHERE o.status != 'completed' 
                    ORDER BY o.created_at DESC");
$activeOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html data-theme="cupcake">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Rav n tea Cashier</title>
    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <!-- Lucide Icons -->
    <script type="module">
        import { createIcons, Plus, ListOrdered, User, Clock, CheckCircle, Coffee, Utensils, X } from 'https://cdn.jsdelivr.net/npm/lucide@0.562.0/+esm'
        
        // Initialize icons
        createIcons({
            icons: {
                Plus,
                ListOrdered,
                User,
                Clock,
                CheckCircle,
                Coffee,
                Utensils,
                X
            }
        });
    </script>
    <style>
        .order-card {
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
                <div class="flex-1 px-2 mx-2 font-bold text-xl">Rav n tea - Cashier</div>
                <div class="flex-none">
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" class="btn btn-ghost btn-circle avatar">
                            <div class="w-10 rounded-full">
                                <i data-lucide="user" class="w-6 h-6 mx-auto mt-2"></i>
                            </div>
                        </div>
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            <li><a href="#">Profile</a></li>
                            <li><a href="../logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Active Orders</h1>
                    <a href="new-order.php" class="btn btn-primary">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i> New Order
                    </a>
                </div>

                <?php if (empty($activeOrders)): ?>
                    <div class="alert alert-info shadow-lg">
                        <div>
                            <i data-lucide="info" class="w-6 h-6"></i>
                            <span>No active orders. Create a new order to get started.</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($activeOrders as $order): ?>
                            <div class="card bg-base-100 shadow-xl order-card">
                                <div class="card-body">
                                    <div class="flex justify-between items-start">
                                        <h2 class="card-title">Order #<?php echo $order['id']; ?></h2>
                                        <div class="badge <?php 
                                            echo $order['status'] === 'pending' ? 'badge-warning' : 
                                                ($order['status'] === 'in-progress' ? 'badge-info' : 'badge-success'); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </div>
                                    </div>
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
                                    <div class="divider my-2"></div>
                                    <div class="space-y-2">
                                        <!-- Order items would be listed here -->
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium">Total:</span>
                                            <span class="font-bold"><?php echo format_peso($order['total']); ?></span>
                                        </div>
                                    </div>
                                    <div class="card-actions justify-end mt-4">
                                        <a href="view-order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                        <button class="btn btn-sm btn-primary">Complete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="drawer-side">
            <label for="my-drawer-2" class="drawer-overlay"></label>
            <ul class="menu p-4 w-64 h-full bg-base-100 text-base-content">
                <li class="mb-4">
                    <h1 class="text-2xl font-bold p-4">Rav n tea</h1>
                </li>
                <li><a href="orders.php" class="active"><i data-lucide="list-ordered" class="w-5 h-5"></i> Orders</a></li>
                <li><a href="history.php"><i data-lucide="history" class="w-5 h-5"></i> Order History</a></li>
                <li><a href="../logout.php" class="text-error"><i data-lucide="log-out" class="w-5 h-5"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
