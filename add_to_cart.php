<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Store the product information in the session for after login
    // Check both POST and GET requests for product_id and quantity
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : (isset($_GET['product_id']) ? $_GET['product_id'] : null);
    $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : (isset($_GET['quantity']) ? $_GET['quantity'] : null);
    
    if ($product_id && $quantity) {
        // Store the pending cart addition
        $_SESSION['pending_cart_add'] = [
            'product_id' => intval($product_id),
            'quantity' => intval($quantity),
            'redirect' => isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_GET['redirect']) ? $_GET['redirect'] : 'shop.php')
        ];
    }
    
    // Redirect to login page
    header("Location: login.php");
    exit;
}

// Include database connection
include 'db_connection.php';

// Check if form data is provided (either POST or GET)
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : (isset($_GET['product_id']) ? intval($_GET['product_id']) : 0);
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : (isset($_GET['quantity']) ? intval($_GET['quantity']) : 0);

// Check if data exists
if ($product_id > 0 && $quantity > 0) {
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
        
        // Get redirect URL from either POST or GET
        $redirect_url = 'shop.php'; // Default
        
        if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
            $redirect_url = $_POST['redirect'];
        } else if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
            $redirect_url = $_GET['redirect'];
        }
        
        // Preserve query parameters when redirecting back to shop.php
        if ($redirect_url === 'shop.php' && isset($_POST['current_url'])) {
            $redirect_url = $_POST['current_url'];
        }
        
        // Redirect to the specified page
        header("Location: " . $redirect_url);
        exit;
        
    } else {
        // Product not found
        $response = [
            'success' => false,
            'message' => 'Product not found'
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Close the database connection
    mysqli_close($conn);
    
} else {
    // Required parameters not provided
    $response = [
        'success' => false,
        'message' => 'Product ID and quantity are required'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?> 