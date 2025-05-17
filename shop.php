<?php
session_start();
require 'db_connection.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart - Will be redirected to login if not logged in from the form submission

// Get filter parameters and product listing
$category = isset($_GET['category']) ? $_GET['category'] : '';
$city = isset($_GET['city']) ? $_GET['city'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Define available cities in Cebu province
$valid_cities = [
    'Cebu City',
    'Mandaue',
    'Lapu-Lapu',
    'Carcar',
    'Talisay',
    'Danao',
    'Toledo',
    'Bogo',
    'Naga',
    'Minglanilla',
    'Moalboal',
    'Santander',
    'Aloguinsan',
    'Alcoy',
    'Dumanjug',
    'Catmon',
    'Borbon',
    'Alcantara'
];

// Optionally get additional cities from database
$sql_cities = "SELECT DISTINCT city FROM products WHERE city IS NOT NULL AND city != '' ORDER BY city";
$result_cities = mysqli_query($conn, $sql_cities);
$db_cities = [];

if ($result_cities) {
    while ($row = mysqli_fetch_assoc($result_cities)) {
        if (!empty($row['city'])) {
            $db_cities[] = $row['city'];
        }
    }
}

// Merge and remove duplicates
$all_cities = array_unique(array_merge($valid_cities, $db_cities));
sort($all_cities);

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}
if (!empty($city)) {
    $sql .= " AND city = ?";
    $params[] = $city;
    $types .= "s";
}
if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

// Pagination
$items_per_page = 8;
$total_items = count($products);
$total_pages = ceil($total_items / $items_per_page);
$page = isset($_GET['page']) ? max(1, min($total_pages, (int)$_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;
$paginated_products = array_slice($products, $offset, $items_per_page);

// Automatically add stock to products that are out of stock
foreach ($paginated_products as $key => $product) {
    if (!isset($product['stock']) || $product['stock'] <= 0) {
        // Set a default stock value between 15-50 for featured products
        $default_stock = rand(15, 50);
        
        // Update in the database
        $update_sql = "UPDATE products SET stock = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $default_stock, $product['id']);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Update in the current array
            $paginated_products[$key]['stock'] = $default_stock;
        }
        
        mysqli_stmt_close($update_stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtiSell - Shop Cebu Artisan Products</title>
    <meta name="description" content="Shop our curated collection of authentic Cebuano arts, crafts, and traditional foods">
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .shop-container {
            padding: var(--space-6) 0;
        }
        
        .shop-header {
            background-color: var(--neutral-100);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
            box-shadow: var(--shadow-md);
            background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('images/cebu-crafts-banner.jpg');
            background-size: cover;
            background-position: center;
            color: white;
        }
        
        .shop-title {
            font-size: var(--font-size-3xl);
            margin-bottom: var(--space-2);
        }
        
        .shop-subtitle {
            font-size: var(--font-size-lg);
            margin-bottom: var(--space-4);
            opacity: 0.9;
        }
        
        .filter-section {
            margin-bottom: var(--space-6);
        }
        
        .shop-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: var(--space-6);
        }
        
        .filter-card {
            background-color: var(--neutral-100);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            box-shadow: var(--shadow-md);
            height: fit-content;
        }
        
        .filter-title {
            font-size: var(--font-size-lg);
            margin-bottom: var(--space-4);
            padding-bottom: var(--space-2);
            border-bottom: 1px solid var(--neutral-300);
        }
        
        .filter-group {
            margin-bottom: var(--space-4);
        }
        
        .filter-label {
            display: block;
            margin-bottom: var(--space-2);
            font-weight: 500;
            color: var(--neutral-700);
        }
        
        .filter-control {
            width: 100%;
            padding: var(--space-2);
            border: 1px solid var(--neutral-400);
            border-radius: var(--radius-md);
            background-color: var(--neutral-100);
            margin-bottom: var(--space-3);
        }
        
        .filter-btn {
            width: 100%;
            margin-top: var(--space-2);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: var(--space-6);
            gap: var(--space-2);
        }
        
        .pagination a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            border: 1px solid var(--neutral-400);
            font-weight: 500;
            transition: all var(--transition-fast) ease;
        }
        
        .pagination a:hover {
            background-color: var(--neutral-200);
        }
        
        .pagination a.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .stock-badge {
            display: inline-block;
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
            margin-bottom: var(--space-3);
        }
        
        .in-stock {
            background-color: rgba(52, 199, 89, 0.1);
            color: var(--accent);
        }
        
        .low-stock {
            background-color: rgba(255, 204, 0, 0.1);
            color: #ffc107;
        }
        
        .out-of-stock {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: var(--radius-lg);
        }
        
        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }
        
        @media (max-width: 992px) {
            .shop-layout {
                grid-template-columns: 1fr;
            }
            
            .filter-card {
                margin-bottom: var(--space-4);
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
    
    <main class="container shop-container">
        <div class="shop-header">
            <h1 class="shop-title">Shop Cebu's Artisan Treasures</h1>
            <p class="shop-subtitle">Authentic crafts and delicacies made by local artisans</p>
        </div>
        
        <div class="shop-layout">
            <div class="filters">
                <div class="filter-card">
                    <h2 class="filter-title">Filters</h2>
                    <form action="shop.php" method="GET">
                        <div class="filter-group">
                            <label for="category" class="filter-label">Category</label>
                            <select name="category" id="category" class="filter-control">
                                <option value="">All Categories</option>
                                <option value="jewelry" <?php echo $category == 'jewelry' ? 'selected' : ''; ?>>Jewelry</option>
                                <option value="home-decor" <?php echo $category == 'home-decor' ? 'selected' : ''; ?>>Home Decor</option>
                                <option value="textiles" <?php echo $category == 'textiles' ? 'selected' : ''; ?>>Textiles</option>
                                <option value="food" <?php echo $category == 'food' ? 'selected' : ''; ?>>Food & Delicacies</option>
                                <option value="crafts" <?php echo $category == 'crafts' ? 'selected' : ''; ?>>Handcrafted Items</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="city" class="filter-label">City</label>
                            <select name="city" id="city" class="filter-control">
                                <option value="">All Cities</option>
                                <?php foreach ($all_cities as $city_option): ?>
                                    <option value="<?php echo htmlspecialchars($city_option); ?>" <?php echo $city == $city_option ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($city_option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="search" class="filter-label">Search</label>
                            <input type="text" name="search" id="search" class="filter-control" placeholder="Search for products..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary filter-btn">Apply Filters</button>
                    </form>
                </div>
            </div>
            
            <div class="products-section">
                <div class="product-grid">
                    <?php if (empty($paginated_products)): ?>
                        <div class="card p-5 text-center" style="grid-column: 1 / -1;">
                            <i class="fas fa-box-open fa-4x mb-3 text-neutral-500"></i>
                            <h2 class="mb-3">No products found</h2>
                            <p class="mb-4">Try adjusting your filters or check back later for new products.</p>
                            <a href="shop.php" class="btn btn-primary">Clear Filters</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($paginated_products as $product): ?>
                            <div class="card">
                                <img src="<?php echo isset($product['image']) && !empty($product['image']) ? htmlspecialchars($product['image']) : 'image/coconut-bowl-palm.jpg'; ?>" 
                                     alt="<?php echo isset($product['name']) ? htmlspecialchars($product['name']) : 'Product'; ?>" 
                                     class="card-img"
                                     onclick="openModal('<?php echo isset($product['image']) && !empty($product['image']) ? htmlspecialchars($product['image']) : 'image/coconut-bowl-palm.jpg'; ?>')">
                                
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo isset($product['name']) ? htmlspecialchars($product['name']) : 'Product'; ?></h3>
                                    
                                                                        <?php                                        $stock = isset($product['stock']) ? (int)$product['stock'] : 0;                                        if ($stock > 10) {                                            echo '<div class="stock-badge in-stock"><i class="fas fa-check-circle"></i> In Stock (' . $stock . ')</div>';                                        } else if ($stock > 0) {                                            echo '<div class="stock-badge low-stock"><i class="fas fa-exclamation-circle"></i> Low Stock (' . $stock . ')</div>';                                        } else {                                            echo '<div class="stock-badge out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock (0)</div>';                                        }                                    ?>
                                    
                                    <p class="card-text"><?php echo isset($product['description']) ? htmlspecialchars(substr($product['description'], 0, 80) . (strlen($product['description']) > 80 ? '...' : '')) : ''; ?></p>
                                    
                                    <div class="card-price">₱<?php echo isset($product['price']) ? number_format($product['price'], 2) : '0.00'; ?></div>
                                    
                                    <div class="d-flex mt-3" style="gap: var(--space-2);">
                                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">View Details</a>
                                        
                                        <form action="add_to_cart.php" method="POST" style="flex-grow: 1;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <input type="hidden" name="current_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                            <input type="hidden" name="redirect" value="shop.php">
                                            <button type="submit" class="btn btn-primary" style="width: 100%;" <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($city) ? '&city=' . urlencode($city) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($city) ? '&city=' . urlencode($city) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="<?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($city) ? '&city=' . urlencode($city) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>
    
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
        // Image modal functions
        function openModal(imageUrl) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            modalImg.src = imageUrl;
        }
        
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
    </script>
</body>
</html>