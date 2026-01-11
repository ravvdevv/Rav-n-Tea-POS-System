<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a barista or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['barista', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Date filter
$dateFilter = '';
$dateValue = '';

if (isset($_GET['date']) && !empty($_GET['date'])) {
    $dateValue = $_GET['date'];
    $dateFilter = " AND DATE(o.completed_at) = :date";
}

// Get total number of completed orders for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'completed' $dateFilter");
if (!empty($dateValue)) {
    $countStmt->bindValue(':date', $dateValue);
}
$countStmt->execute();
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $perPage);

// Get completed orders with pagination and filters
$query = "
    SELECT o.*, u.username as cashier_name,
           (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ')
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = o.id) as items
    FROM orders o
    JOIN users u ON o.cashier_id = u.id
    WHERE o.status = 'completed' $dateFilter
    GROUP BY o.id
    ORDER BY o.completed_at DESC
    LIMIT :offset, :perPage
";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);

if (!empty($dateValue)) {
    $stmt->bindValue(':date', $dateValue);
}

$stmt->execute();
$completedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html data-theme="cupcake">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Orders - CafD Barista</title>
    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <!-- Lucide Icons -->
    <script type="module">
        import { createIcons, CheckCircle, Calendar, X, Clock, User, Search } from 'https://cdn.jsdelivr.net/npm/lucide@0.562.0/+esm'
        
        // Initialize icons
        createIcons({
            icons: {
                CheckCircle,
                Calendar,
                X,
                Clock,
                User,
                Search
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
                <div class="flex-1 px-2 mx-2 font-bold text-xl">Completed Orders</div>
                <div class="flex-none">
                    <a href="queue.php" class="btn btn-ghost">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to Queue
                    </a>
                </div>
            </div>

            <!-- Page content -->
            <div class="p-6">
                <!-- Filters -->
                <div class="bg-base-100 rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Filters</h2>
                    <form method="GET" class="flex flex-col md:flex-row gap-4">
                        <div class="form-control flex-1">
                            <label class="label">
                                <span class="label-text">Completion Date</span>
                            </label>
                            <div class="relative">
                                <input type="date" name="date" value="<?php echo htmlspecialchars($dateValue); ?>" class="input input-bordered w-full">
                                <?php if (!empty($dateValue)): ?>
                                    <button type="button" onclick="window.location.href='completed.php'" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                                        <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-control self-end">
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="search" class="w-4 h-4 mr-2"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Orders Table -->
                <div class="bg-base-100 rounded-lg shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Completed At</th>
                                    <th>Cashier</th>
                                    <th>Items</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-center">Time Taken</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($completedOrders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-8">
                                            <div class="flex flex-col items-center justify-center">
                                                <i data-lucide="check-circle" class="w-12 h-12 text-gray-400 mb-2"></i>
                                                <p class="text-gray-500">No completed orders found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($completedOrders as $order): 
                                        $createdAt = new DateTime($order['created_at']);
                                        $completedAt = new DateTime($order['completed_at']);
                                        $interval = $completedAt->diff($createdAt);
                                        $timeTaken = '';
                                        
                                        if ($interval->h > 0) {
                                            $timeTaken .= $interval->h . 'h ';
                                        }
                                        $timeTaken .= $interval->i . 'm ' . $interval->s . 's';
                                    ?>
                                        <tr class="hover:bg-base-200">
                                            <td class="font-medium">#<?php echo $order['id']; ?></td>
                                            <td><?php echo date('M j, Y h:i A', strtotime($order['completed_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($order['cashier_name']); ?></td>
                                            <td class="max-w-xs truncate" title="<?php echo htmlspecialchars($order['items']); ?>">
                                                <?php echo htmlspecialchars($order['items']); ?>
                                            </td>
                                            <td class="text-right">$<?php echo number_format($order['total'], 2); ?></td>
                                            <td class="text-center" title="Created: <?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?>">
                                                <?php echo $timeTaken; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="flex justify-center my-6">
                            <div class="btn-group">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($dateValue) ? '&date=' . urlencode($dateValue) : ''; ?>" class="btn">
                                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-disabled">
                                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1) {
                                    echo '<a href="?page=1' . (!empty($dateValue) ? '&date=' . urlencode($dateValue) : '') . '" class="btn">1</a>';
                                    if ($startPage > 2) {
                                        echo '<button class="btn btn-disabled">...</button>';
                                    }
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($dateValue) ? '&date=' . urlencode($dateValue) : ''; ?>" 
                                       class="btn <?php echo $i === $page ? 'btn-active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor;
                                
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<button class="btn btn-disabled">...</button>';
                                    }
                                    echo '<a href="?page=' . $totalPages . (!empty($dateValue) ? '&date=' . urlencode($dateValue) : '') . '" class="btn">' . $totalPages . '</a>';
                                }
                                ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($dateValue) ? '&date=' . urlencode($dateValue) : ''; ?>" class="btn">
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-disabled">
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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
                <li><a href="queue.php"><i data-lucide="list-ordered" class="w-5 h-5"></i> Order Queue</a></li>
                <li><a href="completed.php" class="active"><i data-lucide="check-circle" class="w-5 h-5"></i> Completed Orders</a></li>
                <li><a href="../logout.php" class="text-error"><i data-lucide="log-out" class="w-5 h-5"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
