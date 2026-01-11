<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a cashier or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['cashier', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Redirect to the orders page
header('Location: orders.php');
exit();
