<?php
// Start the session
session_start();
require 'db_connection.php';

// Initialize cart if needed
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Create cities image directory if it doesn't exist
$cityImagesDir = 'images/cities';
if (!file_exists($cityImagesDir)) {
    mkdir($cityImagesDir, 0777, true);
}

// Create barangay images directory if it doesn't exist
$barangayImagesDir = 'images/barangays/minglanilla';
if (!file_exists($barangayImagesDir)) {
    mkdir($barangayImagesDir, 0777, true);
}

// Define barangays for Minglanilla
$minglanilla_barangays = [
    'Cadulawan',
    'Calajo-an',
    'Cuanos',
    'Guindaruhan',
    'Linao',
    'Manduang',
    'Pakigne',
    'Poblacion Ward I',
    'Poblacion Ward II',
    'Poblacion Ward III',
    'Poblacion Ward IV',
    'Tubod',
    'Tulay',
    'Tunghaan',
    'Tungkil',
    'Tungkop',
    'Vito'
];

// Fetch distinct cities from products table with LIMIT to prevent excessive data
$sql = "SELECT DISTINCT city FROM products WHERE city IS NOT NULL LIMIT 20";
$result = mysqli_query($conn, $sql);
$cities = [];
while ($row = mysqli_fetch_assoc($result)) {
    if (!empty($row['city'])) {
        $cities[] = $row['city'];
    }
}

// City descriptions - These are used if a city is not in the database
$city_descriptions = [
    'Aloguinsan' => 'Known for the Bojo River and eco-tourism activities.',
    'Catmon' => 'Home to the Esoy Hot Spring and beautiful waterfalls.',
    'Dumanjug' => 'Famous for its heritage church and cultural festivals.',
    'Santander' => 'The southernmost municipality of Cebu with stunning beaches.',
    'Alcoy' => 'Known for the Tingko Beach and the Silmugi Festival.',
    'Minglanilla' => 'Famous for the Sugat-Kabanhawan Festival and local delicacies.',
    'Alcantara' => 'Home to Ronda Beach and agricultural products.',
    'Moalboal' => 'Popular for diving, snorkeling, and the sardine run.',
    'Borbon' => 'Known for the Ilocos Fishery Reserve and beautiful coastlines.',
    'Talisay City' => 'Famous for its "Inasal" grilled food and Talisay Beach, located just south of Metro Cebu.'
];

// Check if all cities from the database have descriptions, if not add defaults
foreach ($cities as $city) {
    if (!array_key_exists($city, $city_descriptions)) {
        $city_descriptions[$city] = 'A beautiful city in Cebu with unique local products.';
    }
}

// If no cities found in database, use the predefined list
if (empty($cities)) {
    $cities = array_keys($city_descriptions);
}

// Use a base64 encoded placeholder image to avoid CORS and external dependencies
$defaultImageData = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMjAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIGZpbGw9IiM1NTUiPkNpdHkgSW1hZ2U8L3RleHQ+PC9zdmc+';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtiSell - Explore Cebu Province</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .hero-cities {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('images/cebu-panorama.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: var(--space-8) 0;
            text-align: center;
            margin-bottom: var(--space-6);
        }
        
        .hero-title {
            font-size: var(--font-size-4xl);
            margin-bottom: var(--space-4);
            color: white;
        }
        
        .search-container {
            margin: var(--space-6) auto;
            max-width: 500px;
            text-align: center;
        }
        
        #citySearch {
            width: 100%;
            padding: var(--space-3) var(--space-4);
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-lg);
            font-size: var(--font-size-md);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal) ease;
        }
        
        #citySearch:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        
        .cities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--space-5);
            margin-bottom: var(--space-8);
        }
        
        .city-card {
            background-color: var(--neutral-100);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: transform var(--transition-normal) ease, box-shadow var(--transition-normal) ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .city-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .city-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        
        .city-info {
            padding: var(--space-4);
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .city-name {
            font-size: var(--font-size-xl);
            margin-bottom: var(--space-2);
            color: var(--neutral-900);
            font-weight: 600;
        }
        
        .city-description {
            color: var(--neutral-700);
            margin-bottom: var(--space-4);
            flex-grow: 1;
        }
        
        .city-button {
            align-self: flex-start;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: var(--space-2) var(--space-4);
            border-radius: var(--radius-md);
            font-weight: 500;
            cursor: pointer;
            transition: background-color var(--transition-normal) ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .city-button:hover {
            background-color: var(--primary-dark);
        }
        
        .section-title {
            text-align: center;
            margin: var(--space-6) 0;
            font-size: var(--font-size-3xl);
            color: var(--neutral-800);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: var(--radius-full);
        }
        
        .no-image {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--neutral-200);
            color: var(--neutral-600);
            font-weight: 500;
            height: 100%;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="index.php" class="logo"><span class="text-green">Arti</span><span class="text-blue">Sell</span></a>
            
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="shop.php" class="nav-link">Shop</a></li>
                    <li><a href="cities.php" class="nav-link active">Cities</a></li>
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
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="profile-dropdown">
                        <a href="#" class="profile-link">
                            <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Account'; ?>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-content">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a href="orders.php" class="dropdown-item">
                                <i class="fas fa-box"></i> Orders
                            </a>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <a href="admin/dashboard.php" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                </a>
                            <?php endif; ?>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm btn-primary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="hero-cities">
        <div class="container">
            <h1 class="hero-title">Explore - Cebu Province</h1>
            <p>Discover the rich culture, traditions, and artisan products from different cities across Cebu</p>
        </div>
    </section>

    <div class="container">
        <div class="search-container">
            <input type="text" id="citySearch" placeholder="Search for a city..." oninput="filterCities()">
        </div>

        <h2 class="section-title">Cities in Cebu</h2>
        
        <div class="cities-grid" id="citiesGrid">
            <?php
            foreach ($cities as $city) {
                $cityImageFile = "images/cities/" . strtolower(str_replace(' ', '_', $city)) . ".jpg";
                $cityImageUrl = file_exists($cityImageFile) ? $cityImageFile : $defaultImageData;
                $description = isset($city_descriptions[$city]) ? $city_descriptions[$city] : 'A beautiful city in Cebu with unique local products.';
            ?>
                <div class="city-card" data-city="<?php echo strtolower($city); ?>">
                    <?php if (file_exists($cityImageFile)): ?>
                        <img src="<?php echo $cityImageUrl; ?>" alt="<?php echo htmlspecialchars($city); ?>" class="city-image">
                    <?php else: ?>
                        <div class="city-image no-image">
                            <span>City Image</span>
                        </div>
                    <?php endif; ?>
                    <div class="city-info">
                        <h3 class="city-name"><?php echo htmlspecialchars($city); ?></h3>
                        <p class="city-description"><?php echo htmlspecialchars($description); ?></p>
                        <a href="city_products.php?city=<?php echo urlencode($city); ?>" class="city-button">Explore Artisan Products</a>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div id="loading" class="loading">Loading more cities...</div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo">ArtiSell</a>
                    <p>Connecting artisans of Cebu with customers around the world.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Explore</h3>
                    <ul class="footer-links">
                        <li><a href="shop.php">Shop</a></li>
                        <li><a href="cities.php">Cities</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="artisans.php">Artisans</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <ul class="footer-links">
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="faq.php">FAQs</a></li>
                        <li><a href="shipping.php">Shipping Policy</a></li>
                        <li><a href="returns.php">Returns & Exchanges</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>About Us</h3>
                    <ul class="footer-links">
                        <li><a href="about.php">Our Story</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="testimonials.php">Testimonials</a></li>
                        <li><a href="careers.php">Careers</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ArtiSell. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function filterCities() {
            const searchTerm = document.getElementById('citySearch').value.toLowerCase();
            const cityCards = document.querySelectorAll('.city-card');
            
            cityCards.forEach(card => {
                const cityName = card.getAttribute('data-city');
                if (cityName.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Lazy loading for images (optional enhancement)
        document.addEventListener('DOMContentLoaded', function() {
            const lazyImages = document.querySelectorAll('.city-image');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.tagName === 'IMG' && img.hasAttribute('data-src')) {
                                img.src = img.getAttribute('data-src');
                                img.removeAttribute('data-src');
                            }
                            observer.unobserve(img);
                        }
                    });
                });
                
                lazyImages.forEach(img => {
                    if (img.tagName === 'IMG') {
                        imageObserver.observe(img);
                    }
                });
            }
        });
    </script>
</body>
</html> 