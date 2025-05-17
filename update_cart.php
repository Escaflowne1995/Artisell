<?php
session_start();
require 'db_connection.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [
        'success' => false,
        'message' => 'Invalid request',
        'cartCount' => 0,
        'total' => 0,
        'stockLimitReached' => false
    ];

    // Handle the regular form submission for a single product
    if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        // Check stock availability
        $stock_sql = "SELECT stock FROM products WHERE id = ?";
        $stock_stmt = mysqli_prepare($conn, $stock_sql);
        mysqli_stmt_bind_param($stock_stmt, "i", $product_id);
        mysqli_stmt_execute($stock_stmt);
        $stock_result = mysqli_stmt_get_result($stock_stmt);
        $stock_data = mysqli_fetch_assoc($stock_result);
        mysqli_stmt_close($stock_stmt);
        
        // If stock exists, limit quantity to available stock
        if ($stock_data) {
            $max_quantity = $stock_data['stock'];
            if ($quantity > $max_quantity) {
                $quantity = $max_quantity;
                $response['stockLimitReached'] = true;
                $response['message'] = 'Stock limit reached, quantity adjusted to available stock';
            }
        }

        // If quantity is greater than 0, update the cart
        if ($quantity > 0) {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                
                // If logged in, update database too
                if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
                    $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iii", $quantity, $_SESSION['id'], $product_id);
                    mysqli_stmt_execute($stmt);
                }
                
                $response['success'] = true;
                if (!$response['stockLimitReached']) {
                    $response['message'] = 'Cart updated successfully';
                }
            }
        } 
        // If quantity is 0, remove the item from cart
        else {
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                
                // If logged in, remove from database too
                if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
                    $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ii", $_SESSION['id'], $product_id);
                    mysqli_stmt_execute($stmt);
                }
                
                $response['success'] = true;
                $response['message'] = 'Item removed from cart';
            }
        }
    }
    // Handle the array of quantities from the cart form
    elseif (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $product_id = (int)$product_id;
            $quantity = (int)$quantity;
            
            // Check stock availability
            $stock_sql = "SELECT stock FROM products WHERE id = ?";
            $stock_stmt = mysqli_prepare($conn, $stock_sql);
            mysqli_stmt_bind_param($stock_stmt, "i", $product_id);
            mysqli_stmt_execute($stock_stmt);
            $stock_result = mysqli_stmt_get_result($stock_stmt);
            $stock_data = mysqli_fetch_assoc($stock_result);
            mysqli_stmt_close($stock_stmt);
            
            // If stock exists, limit quantity to available stock
            if ($stock_data) {
                $max_quantity = $stock_data['stock'];
                if ($quantity > $max_quantity) {
                    $quantity = $max_quantity;
                    $response['stockLimitReached'] = true;
                }
            }
            
            // If quantity is greater than 0, update the cart
            if ($quantity > 0) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                    
                    // If logged in, update database too
                    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
                        $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "iii", $quantity, $_SESSION['id'], $product_id);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
            // If quantity is 0, remove the item from cart
            elseif ($quantity == 0) {
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                    
                    // If logged in, remove from database too
                    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
                        $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['id'], $product_id);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
        }
        
        $response['success'] = true;
        if ($response['stockLimitReached']) {
            $response['message'] = 'Cart updated successfully, some items were limited by available stock';
        } else {
            $response['message'] = 'Cart updated successfully';
        }
    }
    
    // Calculate new total and cart count
    $total = 0;
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $price = isset($item['price']) ? (float)$item['price'] : 0;
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
        $total += $price * $quantity;
        $cart_count += $quantity;
    }
    
    $response['cartCount'] = $cart_count;
    $response['total'] = number_format($total, 2);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Redirect to cart page if accessed directly
    header('Location: cart.php');
    exit;
}
?> 