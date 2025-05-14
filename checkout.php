<?php
session_start();
require 'db_connection.php';
require 'components/paypal_api.php'; // Add PayPal API include

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
            
            // Store order ID in session for payment processing
            $_SESSION['order_id'] = $order_id;
            
            // Handle different payment methods
            if ($payment_method === 'paypal') {
                // Check if PayPal credentials are properly configured
                if (PAYPAL_CLIENT_ID === 'YOUR_PAYPAL_CLIENT_ID' || PAYPAL_CLIENT_SECRET === 'YOUR_PAYPAL_CLIENT_SECRET') {
                    header("location: checkout.php?error=paypal_not_configured");
                    exit;
                }
                
                // Create PayPal order
                $paypal_order = createPayPalOrder($total);
                
                if ($paypal_order && isset($paypal_order['id'])) {
                    // Store PayPal order ID in session
                    $_SESSION['paypal_order_id'] = $paypal_order['id'];
                    
                    // Find the approval URL
                    $approval_url = '';
                    foreach ($paypal_order['links'] as $link) {
                        if ($link['rel'] === 'approve') {
                            $approval_url = $link['href'];
                            break;
                        }
                    }
                    
                    if (!empty($approval_url)) {
                        // Redirect to PayPal for payment
                        header("location: " . $approval_url);
                        exit;
                    }
                }
                
                // If we get here, something went wrong with PayPal
                header("location: checkout.php?error=paypal_error");
                exit;
            } else {
                // For other payment methods, go directly to confirmation
                header("location: order_confirmation.php?order_id=" . $order_id);
                exit;
            }
        } catch (Exception $e) {
            // Roll back the transaction on error
            mysqli_rollback($conn);
            $formValid = false;
            $message = "An error occurred while processing your order: " . $e->getMessage();
        }
    }
}

// Handle PayPal errors
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'payment_failed':
            $error_message = 'Your PayPal payment failed. Please try again or choose a different payment method.';
            break;
        case 'payment_cancelled':
            $error_message = 'You cancelled your PayPal payment. Please try again or choose a different payment method.';
            break;
        case 'paypal_error':
            $error_message = 'There was an error connecting to PayPal. Please try again later.';
            break;
        case 'paypal_not_configured':
            $error_message = 'PayPal is not properly configured on this site. Please choose a different payment method or contact the site administrator.';
            break;
        case 'invalid_paypal_response':
            $error_message = 'Invalid response from PayPal. Please try again.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtSell - Checkout</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2E8B57;         /* Sea Green primary */
            --primary-dark: #1e6e45;    /* Dark green */
            --primary-light: #3cb371;   /* Light green */
            --secondary: #1a3d55;       /* Deep blue */
            --secondary-light: #2c5a7c; /* Medium blue */
            --accent: #4fb3ff;
            --blue: #0066cc;           /* Logo blue */
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --gray: #6c757d;
            --gray-light: #f1f3f5;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo a {
            text-decoration: none;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        /* Navigation styles */
        nav {
            flex: 1;
            display: flex;
            justify-content: flex-end;
        }

        .nav-links {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .nav-links li {
            margin-left: 25px;
        }

        .nav-links li a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: var(--transition);
            font-size: 15px;
        }

        .nav-links li a:hover {
            color: var(--primary);
        }

        .profile-dropdown {
            position: relative;
            margin-left: auto;
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-content {
            position: absolute;
            right: 0;
            background: white;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            min-width: 180px;
            opacity: 0;
            transform: translateY(10px);
            transition: var(--transition);
            display: none;
            overflow: hidden;
        }

        .dropdown-content a {
            display: block;
            padding: 12px 20px;
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
            border-bottom: 1px solid var(--gray-light);
        }

        .dropdown-content a:last-child {
            border-bottom: none;
        }

        .dropdown-content a:hover {
            background: var(--gray-light);
        }
        
        /* Profile Link */
        .profile-link {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-pic {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }
        
        /* Checkout Container */
        .checkout-container {
            padding: 40px 0;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
            }
        }

        h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 25px;
            color: var(--secondary);
        }

        /* Shipping Form */
        .shipping-form {
            flex: 2;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            min-width: 300px;
        }

        /* Order Summary */
        .order-summary {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            height: fit-content;
            position: sticky;
            top: 100px;
            min-width: 250px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary);
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            font-size: 15px;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
        }

        /* Payment Methods */
        .payment-methods {
            margin: 30px 0;
            background-color: var(--gray-light);
            padding: 20px;
            border-radius: var(--border-radius);
        }

        .payment-methods h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--secondary);
        }

        .payment-option {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 10px;
            background: white;
            border-radius: var(--border-radius);
            transition: var(--transition);
            cursor: pointer;
        }

        .payment-option:hover {
            box-shadow: var(--box-shadow);
        }

        .payment-option input[type="radio"] {
            margin-right: 15px;
            accent-color: var(--primary);
            width: 18px;
            height: 18px;
        }

        .payment-option label {
            display: flex;
            align-items: center;
            cursor: pointer;
            width: 100%;
            font-weight: 500;
        }

        .payment-option i {
            margin-right: 10px;
            font-size: 20px;
        }

        .place-order-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .place-order-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .place-order-btn:active {
            transform: translateY(0);
        }

        /* Order Summary Styles */
        .order-summary h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--secondary);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gray-light);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--gray-light);
        }

        .summary-item .item-name {
            display: flex;
            align-items: center;
        }

        .summary-item .item-quantity {
            background-color: var(--gray-light);
            color: var(--secondary);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 8px;
        }

        .summary-total {
            border-top: 2px solid var(--gray-light);
            padding-top: 15px;
            font-weight: 600;
            font-size: 18px;
            color: var(--secondary);
            margin-top: 10px;
        }

        .summary-total .total-amount {
            color: var(--primary);
            font-size: 20px;
        }

        /* Error Messages */
        .error {
            color: var(--danger);
            font-size: 13px;
            margin-top: 5px;
            display: block;
        }

        /* Info Message */
        .info-message {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 4px solid var(--info);
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .info-message i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background-color: var(--gray-light);
            border-radius: var(--border-radius);
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .back-button:hover {
            background-color: var(--gray);
            color: white;
        }

        /* Header Container */
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .header-container h1 {
            margin-bottom: 0;
        }

        /* Alert */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: var(--danger);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .checkout-container {
                padding: 20px 0;
            }
            
            .shipping-form, .order-summary {
                padding: 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .form-group input {
                padding: 10px;
            }
            
            .place-order-btn {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-inner">
            <div class="logo"><a href="index.php"><span class="text-green">Arti</span><span class="text-blue">Sell</span></a></div>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="shop.php"><i class="fas fa-store"></i> Shop</a></li>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> (<?php echo count($_SESSION['cart']); ?>)</a></li>
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
                            <a href="profileA.php"><i class="fas fa-user-cog"></i> Settings</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                    <i class="fas fa-arrow-left"></i> Back to Cart
                </a>
            </div>
            <h1>Shipping Information</h1>
            <?php if(!empty($csrfError)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $csrfError; ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($name) || !empty($address) || !empty($city) || !empty($zip)): ?>
                <div class="info-message">
                    <i class="fas fa-info-circle"></i> Your shipping information has been pre-filled from your previous order. Please review and update if necessary.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required placeholder="Enter your full name">
                    <?php if(!empty($nameError)): ?><span class="error"><?php echo $nameError; ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" required placeholder="Enter your address">
                    <?php if(!empty($addressError)): ?><span class="error"><?php echo $addressError; ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="city"><i class="fas fa-city"></i> City</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required placeholder="Enter your city">
                    <?php if(!empty($cityError)): ?><span class="error"><?php echo $cityError; ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="zip"><i class="fas fa-mail-bulk"></i> Zip Code</label>
                    <input type="text" id="zip" name="zip" value="<?php echo htmlspecialchars($zip); ?>" required placeholder="Enter your zip code">
                    <?php if(!empty($zipError)): ?><span class="error"><?php echo $zipError; ?></span><?php endif; ?>
                </div>
                <div class="payment-methods">
                    <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
                    <div class="payment-option">
                        <input type="radio" id="cod" name="payment_method" value="cod" <?php if($payment_method === 'cod') echo 'checked'; ?> required>
                        <label for="cod"><i class="fas fa-money-bill-wave"></i> Cash on Delivery</label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="gcash" name="payment_method" value="gcash" <?php if($payment_method === 'gcash') echo 'checked'; ?>>
                        <label for="gcash"><i class="fas fa-wallet"></i> GCash</label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="paypal" name="payment_method" value="paypal" <?php if($payment_method === 'paypal') echo 'checked'; ?>>
                        <label for="paypal"><i class="fab fa-paypal"></i> PayPal</label>
                    </div>
                    <?php if(!empty($paymentError)): ?><span class="error"><?php echo $paymentError; ?></span><?php endif; ?>
                </div>
                <button type="submit" name="place_order" class="place-order-btn">
                    <i class="fas fa-check-circle"></i> Place Order
                </button>
            </form>
        </div>

        <div class="order-summary">
            <h2><i class="fas fa-clipboard-list"></i> Order Summary</h2>
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="summary-item">
                    <div class="item-name">
                        <?php echo htmlspecialchars($item['name']); ?>
                        <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                    </div>
                    <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="summary-item summary-total">
                <span>Total:</span>
                <span class="total-amount">₱<?php echo number_format($total, 2); ?></span>
            </div>
        </div>
    </div>

</body>
</html>