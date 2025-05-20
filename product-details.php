<?php
session_start();
include 'db_connection.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get product details
$product = null;
if ($product_id > 0) {
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
    }
}

// If product not found
if (!$product) {
    header("Location: index.php");
    exit;
}

// Get product name from name field
$product_name = isset($product['name']) ? $product['name'] : 'Product';

// Get image path
$image_path = isset($product['image']) && !empty($product['image']) ? $product['image'] : "image/coconut-bowl-palm.jpg";

// Get stock info
$stock = isset($product['stock']) ? (int)$product['stock'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product_name); ?> - ArtiSell</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(isset($product['description']) ? $product['description'] : 'Authentic Cebuano crafts and delicacies', 0, 160)); ?>">
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .product-container {
            padding: var(--space-6) 0;
        }
        
        .product-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-6);
        }
        
        .product-image-container {
            position: relative;
        }
        
        .product-image {
            width: 100%;
            height: auto;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            cursor: pointer;
        }
        
        .product-badges {
            position: absolute;
            top: var(--space-4);
            left: var(--space-4);
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
        }
        
        .product-badge {
            display: inline-block;
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
            background-color: var(--primary);
            color: white;
        }
        
        .product-info {
            display: flex;
            flex-direction: column;
        }
        
        .product-title {
            font-size: var(--font-size-3xl);
            margin-bottom: var(--space-2);
        }
        
        .product-price {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--primary);
            margin-bottom: var(--space-4);
        }
        
        .product-description {
            color: var(--neutral-700);
            line-height: 1.7;
            margin-bottom: var(--space-5);
        }
        
        .stock-badge {
            display: inline-block;
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
            margin-bottom: var(--space-4);
        }
        
        .in-stock {
            background-color: rgba(52, 199, 89, 0.1);
            color: var(--accent);
        }
        
        .low-stock {
            background-color: rgba(255, 204, 0, 0.1);
            color: #ffc107;
        }
        
        .out-of-stock {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            margin-bottom: var(--space-4);
            width: fit-content;
        }
        
        .quantity-label {
            margin-right: var(--space-3);
            font-weight: 500;
        }
        
        .product-actions {
            margin-top: var(--space-4);
        }
        
        .action-buttons {
            display: flex;
            gap: var(--space-3);
        }
        
        .action-btn {
            padding: var(--space-3) var(--space-4);
        }
        
        /* Related products */
        .related-products {
            margin-top: var(--space-8);
        }
        
        .section-title {
            font-size: var(--font-size-2xl);
            margin-bottom: var(--space-5);
            position: relative;
            padding-bottom: var(--space-2);
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--primary);
            border-radius: var(--radius-full);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: var(--radius-lg);
        }
        
        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }
        
        /* Login Modal */
        #loginModal .modal-dialog {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        
        #loginModal .modal-content {
            background-color: white;
            padding: var(--space-4);
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        
        #loginModal .modal-header {
            border-bottom: 1px solid var(--neutral-200);
            padding-bottom: var(--space-3);
            margin-bottom: var(--space-3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        #loginModal .modal-header .close {
            position: static;
            color: var(--neutral-500);
            font-size: 24px;
        }
        
        #loginModal .modal-footer {
            display: flex;
            gap: var(--space-3);
            margin-top: var(--space-4);
        }
        
        @media (max-width: 768px) {
            .product-layout {
                grid-template-columns: 1fr;
            }
            
            .product-image-container {
                margin-bottom: var(--space-4);
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="index.php" class="logo">Arti<span class="text-primary">Sell</span></a>
            
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="shop.php" class="nav-link active">Shop</a></li>
                    <li><a href="cities.php" class="nav-link">Cities</a></li>
                    <li><a href="about.php" class="nav-link">About</a></li>
                </ul>
            </nav>
            
            <div class="header-right">
                <a href="cart.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    $cart_count = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
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
                            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                                <a href="vendor_products.php" class="dropdown-item">
                                    <i class="fas fa-box"></i> My Products
                                </a>
                            <?php endif; ?>
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
    
    <main class="container product-container">
        <div class="product-layout">
            <div class="product-image-container">
                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($product_name); ?>" class="product-image" onclick="openModal('<?php echo htmlspecialchars($image_path); ?>')">
                
                <div class="product-badges">
                    <?php if (isset($product['is_featured']) && $product['is_featured']): ?>
                        <div class="product-badge">Featured</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product_name); ?></h1>
                <div class="product-price">₱<?php echo number_format(isset($product['price']) ? $product['price'] : 0, 2); ?></div>
                
                <?php
                if ($stock > 10) {
                    echo '<div class="stock-badge in-stock"><i class="fas fa-check-circle"></i> In Stock</div>';
                } else if ($stock > 0) {
                    echo '<div class="stock-badge low-stock"><i class="fas fa-exclamation-circle"></i> Only ' . $stock . ' left</div>';
                } else {
                    echo '<div class="stock-badge out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</div>';
                }
                ?>
                
                <div class="product-description">
                    <?php if (isset($product['description']) && !empty($product['description'])): ?>
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    <?php else: ?>
                        <p>This authentic Cebuano product represents the rich craftsmanship of local artisans. 
                        Each piece is carefully handcrafted using traditional methods passed down through generations.</p>
                    <?php endif; ?>
                </div>
                
                <form <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>action="add_to_cart.php" method="POST"<?php else: ?>id="cart-form"<?php endif; ?>>
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="redirect" value="product-details.php?id=<?php echo $product_id; ?>">
                    
                    <div class="d-flex align-items-center mb-4">
                        <span class="quantity-label">Quantity:</span>
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn" onclick="decrementQuantity()">-</button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" class="quantity-input">
                            <button type="button" class="quantity-btn" onclick="incrementQuantity()">+</button>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                            <button type="submit" name="add_to_cart" class="btn btn-primary action-btn" <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        <?php else: ?>
                            <?php if ($stock > 0): ?>
                                <button type="button" onclick="requireLogin()" class="btn btn-primary action-btn">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary action-btn" disabled>
                                    <i class="fas fa-cart-plus"></i> Out of Stock
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <a href="shop.php" class="btn btn-outline action-btn">
                            <i class="fas fa-arrow-left"></i> Back to Shop
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Related Products Section -->
        <div class="related-products">
            <h2 class="section-title">You May Also Like</h2>
            
            <div class="product-grid">
                <?php
                // Get related products
                $related_query = "SELECT * FROM products WHERE id != ? ORDER BY RAND() LIMIT 4";
                $related_stmt = mysqli_prepare($conn, $related_query);
                mysqli_stmt_bind_param($related_stmt, "i", $product_id);
                mysqli_stmt_execute($related_stmt);
                $related_result = mysqli_stmt_get_result($related_stmt);
                
                if ($related_result && mysqli_num_rows($related_result) > 0) {
                    while ($related = mysqli_fetch_assoc($related_result)) {
                        $related_name = isset($related['name']) ? $related['name'] : 'Product';
                        $related_image = isset($related['image']) && !empty($related['image']) ? $related['image'] : "image/coconut-bowl-palm.jpg";
                        $related_id = isset($related['id']) ? $related['id'] : 1;
                        $related_price = isset($related['price']) ? $related['price'] : 0;
                        $related_description = isset($related['description']) ? $related['description'] : 'Authentic Cebuano product';
                ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($related_image); ?>" alt="<?php echo htmlspecialchars($related_name); ?>" class="card-img">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($related_name); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars(substr($related_description, 0, 80) . (strlen($related_description) > 80 ? '...' : '')); ?></p>
                        <div class="card-price">₱<?php echo number_format($related_price, 2); ?></div>
                        <a href="product-details.php?id=<?php echo $related_id; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
                <?php
                    }
                } else {
                    // Display fallback products if related products not found
                    $dummy_products = [
                        ["name" => "Handcrafted Jewelry", "price" => "1200.00", "image" => "image/jewelry.jpg", "description" => "Beautiful handmade jewelry from local artisans"],
                        ["name" => "Coconut Shell Bowl", "price" => "450.00", "image" => "image/coconut-bowl-palm.jpg", "description" => "Eco-friendly bowl made from coconut shells"],
                        ["name" => "Woven Basket", "price" => "850.00", "image" => "image/basket.jpg", "description" => "Traditional woven basket using local materials"],
                        ["name" => "Handwoven Fabric", "price" => "1500.00", "image" => "image/fabric.jpg", "description" => "Colorful handwoven fabric with traditional patterns"]
                    ];
                    
                    foreach ($dummy_products as $index => $related) {
                ?>
                <div class="card">
                    <img src="<?php echo $related['image']; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" class="card-img">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars($related['description']); ?></p>
                        <div class="card-price">₱<?php echo $related['price']; ?></div>
                        <a href="product-details.php?id=<?php echo $index + 1; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </main>
    
    <!-- Login Required Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Login Required</h4>
                    <span class="close" onclick="closeLoginModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p>You need to be logged in to add items to your cart.</p>
                </div>
                <div class="modal-footer">
                    <a href="login.php?redirect=product-details.php?id=<?php echo $product_id; ?>" class="btn btn-primary">Login Now</a>
                    <a href="signup.php" class="btn btn-outline">Sign Up</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>
    
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
    
    <script>
        // Image modal functions
        function openModal(imageUrl) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            modalImg.src = imageUrl;
        }
        
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        // Login Modal functions
        function requireLogin() {
            const modal = document.getElementById('loginModal');
            modal.style.display = 'flex';
            
            // Prepare the login link with product details
            const productId = <?php echo $product_id; ?>;
            const quantity = document.getElementById('quantity').value;
            const loginLink = document.querySelector('#loginModal .modal-footer a.btn-primary');
            
            // Update login link to include product information
            loginLink.href = `login.php?product_id=${productId}&quantity=${quantity}&redirect=product-details.php?id=${productId}`;
        }
        
        function closeLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
        }
        
        // Quantity control
        function incrementQuantity() {
            const input = document.getElementById('quantity');
            input.value = parseInt(input.value) + 1;
        }
        
        function decrementQuantity() {
            const input = document.getElementById('quantity');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }
    </script>
</body>
</html> 