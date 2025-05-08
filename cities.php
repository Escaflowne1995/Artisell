<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php"); // Redirect to login page
    exit; // Ensure no further code is executed after redirection
}

// Get the selected city from the query parameter
$selected_city = isset($_GET['city']) ? strtolower($_GET['city']) : '';

// Fetch distinct cities from products table
$sql = "SELECT DISTINCT city FROM products WHERE city IS NOT NULL AND city != ''";
$result = mysqli_query($conn, $sql);
$cities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cities[] = $row['city'];
}

// City information
$city_info = [
    'Aloguinsan' => [
        'name' => 'ALOGUINSAN',
        'image' => 'image/ALOGUINSAN.jpg',
        'description' => 'Located in the southwestern part of Cebu, Aloguinsan is known for its eco-tourism sites, including the famous Bojo River. The town offers unique crafts that reflect its natural beauty and cultural heritage.',
        'products' => 'Handwoven baskets, bamboo crafts, and local delicacies made from indigenous ingredients.'
    ],
    'Catmon' => [
        'name' => 'CATMON',
        'image' => 'image/CATMON.jpg',
        'description' => 'Catmon is a coastal municipality in northern Cebu, known for its pristine beaches and waterfalls. The town has a rich tradition of craftsmanship passed down through generations.',
        'products' => 'Famous for Budbud Kabog (millet dessert), handcrafted baskets, and native bamboo crafts.'
    ],
    'Dumanjug' => [
        'name' => 'DUMANJUG',
        'image' => 'image/DUMANJUG.jpg',
        'description' => 'Dumanjug is a historic town on the southwestern coast of Cebu. Known for its Spanish-era church and traditional celebrations, the town preserves many old crafting techniques.',
        'products' => 'Known for "Torta sa Dumanjug" (a traditional cake), woven mats, and handcrafted religious items.'
    ],
    'Santander' => [
        'name' => 'SANTANDER',
        'image' => 'image/mingla.png',
        'description' => 'The southernmost municipality of Cebu, Santander is a gateway to Negros Island. With its beautiful beaches and coral reefs, the town influences many of its craft designs.',
        'products' => 'Fresh seafood products, shell crafts, and handwoven beach accessories.'
    ],
    'Alcoy' => [
        'name' => 'ALCOY',
        'image' => 'image/mingla.png',
        'description' => 'Alcoy is home to the beautiful Tingko Beach and serves as a quiet retreat in southeastern Cebu. The town has a thriving community of artisans working with coconut and other local materials.',
        'products' => 'Coconut-based products and delicacies, handcrafted souvenirs, and beach accessories.'
    ],
    'Minglanilla' => [
        'name' => 'MINGLANILLA',
        'image' => 'image/mingla.png',
        'description' => 'One of the oldest towns in Cebu, Minglanilla is known for its "Sugat-Kabanhawan" Festival. Its proximity to Cebu City has helped its artisans blend traditional and contemporary styles.',
        'products' => 'Known for intricate handwoven bags, furniture made of native materials, and local pastries and delicacies.'
    ],
    'Alcantara' => [
        'name' => 'ALCANTARA',
        'image' => 'image/mingla.png',
        'description' => 'Alcantara is a coastal municipality on Cebu\'s western seaboard. With abundant marine resources, many of its crafts incorporate elements from the sea.',
        'products' => 'Fresh and dried seafood products, woven baskets, and traditional handmade accessories.'
    ],
    'Moalboal' => [
        'name' => 'MOALBOAL',
        'image' => 'image/mingla.png',
        'description' => 'Famous for its sardine run and diving spots, Moalboal is a tourism hotspot in western Cebu. The influx of tourists has inspired local artisans to create unique souvenirs.',
        'products' => 'Shell crafts, beach-themed accessories, and native snacks and preserves.'
    ],
    'Borbon' => [
        'name' => 'BORBON',
        'image' => 'image/mingla.png',
        'description' => 'Borbon is a historic town in northern Cebu with rich agricultural traditions. The town is preserving its heritage through its crafts and culinary offerings.',
        'products' => 'Famous for "Takyong" (land snails) delicacy, woven products, and handcrafted farming tools.'
    ],
    'Cebu' => [
        'name' => 'CEBU CITY',
        'image' => 'image/mingla.png',
        'description' => 'The capital city of Cebu province, Cebu City is a vibrant urban center that blends history, culture, and modernity. As the heart of the province, it showcases a wide variety of crafts from across the region.',
        'products' => 'Guitar making, shell craft jewelry, dried mango products, and contemporary urban crafts.'
    ],
    'Carcar' => [
        'name' => 'Carcar',
        'image' => 'image/mingla.png',
        'description' => 'The capital city of Cebu province, Cebu City is a vibrant urban center that blends history, culture, and modernity. As the heart of the province, it showcases a wide variety of crafts from across the region.',
        'products' => 'Guitar making, shell craft jewelry, dried mango products, and contemporary urban crafts.'
    ],

     
];

// Fetch products from selected city
$products = [];
if (!empty($selected_city)) {
    $sql = "SELECT * FROM products WHERE LOWER(city) = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $selected_city);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cities of Cebu - ArtiSell</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        .cities-header {
            padding: 60px 0;
            background: #4F8CA3;
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            margin-bottom: 40px;
            margin-top: 60px;
        }
        .cities-header h1 {
            font-size: 36px;
            margin-bottom: 15px;
        }
        .cities-header p {
            max-width: 800px;
            margin: 0 auto;
            font-size: 16px;
            line-height: 1.6;
        }
        .cities-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            padding: 30px 0;
        }
        .city-sidebar {
            flex: 1;
            min-width: 250px;
            max-width: 300px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            height: fit-content;
        }
        .city-sidebar h2 {
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .city-sidebar ul {
            list-style: none;
        }
        .city-sidebar li {
            margin-bottom: 10px;
        }
        .city-sidebar a {
            display: block;
            padding: 8px 12px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .city-sidebar a:hover, .city-sidebar a.active {
            background: #f5f5f5;
            color: #FF6B17;
        }
        .city-content {
            flex: 3;
            min-width: 300px;
        }
        .city-card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .city-image {
            height: 300px;
            overflow: hidden;
        }
        .city-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .city-details {
            padding: 25px;
        }
        .city-details h2 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #FF6B17;
        }
        .city-details p {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .city-details h3 {
            font-size: 18px;
            margin: 20px 0 15px;
            color: #333;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .product-card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            height: 180px;
            overflow: hidden;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-details {
            padding: 15px;
        }
        .product-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }
        .product-price {
            color: #FF6B17;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .add-to-cart {
            display: block;
            width: 100%;
            padding: 8px 0;
            background: #4F8CA3;
            color: white;
            border: none;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .add-to-cart:hover {
            background: #3D7A8F;
        }
        .no-products {
            padding: 30px;
            text-align: center;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .explore-link {
            display: inline-block;
            margin-top: 10px;
            color: #FF6B17;
            text-decoration: none;
            font-weight: 500;
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
    <header class="header">
        <div class="container header-inner">
            <a href="index.php" class="logo">Arti<span>Sell</span></a>
            <nav>
                <div class="nav-links">
                    <a href="shop.php" class="nav-link">Shop</a>
                    <a href="categories.php" class="nav-link">Categories</a>
                    <a href="cities.php" class="nav-link active">Cities</a>
                    <a href="about.php" class="nav-link">About</a>
                </div>
            </nav>
            <div class="header-right">
                <a href="#"><i class="search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                </svg></i></a>
                <a href="cart.php"><i class="cart-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                </svg></i><?php echo isset($_SESSION['cart']) ? " (" . count($_SESSION['cart']) . ")" : ""; ?></a>
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
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
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="cities-header">
        <div class="container">
            <h1>Cities of Cebu</h1>
            <p>Discover the unique crafts and delicacies from different cities across the beautiful province of Cebu. Each locale offers distinct cultural products that reflect its heritage, traditions, and natural resources.</p>
        </div>
    </section>

    <div class="container cities-container">
        <!-- City Sidebar -->
        <div class="city-sidebar">
            <h2>Explore Cities</h2>
            <ul>
                <li><a href="cities.php" <?php echo empty($selected_city) ? 'class="active"' : ''; ?>>All Cities</a></li>
                <?php foreach ($city_info as $city_key => $city): ?>
                    <li>
                        <a href="cities.php?city=<?php echo $city_key; ?>" <?php echo $selected_city === $city_key ? 'class="active"' : ''; ?>>
                            <?php echo $city['name']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- City Content -->
        <div class="city-content">
            <?php if (empty($selected_city)): ?>
                <!-- Show all cities when no city is selected -->
                <?php foreach ($city_info as $city_key => $city): ?>
                    <div class="city-card">
                        <div class="city-image">
                            <img src="<?php echo $city['image']; ?>" alt="<?php echo $city['name']; ?>">
                        </div>
                        <div class="city-details">
                            <h2><?php echo $city['name']; ?></h2>
                            <p><?php echo $city['description']; ?></p>
                            <h3>Notable Products:</h3>
                            <p><?php echo $city['products']; ?></p>
                            <a href="cities.php?city=<?php echo $city_key; ?>" class="explore-link">Explore Products from <?php echo $city['name']; ?> →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Show selected city and its products -->
                <div class="city-card">
                    <div class="city-image">
                        <img src="<?php echo $city_info[$selected_city]['image']; ?>" alt="<?php echo $city_info[$selected_city]['name']; ?>">
                    </div>
                    <div class="city-details">
                        <h2><?php echo $city_info[$selected_city]['name']; ?></h2>
                        <p><?php echo $city_info[$selected_city]['description']; ?></p>
                        <h3>Notable Products:</h3>
                        <p><?php echo $city_info[$selected_city]['products']; ?></p>
                        
                        <h3>Available Products from <?php echo $city_info[$selected_city]['name']; ?>:</h3>
                        <?php if (!empty($products)): ?>
                            <div class="product-grid">
                                <?php foreach ($products as $product): ?>
                                    <div class="product-card">
                                        <div class="product-image">
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </div>
                                        <div class="product-details">
                                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                            <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                                            <form action="add_to_cart.php" method="POST">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="add-to-cart">Add to Cart</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-products">
                                <p>No products available from this city at the moment.</p>
                                <a href="shop.php" class="explore-link">Browse all products</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>
</body>
</html> 