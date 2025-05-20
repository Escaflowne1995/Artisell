<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<header class="navbar">
    <div class="container navbar-container">
        <div class="navbar-logo">
            <a href="index.php" class="logo">
                <span class="text-green">Arti</span><span class="text-blue">Sell</span>
            </a>
        </div>
        
        <button class="mobile-menu-toggle" aria-label="Toggle menu">
            <span class="hamburger-icon">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>
        
        <nav class="navbar-menu">
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="shop.php" class="nav-link <?php echo $current_page === 'shop.php' ? 'active' : ''; ?>">Shop</a></li>
                <li><a href="cities.php" class="nav-link <?php echo $current_page === 'cities.php' ? 'active' : ''; ?>">Cities</a></li>
                <li><a href="about.php" class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">About</a></li>
            </ul>
        </nav>
        
        <div class="navbar-actions">
            <!-- Cart Link -->
            <a href="cart.php" class="cart-link <?php echo $current_page === 'cart.php' ? 'active' : ''; ?>">
                <span class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    $cart_count = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
                        }
                    }
                    if ($cart_count > 0):
                    ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </span>
            </a>
            
            <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <div class="profile-dropdown">
                    <button class="profile-button">
                        <?php if (isset($_SESSION["profile_picture"]) && $_SESSION["profile_picture"]): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION["profile_picture"]); ?>" alt="Profile" class="profile-avatar">
                        <?php else: ?>
                            <span class="profile-initial"><?php echo substr(htmlspecialchars($_SESSION["username"]), 0, 1); ?></span>
                        <?php endif; ?>
                        <span class="profile-name"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                            <a href="vendor_products.php" class="dropdown-item">
                                <i class="fas fa-box"></i> My Products
                            </a>
                            <a href="add_product.php" class="dropdown-item">
                                <i class="fas fa-plus"></i> Add Product
                            </a>
                            <a href="manage_orders.php" class="dropdown-item">
                                <i class="fas fa-clipboard-list"></i> Manage Orders
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php elseif (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                            <a href="admin/" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<style>
    /* Navbar Styles */
    .navbar {
        background-color: var(--neutral-100);
        box-shadow: var(--shadow-sm);
        position: sticky;
        top: 0;
        z-index: 1000;
        padding: 0.75rem 0;
    }
    
    .navbar-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }
    
    .navbar-logo .logo {
        font-size: 1.75rem;
        font-weight: 700;
        text-decoration: none;
        color: var(--neutral-900);
    }
    
    .text-green {
        color: #008a39;
    }
    
    .text-blue {
        color: #0066cc;
    }
    
    /* Navigation Links */
    .navbar-menu {
        display: flex;
        align-items: center;
    }
    
    .nav-links {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 1.5rem;
    }
    
    .nav-link {
        color: var(--neutral-700);
        text-decoration: none;
        font-weight: 500;
        padding: 0.5rem 0;
        position: relative;
        transition: color 0.3s ease;
    }
    
    .nav-link:hover, 
    .nav-link.active {
        color: var(--primary);
    }
    
    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: var(--primary);
        border-radius: 2px;
    }
    
    /* Navbar Actions */
    .navbar-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    /* Cart */
    .cart-link {
        display: flex;
        align-items: center;
        color: var(--neutral-700);
        text-decoration: none;
        font-size: 1.25rem;
        position: relative;
    }
    
    .cart-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: var(--primary);
        color: white;
        font-size: 0.75rem;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    
    /* Auth Buttons */
    .auth-buttons {
        display: flex;
        gap: 0.75rem;
    }
    
    /* Profile Dropdown */
    .profile-dropdown {
        position: relative;
    }
    
    .profile-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: none;
        border: none;
        padding: 0.5rem;
        cursor: pointer;
        border-radius: var(--radius-md);
        transition: background-color 0.3s ease;
    }
    
    .profile-button:hover {
        background-color: var(--neutral-200);
    }
    
    .profile-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .profile-initial {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    
    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: white;
        min-width: 220px;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        padding: 0.5rem 0;
        z-index: 1000;
        visibility: hidden;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }
    
    .profile-dropdown:hover .dropdown-menu,
    .profile-button:focus + .dropdown-menu {
        visibility: visible;
        opacity: 1;
        transform: translateY(0);
    }
    
    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        color: var(--neutral-800);
        text-decoration: none;
        transition: background-color 0.2s ease;
    }
    
    .dropdown-item:hover {
        background-color: var(--neutral-100);
    }
    
    .dropdown-item i {
        width: 20px;
        color: var(--neutral-600);
    }
    
    .dropdown-divider {
        height: 1px;
        background-color: var(--neutral-200);
        margin: 0.5rem 0;
    }
    
    /* Mobile Menu */
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        padding: 0.5rem;
        cursor: pointer;
    }
    
    .hamburger-icon {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        width: 24px;
        height: 20px;
    }
    
    .hamburger-icon span {
        display: block;
        height: 2px;
        width: 100%;
        background-color: var(--neutral-800);
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    
    /* Media Queries */
    @media (max-width: 991px) {
        .navbar-menu {
            order: 3;
            flex-basis: 100%;
            display: none;
            margin-top: 1rem;
        }
        
        .navbar-menu.open {
            display: block;
        }
        
        .nav-links {
            flex-direction: column;
            gap: 0;
        }
        
        .nav-link {
            display: block;
            padding: 0.75rem 0;
        }
        
        .nav-link.active::after {
            display: none;
        }
        
        .nav-link.active {
            color: var(--primary);
            font-weight: 600;
        }
        
        .mobile-menu-toggle {
            display: block;
            order: 3;
        }
        
        .navbar-container {
            flex-wrap: wrap;
        }
        
        .auth-buttons {
            flex-direction: column;
            gap: 0.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .profile-name {
            display: none;
        }
        
        .auth-buttons .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const navbarMenu = document.querySelector('.navbar-menu');
        
        if (mobileMenuToggle && navbarMenu) {
            mobileMenuToggle.addEventListener('click', function() {
                navbarMenu.classList.toggle('open');
                
                // Toggle hamburger icon animation
                const spans = this.querySelectorAll('.hamburger-icon span');
                if (navbarMenu.classList.contains('open')) {
                    spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                    spans[1].style.opacity = '0';
                    spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
                } else {
                    spans[0].style.transform = 'none';
                    spans[1].style.opacity = '1';
                    spans[2].style.transform = 'none';
                }
            });
        }
    });
</script> 