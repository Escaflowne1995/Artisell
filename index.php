<?php
session_start(); // Start the session to access session variables
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ArtiSell - Cebu Artisan Marketplace</title>
    <meta name="description" content="Discover authentic Cebuano arts, crafts, and traditional foods" />
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/index.css">
        <style>
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

    .social-links {
  display: flex;
  gap: 15px;
}

.social-links a {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  color: white;
  text-decoration: none;
  font-size: 16px;
  background-color: rgba(255,255,255,0.1);
  transition: background-color 0.3s ease;
}

.social-links a:hover {
  background-color: #ff6b00;
}
        </style>
  </head>
  <body>
    
    <div class="main-container">
      <main class="main-content">
    
      <header class="header">
    <div class="container header-inner">
        <a href="" class="logo">Art<span>iSell</span></a>
        <nav>
            <div class="nav-links">
                <a href="categories.php" class="nav-link">Products</a>
                <a href="cities.php" class="nav-link">Cities</a>
                <a href="about.php" class="nav-link">About</a>
            </div>
        </nav>
        <div class="header-right">
            <a href="#"><i class="search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
            </svg></i></a>
            <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <a href="cart.php"><i class="cart-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                </svg></i><?php echo isset($_SESSION['cart']) ? " (" . count($_SESSION['cart']) . ")" : ""; ?></a>
                <div class="profile-dropdown">
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
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
                <a href="signup.php" class="nav-link">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</header>

        <!-- Hero Section -->
        <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Discover Cebu's Native Crafts & Delicacies</h1>
                <p>Connecting you with authentic local treasures, handcrafted by Filipino artisans. Support local businesses and find the best of Cebu's culture.</p>
                <a href="#" class="btn btn-primary">Shop Now</a>
                <a href="#" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-products">
        <div class="container">
            <h2>Featured Products</h2>
            <p>Explore our curated selection of Cebu's finest treasures, each with authentic craftsmanship and cultural significance.</p>
            
            <div class="products-container">
                <div class="product-card">
                    <div class="product-image">
                        <img src="image/coconut-bowl-palm.jpg" alt="Coconut Bowl">
                    </div>
                    <div class="product-details">
                        <div class="product-category">Featured</div>
                        <h3 class="product-title">Coconut Bowl</h3>
                        <p class="product-price">₱ 450.00</p>
                        <button class="add-to-cart">Add to Cart</button>
                    </div>
                </div>
                <!-- More product cards would go here -->
            </div>
            
            <a href="#" class="view-all-btn">View All Products</a>
        </div>
    </section>

    <!-- Explore by City Section -->
    <section class="explore-city">
        <div class="container">
            <h2>Explore by City</h2>
            <p>Find local arts, crafts, and delicacies unique to each city. Discover items from different regions across the Philippines.</p>
            <a href="#" class="view-all-btn">View All Cities</a>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="container" style="display: flex;">
            <div class="about-image">
                <div class="image-placeholder">
                </div>
            </div>
            
            <div class="about-content">
                <h2>About ArtiSell</h2>
                <p>ArtiSell is a marketplace dedicated to promoting authentic local arts and connecting artisans with customers who care about heritage through craft and culture.</p>
                <p>Our mission is to empower local artisans and preserve Filipino craft traditions while providing quality products to customers around the globe.</p>
                <p>By supporting ArtSell, you're not just buying products - you're helping preserve traditional craftsmanship and supporting local communities.</p>
                <a href="#" class="btn btn-primary">Learn More About Us</a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <h2>What Our Customers Say</h2>
            <div class="testimonials-container">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p>"The quality of the products is exceptional! I love how each item comes with a story about the artisan who made it."</p>
                    <div class="testimonial-name">Maria Garcia</div>
                    <div class="testimonial-location">Manila, PH</div>
                </div>
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p>"I ordered several items as gifts for my family, and they all arrived beautifully packaged. Will definitely order again!"</p>
                    <div class="testimonial-name">John Santos</div>
                    <div class="testimonial-location">Cebu, PH</div>
                </div>
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <p>"The coconut bowls are gorgeous! Supporting local artisans while getting beautiful, sustainable products feels great."</p>
                    <div class="testimonial-name">Sophie Reyes</div>
                    <div class="testimonial-location">Davao, PH</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Discover Cebu's Treasures?</h2>
            <p>Join ArtSell today and start your journey through Cebu's rich culture of native crafts and delicacies.</p>
            <div class="cta-buttons">
                <a href="shop.php" class="btn btn-primary">Shop Now</a>
                <a href="#" class="btn btn-secondary">Become a Vendor</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>
</body>
</html>