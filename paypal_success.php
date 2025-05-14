<?php
session_start();
require 'db_connection.php';
require 'components/paypal_api.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if we have the necessary PayPal parameters
if (!isset($_GET['token']) || !isset($_SESSION['paypal_order_id'])) {
    header("location: checkout.php?error=invalid_paypal_response");
    exit;
}

$paypal_order_id = $_SESSION['paypal_order_id'];
$order_id = $_SESSION['order_id'];

// Capture the payment
$capture_result = capturePayPalPayment($paypal_order_id);

if ($capture_result && isset($capture_result['status']) && $capture_result['status'] == 'COMPLETED') {
    // Payment was successful, update order status
    $sql = "UPDATE orders SET status = 'Paid' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    
    // Clear session variables
    unset($_SESSION['paypal_order_id']);
    unset($_SESSION['order_id']);
    
    // Redirect to order confirmation
    header("location: order_confirmation.php?order_id=" . $order_id . "&payment_status=success");
    exit;
} else {
    // Payment failed
    header("location: checkout.php?error=payment_failed");
    exit;
}
?> 