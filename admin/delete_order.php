<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../db_connection.php";

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No order ID provided.";
    header("location: order_new.php");
    exit;
}

$order_id = mysqli_real_escape_string($conn, $_GET['id']);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // First delete order items
    $delete_items_sql = "DELETE FROM order_items WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $delete_items_sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    $items_deleted = mysqli_stmt_execute($stmt);
    
    if (!$items_deleted) {
        throw new Exception("Failed to delete order items: " . mysqli_error($conn));
    }
    
    // Then delete the order
    $delete_order_sql = "DELETE FROM orders WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_order_sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    $order_deleted = mysqli_stmt_execute($stmt);
    
    if (!$order_deleted) {
        throw new Exception("Failed to delete order: " . mysqli_error($conn));
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    $_SESSION['success_message'] = "Order #$order_id has been successfully deleted.";
    header("location: order_new.php");
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    $_SESSION['error_message'] = "Error deleting order: " . $e->getMessage();
    header("location: view_order.php?id=" . $order_id);
    exit;
}
?> 