<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $response = [
        'success' => false,
        'message' => 'User not logged in',
        'redirect' => 'login.php'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Include database connection
include 'db_connection.php';

// Check if POST data is provided
if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Basic validation
    if ($product_id <= 0 || $quantity <= 0) {
        $response = [
            'success' => false,
            'message' => 'Invalid product ID or quantity'
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Check if product exists and has enough stock
    $query = "SELECT product_id, product_name, price, stock_quantity FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($product = mysqli_fetch_assoc($result)) {
        // Check if there's enough stock
        if ($product['stock_quantity'] < $quantity) {
            $response = [
                'success' => false,
                'message' => 'Not enough stock available',
                'available' => $product['stock_quantity']
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        
        // Initialize the cart if it doesn't exist
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if the product is already in the cart
        $product_in_cart = false;
        
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                // Update quantity
                $item['quantity'] += $quantity;
                $product_in_cart = true;
                break;
            }
        }
        
        // Add product to cart if not already there
        if (!$product_in_cart) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'name' => $product['product_name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
        
        // Prepare success response
        $response = [
            'success' => true,
            'message' => 'Product added to cart',
            'cart_count' => count($_SESSION['cart']),
            'product_name' => $product['product_name']
        ];
        
    } else {
        // Product not found
        $response = [
            'success' => false,
            'message' => 'Product not found'
        ];
    }
    
    // Close the database connection
    mysqli_close($conn);
    
} else {
    // Required parameters not provided
    $response = [
        'success' => false,
        'message' => 'Product ID and quantity are required'
    ];
}

// Set content type to JSON
header('Content-Type: application/json');

// Output the response
echo json_encode($response);
?> 