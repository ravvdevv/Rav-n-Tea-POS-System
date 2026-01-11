<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user role and redirect to appropriate dashboard
$userRole = $_SESSION['role'] ?? 'guest';

switch ($userRole) {
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    case 'barista':
        header('Location: barista/dashboard.php');
        break;
    case 'cashier':
        header('Location: cashier/dashboard.php');
        break;
    default:
        // If role is not recognized, show error or redirect to login
        session_destroy();
        header('Location: login.php?error=invalid_role');
        break;
}
exit();