<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if we have the necessary order information
if (!isset($_SESSION['order_id'])) {
    header("location: checkout.php");
    exit;
}

$order_id = $_SESSION['order_id'];

// Update order status to cancelled
$sql = "UPDATE orders SET status = 'Cancelled' WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);

// Clear session variables
unset($_SESSION['paypal_order_id']);
unset($_SESSION['order_id']);

// Redirect to checkout with error message
header("location: checkout.php?error=payment_cancelled&order_id=" . $order_id);
exit;
?> 