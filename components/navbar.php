<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="header">
    <div class="container header-inner">
        <a href="index.php" class="logo"><span class="text-green">Arti</span><span class="text-blue">Sell</span></a>
        
        <nav>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="shop.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' ? 'active' : ''; ?>">Shop</a></li>
                <li><a href="cities.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'cities.php' ? 'active' : ''; ?>">Cities</a></li>
                <li><a href="about.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : ''; ?>">About</a></li>
                
                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                    <li><a href="add_product.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'add_product.php' ? 'active' : ''; ?>">Add Product</a></li>
                    <li><a href="vendor_products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'vendor_products.php' ? 'active' : ''; ?>">My Products</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="header-right">
            <!-- Cart Link -->
            <a href="cart.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : ''; ?>">
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

<style>
    .text-blue {
        color: #0066cc;
    }
    
    .text-green {
        color: #008a39;
    }
</style> 