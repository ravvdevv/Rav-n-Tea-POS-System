<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Orders - Rav\'n\'Tea POS';
$userRole = $_SESSION['role'];
$username = htmlspecialchars($_SESSION['username']);

// Get all orders with user details
$query = "SELECT o.*, u.username as cashier_name 
          FROM orders o 
          JOIN users u ON o.cashier_id = u.id 
          ORDER BY o.created_at DESC";
$stmt = $pdo->query($query);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get order status badge
function getStatusBadge($status) {
    $statusClasses = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'in-progress' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800'
    ];
    $class = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
    $displayStatus = ucfirst(str_replace('-', ' ', $status));
    return "<span class='px-2 py-1 text-xs font-medium rounded-full $class'>$displayStatus</span>";
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Rav n tea Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-base-200 flex flex-col">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <div class="flex flex-1">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 p-6 overflow-x-hidden overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Orders</h1>
                    <div class="flex space-x-2">
                        <div class="form-control">
                            <div class="input-group">
                                <input type="text" placeholder="Search orders..." class="input input-bordered w-full md:w-auto" id="searchInput">
                                <button class="btn btn-square">
                                    <i data-lucide="search" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-base-100 rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Cashier</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-base-200">
                                    <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['cashier_name']); ?></td>
                                    <td><?php echo getStatusBadge($order['status']); ?></td>
                                    <td>₱<?php echo number_format($order['total'], 2); ?></td>
                                    <td class="text-right">
                                        <div class="dropdown dropdown-end">
                                            <label tabindex="0" class="btn btn-ghost btn-sm">
                                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                            </label>
                                            <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                                                <li><a href="view-order.php?id=<?php echo $order['id']; ?>"><i data-lucide="eye" class="w-4 h-4"></i> View Details</a></li>
                                                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                                <li><a href="update-order.php?id=<?php echo $order['id']; ?>&status=in-progress" class="text-blue-600"><i data-lucide="refresh-cw" class="w-4 h-4"></i> Mark as In Progress</a></li>
                                                <li><a href="update-order.php?id=<?php echo $order['id']; ?>&status=completed" class="text-green-600"><i data-lucide="check-circle" class="w-4 h-4"></i> Mark as Completed</a></li>
                                                <li><a href="update-order.php?id=<?php echo $order['id']; ?>&status=cancelled" class="text-red-600"><i data-lucide="x-circle" class="w-4 h-4"></i> Cancel Order</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">
                                        <i data-lucide="shopping-cart" class="w-12 h-12 mx-auto mb-2 opacity-30"></i>
                                        <p>No orders found</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const rows = document.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>
