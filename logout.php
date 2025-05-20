<?php
session_start();
require 'db_connection.php';

// If the user is logged in, save cart items to the database before logging out
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && !empty($_SESSION['cart'])) {
    $user_id = $_SESSION['id'];
    
    // First, clear the user's existing cart items in the database
    $clear_sql = "DELETE FROM cart WHERE user_id = ?";
    $clear_stmt = mysqli_prepare($conn, $clear_sql);
    mysqli_stmt_bind_param($clear_stmt, "i", $user_id);
    mysqli_stmt_execute($clear_stmt);
    mysqli_stmt_close($clear_stmt);
    
    // Then insert current cart items, but only if the product exists
    foreach ($_SESSION['cart'] as $product_id => $item) {
        // Check if the product exists first
        $check_product_sql = "SELECT 1 FROM products WHERE id = ? LIMIT 1";
        $check_product_stmt = mysqli_prepare($conn, $check_product_sql);
        mysqli_stmt_bind_param($check_product_stmt, "i", $product_id);
        mysqli_stmt_execute($check_product_stmt);
        mysqli_stmt_store_result($check_product_stmt);
        
        // Only insert if the product exists
        if (mysqli_stmt_num_rows($check_product_stmt) > 0) {
            $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "iii", $user_id, $product_id, $item['quantity']);
            
            // Use try-catch to handle potential foreign key constraint errors
            try {
                mysqli_stmt_execute($insert_stmt);
            } catch (Exception $e) {
                // Log error or simply ignore, since we're logging out anyway
            }
            
            mysqli_stmt_close($insert_stmt);
        }
        
        mysqli_stmt_close($check_product_stmt);
    }
    
    // Set a cookie to indicate this user just logged out
    // This helps prevent duplicate items on login
    setcookie('recent_logout', '1', time() + 86400, '/'); // 24 hour cookie
}

// Store cart items temporarily only if not logged in
// This prevents duplication when a logged in user logs out then back in
$temp_cart = [];
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $temp_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
}

$_SESSION = array(); // Clear the session variables
session_destroy(); // Destroy the session

// Start a new session to store the cart items for guest users
session_start();
$_SESSION['cart'] = $temp_cart;

header("location: index.php"); // Redirect to home page
exit;


?>