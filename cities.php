<?php
// Start the session
session_start();
require 'db_connection.php';

// Initialize cart if needed
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch distinct cities from products table
$sql = "SELECT DISTINCT city FROM products WHERE city IS NOT NULL";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtiSell - Explore Cebu's Cities</title>
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
            background-color: #4CAF50;
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
            background-color: #45a049;
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
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container">
        <h1 class="cities-heading">Explore Cebu's Cities</h1>
        
        <div class="search-container">
            <input type="text" id="citySearch" placeholder="Search for a city...">
        </div>
        
        <div class="cities-grid">
            <?php foreach ($city_descriptions as $city => $description): ?>
            <div class="city-card" data-city="<?php echo strtolower($city); ?>">
                <img src="images/cities/<?php echo strtolower(str_replace(' ', '-', $city)); ?>.jpg" 
                     onerror="this.src='images/cities/default-city.jpg'" 
                     alt="<?php echo $city; ?>" 
                     class="city-image">
                <div class="city-info">
                    <h2 class="city-name"><?php echo $city; ?></h2>
                    <p class="city-description"><?php echo $description; ?></p>
                    <a href="shop.php?city=<?php echo urlencode($city); ?>" class="city-button">Explore Products</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script>
        // City search functionality
        document.getElementById('citySearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
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
    </script>
</body>
</html> 