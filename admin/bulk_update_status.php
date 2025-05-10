<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../db_connection.php";

// Check if order IDs and status are provided
if (!isset($_POST['order_ids']) || !isset($_POST['status'])) {
    $_SESSION['error_message'] = "Missing required parameters";
    header("location: order_new.php");
    exit;
}

$order_ids = explode(',', $_POST['order_ids']);
$status = mysqli_real_escape_string($conn, $_POST['status']);

// Validate status
$valid_statuses = ['pending', 'processing', 'preparing', 'ready_for_pickup', 'out_for_delivery', 
                   'delivered', 'completed', 'cancelled', 'refunded'];
if (!in_array($status, $valid_statuses)) {
    $_SESSION['error_message'] = "Invalid status";
    header("location: order_new.php");
    exit;
}

// Prepare update statement
$sql = "UPDATE orders SET status = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
$success_count = 0;
$error_count = 0;

// Update each order
foreach ($order_ids as $order_id) {
    $order_id = intval($order_id);
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_count++;
    } else {
        $error_count++;
    }
}

// Set appropriate message
if ($error_count == 0) {
    $_SESSION['success_message'] = "Successfully updated " . $success_count . " order(s)";
} else {
    $_SESSION['error_message'] = "Updated " . $success_count . " order(s), failed to update " . $error_count . " order(s)";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

// Redirect back to orders page
header("location: order_new.php");
exit;
?> 