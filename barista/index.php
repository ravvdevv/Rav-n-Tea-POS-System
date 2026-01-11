<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a barista or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['barista', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Redirect to the order queue
header('Location: queue.php');
exit();
