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

// Array to store stock information for JavaScript
$stock_info = [];

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
            
            // Set the stock value for JavaScript
            $stock_info[$product_id] = $default_stock;
        } else {
            // Store the stock value for JavaScript
            $stock_info[$product_id] = $stock_data['stock'];
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
    <title>ArtiSell - Your Cart</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 400px;
            text-align: center;
            transform: translateY(-20px);
            transition: transform 0.3s;
        }
        
        .modal-overlay.active .modal-container {
            transform: translateY(0);
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .modal-message {
            margin-bottom: 1.5rem;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
        }
        
        .modal-btn-cancel {
            background-color: #e5e7eb;
            color: #111827;
        }
        
        .modal-btn-confirm {
            background-color: #ef4444;
            color: white;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="index.php" class="logo">Arti<span class="text-blue-logo">Sell</span></a>
            
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="shop.php" class="nav-link">Shop</a></li>
                    <li><a href="cities.php" class="nav-link">Cities</a></li>
                    <li><a href="about.php" class="nav-link">About</a></li>
                </ul>
            </nav>
            
            <div class="header-right">
                <a href="cart.php" class="nav-link active">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    $cart_count = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $cart_count += $item['quantity'];
                        }
                    }
                    if ($cart_count > 0) {
                        echo "<span>($cart_count)</span>";
                    }
                    ?>
                </a>
                
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <div class="profile-dropdown">
                        <a href="#" class="profile-link">
                            <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-content">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a href="settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                    <a href="signup.php" class="btn btn-primary btn-sm">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="container cart-container">
        <h1 class="mb-4">Your Cart</h1>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="card p-5 text-center">
                <i class="fas fa-shopping-cart fa-4x text-neutral-500 mb-3"></i>
                <h2 class="mb-3">Your cart is empty</h2>
                <p class="mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="shop.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="card mb-5">
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
                                    <div class="cart-product">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-product-img">
                                        <span class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td class="fw-semibold">₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <form method="post" action="update_cart.php" class="d-flex align-items-center">
                                        <div class="quantity-control">
                                            <button type="button" class="quantity-btn" onclick="decrementQuantity(<?php echo $product_id; ?>)">-</button>
                                            <input type="number" name="quantity[<?php echo $product_id; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input" id="quantity-<?php echo $product_id; ?>" data-stock="<?php echo $stock_info[$product_id]; ?>">
                                            <button type="button" class="quantity-btn" onclick="incrementQuantity(<?php echo $product_id; ?>)">+</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="fw-bold text-primary">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td>
                                    <form method="post" action="cart.php">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <button type="submit" name="remove_item" class="btn btn-sm" style="color: #dc3545; background: none; padding: 0;">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between flex-wrap">
                <div class="mb-4">
                    <a href="shop.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left mr-2"></i> Continue Shopping
                    </a>
                </div>
                
                <div class="cart-summary" style="width: 350px;">
                    <h2 class="cart-summary-title">Cart Summary</h2>
                    <div class="cart-total">
                        <span>Total:</span>
                        <span class="text-primary" id="cart-total-display">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">
                        Proceed to Checkout <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo">ArtiSell</a>
                    <p>Connecting artisans with customers who appreciate authentic local crafts and delicacies.</p>
                    <div class="social-links">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="shop.php">Products</a></li>
                        <li><a href="cities.php">Cities</a></li>
                        <li><a href="about.php">About Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <ul class="footer-links">
                        <li><a href="#">Shipping & Returns</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Main Street, Cebu City, Philippines</li>
                        <li><i class="fas fa-phone"></i> +63 (32) 123-4567</li>
                        <li><i class="fas fa-envelope"></i> info@artisell.com</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> ArtiSell. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Modal for confirmation -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-container">
            <div class="modal-title">Confirm Removal</div>
            <div class="modal-message">Do you want to remove this product?</div>
            <div class="modal-buttons">
                <button class="btn modal-btn-cancel" id="modalCancel">No</button>
                <button class="btn modal-btn-confirm" id="modalConfirm">Yes</button>
            </div>
        </div>
    </div>
    
    <!-- Stock limit modal -->
    <div class="modal-overlay" id="stockLimitModal">
        <div class="modal-container">
            <div class="modal-title">Stock Limit Reached</div>
            <div class="modal-message">Sorry, you cannot add more of this product. Stock limit reached.</div>
            <div class="modal-buttons">
                <button class="btn modal-btn-confirm" id="stockLimitOk">OK</button>
            </div>
        </div>
    </div>

    <script>
        // Stock information from PHP
        const productStock = <?php echo json_encode($stock_info); ?>;
        
        // Product ID to remove - used by the modal
        let productToRemove = null;
        
        // Modal elements
        const modal = document.getElementById('confirmModal');
        const confirmBtn = document.getElementById('modalConfirm');
        const cancelBtn = document.getElementById('modalCancel');
        
        // Stock limit modal elements
        const stockLimitModal = document.getElementById('stockLimitModal');
        const stockLimitOkBtn = document.getElementById('stockLimitOk');
        
        // Show modal function
        function showModal(productId) {
            productToRemove = productId;
            modal.classList.add('active');
        }
        
        // Hide modal function
        function hideModal() {
            modal.classList.remove('active');
            productToRemove = null;
        }
        
        // Show stock limit modal
        function showStockLimitModal() {
            stockLimitModal.classList.add('active');
        }
        
        // Hide stock limit modal
        function hideStockLimitModal() {
            stockLimitModal.classList.remove('active');
        }
        
        // Modal event listeners
        confirmBtn.addEventListener('click', function() {
            if (productToRemove !== null) {
                removeProduct(productToRemove);
            }
            hideModal();
        });
        
        cancelBtn.addEventListener('click', function() {
            // Reset quantity to 1
            if (productToRemove !== null) {
                const input = document.getElementById('quantity-' + productToRemove);
                if (input) {
                    input.value = 1;
                    updateCartItem(productToRemove);
                }
            }
            hideModal();
        });
        
        // Stock limit modal event listener
        stockLimitOkBtn.addEventListener('click', function() {
            hideStockLimitModal();
        });
        
        // Close modal if clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cancelBtn.click();
            }
        });
        
        stockLimitModal.addEventListener('click', function(e) {
            if (e.target === stockLimitModal) {
                hideStockLimitModal();
            }
        });
        
        function removeProduct(productId) {
            // Create and submit the remove form
            const removeForm = document.createElement('form');
            removeForm.method = 'post';
            removeForm.action = 'cart.php';
            
            const productIdInput = document.createElement('input');
            productIdInput.type = 'hidden';
            productIdInput.name = 'product_id';
            productIdInput.value = productId;
            
            const removeItemInput = document.createElement('input');
            removeItemInput.type = 'hidden';
            removeItemInput.name = 'remove_item';
            removeItemInput.value = '1';
            
            removeForm.appendChild(productIdInput);
            removeForm.appendChild(removeItemInput);
            document.body.appendChild(removeForm);
            removeForm.submit();
        }

        function incrementQuantity(productId) {
            const input = document.getElementById('quantity-' + productId);
            const currentValue = parseInt(input.value);
            const stockLimit = productStock[productId];
            
            // Check if current quantity is already at stock limit
            if (currentValue >= stockLimit) {
                showStockLimitModal();
                return;
            }
            
            input.value = currentValue + 1;
            
            // Auto-update the cart when plus button is clicked
            updateCartItem(productId);
        }
        
        function decrementQuantity(productId) {
            const input = document.getElementById('quantity-' + productId);
            const newValue = parseInt(input.value) - 1;
            
            if (newValue === 0) {
                // Show custom modal instead of browser confirm
                showModal(productId);
            } else if (newValue > 0) {
                input.value = newValue;
                // Auto-update the cart
                updateCartItem(productId);
            }
        }
        
        function updateCartItem(productId) {
            const input = document.getElementById('quantity-' + productId);
            const quantity = parseInt(input.value);
            
            // Create and send XMLHttpRequest to update cart
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_cart.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        // Parse the JSON response
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            // If stock limit was reached, update the input value and show the modal
                            if (response.stockLimitReached) {
                                // Get quantity from the response (it will be adjusted to the max allowed)
                                const availableQuantity = parseInt(input.value);
                                
                                // Update product stock data
                                productStock[productId] = availableQuantity;
                                
                                // Show the stock limit modal
                                showStockLimitModal();
                            }
                            
                            // Update item total
                            const priceElement = input.closest('tr').querySelector('td:nth-child(3)');
                            const priceText = priceElement.textContent.replace('₱', '').trim();
                            const price = parseFloat(priceText);
                            const totalElement = input.closest('tr').querySelector('td:nth-child(5)');
                            totalElement.textContent = '₱' + (price * quantity).toFixed(2);
                            
                            // Update cart total
                            document.getElementById('cart-total-display').textContent = '₱' + response.total;
                            
                            // Update cart count in header if it exists
                            const cartCountElement = document.querySelector('.fa-shopping-cart + span');
                            if (cartCountElement) {
                                cartCountElement.textContent = '(' + response.cartCount + ')';
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                }
            };
            xhr.send('quantity[' + productId + ']=' + quantity + '&update=Update');
        }
    </script>
</body>
</html>