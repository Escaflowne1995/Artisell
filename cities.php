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
    'Cebu City' => 'The capital city of the province, known for its rich history and culture.'
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
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .cities-heading {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .cities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .city-card {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .city-card:hover {
            transform: translateY(-5px);
        }
        .city-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #f0f0f0;
        }
        .city-info {
            padding: 20px;
        }
        .city-name {
            font-size: 20px;
            margin-bottom: 10px;
            color: #333;
        }
        .city-description {
            color: #666;
            margin-bottom: 15px;
        }
        .city-button {
            background-color: #FF6B35;
            color: white;
            border: none;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .city-button:hover {
            background-color: #E85A2D;
        }
        .search-container {
            margin: 20px 0;
            text-align: center;
        }
        #citySearch {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
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
        /* Loading indicator */
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            font-size: 18px;
        }
        .loading::after {
            content: "...";
            animation: dots 1.5s steps(4, end) infinite;
        }
        @keyframes dots {
            0%, 20% { content: ""; }
            40% { content: "."; }
            60% { content: ".."; }
            80%, 100% { content: "..."; }
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container">
        <h1 class="cities-heading">Explore - Cebu Province</h1>
        
        <div class="search-container">
            <input type="text" id="citySearch" placeholder="Search for a city...">
        </div>
        
        <div class="loading" id="loadingIndicator">Loading cities</div>
        
        <div class="cities-grid" id="citiesGrid" style="display: none;">
            <?php foreach ($city_descriptions as $city => $description): ?>
            <div class="city-card" data-city="<?php echo strtolower($city); ?>">
                <img src="<?php echo $defaultImageData; ?>" 
                     data-src="images/cities/<?php echo strtolower(str_replace(' ', '-', $city)); ?>.jpg" 
                     alt="<?php echo $city; ?>" 
                     class="city-image">
                <div class="city-info">
                    <h2 class="city-name"><?php echo $city; ?></h2>
                    <p class="city-description"><?php echo $description; ?></p>
                    <a href="shop.php?city=<?php echo urlencode($city); ?>" class="city-button">Explore Artisan Products</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script>
        // Show loading indicator
        document.getElementById('loadingIndicator').style.display = 'block';
        
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Make sure all scripts and styles are loaded before displaying content
            window.addEventListener('load', function() {
                initializePageContent();
            });
            
            // Fallback in case 'load' event doesn't fire
            setTimeout(initializePageContent, 1000);
        });
        
        function initializePageContent() {
            // Hide loading indicator and show grid
            document.getElementById('loadingIndicator').style.display = 'none';
            document.getElementById('citiesGrid').style.display = 'grid';
            
            // Setup image loading
            setupImageLoading();
            
            // Setup search functionality
            setupSearch();
        }
        
        function setupImageLoading() {
            const images = document.querySelectorAll('.city-image');
            const defaultImage = '<?php echo $defaultImageData; ?>';
            
            images.forEach(img => {
                if (img.dataset.src) {
                    // Create a new image element to preload
                    const preloadImg = new Image();
                    
                    // Set up load event
                    preloadImg.onload = function() {
                        img.src = preloadImg.src;
                    };
                    
                    // Set up error event
                    preloadImg.onerror = function() {
                        img.src = defaultImage;
                    };
                    
                    // Start loading the image
                    preloadImg.src = img.dataset.src;
                }
            });
        }
        
        function setupSearch() {
            const searchInput = document.getElementById('citySearch');
            if (!searchInput) return;
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const cityCards = document.querySelectorAll('.city-card');
                
                cityCards.forEach(card => {
                    const cityName = card.getAttribute('data-city').toLowerCase();
                    const cityText = card.querySelector('.city-name').textContent.toLowerCase();
                    const cityDesc = card.querySelector('.city-description').textContent.toLowerCase();
                    
                    if (cityName.includes(searchTerm) || cityText.includes(searchTerm) || cityDesc.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html> 