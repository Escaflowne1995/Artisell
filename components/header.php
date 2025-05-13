<header>
    <div class="container header-inner">
        <div class="logo"><a href="index.php">Arti<span style="color: #333;">Sell</span></a></div>
        <nav>
            <ul>
                <li><a href="shop.php">Shop</a></li>
                <?php 
                // Calculate cart count
                $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                ?>
                <li><a href="cart.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                    <span class="cart-count">(<?php echo $cartCount; ?>)</span>
                </a></li>
                
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                        <li><a href="add_product.php">Add Product</a></li>
                        <li><a href="vendor_products.php">My Products</a></li>
                    <?php endif; ?>
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
                            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                                <a href="vendor_products.php">My Products</a>
                            <?php endif; ?>
                            <a href="profile.php">Settings</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<style>
.header-inner { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 15px 0;
    flex-wrap: wrap;
}
.logo a { 
    color: #ff6b00; 
    text-decoration: none; 
    font-size: 24px; 
    font-weight: bold; 
}
nav {
    display: flex;
    align-items: center;
}
nav ul { 
    display: flex; 
    list-style: none; 
    margin: 0;
    padding: 0;
    align-items: center;
    flex-wrap: wrap;
}
nav ul li { 
    margin-left: 25px; 
}
nav ul li a { 
    color: #333; 
    text-decoration: none; 
    font-weight: bold;
    display: inline-block;
    padding: 6px 0;
}
.profile-dropdown { 
    position: relative; 
}
.profile-dropdown:hover .dropdown-content { 
    display: block; 
}
.dropdown-content { 
    display: none;
    position: absolute; 
    right: 0; 
    background: #fff; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
    border-radius: 4px; 
    min-width: 150px;
    z-index: 1000;
}
.dropdown-content a { 
    display: block; 
    padding: 10px 15px; 
    color: #333; 
    text-decoration: none; 
}
.dropdown-content a:hover { 
    background: #f5f5f5; 
}
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
.bi-cart {
    vertical-align: middle;
    margin-right: 3px;
}
.cart-count {
    display: inline-block;
    vertical-align: middle;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .header-inner {
        justify-content: center;
        text-align: center;
    }
    
    .logo {
        margin-bottom: 10px;
        width: 100%;
        text-align: center;
    }
    
    nav ul {
        justify-content: center;
    }
    
    nav ul li {
        margin: 0 10px;
    }
}
</style> 