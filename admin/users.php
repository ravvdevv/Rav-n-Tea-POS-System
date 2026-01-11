<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'User Management';
$userRole = $_SESSION['role'];
$username = htmlspecialchars($_SESSION['username']);

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new user
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $hashedPassword, $role]);
                    $message = "User added successfully!";
                    $messageType = 'success';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = "Username already exists. Please choose a different username.";
                        $messageType = 'error';
                    } else {
                        $message = "Error adding user: " . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'update':
                // Update user
                $id = (int)$_POST['id'];
                $username = trim($_POST['username']);
                $role = $_POST['role'];
                
                // Check if we're updating the password
                $passwordUpdate = '';
                if (!empty($_POST['password'])) {
                    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";
                    $params = [$username, $hashedPassword, $role, $id];
                } else {
                    $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
                    $params = [$username, $role, $id];
                }
                
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $message = "User updated successfully!";
                    $messageType = 'success';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = "Username already exists. Please choose a different username.";
                        $messageType = 'error';
                    } else {
                        $message = "Error updating user: " . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete':
                // Delete user
                $id = (int)$_POST['id'];
                // Prevent deleting own account
                if ($id == $_SESSION['user_id']) {
                    $message = "You cannot delete your own account!";
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = "User deleted successfully!";
                    $messageType = 'success';
                }
                break;
        }
    }
}

// Get all users
$stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="text-3xl font-bold">User Management</h1>
                <div class="text-sm breadcrumbs">
                    <ul>
                        <li><a href="index.php">Home</a></li> 
                        <li>Users</li>
                    </ul>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $messageType === 'error' ? 'error' : 'success'; ?> mb-6">
                    <i data-lucide="<?php echo $messageType === 'error' ? 'alert-circle' : 'check-circle'; ?>" class="w-5 h-5"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Add User Form -->
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title mb-4">Add New User</h2>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Username</span>
                            </label>
                            <input type="text" name="username" class="input input-bordered" required>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Password</span>
                            </label>
                            <input type="password" name="password" class="input input-bordered" required>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Role</span>
                            </label>
                            <select name="role" class="select select-bordered w-full" required>
                                <option value="cashier">Cashier</option>
                                <option value="barista">Barista</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        
                        <div class="form-control md:col-span-4">
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i>
                                Add User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">System Users</h2>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                switch($user['role']) {
                                                    case 'admin': echo 'badge-primary';
                                                        break;
                                                    case 'cashier': echo 'badge-secondary';
                                                        break;
                                                    case 'barista': echo 'badge-accent';
                                                        break;
                                                    default: echo 'badge-ghost';
                                                }
                                            ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="flex gap-2">
                                        <button class="btn btn-sm btn-ghost" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>);">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-ghost text-error" title="Delete">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No users found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <dialog id="editModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Edit User</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Username</span>
                    </label>
                    <input type="text" name="username" id="editUsername" class="input input-bordered w-full" required>
                </div>
                
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">New Password (leave blank to keep current)</span>
                    </label>
                    <input type="password" name="password" id="editPassword" class="input input-bordered w-full">
                    <label class="label">
                        <span class="label-text-alt">Leave empty to keep current password</span>
                    </label>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Role</span>
                        </label>
                        <select name="role" id="editRole" class="select select-bordered w-full" required>
                            <option value="admin">Administrator</option>
                            <option value="cashier">Cashier</option>
                            <option value="barista">Barista</option>
                        </select>
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

    <script>
        function editUser(user) {
            document.getElementById('editId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editRole').value = user.role;
            
            const modal = document.getElementById('editModal');
            modal.showModal();
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
