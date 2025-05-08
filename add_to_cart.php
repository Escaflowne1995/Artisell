<?php
session_start();
require 'db_connection.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if a product_id was submitted
if (($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) || 
    ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['product_id']))) {
    // Validate product_id
    $product_id = 0;
    $redirect = 'cart.php';
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $product_id = $_POST['product_id'];
        if (isset($_POST['redirect'])) {
            $redirect = $_POST['redirect'];
        }
        // Check if it's buy now or add to cart
        $is_buy_now = isset($_POST['buy_now']);
    } else {
        $product_id = $_GET['product_id'];
        if (isset($_GET['redirect'])) {
            $redirect = $_GET['redirect'];
        }
        $is_buy_now = false;
    }
    
    if (empty($product_id) || !is_numeric($product_id)) {
        // Invalid product ID, redirect to shop
        header("Location: shop.php?error=invalid_product");
        exit;
    }
    
    $product_id = (int)$product_id;
    
    // Check if user is logged in
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        // Store product info in session for later
        $_SESSION['pending_cart_add'] = [
            'product_id' => $product_id,
            'redirect' => $redirect,
            'is_buy_now' => $is_buy_now
        ];
        
        // Directly redirect to login page with return URL
        $redirect_after_login = 'add_to_cart.php?product_id=' . $product_id . '&redirect=' . urlencode($redirect);
        header("Location: login.php?redirect=" . urlencode($redirect_after_login));
        exit;
    }
    
    // Validate quantity
    if (isset($_POST['quantity'])) {
        if (!is_numeric($_POST['quantity']) || (int)$_POST['quantity'] < 1) {
            // Invalid quantity, redirect to shop
            header("Location: shop.php?error=invalid_quantity");
            exit;
        }
        $quantity = (int)$_POST['quantity'];
    } else {
        $quantity = 1;
    }
    
    // Ensure quantity is at least 1
    $quantity = max(1, $quantity);
    
    // Get product details including stock
    $sql = "SELECT id, name, description, price, image, stock FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($product = mysqli_fetch_assoc($result)) {
        // Check if product is in stock
        if (isset($product['stock']) && $product['stock'] < 1) {
            // Product out of stock
            header("Location: shop.php?error=out_of_stock&product=" . $product_id);
            exit;
        }
        
        // Check if requested quantity is available
        if (isset($product['stock']) && $product['stock'] < $quantity) {
            // Not enough stock
            header("Location: shop.php?error=insufficient_stock&product=" . $product_id . "&available=" . $product['stock']);
            exit;
        }
        
        // Check if adding more would exceed stock
        $currentQuantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
        if (isset($product['stock']) && ($currentQuantity + $quantity) > $product['stock']) {
            // Would exceed available stock
            header("Location: shop.php?error=would_exceed_stock&product=" . $product_id . "&available=" . ($product['stock'] - $currentQuantity));
            exit;
        }
        
        // Check if product already in cart
        if (isset($_SESSION['cart'][$product_id])) {
            // Update quantity
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            // Add new product to cart
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }
        
        // If user is logged in, also update the database
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
            try {
                // Check if product exists in cart database
                $sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $_SESSION['id'], $product_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_fetch_assoc($result)) {
                    // Update quantity in database
                    $sql = "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iii", $quantity, $_SESSION['id'], $product_id);
                    mysqli_stmt_execute($stmt);
                } else {
                    // Insert new product in database
                    $sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iii", $_SESSION['id'], $product_id, $quantity);
                    mysqli_stmt_execute($stmt);
                }
            } catch (Exception $e) {
                // Database error
                error_log("Database error in add_to_cart.php: " . $e->getMessage());
                header("Location: shop.php?error=database_error");
                exit;
            }
        }
        
        // Validate redirect URL
        $redirect = 'cart.php'; // Default redirect
        if (isset($_POST['redirect'])) {
            $redirect = filter_var($_POST['redirect'], FILTER_SANITIZE_URL);
            // Only allow relative URLs to prevent open redirect vulnerability
            if (strpos($redirect, '://') !== false || strpos($redirect, '//') === 0) {
                $redirect = 'cart.php';
            }
        }
        
        header("Location: " . $redirect);
        exit;
    } else {
        // Product not found
        header("Location: shop.php?error=product_not_found");
        exit;
    }
}

// If we get here, something went wrong
header("Location: shop.php?error=unknown_error");
exit;
?> 