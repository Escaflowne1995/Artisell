<?php
// Start the session
session_start();
require 'db_connection.php';

// Initialize cart if needed
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get the selected city from the query parameter
$selected_city = isset($_GET['city']) ? strtolower($_GET['city']) : '';

// Fetch distinct categories from products table
$sql = "SELECT DISTINCT category FROM products";
$result = mysqli_query($conn, $sql);
$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row['category'];
}

// Get category filter from URL if present
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// Fetch products based on category filter if selected
if (!empty($selected_category)) {
    $sql = "SELECT * FROM products WHERE category = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $selected_category);
    mysqli_stmt_execute($stmt);
    $products_result = mysqli_stmt_get_result($stmt);
} else {
    // Fetch all products if no category selected
    $sql = "SELECT * FROM products";
    $products_result = mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtiSell - Explore Cebu's Treasures</title>
    <link rel="stylesheet" href="css/categories.css">
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
    </style>
</head>
<body>
    <!-- Header -->
    <header>
    <div class="container header-inner">
        <div class="logo">
            <a href="#">Arti<span style="color: #333;">Sell</span></a>
        </div>
        <nav>
            <ul>
                <li><a href="shop.php" class="nav-link">Shop</a></li>
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                        <li><a href="add_product.php" class="nav-link">Add Product</a></li>
                    <?php else: ?>
                        <li><a href="cart.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                        </svg> (<?php echo count($_SESSION['cart']); ?>)</a></li>
                    <?php endif; ?>
                    <li class="profile-dropdown">
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
                    </li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link">Login</a></li>
                    <li><a href="signup.php" class="nav-link">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
    <!-- Hero Banner -->
    <section class="hero-banner">
        <div class="container">
            <h1>DISCOVER THE<br>CEBU CITY<br>UNIQUE CRAFTS</h1>
            <p>Discover authentic crafts and delicacies from talented artisans across Cebu</p>
        </div>
    </section>

    <!-- Main Content Area -->
    <div class="container">
        <main class="main-content">
            <!-- Filters Sidebar -->
            <aside class="filters">
                <div class="filters-header">
                    <i>📊</i> Filters
                </div>
                <div class="filter-section">
                    <div class="dropdown">
                        <select id="cityFilter" onchange="filterByCity(this.value)">
                            <option value="">All Cities</option>
                            <option value="Aloquinsan">Aloguinsan</option>
                            <option value="Catmon">Catmon</option>
                            <option value="Dumanjug">Dumanjug</option>
                            <option value="Santander">Santander</option>
                            <option value="Alcoy">Alcoy</option>
                            <option value="Minglanilla">Minglanilla</option>
                            <option value="Alcantara">Alcantara</option>
                            <option value="Moalboal">Moalboal</option>
                            <option value="Borbon">Borbon</option>
                            <option value="Cebu">Cebu</option>
                        </select>
                    </div>
                </div>
                <div class="search-box">
                    <h3>Search</h3>
                    <form action="shop.php" method="GET">
                        <input type="text" name="search" placeholder="Search categories...">
                    </form>
                </div>
            </aside>

            <!-- Categories Grid -->
            <div class="products-grid">
    <?php
    foreach ($categories as $category) {
        $display = ($selected_city === '' || $selected_city === $category) ? 'block' : 'none';
        // Set the correct image path
        $imagePath = "images/categories/" . strtolower(str_replace(' ', '-', $category)) . ".jpg";
        
        echo "
        <div class='product-card' data-city='{$category}' style='display: {$display};'>
            <div class='product-image' onclick=\"openModal('{$imagePath}')\">
                <img src='{$imagePath}' alt='{$category}'>
            </div>
            <div class='product-details'>
                <h3 class='product-name'>{$category}</h3>
                <a href='shop.php?category={$category}' class='add-to-cart'>Shop Now</a>
            </div>
        </div>";
    }
    ?>
</div>
        </main>
        
        <div class="products-count" id="productsCount">
            Showing <?php echo count(array_filter($categories, fn($c) => $selected_city === '' || $selected_city === $c)); ?> of <?php echo count($categories); ?> categories
        </div>
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

        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Filter product cards by city
        function filterByCity(city) {
            const cards = document.querySelectorAll('.product-card');
            const countElement = document.getElementById('productsCount');
            let visibleCount = 0;

            cards.forEach(card => {
                const cardCity = card.getAttribute('data-city');
                if (city === '' || cardCity === city) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            countElement.textContent = `Showing ${visibleCount} of ${cards.length} categories`;
            window.history.pushState({}, document.title, city ? `?city=${city}` : window.location.pathname);
        }

        // Set initial filter based on URL parameter
        document.addEventListener('DOMContentLoaded', () => {
            const urlCity = '<?php echo $selected_city; ?>';
            if (urlCity) {
                document.getElementById('cityFilter').value = urlCity;
                filterByCity(urlCity);
            }
        });
    </script>
</body>
</html>