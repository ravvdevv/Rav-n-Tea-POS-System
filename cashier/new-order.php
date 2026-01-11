<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a cashier or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['cashier', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Get all active products
$stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (cashier_id, status, total) VALUES (?, 'pending', 0)");
        $stmt->execute([$_SESSION['user_id']]);
        $orderId = $pdo->lastInsertId();
        
        $total = 0;
        
        // Add order items
        if (!empty($_POST['items'])) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $updateProductStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            
            foreach ($_POST['items'] as $item) {
                $productId = $item['product_id'];
                $quantity = (int)$item['quantity'];
                
                if ($quantity <= 0) continue;
                
                // Get product price
                $productStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $productStmt->execute([$productId]);
                $product = $productStmt->fetch();
                
                if ($product) {
                    $price = $product['price'];
                    $subtotal = $price * $quantity;
                    $total += $subtotal;
                    
                    // Add order item
                    $stmt->execute([$orderId, $productId, $quantity, $price]);
                    
                    // Update product stock
                    $updateProductStmt->execute([$quantity, $productId]);
                }
            }
            
            // Update order total
            $updateOrderStmt = $pdo->prepare("UPDATE orders SET total = ? WHERE id = ?");
            $updateOrderStmt->execute([$total, $orderId]);
            
            $pdo->commit();
            
            // Redirect to order confirmation
            header("Location: view-order.php?id=$orderId");
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error creating order: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html data-theme="cupcake">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order - Rav n tea Cashier</title>
    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <!-- Lucide Icons -->
    <script type="module">
        import { createIcons, Plus, Minus, X, ShoppingCart, Trash2, ArrowLeft } from 'https://cdn.jsdelivr.net/npm/lucide@0.562.0/+esm'
        
        // Initialize icons
        createIcons({
            icons: {
                Plus,
                Minus,
                X,
                ShoppingCart,
                Trash2,
                ArrowLeft
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
                <div class="flex-1 px-2 mx-2 font-bold text-xl">New Order</div>
                <div class="flex-none">
                    <a href="orders.php" class="btn btn-ghost">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to Orders
                    </a>
                </div>
            </div>

            <!-- Page content -->
            <div class="p-6">
                <?php if (isset($error)): ?>
                    <div class="alert alert-error mb-6">
                        <i data-lucide="alert-circle" class="w-6 h-6"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form id="orderForm" method="POST" action="new-order.php" class="flex flex-col lg:flex-row gap-6">
                    <!-- Products Grid -->
                    <div class="flex-1">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($products as $product): ?>
                                <div class="card bg-base-100 shadow-xl cursor-pointer hover:shadow-2xl transition-shadow" 
                                     onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    <div class="card-body p-4">
                                        <h3 class="card-title text-lg"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo format_peso($product['price']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="lg:w-96 bg-base-100 rounded-lg shadow-lg p-6 h-fit sticky top-24">
                        <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                        
                        <div id="orderItems" class="space-y-4 mb-6 max-h-96 overflow-y-auto">
                            <!-- Order items will be added here dynamically -->
                            <div class="text-center text-gray-500 py-8">
                                <i data-lucide="shopping-cart" class="w-12 h-12 mx-auto mb-2 opacity-30"></i>
                                <p>Add items to start an order</p>
                            </div>
                        </div>
                        
                        <div class="divider"></div>
                        
                        <div class="flex justify-between items-center mb-4">
                            <span class="font-bold">Total:</span>
                            <span id="orderTotal" class="text-xl font-bold">₱0.00</span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full" id="submitOrder" disabled>
                            Place Order
                        </button>
                    </div>
                </form>
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

    <script>
        let cart = [];
        
        function addToCart(product) {
            const existingItem = cart.find(item => item.id === product.id);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    quantity: 1
                });
            }
            
            updateCart();
        }
        
        function updateCart() {
            const orderItems = document.getElementById('orderItems');
            const orderTotal = document.getElementById('orderTotal');
            const submitButton = document.getElementById('submitOrder');
            
            if (cart.length === 0) {
                orderItems.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i data-lucide="shopping-cart" class="w-12 h-12 mx-auto mb-2 opacity-30"></i>
                        <p>Add items to start an order</p>
                    </div>
                `;
                orderTotal.textContent = '₱0.00';
                submitButton.disabled = true;
                return;
            }
            
            let total = 0;
            let itemsHtml = '';
            
            cart.forEach((item, index) => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                
                itemsHtml += `
                    <div class="flex items-center justify-between p-2 bg-base-200 rounded-lg">
                        <div>
                            <h4 class="font-medium">${item.name}</h4>
                            <p class="text-sm text-gray-500">$${item.price.toFixed(2)} each</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="btn btn-xs btn-square" onclick="updateQuantity(${index}, -1)">
                                <i data-lucide="minus" class="w-3 h-3"></i>
                            </button>
                            <span class="w-8 text-center">${item.quantity}</span>
                            <button type="button" class="btn btn-xs btn-square" onclick="updateQuantity(${index}, 1)">
                                <i data-lucide="plus" class="w-3 h-3"></i>
                            </button>
                            <button type="button" class="btn btn-xs btn-ghost text-error" onclick="removeItem(${index})">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            orderItems.innerHTML = itemsHtml;
            orderTotal.textContent = `$${total.toFixed(2)}`;
            submitButton.disabled = false;
            
            // Update hidden form fields
            updateFormData();
        }
        
        function updateQuantity(index, change) {
            const item = cart[index];
            item.quantity += change;
            
            if (item.quantity <= 0) {
                cart.splice(index, 1);
            }
            
            updateCart();
        }
        
        function removeItem(index) {
            cart.splice(index, 1);
            updateCart();
        }
        
        function updateFormData() {
            const form = document.getElementById('orderForm');
            
            // Clear existing hidden inputs
            const existingInputs = form.querySelectorAll('input[name^="items"]');
            existingInputs.forEach(input => input.remove());
            
            // Add new hidden inputs for each cart item
            cart.forEach((item, index) => {
                const productIdInput = document.createElement('input');
                productIdInput.type = 'hidden';
                productIdInput.name = `items[${index}][product_id]`;
                productIdInput.value = item.id;
                
                const quantityInput = document.createElement('input');
                quantityInput.type = 'hidden';
                quantityInput.name = `items[${index}][quantity]`;
                quantityInput.value = item.quantity;
                
                form.appendChild(productIdInput);
                form.appendChild(quantityInput);
            });
        }
    </script>
</body>
</html>
