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
        font-weight: 700;
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

    .header-right a {
        font-weight: 700;
        text-decoration: none;
        color: #333;
    }

    .nav-link {
        font-weight: 700;
    }
    
    .cart-icon {
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        vertical-align: middle;
        margin-right: 4px;
    }

    .about-image {
    width: 100%; /* Adjust as needed */
    max-width: 100%;
}

.about-image img {
    width: 100%;
    height: auto;
    aspect-ratio: 16 / 9;
    object-fit: cover; /* Ensures the image fills the space while maintaining aspect ratio */
}
        </style>
  </head>
  <body>
    
    <div class="main-container">
      <main class="main-content">
    
      <header class="header">
    <div class="container header-inner">
        <a href="" class="logo">Art<span>iSell</span></a>
        
        <div class="header-right">
            <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <a href="cart.php" class="nav-link"><i class="cart-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
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
                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                            <a href="vendor_products.php" class="dropdown-item">My Products</a>
                        <?php endif; ?>
                        <a href="settings.php" class="dropdown-item">Settings</a>
                        <a href="logout.php" class="dropdown-item">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
                
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
                <a href="shop.php" class="btn btn-primary">Shop Now</a>
                <a href="#" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-products">
        <div class="container">
            <h2>Featured Products</h2>
            <p>Explore our curated selection of Cebu's finest treasures, each with authentic craftsmanship and cultural significance.</p>
            
            <div class="product-controls">
                <button id="prev-product" class="product-nav-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                </svg></button>
                
                <div class="products-slider-container">
                    <div class="products-slider">
                        <?php
                        // Connect to the database
                        include 'db_connection.php';
                        
                        // Query to get featured products (with error handling for vendors table)
                        try {
                            // First check if vendors table exists and has vendor_id column
                            $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'vendors'");
                            
                            // Get featured products with error handling
                            try {
                                $query = "SELECT * FROM products WHERE is_featured = 1 LIMIT 8";
                                $result = mysqli_query($conn, $query);
                                
                                if (!$result) {
                                    // If that fails, try without the is_featured condition
                                    $query = "SELECT * FROM products LIMIT 8";
                                    $result = mysqli_query($conn, $query);
                                    
                                    if (!$result) {
                                        throw new Exception(mysqli_error($conn));
                                    }
                                }
                            } catch (Exception $e) {
                                // If all queries fail, show dummy products
                                $result = false;
                                echo "<!-- Database error: " . htmlspecialchars($e->getMessage()) . " -->";
                            }
                            
                            // Check if products exist
                            if (mysqli_num_rows($result) > 0) {
                                while ($product = mysqli_fetch_assoc($result)) {
                                    // Set default image since product_images table doesn't exist
                                    $image_path = "image/coconut-bowl-palm.jpg"; // Default image
                                    
                                    // Format price with error handling
                                    $price = isset($product['price']) && is_numeric($product['price']) ? 
                                        number_format($product['price'], 2) : 
                                        "0.00";
                                    
                                    // Make sure product_name exists and is not a file path
                                    $product_name = isset($product['product_name']) ? $product['product_name'] : "Product";
                                    
                                    // If product_name contains a file path indicator, use a generic name
                                    if (strpos($product_name, '\\') !== false || strpos($product_name, '/') !== false) {
                                        $product_name = "Handcrafted Product";
                                    }
                                    
                                    // Ensure product_id exists
                                    $product_id = isset($product['product_id']) ? $product['product_id'] : "1";
                        ?>
                        <div class="product-card">
                            <div class="product-image">
                                <div class="product-badge">Featured</div>
                                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($product_name); ?>">
                                <div class="product-quick-actions">
                                    <button class="quick-view-btn" data-id="<?php echo $product_id; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                                        </svg>
                                    </button>
                                    <button class="add-to-wishlist" data-id="<?php echo $product_id; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="product-details">
                                <div class="product-vendor">
                                    <?php 
                                        // Safely display vendor name if available, otherwise show default
                                        echo 'Artisell Vendor';
                                    ?>
                                </div>
                                <h3 class="product-title">
                                    <a href="product-details.php?id=<?php echo $product_id; ?>">
                                        <?php echo htmlspecialchars($product_name); ?>
                                    </a>
                                </h3>
                                <p class="product-price">₱ <?php echo $price; ?></p>
                                <button class="add-to-cart-btn" data-id="<?php echo $product_id; ?>">Add to Cart</button>
                            </div>
                        </div>
                        <?php
                            }
                        } else {
                            // If no featured products found in database, show dummy products
                        ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <div class="product-badge">Featured</div>
                                    <img src="image/coconut-bowl-palm.jpg" alt="Coconut Bowl">
                                    <div class="product-quick-actions">
                                        <button class="quick-view-btn" data-id="1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                                <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                                            </svg>
                                        </button>
                                        <button class="add-to-wishlist" data-id="1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="product-details">
                                    <div class="product-vendor">Cebu Crafts</div>
                                    <h3 class="product-title"><a href="product-details.php?id=1">Handcrafted Coconut Bowl</a></h3>
                                    <p class="product-price">₱ 450.00</p>
                                    <button class="add-to-cart-btn" data-id="1">Add to Cart</button>
                                </div>
                            </div>
                            
                            <div class="product-card">
                                <div class="product-image">
                                    <div class="product-badge">Featured</div>
                                    <img src="image/coconut-bowls.jpg" alt="Coconut Bowl Set">
                                    <div class="product-quick-actions">
                                        <button class="quick-view-btn" data-id="2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                                <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                                            </svg>
                                        </button>
                                        <button class="add-to-wishlist" data-id="2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="product-details">
                                    <div class="product-vendor">Island Artisans</div>
                                    <h3 class="product-title"><a href="product-details.php?id=2">Coconut Bowl Set (4 pcs)</a></h3>
                                    <p class="product-price">₱ 1,200.00</p>
                                    <button class="add-to-cart-btn" data-id="2">Add to Cart</button>
                                </div>
                            </div>
                            
                            <div class="product-card">
                                <div class="product-image">
                                    <div class="product-badge">Featured</div>
                                    <img src="image/alcoy.jpg" alt="Bamboo Tumbler">
                                    <div class="product-quick-actions">
                                        <button class="quick-view-btn" data-id="3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                                <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                                            </svg>
                                        </button>
                                        <button class="add-to-wishlist" data-id="3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="product-details">
                                    <div class="product-vendor">Eco Crafters</div>
                                    <h3 class="product-title"><a href="product-details.php?id=3">Bamboo Tumbler</a></h3>
                                    <p class="product-price">₱ 350.00</p>
                                    <button class="add-to-cart-btn" data-id="3">Add to Cart</button>
                                </div>
                            </div>
                            
                            <div class="product-card">
                                <div class="product-image">
                                    <div class="product-badge">Featured</div>
                                    <img src="image/CATMON.jpg" alt="Handwoven Basket">
                                    <div class="product-quick-actions">
                                        <button class="quick-view-btn" data-id="4">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                                <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                                            </svg>
                                        </button>
                                        <button class="add-to-wishlist" data-id="4">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="product-details">
                                    <div class="product-vendor">Weave Artisans</div>
                                    <h3 class="product-title"><a href="product-details.php?id=4">Handwoven Basket</a></h3>
                                    <p class="product-price">₱ 550.00</p>
                                    <button class="add-to-cart-btn" data-id="4">Add to Cart</button>
                                </div>
                            </div>
                        <?php
                        }
                        
                        // Close database connection
                        mysqli_close($conn);
                        } catch (Exception $e) {
                            echo "Error: " . $e->getMessage();
                        }
                        ?>
                    </div>
                </div>
                
                <button id="next-product" class="product-nav-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                </svg></button>
            </div>
            
            <div class="product-indicators">
                <span class="active"></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <!-- Quick View Modal -->
            <div id="quick-view-modal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <div id="quick-view-content">
                        <!-- Product details will be loaded here via AJAX -->
                    </div>
                </div>
            </div>
            
            <a href="shop.php" class="view-all-btn">View All Products</a>
        </div>
    </section>

    <!-- Explore by City Section -->
    <section class="explore-city">
        <div class="container">
            <h2>Explore by City</h2>
            <p>Find local arts, crafts, and delicacies unique to each city. Discover items from different regions across the Philippines.</p>
            <a href="cities.php" class="view-all-btn">View All Cities</a>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="container">
            <div class="about-wrapper" style="display: flex; gap: 2rem; align-items: center;">
                <div class="about-image" style="flex: 1;">
                    <img src="image/about.jpg" alt="About ArtiSell" style="width: 100%; height: auto; border-radius: 8px; object-fit: cover;">
                </div>
                <div class="about-content" style="flex: 1;">
                    <h2>About ArtiSell</h2>
                    <p>ArtiSell is a marketplace dedicated to promoting authentic local arts and connecting artisans with customers who care about heritage through craft and culture.</p>
                    <p>Our mission is to empower local artisans and preserve Filipino craft traditions while providing quality products to customers around the globe.</p>
                    <p>By supporting ArtSell, you're not just buying products - you're helping preserve traditional craftsmanship and supporting local communities.</p>
                    <a href="#" class="btn btn-primary">Learn More About Us</a>
                </div>
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
                <a href="categories.php" class="btn btn-primary">Shop Now</a>
                <a href="#" class="btn btn-secondary">Become a Vendor</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <!-- JavaScript for Featured Products Slider -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const slider = document.querySelector('.products-slider');
            const prevBtn = document.getElementById('prev-product');
            const nextBtn = document.getElementById('next-product');
            const indicators = document.querySelectorAll('.product-indicators span');
            const productCards = document.querySelectorAll('.product-card');
            const modal = document.getElementById('quick-view-modal');
            const closeModal = document.querySelector('.close-modal');
            const quickViewContent = document.getElementById('quick-view-content');
            
            // Variables
            let slideIndex = 0;
            const cardsPerView = window.innerWidth < 768 ? 2 : 3;
            const cardWidth = productCards[0].offsetWidth + 20; // Card width + gap
            
            // Initialize slider
            updateSlider();
            
            // Event Listeners
            prevBtn.addEventListener('click', () => {
                slideIndex = Math.max(0, slideIndex - 1);
                updateSlider();
            });
            
            nextBtn.addEventListener('click', () => {
                slideIndex = Math.min(productCards.length - cardsPerView, slideIndex + 1);
                updateSlider();
            });
            
            // Indicator click events
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    slideIndex = index * Math.floor(productCards.length / indicators.length);
                    slideIndex = Math.min(slideIndex, productCards.length - cardsPerView);
                    updateSlider();
                });
            });
            
            // Quick view buttons
            const quickViewBtns = document.querySelectorAll('.quick-view-btn');
            quickViewBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = btn.getAttribute('data-id');
                    openQuickView(productId);
                });
            });
            
            // Close modal
            closeModal.addEventListener('click', () => {
                modal.style.display = 'none';
            });
            
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Add to cart buttons
            const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
            addToCartBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = btn.getAttribute('data-id');
                    addToCart(productId);
                });
            });
            
            // Add to wishlist buttons
            const wishlistBtns = document.querySelectorAll('.add-to-wishlist');
            wishlistBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = btn.getAttribute('data-id');
                    addToWishlist(productId);
                    
                    // Visual feedback
                    btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314z"/>
                    </svg>`;
                    btn.style.backgroundColor = '#FF6B17';
                    btn.style.color = 'white';
                });
            });
            
            // Functions
            function updateSlider() {
                const translateValue = -slideIndex * cardWidth;
                slider.style.transform = `translateX(${translateValue}px)`;
                
                // Update indicators
                const indicatorIndex = Math.floor(slideIndex / Math.floor(productCards.length / indicators.length));
                indicators.forEach((indicator, index) => {
                    indicator.classList.toggle('active', index === indicatorIndex);
                });
                
                // Enable/disable navigation buttons
                prevBtn.disabled = slideIndex === 0;
                nextBtn.disabled = slideIndex >= productCards.length - cardsPerView;
                
                // Visual feedback for buttons
                prevBtn.style.opacity = prevBtn.disabled ? '0.5' : '1';
                nextBtn.style.opacity = nextBtn.disabled ? '0.5' : '1';
            }
            
            function openQuickView(productId) {
                // Show loading state
                quickViewContent.innerHTML = '<div style="text-align: center; padding: 30px;"><p>Loading product details...</p></div>';
                modal.style.display = 'block';
                
                // Fetch product details via AJAX
                fetch(`get_product_details.php?product_id=${productId}`)
                    .then(response => response.json())
                    .catch(error => {
                        console.error('Error fetching product details:', error);
                        quickViewContent.innerHTML = `
                            <div style="text-align: center; padding: 30px;">
                                <p>Error loading product details. Please try again later.</p>
                                <button onclick="modal.style.display='none'" class="btn btn-primary">Close</button>
                            </div>
                        `;
                    });
            }
            
            function addToCart(productId, quantity = 1) {
                // Prepare form data
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', quantity);
                
                // Show loading feedback
                const targetBtn = document.querySelector(`.add-to-cart-btn[data-id="${productId}"]`);
                if (targetBtn) {
                    const originalText = targetBtn.textContent;
                    targetBtn.textContent = 'Adding...';
                    targetBtn.disabled = true;
                    
                    // Enable button after 1 second
                    setTimeout(function() {
                        targetBtn.disabled = false;
                        targetBtn.textContent = 'Add to Cart';
                        alert('Product added to cart!');
                    }, 1000);
                }
            }
            
            function addToWishlist(productId) {
                // In a real implementation, this would use AJAX to add the product to wishlist
                console.log(`Added product #${productId} to wishlist`);
                
                // For demo purposes
                alert(`Product added to wishlist!`);
            }
            
            // Responsive adjustment
            window.addEventListener('resize', () => {
                const newCardsPerView = window.innerWidth < 768 ? 2 : 3;
                if (newCardsPerView !== cardsPerView) {
                    cardsPerView = newCardsPerView;
                    slideIndex = Math.min(slideIndex, productCards.length - cardsPerView);
                    updateSlider();
                }
            });
        });
    </script>
</body>
</html>