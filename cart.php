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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtSell - Your Cart</title>
    <link rel="stylesheet" href="css/styles.css">
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
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.cart-container {
  padding: 40px 20px;
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
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'components/header.php'; ?>

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
                    if (action === 'decrease' && currentValue > 1) {
                        inputField.value = currentValue - 1;
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