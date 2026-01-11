<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Check if required parameters are provided
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = 'Invalid request parameters';
    header('Location: orders.php');
    exit();
}

$order_id = (int)$_GET['id'];
$new_status = $_GET['status'];

// Validate status
$valid_statuses = ['pending', 'in-progress', 'completed', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    $_SESSION['error'] = 'Invalid status provided';
    header('Location: orders.php');
    exit();
}

// Get current order status
$query = "SELECT status FROM orders WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = 'Order not found';
    header('Location: orders.php');
    exit();
}

// Don't update if status is the same
if ($order['status'] === $new_status) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'orders.php'));
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Update order status
    $update_query = "UPDATE orders SET status = :status, updated_at = NOW()";
    $params = ['id' => $order_id, 'status' => $new_status];
    
    // If completing the order, set completed_at timestamp
    if ($new_status === 'completed') {
        $update_query .= ", completed_at = NOW()";
    } elseif ($new_status === 'cancelled' && $order['status'] === 'completed') {
        // If cancelling a completed order, we might want to handle this differently
        // For now, we'll just update the status without changing completed_at
    }
    
    $update_query .= " WHERE id = :id";
    
    $stmt = $pdo->prepare($update_query);
    $stmt->execute($params);
    
    // If order is cancelled, we might want to handle inventory restocking here
    if ($new_status === 'cancelled' && $order['status'] === 'completed') {
        // This is a simplified example - in a real app, you'd want to:
        // 1. Check if the order was previously completed
        // 2. Restock the inventory items
        // 3. Log the cancellation reason
    }
    
    $pdo->commit();
    
    $_SESSION['success'] = "Order #" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . " status updated to " . ucfirst($new_status);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Failed to update order status: ' . $e->getMessage();
}

// Redirect back to the previous page or orders list
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'orders.php'));
exit();
