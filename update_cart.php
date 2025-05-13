<?php
session_start();
require 'db_connection.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [
        'success' => false,
        'message' => 'Invalid request',
        'cartCount' => 0,
        'total' => 0
    ];

    // Check if product_id and quantity are set
    if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

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
                $response['message'] = 'Cart updated successfully';
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
        
        // Calculate new total
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $price = isset($item['price']) ? (float)$item['price'] : 0;
            $total += $price * $item['quantity'];
        }
        
        $response['cartCount'] = count($_SESSION['cart']);
        $response['total'] = number_format($total, 2);
    }
    
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