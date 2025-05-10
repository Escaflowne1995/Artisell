<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../db_connection.php";

// Check if order ID and status are provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['status'])) {
    header("location: order_new.php");
    exit;
}

$order_id = mysqli_real_escape_string($conn, $_GET['id']);
$status = mysqli_real_escape_string($conn, $_GET['status']);

// Validate status
$valid_statuses = [
    'pending',
    'processing',
    'preparing',
    'ready_for_pickup',
    'out_for_delivery',
    'delivered',
    'completed',
    'cancelled',
    'refunded'
];
if (!in_array($status, $valid_statuses)) {
    header("location: view_order.php?id=" . $order_id);
    exit;
}

// Update order status
$sql = "UPDATE orders SET status = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $status, $order_id);

if (mysqli_stmt_execute($stmt)) {
    // Success - redirect back to order view
    header("location: view_order.php?id=" . $order_id);
} else {
    // Error handling
    echo "Error updating order status. Please try again.";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 