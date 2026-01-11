<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Inventory Management';
$userRole = $_SESSION['role'];
$username = htmlspecialchars($_SESSION['username']);

// Handle inventory actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new inventory item
                $name = $_POST['name'];
                $quantity = (float)$_POST['quantity'];
                $unit = $_POST['unit'];
                $threshold = (float)$_POST['threshold'];
                
                $stmt = $pdo->prepare("INSERT INTO inventory_items (name, quantity, unit, threshold) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $quantity, $unit, $threshold]);
                $message = "Inventory item added successfully!";
                break;
                
            case 'update':
                // Update inventory item
                $id = (int)$_POST['id'];
                $name = $_POST['name'];
                $quantity = (float)$_POST['quantity'];
                $unit = $_POST['unit'];
                $threshold = (float)$_POST['threshold'];
                
                $stmt = $pdo->prepare("UPDATE inventory_items SET name = ?, quantity = ?, unit = ?, threshold = ? WHERE id = ?");
                $stmt->execute([$name, $quantity, $unit, $threshold, $id]);
                $message = "Inventory item updated successfully!";
                break;
                
            case 'delete':
                // Delete inventory item
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Inventory item deleted successfully!";
                break;
                
            case 'adjust':
                // Adjust inventory quantity
                $id = (int)$_POST['id'];
                $adjustment = (float)$_POST['adjustment'];
                $note = $_POST['note'] ?? '';
                
                // Get current quantity
                $stmt = $pdo->prepare("SELECT quantity FROM inventory_items WHERE id = ?");
                $stmt->execute([$id]);
                $current = $stmt->fetchColumn();
                
                // Update quantity
                $newQuantity = $current + $adjustment;
                $stmt = $pdo->prepare("UPDATE inventory_items SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQuantity, $id]);
                
                // Log the adjustment
                $stmt = $pdo->prepare("INSERT INTO inventory_history (inventory_item_id, adjustment, note, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id, $adjustment, $note, $_SESSION['user_id']]);
                
                $message = "Inventory adjusted successfully!";
                break;
        }
    }
}

// Fetch all inventory items
$inventory = $pdo->query("SELECT * FROM inventory_items ORDER BY name")->fetchAll();

// Fetch inventory history (if table exists)
$history = [];
try {
    $history = $pdo->query("
        SELECT h.*, i.name as item_name, u.username 
        FROM inventory_history h
        JOIN inventory_items i ON h.inventory_item_id = i.id
        LEFT JOIN users u ON h.created_by = u.id
        ORDER BY h.created_at DESC
        LIMIT 50
    ")->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet
}
?>

<!DOCTYPE html>
<html data-theme="cupcake" class="min-h-full">
<head>
    <?php include '../includes/head.php'; ?>
    <title><?php echo $pageTitle; ?> - CafD</title>
</head>
<body class="min-h-screen bg-base-200 flex flex-col">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="flex flex-1">
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Inventory Management</h1>
                <div class="text-sm breadcrumbs">
                    <ul>
                        <li><a href="index.php">Home</a></li> 
                        <li>Inventory</li>
                    </ul>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-success mb-6">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Add Inventory Item Form -->
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title mb-4">Add New Inventory Item</h2>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Item Name</span>
                            </label>
                            <input type="text" name="name" class="input input-bordered" required>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Quantity</span>
                            </label>
                            <input type="number" name="quantity" step="0.01" min="0" class="input input-bordered" required>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Unit</span>
                            </label>
                            <select name="unit" class="select select-bordered w-full" required>
                                <option value="g">Grams (g)</option>
                                <option value="kg">Kilograms (kg)</option>
                                <option value="ml">Milliliters (ml)</option>
                                <option value="L">Liters (L)</option>
                                <option value="pcs">Pieces</option>
                                <option value="box">Boxes</option>
                                <option value="pack">Packs</option>
                            </select>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Low Stock Threshold</span>
                            </label>
                            <input type="number" name="threshold" step="0.01" min="0" class="input input-bordered" required>
                        </div>
                        
                        <div class="form-control md:col-span-4">
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                                Add Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Inventory List -->
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title mb-4">Inventory Items</h2>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory as $item): 
                                    $isLow = $item['quantity'] <= $item['threshold'];
                                ?>
                                <tr class="<?php echo $isLow ? 'bg-error/10' : ''; ?>">
                                    <td><?php echo $item['id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo number_format($item['quantity'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td>
                                        <?php if ($isLow): ?>
                                            <span class="badge badge-error">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="flex gap-2">
                                        <button class="btn btn-sm btn-ghost" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>);">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                        <button class="btn btn-sm btn-ghost" onclick="showAdjustmentModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>');">
                                            <i data-lucide="plus-minus" class="w-4 h-4"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
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

            <!-- Inventory History -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">Inventory History</h2>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Item</th>
                                    <th>Adjustment</th>
                                    <th>Note</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $record): ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i A', strtotime($record['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['item_name']); ?></td>
                                    <td class="font-mono <?php echo $record['adjustment'] >= 0 ? 'text-success' : 'text-error'; ?>">
                                        <?php echo ($record['adjustment'] >= 0 ? '+' : '') . number_format($record['adjustment'], 2); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['note']); ?></td>
                                    <td><?php echo htmlspecialchars($record['username'] ?? 'System'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No inventory history found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <dialog id="editModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Edit Inventory Item</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Item Name</span>
                    </label>
                    <input type="text" name="name" id="editName" class="input input-bordered w-full" required>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Quantity</span>
                        </label>
                        <input type="number" name="quantity" id="editQuantity" step="0.01" min="0" class="input input-bordered w-full" required>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Unit</span>
                        </label>
                        <select name="unit" id="editUnit" class="select select-bordered w-full" required>
                            <option value="g">Grams (g)</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="ml">Milliliters (ml)</option>
                            <option value="L">Liters (L)</option>
                            <option value="pcs">Pieces</option>
                            <option value="box">Boxes</option>
                            <option value="pack">Packs</option>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Low Stock Threshold</span>
                        </label>
                        <input type="number" name="threshold" id="editThreshold" step="0.01" min="0" class="input input-bordered w-full" required>
                    </div>
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

    <!-- Adjust Inventory Modal -->
    <dialog id="adjustModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Adjust Inventory</h3>
            <form method="POST" id="adjustForm">
                <input type="hidden" name="action" value="adjust">
                <input type="hidden" name="id" id="adjustId">
                
                <div class="mb-4">
                    <p>Item: <span id="adjustItemName" class="font-semibold"></span></p>
                    <p>Current Quantity: <span id="currentQuantity" class="font-mono">0</span> <span id="itemUnit"></span></p>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Adjustment</span>
                    </label>
                    <div class="join w-full">
                        <button type="button" class="btn join-item" onclick="updateAdjustment(-1)">-1</button>
                        <button type="button" class="btn join-item" onclick="updateAdjustment(-0.5)">-0.5</button>
                        <input type="number" name="adjustment" id="adjustment" step="0.01" class="input input-bordered join-item flex-1 text-center" value="0" required>
                        <button type="button" class="btn join-item" onclick="updateAdjustment(0.5)">+0.5</button>
                        <button type="button" class="btn join-item" onclick="updateAdjustment(1)">+1</button>
                    </div>
                    <div class="flex justify-between text-xs mt-1">
                        <span>Use negative to remove</span>
                        <span>New total: <span id="newTotal" class="font-mono">0</span> <span id="newTotalUnit"></span></span>
                    </div>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Note (Optional)</span>
                    </label>
                    <input type="text" name="note" class="input input-bordered w-full" placeholder="e.g., Received new shipment">
                </div>
                
                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('adjustModal').close()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                        Save Adjustment
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        function editItem(item) {
            document.getElementById('editId').value = item.id;
            document.getElementById('editName').value = item.name;
            document.getElementById('editQuantity').value = parseFloat(item.quantity).toFixed(2);
            document.getElementById('editUnit').value = item.unit;
            document.getElementById('editThreshold').value = parseFloat(item.threshold).toFixed(2);
            
            const modal = document.getElementById('editModal');
            modal.showModal();
        }
        
        function showAdjustmentModal(id, name) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            const quantity = parseFloat(row.querySelector('td:nth-child(3)').textContent);
            const unit = row.querySelector('td:nth-child(4)').textContent;
            
            document.getElementById('adjustId').value = id;
            document.getElementById('adjustItemName').textContent = name;
            document.getElementById('currentQuantity').textContent = quantity.toFixed(2);
            document.getElementById('itemUnit').textContent = unit;
            document.getElementById('newTotalUnit').textContent = unit;
            document.getElementById('adjustment').value = '0';
            document.getElementById('newTotal').textContent = quantity.toFixed(2);
            
            const modal = document.getElementById('adjustModal');
            modal.showModal();
        }
        
        function updateAdjustment(value) {
            const input = document.getElementById('adjustment');
            const currentValue = parseFloat(input.value) || 0;
            const newValue = currentValue + value;
            input.value = newValue.toFixed(2);
            updateTotal();
        }
        
        document.getElementById('adjustment').addEventListener('input', updateTotal);
        
        function updateTotal() {
            const current = parseFloat(document.getElementById('currentQuantity').textContent) || 0;
            const adjustment = parseFloat(document.getElementById('adjustment').value) || 0;
            const newTotal = current + adjustment;
            document.getElementById('newTotal').textContent = newTotal.toFixed(2);
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
