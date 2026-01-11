<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in to access this page';
    header('Location: ../login.php');
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'You do not have permission to access this page';
    header('Location: ../index.php');
    exit();
}

// Set username and userRole variables for use in the navbar
$username = $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'guest';
?>
