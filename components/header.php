<header>
    <div class="container header-inner">
        <div class="logo"><a href="#">Arti<span style="color: #333;">Sell</span></a></div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="shop.php">Shop</a></li>
                <?php 
                // Calculate cart count
                $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                ?>
                <li><a href="cart.php">Cart (<?php echo $cartCount; ?>)</a></li>
                
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
}
.logo a { 
    color: #ff6b00; 
    text-decoration: none; 
    font-size: 20px; 
    font-weight: bold; 
}
nav ul { 
    display: flex; 
    list-style: none; 
}
nav ul li { 
    margin-left: 25px; 
}
nav ul li a { 
    color: #333; 
    text-decoration: none; 
    font-weight: bold; 
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
    min-width: 120px; 
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
</style> 