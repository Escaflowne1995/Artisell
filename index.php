<?php
session_start(); // Start the session to access session variables
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtiSell - Cebu Artisan Marketplace</title>
    <meta name="description" content="Discover authentic Cebuano arts, crafts, and traditional foods">
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Hero section styles */
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/cebu-crafts-hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: var(--space-10) 0;
            margin-top: -80px;
            position: relative;
        }
        
        .hero-content {
            max-width: 600px;
            padding-top: 80px;
        }
        
        .hero h1 {
            font-size: var(--font-size-4xl);
            margin-bottom: var(--space-4);
            color: var(--neutral-900);
        }
        
        .hero p {
            font-size: var(--font-size-lg);
            margin-bottom: var(--space-6);
            opacity: 0.9;
        }
        
        .hero .btn {
            margin-right: var(--space-3);
        }
        
        /* Featured products section */
        .featured-section {
            padding: var(--space-8) 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: var(--space-6);
        }
        
        .section-title {
            font-size: var(--font-size-3xl);
            margin-bottom: var(--space-2);
        }
        
        .section-subtitle {
            color: var(--neutral-600);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--space-5);
        }
        
        /* Categories section */
        .categories-section {
            padding: var(--space-8) 0;
            background-color: var(--neutral-100);
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: var(--space-4);
        }
        
        .category-card {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            height: 200px;
            transition: transform var(--transition-normal) ease;
        }
        
        .category-card:hover {
            transform: translateY(-4px);
        }
        
        .category-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .category-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: var(--space-3);
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
        }
        
        .category-name {
            font-weight: 600;
            font-size: var(--font-size-lg);
        }
        
        /* Testimonials section */
        .testimonials-section {
            padding: var(--space-8) 0;
            background-color: var(--neutral-200);
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: var(--space-5);
        }
        
        .testimonial-card {
            padding: var(--space-5);
            border-radius: var(--radius-lg);
            background-color: var(--neutral-100);
            box-shadow: var(--shadow-md);
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: var(--space-4);
            color: var(--neutral-700);
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: var(--space-3);
            object-fit: cover;
        }
        
        .author-info h4 {
            margin-bottom: var(--space-1);
            font-size: var(--font-size-md);
        }
        
        .author-info p {
            color: var(--neutral-600);
            font-size: var(--font-size-sm);
            margin: 0;
        }
        
        /* CTA section */
        .cta-section {
            padding: var(--space-10) 0;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('images/cebu-artisans.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
        }
        
        .cta-content {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .cta-title {
            font-size: var(--font-size-3xl);
            margin-bottom: var(--space-4);
            color: white;
        }
        
        .cta-text {
            margin-bottom: var(--space-6);
            font-size: var(--font-size-lg);
            opacity: 0.9;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: var(--font-size-3xl);
            }
            
            .hero p {
                font-size: var(--font-size-md);
            }
            
            .testimonials-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .text-blue {
            color: #0066cc;
        }
        
        .text-green {
            color: #008a39;
        }
    </style>
</head>
<body>
        <?php include 'components/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Discover Cebu's Native Crafts & Delicacies</h1>
                <p>Connecting you with authentic local treasures, handcrafted by Filipino artisans. Support local businesses and find the best of Cebu's culture.</p>
                <div class="d-flex">
                    <a href="shop.php" class="btn btn-primary btn-lg">Shop Now</a>
                    <a href="about.php" class="btn btn-outline btn-lg" style="color: white; border-color: white;">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Featured Products</h2>
                <p class="section-subtitle">Explore our curated selection of Cebu's finest treasures, each with authentic craftsmanship and cultural significance.</p>
            </div>
            
            <div class="product-grid">
                <?php
                // Connect to the database
                include 'db_connection.php';
                
                // Query to get featured products
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
                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($product = mysqli_fetch_assoc($result)) {
                            // Set default image since product_images table doesn't exist
                            $image_path = isset($product['image']) && !empty($product['image']) ? 
                                htmlspecialchars($product['image']) : 
                                "image/coconut-bowl-palm.jpg"; // Default image
                            
                            // Format price with error handling
                            $price = isset($product['price']) && is_numeric($product['price']) ? 
                                number_format($product['price'], 2) : 
                                "0.00";
                            
                            // Make sure product_name exists and is not a file path
                            $product_name = isset($product['name']) ? $product['name'] : 
                                (isset($product['product_name']) ? $product['product_name'] : "Product");
                            
                            // If product_name contains a file path indicator, use a generic name
                            if (strpos($product_name, '\\') !== false || strpos($product_name, '/') !== false) {
                                $product_name = "Handcrafted Product";
                            }
                            
                            // Ensure product_id exists
                            $product_id = isset($product['id']) ? $product['id'] : 
                                (isset($product['product_id']) ? $product['product_id'] : "1");
                            
                            // Get description
                            $description = isset($product['description']) ? $product['description'] : "Beautiful handcrafted item from Cebu";
                ?>
                <div class="card">
                    <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product_name); ?>" class="card-img">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($product_name); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars(substr($description, 0, 80)) . (strlen($description) > 80 ? '...' : ''); ?></p>
                        <div class="card-price">₱<?php echo $price; ?></div>
                        <a href="product-details.php?id=<?php echo $product_id; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
                <?php
                        }
                    } else {
                        // Display dummy products if no products found
                        $dummy_products = [
                            ["name" => "Handcrafted Jewelry", "price" => "1200.00", "image" => "image/jewelry.jpg", "description" => "Beautiful handmade jewelry from local artisans"],
                            ["name" => "Coconut Shell Bowl", "price" => "450.00", "image" => "image/coconut-bowl-palm.jpg", "description" => "Eco-friendly bowl made from coconut shells"],
                            ["name" => "Woven Basket", "price" => "850.00", "image" => "image/basket.jpg", "description" => "Traditional woven basket using local materials"],
                            ["name" => "Handwoven Fabric", "price" => "1500.00", "image" => "image/fabric.jpg", "description" => "Colorful handwoven fabric with traditional patterns"]
                        ];
                        
                        foreach ($dummy_products as $index => $product) {
                ?>
                <div class="card">
                    <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="card-img">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="card-price">₱<?php echo $product['price']; ?></div>
                        <a href="product-details.php?id=<?php echo $index + 1; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
                <?php
                        }
                    }
                } catch (Exception $e) {
                    echo "<!-- Error: " . htmlspecialchars($e->getMessage()) . " -->";
                    // Display dummy products as fallback
                    $dummy_products = [
                        ["name" => "Handcrafted Jewelry", "price" => "1200.00", "image" => "image/jewelry.jpg", "description" => "Beautiful handmade jewelry from local artisans"],
                        ["name" => "Coconut Shell Bowl", "price" => "450.00", "image" => "image/coconut-bowl-palm.jpg", "description" => "Eco-friendly bowl made from coconut shells"],
                        ["name" => "Woven Basket", "price" => "850.00", "image" => "image/basket.jpg", "description" => "Traditional woven basket using local materials"],
                        ["name" => "Handwoven Fabric", "price" => "1500.00", "image" => "image/fabric.jpg", "description" => "Colorful handwoven fabric with traditional patterns"]
                    ];
                    
                    foreach ($dummy_products as $index => $product) {
                ?>
                <div class="card">
                    <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="card-img">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="card-price">₱<?php echo $product['price']; ?></div>
                        <a href="product-details.php?id=<?php echo $index + 1; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
                <?php
                    }
                }
                ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="shop.php" class="btn btn-outline">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Shop by Category</h2>
                <p class="section-subtitle">Explore Cebu's diverse artisanal products across different categories</p>
            </div>
            
            <div class="category-grid">
                <a href="shop.php?category=jewelry" class="category-card">
                    <img src="image/jewelry.jpg" alt="Jewelry">
                    <div class="category-overlay">
                        <div class="category-name">Jewelry</div>
                    </div>
                </a>
                
                <a href="shop.php?category=home-decor" class="category-card">
                    <img src="image/coconut-bowl-palm.jpg" alt="Home Decor">
                    <div class="category-overlay">
                        <div class="category-name">Home Decor</div>
                    </div>
                </a>
                
                <a href="shop.php?category=textiles" class="category-card">
                    <img src="image/fabric.jpg" alt="Textiles">
                    <div class="category-overlay">
                        <div class="category-name">Textiles</div>
                    </div>
                </a>
                
                <a href="shop.php?category=food" class="category-card">
                    <img src="image/food.jpg" alt="Food & Delicacies">
                    <div class="category-overlay">
                        <div class="category-name">Food & Delicacies</div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">What Our Customers Say</h2>
                <p class="section-subtitle">Hear from people who have discovered the beauty of Cebuano craftsmanship through ArtiSell</p>
            </div>
            
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "I discovered ArtiSell while visiting Cebu and was amazed by the quality of handcrafted items. Now I can order my favorite Cebuano products even when I'm back home in Manila!"
                    </div>
                    <div class="testimonial-author">
                        <img src="images/avatar-1.jpg" alt="Maria Santos" class="author-avatar">
                        <div class="author-info">
                            <h4>Maria Santos</h4>
                            <p>Manila, Philippines</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "As a collector of indigenous crafts, I'm impressed by the authenticity and quality of products on ArtiSell. It's wonderful to directly support local artisans."
                    </div>
                    <div class="testimonial-author">
                        <img src="images/avatar-2.jpg" alt="John Reyes" class="author-avatar">
                        <div class="author-info">
                            <h4>Choox tv</h4>
                            <p>Davao City, Philippines</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "The coconut shell crafts I purchased are absolutely beautiful! Shipping was fast and everything arrived perfectly packaged. Will definitely order again!"
                    </div>
                    <div class="testimonial-author">
                        <img src="images/avatar-3.jpg" alt="Lisa Tan" class="author-avatar">
                        <div class="author-info">
                            <h4>Lisa Tan</h4>
                            <p>Singapore</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Join Our Artisan Community</h2>
                <p class="cta-text">Are you a local artisan looking to reach more customers? Partner with ArtiSell and showcase your crafts to a wider audience.</p>
                <a href="signup.php?as=vendor" class="btn btn-primary btn-lg">Become a Seller</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo"><span class="text-green">Arti</span><span class="text-blue">Sell</span></a>
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
        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('open');
        });
    </script>
</body>
</html> 