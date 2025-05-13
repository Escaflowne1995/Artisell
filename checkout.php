<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Store the current page as the redirect destination after login
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header("location: login.php");
    exit;
}

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize error variables
$nameError = $addressError = $cityError = $zipError = $paymentError = $csrfError = "";
$name = $address = $city = $zip = $payment_method = "";
$formValid = true;

// Fetch user's information from profile or most recent order
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// First try to get information from the most recent order
$sql = "SELECT shipping_name, shipping_address, shipping_city, shipping_zip, payment_method FROM orders 
        WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $shipping_info = mysqli_fetch_assoc($result);
    $name = $shipping_info['shipping_name'];
    $address = $shipping_info['shipping_address'];
    $city = $shipping_info['shipping_city'];
    $zip = $shipping_info['shipping_zip'];
    $payment_method = $shipping_info['payment_method'];
} else {
    // If no previous order, use profile information
    $name = $username; // Use username as default name
}

// Calculate total from cart
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $price = isset($item['price']) ? (float)$item['price'] : 0;
    $total += $price * $item['quantity'];
}

// Process order submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $csrfError = "Security verification failed. Please try again.";
        $formValid = false;
    }
    
    // Validate name
    if (empty(trim($_POST['name']))) {
        $nameError = "Please enter your full name";
        $formValid = false;
    } else {
        $name = trim($_POST['name']);
        // Check if name contains only letters and whitespace
        if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
            $nameError = "Only letters and white space allowed";
            $formValid = false;
        }
    }
    
    // Validate address
    if (empty(trim($_POST['address']))) {
        $addressError = "Please enter your address";
        $formValid = false;
    } else {
        $address = trim($_POST['address']);
    }
    
    // Validate city
    if (empty(trim($_POST['city']))) {
        $cityError = "Please enter your city";
        $formValid = false;
    } else {
        $city = trim($_POST['city']);
        // Check if city contains only letters and whitespace
        if (!preg_match("/^[a-zA-Z ]*$/", $city)) {
            $cityError = "Only letters and white space allowed";
            $formValid = false;
        }
    }
    
    // Validate zip code
    if (empty(trim($_POST['zip']))) {
        $zipError = "Please enter your zip code";
        $formValid = false;
    } else {
        $zip = trim($_POST['zip']);
        // Check if zip code contains only numbers
        if (!preg_match("/^[0-9]*$/", $zip)) {
            $zipError = "Only numbers allowed";
            $formValid = false;
        }
    }
    
    // Validate payment method
    if (empty($_POST['payment_method'])) {
        $paymentError = "Please select a payment method";
        $formValid = false;
    } else {
        $payment_method = $_POST['payment_method'];
        // Check if payment method is valid
        if (!in_array($payment_method, ['cod', 'gcash', 'paypal'])) {
            $paymentError = "Invalid payment method";
            $formValid = false;
        }
    }
    
    // If form is valid, process the order
    if ($formValid) {
        // Sanitize inputs
        $name = mysqli_real_escape_string($conn, $name);
        $address = mysqli_real_escape_string($conn, $address);
        $city = mysqli_real_escape_string($conn, $city);
        $zip = mysqli_real_escape_string($conn, $zip);
        $payment_method = mysqli_real_escape_string($conn, $payment_method);
        
        // Start a transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert order into database
            $sql = "INSERT INTO orders (user_id, total, shipping_name, shipping_address, shipping_city, shipping_zip, payment_method, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "idsssss", $_SESSION['id'], $total, $name, $address, $city, $zip, $payment_method);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);
    
            // Insert order items and update stock
            foreach ($_SESSION['cart'] as $product_id => $item) {
                // First, check current stock
                $check_stock_sql = "SELECT stock FROM products WHERE id = ?";
                $check_stmt = mysqli_prepare($conn, $check_stock_sql);
                mysqli_stmt_bind_param($check_stmt, "i", $product_id);
                mysqli_stmt_execute($check_stmt);
                $stock_result = mysqli_stmt_get_result($check_stmt);
                $stock_row = mysqli_fetch_assoc($stock_result);
                
                // Verify we have enough stock
                if (!$stock_row || $stock_row['stock'] < $item['quantity']) {
                    // Not enough stock, rollback and redirect with error
                    mysqli_rollback($conn);
                    header("location: cart.php?error=insufficient_stock&product=" . $product_id);
                    exit;
                }
                
                // Insert order item
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iiid", $order_id, $product_id, $item['quantity'], $item['price']);
                mysqli_stmt_execute($stmt);
                
                // Update product stock
                $new_stock = $stock_row['stock'] - $item['quantity'];
                $update_stock_sql = "UPDATE products SET stock = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_stock_sql);
                mysqli_stmt_bind_param($update_stmt, "ii", $new_stock, $product_id);
                mysqli_stmt_execute($update_stmt);
            }
    
            // Clear cart
            $sql = "DELETE FROM cart WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
            mysqli_stmt_execute($stmt);
            $_SESSION['cart'] = [];
            
            // Commit the transaction
            mysqli_commit($conn);
    
            // Generate a new CSRF token after successful form submission
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
            header("location: order_confirmation.php?order_id=" . $order_id);
            exit;
        } catch (Exception $e) {
            // Roll back the transaction on error
            mysqli_rollback($conn);
            $formValid = false;
            $message = "An error occurred while processing your order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtSell - Checkout</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body { background-color: #f9f9f9; color: #333; font-family: 'Open Sans', sans-serif; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        header { background: #fff; padding: 15px 0; border-bottom: 1px solid #eee; }
        .header-inner { display: flex; justify-content: space-between; align-items: center; }
        .logo a { color: #ff6b00; text-decoration: none; font-size: 20px; font-weight: bold; }
        nav ul { display: flex; list-style: none; margin: 0; padding: 0; }
        nav ul li { margin-left: 25px; }
        nav ul li a { color: #333; text-decoration: none; font-weight: 500; display: flex; align-items: center; }
        .profile-dropdown { position: relative; }
        .profile-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content { position: absolute; right: 0; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 4px; min-width: 120px; }
        .dropdown-content a { display: block; padding: 10px 15px; color: #333; text-decoration: none; }
        .dropdown-content a:hover { background: #f5f5f5; }
        .checkout-container { padding: 30px 0; display: flex; gap: 30px; }
        h1 { font-size: 24px; font-weight: 600; margin-bottom: 20px; color: #333; }
        .shipping-form { flex: 2; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .order-summary { flex: 1; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .payment-methods { margin: 20px 0; }
        .payment-methods h2 { font-size: 18px; font-weight: 600; margin-bottom: 10px; }
        .payment-methods label { display: block; margin: 10px 0; }
        .place-order-btn { width: 100%; padding: 12px; background: #ff6b00; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background 0.3s ease; }
        .place-order-btn:hover { background: #e65c00; }
        .order-summary h2 { font-size: 18px; font-weight: 600; margin-bottom: 15px; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-total { border-top: 1px solid #eee; padding-top: 10px; font-weight: 600; font-size: 18px; }

        .profile-link {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .profile-pic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        .error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        .info-message {
            background-color: #e6f7ff; 
            color: #0066cc; 
            padding: 12px 15px; 
            border-radius: 4px; 
            margin-bottom: 20px;
            border-left: 4px solid #0066cc;
            font-size: 14px;
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background-color: #f0f0f0;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 15px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #e0e0e0;
        }
        .back-button svg {
            width: 16px;
            height: 16px;
        }
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .header-container h1 {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-inner">
            <div class="logo"><a href="index.php">Art<span style="color: #333;">Sell</span></a></div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="cart.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16" style="margin-right: 5px;">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                        </svg>
                        <span>(<?php echo count($_SESSION['cart']); ?>)</span>
                    </a></li>
                    <li class="profile-dropdown">
                        <a href="profile.php" class="profile-link">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php if (!empty($_SESSION['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" class="profile-pic">
                            <?php else: ?>
                                <img src="images/default-profile.jpg" alt="Profile" class="profile-pic">
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="profileA.php">Settings</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Checkout Content -->
    <div class="container checkout-container">
        <div class="shipping-form">
            <div class="header-container">
                <a href="cart.php" class="back-button">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back to Cart
                </a>
            </div>
            <h1>Shipping Information</h1>
            <?php if(!empty($csrfError)): ?>
                <div class="error" style="margin-bottom: 15px;"><?php echo $csrfError; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($name) || !empty($address) || !empty($city) || !empty($zip)): ?>
                <div class="info-message">
                    Your shipping information has been pre-filled from your previous order. Please review and update if necessary.
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    <span class="error"><?php echo $nameError; ?></span>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                    <span class="error"><?php echo $addressError; ?></span>
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required>
                    <span class="error"><?php echo $cityError; ?></span>
                </div>
                <div class="form-group">
                    <label for="zip">Zip Code</label>
                    <input type="text" id="zip" name="zip" value="<?php echo htmlspecialchars($zip); ?>" required>
                    <span class="error"><?php echo $zipError; ?></span>
                </div>
                <div class="payment-methods">
                    <h2>Payment Method</h2>
                    <label><input type="radio" name="payment_method" value="cod" <?php if($payment_method === 'cod') echo 'checked'; ?> required> Cash on Delivery</label>
                    <label><input type="radio" name="payment_method" value="gcash" <?php if($payment_method === 'gcash') echo 'checked'; ?>> GCash</label>
                    <label><input type="radio" name="payment_method" value="paypal" <?php if($payment_method === 'paypal') echo 'checked'; ?>> PayPal</label>
                    <span class="error"><?php echo $paymentError; ?></span>
                </div>
                <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
            </form>
        </div>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="summary-item">
                    <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                    <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="summary-item summary-total">
                <span>Total:</span>
                <span>₱<?php echo number_format($total, 2); ?></span>
            </div>
        </div>
    </div>

</body>
</html>