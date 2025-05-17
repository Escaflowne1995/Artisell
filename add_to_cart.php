<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Store the product information in the session for after login
    if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
        // Store the pending cart addition
        $_SESSION['pending_cart_add'] = [
            'product_id' => intval($_POST['product_id']),
            'redirect' => isset($_POST['redirect']) ? $_POST['redirect'] : 'shop.php'
        ];
    }
    
    // Redirect to login page
    header("Location: login.php");
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
    $query = "SELECT id, name, price, stock, image, description FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($product = mysqli_fetch_assoc($result)) {
        // Check if there's enough stock
        if ($product['stock'] < $quantity) {
            $response = [
                'success' => false,
                'message' => 'Not enough stock available',
                'available' => $product['stock']
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
        
        if (isset($_SESSION['cart'][$product_id])) {
            // Update quantity
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            $product_in_cart = true;
        }
        
        // Add product to cart if not already there
        if (!$product_in_cart) {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }
        
        // Check if redirect parameter is set
        if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
            $redirect_url = $_POST['redirect'];
            
            // Preserve query parameters when redirecting back to shop.php
            if ($redirect_url === 'shop.php' && isset($_POST['current_url'])) {
                $redirect_url = $_POST['current_url'];
            }
            
            // Redirect to the specified page
            header("Location: " . $redirect_url);
            exit;
        } else {
            // Default redirect back to shop page if no redirect specified
            header("Location: shop.php");
            exit;
        }
        
        // Prepare success response
        $response = [
            'success' => true,
            'message' => 'Product added to cart',
            'cart_count' => count($_SESSION['cart']),
            'product_name' => $product['name']
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