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
    <title>ArtSell - Cebu Cultural Marketplace</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body { background-color: #f9f9f9; color: #333; font-family: 'Open Sans', sans-serif; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        header { background: #fff; padding: 15px 0; border-bottom: 1px solid #eee; }
        .header-inner { display: flex; justify-content: space-between; align-items: center; }
        .logo a { color: #ff6b00; text-decoration: none; font-size: 20px; font-weight: bold; }
        nav ul { display: flex; list-style: none; margin: 0; padding: 0; align-items: center; justify-content: flex-end; }
        nav ul li { margin-left: 25px; }
        nav ul li a { color: #333; text-decoration: none; font-weight: 700; }
        .profile-dropdown { position: relative; }
        .profile-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content { position: absolute; right: 0; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 4px; min-width: 120px; }
        .dropdown-content a { display: block; padding: 10px 15px; color: #333; text-decoration: none; }
        .dropdown-content a:hover { background: #f5f5f5; }
        .main-content { display: flex; padding: 30px 0; }
        .filters { width: 240px; padding-right: 30px; }
        .filters h3 { font-size: 14px; margin-bottom: 10px; color: #555; }
        .filters select, .filters input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; }
        .products-grid { flex: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 20px; }
        .product-card { background: #fff; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-5px); }
        .product-image { height: 200px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .product-image img { max-width: 100%; max-height: 100%; object-fit: cover; }
        .product-details { padding: 15px; }
        .product-name { font-weight: bold; margin: 5px 0; font-size: 16px; }
        .product-description { font-size: 14px; color: #666; margin: 10px 0; }
        .product-price { font-weight: 600; color: #ff6b00; }
        .stock-info { font-size: 14px; margin: 5px 0; }
        .in-stock { color: #28a745; }
        .low-stock { color: #ffc107; }
        .out-of-stock { color: #dc3545; }
        .button-container { display: flex; gap: 10px; }
        .add-to-cart, .buy-now { flex: 1; padding: 10px; border: none; border-radius: 4px; cursor: pointer; text-align: center; font-weight: 500; }
        .add-to-cart { background: #3b5998; color: white; }
        .buy-now { background: #ff6b00; color: white; }
        .add-to-cart:hover { background: #2a4373; }
        .buy-now:hover { background: #e65c00; }
        .add-to-cart:disabled, .buy-now:disabled { 
            background: #cccccc; 
            color: #666666; 
            cursor: not-allowed; 
        }
        .pagination { margin: 20px 0; text-align: center; }
        .pagination a { padding: 8px 12px; margin: 0 5px; text-decoration: none; color: #333; border: 1px solid #ddd; border-radius: 4px; }
        .pagination a.active { background: #3b5998; color: white; }
        footer { background: #2c3e50; color: white; padding: 40px 0 20px; }
        .footer-content { display: flex; justify-content: space-between; }
        .footer-column { flex: 1; padding: 0 15px; }
        .footer-logo { color: #ff6b00; font-size: 20px; font-weight: bold; }
        .newsletter input { padding: 8px; width: 70%; border: none; border-radius: 4px 0 0 4px; }
        .newsletter button { padding: 8px; background: #ff6b00; color: white; border: none; border-radius: 0 4px 4px 0; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
        .modal-content { max-width: 90%; max-height: 90%; border-radius: 6px; }
        .close { position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; font-weight: bold; cursor: pointer; }
       
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
        /* Cart styling */
        .cart-link {
            font-weight: 700;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-inner">
            <div class="logo"><a href="#">Art<span style="color: #333;">Sell</span></a></div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                            <li><a href="add_product.php">Add Product</a></li>
                            <li><a href="vendor_products.php">My Products</a></li>
                        <?php else: ?>
                            <li><a href="cart.php" class="cart-link">Cart (<?php echo count($_SESSION['cart']); ?>)</a></li>
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

    <!-- Main Content -->
    <div class="container">
        <main class="main-content">
            <!-- Filters -->
            <aside class="filters">
                <h3>Category</h3>
                <select onchange="location = this.value;">
                    <option value="?">All Categories</option>
                    <option value="?category=crafts" <?php echo $category === 'crafts' ? 'selected' : ''; ?>>Crafts</option>
                    <option value="?category=delicacies" <?php echo $category === 'delicacies' ? 'selected' : ''; ?>>Delicacies</option>
                </select>
                <h3>City</h3>
                <select onchange="location = this.value;">
                    <option value="?">All Cities</option>
                    <?php
                    $cities = ['aloguinsan', 'catmon', 'dumanjug', 'santander', 'alcoy', 'minglanilla', 'alcantara', 'moalboal', 'borbon'];
                    foreach ($cities as $c) {
                        echo "<option value='?city=$c'" . ($city === $c ? ' selected' : '') . ">" . ucfirst($c) . "</option>";
                    }
                    ?>
                </select>
                <h3>Search</h3>
                <form method="GET">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <input type="submit" value="Search" style="display: none;">
                </form>
            </aside>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php if (!empty($paginated_products)): ?>
                    <?php foreach ($paginated_products as $product): ?>
                        <div class="product-card">
                            <div class="product-image" onclick="openModal('<?php echo htmlspecialchars($product['image']); ?>')">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="product-details">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 70)) . '...'; ?></p>
                                <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                                
                                <?php 
                                $stock_status = "";
                                $is_in_stock = true;
                                if (isset($product['stock'])) {
                                    if ($product['stock'] <= 0) {
                                        $stock_status = "Out of Stock";
                                        $stock_class = "out-of-stock";
                                        $is_in_stock = false;
                                    } elseif ($product['stock'] <= 5) {
                                        $stock_status = "Low Stock: " . $product['stock'] . " left";
                                        $stock_class = "low-stock";
                                    } else {
                                        $stock_status = "In Stock: " . $product['stock'] . " available";
                                        $stock_class = "in-stock";
                                    }
                                } else {
                                    $stock_status = "Stock status unknown";
                                    $stock_class = "";
                                }
                                ?>
                                
                                <p class="stock-info <?php echo $stock_class; ?>"><?php echo $stock_status; ?></p>
                                
                                <div class="button-container">
                                    <form method="POST" action="add_to_cart.php">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="redirect" value="shop.php">
                                        <button type="submit" name="add_to_cart" class="add-to-cart" <?php echo !$is_in_stock ? 'disabled' : ''; ?>>Add to Cart</button>
                                    </form>
                                    <form method="POST" action="add_to_cart.php">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="redirect" value="cart.php">
                                        <button type="submit" name="buy_now" class="buy-now" <?php echo !$is_in_stock ? 'disabled' : ''; ?>>Buy Now</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No products found. Try a different search.</p>
                <?php endif; ?>
            </div>
        </main>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?category=<?php echo $category; ?>&city=<?php echo $city; ?>&search=<?php echo $search; ?>&page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">×</span>
        <img class="modal-content" id="modalImage">
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <script>
        // Modal functionality
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            modalImg.src = imageSrc;
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
        }

        // Close modal when clicking outside the image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Buy Now alert
        document.querySelectorAll('.buy-now').forEach(btn => {
            btn.addEventListener('click', () => alert('Proceeding to checkout!'));
        });
    </script>
</body>
</html>