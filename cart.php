<?php
session_start();
require 'db_connection.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// If user is logged in, sync session cart with database
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $sql = "SELECT c.product_id, c.quantity, p.name, p.description, p.price, p.image 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Merge database cart with session cart (database takes precedence)
    while ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['cart'][$row['product_id']] = [
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => $row['price'],
            'image' => $row['image'],
            'quantity' => $row['quantity']
        ];
    }
}

// Handle cart updates
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $quantity = max(1, (int)$quantity);
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
}

// Handle remove item
if (isset($_POST['remove_item'])) {
    $product_id = $_POST['product_id'];
    unset($_SESSION['cart'][$product_id]);
    
    // If logged in, remove from database too
    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
        $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['id'], $product_id);
        mysqli_stmt_execute($stmt);
    }
}

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $price = isset($item['price']) ? (float)$item['price'] : 0;
    $total += $price * $item['quantity'];
}

// Before displaying the cart items, check for out-of-stock products and update them
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $item) {
        // Check stock in database
        $stock_sql = "SELECT stock FROM products WHERE id = ?";
        $stock_stmt = mysqli_prepare($conn, $stock_sql);
        mysqli_stmt_bind_param($stock_stmt, "i", $product_id);
        mysqli_stmt_execute($stock_stmt);
        $stock_result = mysqli_stmt_get_result($stock_stmt);
        $stock_data = mysqli_fetch_assoc($stock_result);
        
        // If product is out of stock, update it
        if (!$stock_data || $stock_data['stock'] <= 0) {
            // Set a default stock value
            $default_stock = rand(15, 50);
            
            // Update in database
            $update_sql = "UPDATE products SET stock = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ii", $default_stock, $product_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }
        
        mysqli_stmt_close($stock_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtSell - Your Cart</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Your existing styles remain unchanged */
        * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: Arial, sans-serif;
}

body {
  background-color: #f9f9f9;
  color: #333;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.cart-container {
  padding: 40px 20px;
  flex: 1;
}

h1 {
  font-size: 28px;
  margin-bottom: 20px;
}

.cart-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border-radius: 5px;
  overflow: hidden;
}

.cart-table th {
  background-color: #f5f5f5;
  padding: 15px;
  text-align: left;
  font-weight: 600;
  color: #555;
}

.cart-table td {
  padding: 15px;
  border-top: 1px solid #eee;
  vertical-align: middle;
}

.cart-table img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  margin-right: 15px;
  border-radius: 4px;
}

.cart-table input[type="number"] {
  width: 60px;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

/* Quantity control styles */
.quantity-control {
  display: flex;
  align-items: center;
  border: 1px solid #ddd;
  border-radius: 4px;
  width: fit-content;
}

.quantity-btn {
  background: #f5f5f5;
  border: none;
  color: #333;
  font-size: 16px;
  width: 30px;
  height: 30px;
  line-height: 30px;
  text-align: center;
  cursor: pointer;
  user-select: none;
  padding: 0;
}

.quantity-btn:hover {
  background: #e0e0e0;
}

.quantity-input {
  width: 40px;
  border: none;
  text-align: center;
  font-size: 14px;
  padding: 5px 0;
}

.update-btn, .checkout-btn {
  background: #3b5998;
  color: white;
  border: none;
  padding: 12px 25px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
  margin-top: 20px;
}

.update-btn:hover, .checkout-btn:hover {
  background: #2a4373;
}

.checkout-btn {
  background: #ff6b00;
  margin-left: 10px;
}

.checkout-btn:hover {
  background: #e65c00;
}

.cart-summary {
  margin-top: 30px;
  background: white;
  padding: 20px;
  border-radius: 5px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.cart-summary h2 {
  font-size: 20px;
  margin-bottom: 15px;
}

.cart-total {
  font-size: 24px;
  font-weight: 600;
  color: #ff6b00;
}

.continue-shopping {
  display: inline-block;
  margin-top: 20px;
  color: #3b5998;
  text-decoration: none;
}

.continue-shopping:hover {
  text-decoration: underline;
}

.empty-cart {
  padding: 60px 0;
  text-align: center;
}

.empty-cart p {
  font-size: 18px;
  margin-bottom: 20px;
  color: #555;
}

.empty-cart a {
  display: inline-block;
  background: #3b5998;
  color: white;
  padding: 12px 25px;
  border-radius: 4px;
  text-decoration: none;
}

.empty-cart a:hover {
  background: #2a4373;
}

.remove-btn {
  background: #f44336;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
}

.remove-btn:hover {
  background: #d32f2f;
}

/* Profile styles for header */
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

/* Navigation bar styling */
header {
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.header-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
}

.logo a {
    font-size: 24px;
    font-weight: 700;
    color: #ff6b00;
    text-decoration: none;
}

nav ul {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    align-items: center;
}

nav ul li {
    margin-left: 25px;
}

nav ul li:first-child {
    margin-left: 0;
}

.nav-link {
    text-decoration: none;
    color: #333;
    font-weight: bold;
    transition: color 0.3s;
    display: flex;
    align-items: center;
}

.nav-link:hover {
    color: #ff6b00;
}

.profile-dropdown {
    position: relative;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #fff;
    min-width: 160px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    z-index: 10;
    border-radius: 4px;
}

.dropdown-item {
    display: block;
    padding: 12px 15px;
    text-decoration: none;
    color: #333;
    font-weight: bold;
    transition: background-color 0.3s;
}

.dropdown-item:hover {
    background-color: #f5f5f5;
    color: #ff6b00;
}

.profile-dropdown:hover .dropdown-content {
    display: block;
}

/* Footer styling */
footer {
    background-color: #333;
    color: #fff;
    padding: 40px 0 20px;
    margin-top: auto;
}

.footer-content {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
}

.footer-section {
    flex: 1;
    min-width: 200px;
    margin-bottom: 20px;
    padding-right: 20px;
}

.footer-section h3 {
    font-size: 18px;
    margin-bottom: 15px;
    font-weight: bold;
    color: #ff6b00;
}

.footer-section p {
    line-height: 1.6;
    margin-bottom: 10px;
}

.footer-links a {
    display: block;
    color: #fff;
    text-decoration: none;
    margin-bottom: 8px;
    transition: color 0.3s;
}

.footer-links a:hover {
    color: #ff6b00;
}

.contact-info span {
    display: block;
    margin-bottom: 8px;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 15px;
}

.social-links a {
    color: #fff;
    font-size: 18px;
    transition: color 0.3s;
}

.social-links a:hover {
    color: #ff6b00;
}

.footer-bottom {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #444;
    margin-top: 20px;
    font-size: 14px;
}
    </style>
</head>
<body>
    <!-- Header -->
    <header>
    <div class="container header-inner">
        <div class="logo">
            <a href="#">Arti<span style="color: #333;">Sell</span></a>
        </div>
        <nav>
            <ul>
                <li><a href="shop.php" class="nav-link">Shop</a></li>
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                        <li><a href="add_product.php" class="nav-link">Add Product</a></li>
                    <?php else: ?>
                        <li><a href="cart.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16" style="margin-right: 5px;">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                        </svg> (<?php echo count($_SESSION['cart']); ?>)</a></li>
                    <?php endif; ?>
                    <li class="profile-dropdown">
                        <a href="profile.php" class="nav-link profile-link">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php if (!empty($_SESSION['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" class="profile-pic">
                            <?php else: ?>
                                <img src="images/default-profile.jpg" alt="Profile" class="profile-pic">
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="settings.php" class="dropdown-item">Settings</a>
                            <a href="logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link">Login</a></li>
                    <li><a href="signup.php" class="nav-link">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

    <!-- Cart Content -->
    <div class="container cart-container">
        <h1>Your Cart</h1>
        <?php if (!empty($_SESSION['cart'])): ?>
            <form method="POST">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <div class="quantity-control">
                                        <button type="button" class="quantity-btn" data-product-id="<?php echo $product_id; ?>" data-action="decrease">-</button>
                                        <input type="text" name="quantity[<?php echo $product_id; ?>]" value="<?php echo $item['quantity']; ?>" class="quantity-input">
                                        <button type="button" class="quantity-btn" data-product-id="<?php echo $product_id; ?>" data-action="increase">+</button>
                                    </div>
                                </td>
                                <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <button type="submit" name="remove_item" class="remove-btn">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <button type="submit" name="update_cart" class="update-btn">Update Cart</button>
                    <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <a href="checkout.php" class="checkout-btn" style="display: inline-block;">Proceed to Checkout</a>
                    <?php else: ?>
                        <a href="login.php?redirect_after_login=checkout.php" class="checkout-btn" style="display: inline-block;">Login to Checkout</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="cart-summary">
                <h2>Cart Summary</h2>
                <p>Total: <span class="cart-total">₱<?php echo number_format($total, 2); ?></span></p>
            </div>

            <a href="shop.php" class="continue-shopping">Continue Shopping</a>
        <?php else: ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <a href="shop.php">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all quantity buttons
            const quantityBtns = document.querySelectorAll('.quantity-btn');
            
            // Add event listeners to each button
            quantityBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.getAttribute('data-action');
                    const productId = this.getAttribute('data-product-id');
                    const inputField = this.parentNode.querySelector('.quantity-input');
                    let currentValue = parseInt(inputField.value);
                    
                    // Decrease quantity
                    if (action === 'decrease') {
                        if (currentValue > 1) {
                            inputField.value = currentValue - 1;
                        } else if (currentValue === 1) {
                            // Set quantity to 0 first
                            inputField.value = 0;
                            
                            // Add a small delay before removing the item
                            setTimeout(() => {
                                const removeForm = this.closest('tr').querySelector('form');
                                removeForm.submit(); // This will remove the item
                            }, 500); // 500ms delay to show the zero
                        }
                    }
                    // Increase quantity
                    else if (action === 'increase') {
                        inputField.value = currentValue + 1;
                    }
                });
            });
            
            // Ensure manual input is a valid number
            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Make sure it's a number and at least 1
                    const value = parseInt(this.value);
                    if (isNaN(value) || value < 1) {
                        this.value = 1;
                    } else {
                        this.value = value;
                    }
                });
            });
        });
    </script>
</body>
</html>