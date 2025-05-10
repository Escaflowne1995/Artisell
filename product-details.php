<?php
session_start();
include 'db_connection.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get product details
$product = null;
if ($product_id > 0) {
    $query = "SELECT * FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
    }
}

// If product not found
if (!$product) {
    header("Location: index.php");
    exit;
}

// Default image
$image_path = "image/coconut-bowl-palm.jpg";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - ArtiSell</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .product-container {
            display: flex;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .product-images {
            flex: 1;
            min-width: 300px;
            padding-right: 40px;
        }
        
        .product-info {
            flex: 1;
            min-width: 300px;
        }
        
        .product-main-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .product-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-size: 24px;
            font-weight: bold;
            color: #ff6b00;
            margin-bottom: 20px;
        }
        
        .product-description {
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .add-to-cart-btn {
            background-color: #ff6b00;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .add-to-cart-btn:hover {
            background-color: #e05f00;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .quantity-selector input {
            width: 50px;
            text-align: center;
            margin: 0 10px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .quantity-selector button {
            background: #eee;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header class="header">
            <div class="container header-inner">
                <a href="index.php" class="logo">Art<span>iSell</span></a>
                
                <div class="header-right">
                    <a href="#"><i class="search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg></i></a>
                    <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <a href="cart.php" class="nav-link"><i class="cart-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                        </svg></i><?php echo isset($_SESSION['cart']) ? " (" . count($_SESSION['cart']) . ")" : ""; ?></a>
                        <a href="profile.php" class="nav-link"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <main>
            <div class="product-container">
                <div class="product-images">
                    <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-main-image">
                </div>
                
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                    
                    <div class="product-description">
                        <?php if (isset($product['description']) && !empty($product['description'])): ?>
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        <?php else: ?>
                            <p>This authentic Cebuano product represents the rich craftsmanship of local artisans. 
                            Each piece is carefully handcrafted using traditional methods passed down through generations.</p>
                        <?php endif; ?>
                    </div>
                    
                    <form action="add_to_cart.php" method="post">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <button type="button" onclick="decrementQuantity()">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1">
                            <button type="button" onclick="incrementQuantity()">+</button>
                        </div>
                        
                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </main>
        
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-logo">
                        <h2>ArtiSell</h2>
                        <p>Connecting you with authentic Cebuano crafts and delicacies.</p>
                    </div>
                    
                    <div class="footer-links">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="shop.php">Shop</a></li>
                            <li><a href="about.php">About Us</a></li>
                            <li><a href="contact.php">Contact</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-contact">
                        <h3>Contact Us</h3>
                        <p>Email: info@artisell.ph</p>
                        <p>Phone: +63 32 123 4567</p>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; 2023 ArtiSell. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
    
    <script>
        function incrementQuantity() {
            const quantityInput = document.getElementById('quantity');
            quantityInput.value = parseInt(quantityInput.value) + 1;
        }
        
        function decrementQuantity() {
            const quantityInput = document.getElementById('quantity');
            if (parseInt(quantityInput.value) > 1) {
                quantityInput.value = parseInt(quantityInput.value) - 1;
            }
        }
    </script>
</body>
</html> 