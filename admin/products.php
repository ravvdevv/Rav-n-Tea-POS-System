<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Products Management';
$userRole = $_SESSION['role'];
$username = htmlspecialchars($_SESSION['username']);

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new product
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = (float)$_POST['price'];
                $cost = (float)$_POST['cost'];
                
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, cost) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $cost]);
                $message = "Product added successfully!";
                break;
                
            case 'update':
                // Update product
                $id = (int)$_POST['id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = (float)$_POST['price'];
                $cost = (float)$_POST['cost'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, cost = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $cost, $is_active, $id]);
                $message = "Product updated successfully!";
                break;
                
            case 'delete':
                // Delete product
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Product deleted successfully!";
                break;
        }
    }
}

// Fetch all products
$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html data-theme="cupcake" class="min-h-full">
<head>
    <?php include '../includes/head.php'; ?>
    <title><?php echo $pageTitle; ?> - Rav n tea</title>
</head>
<body class="min-h-screen bg-base-200 flex flex-col">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="flex flex-1">
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Products Management</h1>
                <div class="text-sm breadcrumbs">
                    <ul>
                        <li><a href="index.php">Home</a></li> 
                        <li>Products</li>
                    </ul>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-success mb-6">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Add Product Form -->
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title mb-4">Add New Product</h2>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Product Name</span>
                            </label>
                            <input type="text" name="name" class="input input-bordered" required>
                        </div>
                        
                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text">Description</span>
                            </label>
                            <input type="text" name="description" class="input input-bordered">
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Price</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2">$</span>
                                <input type="number" name="price" step="0.01" min="0" class="input input-bordered pl-8" required>
                            </div>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Cost</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2">$</span>
                                <input type="number" name="cost" step="0.01" min="0" class="input input-bordered pl-8" required>
                            </div>
                        </div>
                        
                        <div class="form-control md:col-span-4">
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                Add Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">Product List</h2>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td>$<?php echo number_format($product['cost'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-error'; ?>">
                                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="flex gap-2">
                                        <button class="btn btn-sm btn-ghost" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>);">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-ghost text-error">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <dialog id="editModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Edit Product</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Product Name</span>
                    </label>
                    <input type="text" name="name" id="editName" class="input input-bordered w-full" required>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Description</span>
                    </label>
                    <input type="text" name="description" id="editDescription" class="input input-bordered w-full">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Price</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2">$</span>
                            <input type="number" name="price" id="editPrice" step="0.01" min="0" class="input input-bordered w-full pl-8" required>
                        </div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Cost</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2">$</span>
                            <input type="number" name="cost" id="editCost" step="0.01" min="0" class="input input-bordered w-full pl-8" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-2">
                        <input type="checkbox" name="is_active" id="editIsActive" class="checkbox checkbox-primary">
                        <span class="label-text">Active</span>
                    </label>
                </div>
                
                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('editModal').close()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        function editProduct(product) {
            document.getElementById('editId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editDescription').value = product.description || '';
            document.getElementById('editPrice').value = parseFloat(product.price).toFixed(2);
            document.getElementById('editCost').value = parseFloat(product.cost).toFixed(2);
            document.getElementById('editIsActive').checked = product.is_active == 1;
            
            const modal = document.getElementById('editModal');
            modal.showModal();
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
