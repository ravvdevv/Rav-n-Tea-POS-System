<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// At this point, the user is an admin
// You can add admin-specific functionality here

// Redirect to main admin dashboard
header('Location: dashboard.php');
exit();
