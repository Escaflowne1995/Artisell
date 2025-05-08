<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is a vendor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'vendor') {
    header("location: login.php");
    exit;
}

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize message/error variables
$message = "";
$error = "";

// Handle delete request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security verification failed. Please try again.";
    } 
    // Validate product_id
    else if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        $error = "Invalid product ID";
    } else {
        $product_id = (int)$_POST['product_id'];

        // Fetch product to ensure it belongs to the vendor and get image path
        $sql = "SELECT image FROM products WHERE id = ? AND vendor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $product_id, $_SESSION['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);

        if ($product) {
            // Delete the image file
            if (!empty($product['image']) && file_exists($product['image'])) {
                unlink($product['image']);
            }

            // Delete from database
            $sql = "DELETE FROM products WHERE id = ? AND vendor_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $product_id, $_SESSION['id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Product deleted successfully";
                // Generate new CSRF token after successful action
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = "Error deleting product: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Product not found or you don't have permission to delete it";
        }
    }
}

// Fetch vendor's products
$sql = "SELECT * FROM products WHERE vendor_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ArtSell</title>
    <style>
        body { 
            background-color: #f9f9f9; 
            color: #333; 
            font-family: 'Open Sans', sans-serif; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
        }
        header { 
            background: #fff; 
            padding: 15px 0; 
            border-bottom: 1px solid #eee; 
        }
        .header-inner { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .logo a { 
            color: #ff6b00; 
            text-decoration: none; 
            font-size: 20px; 
            font-weight: bold; 
        }
        nav ul { 
            display: flex; 
            list-style: none; 
        }
        nav ul li { 
            margin-left: 25px; 
        }
        nav ul li a { 
            color: #333; 
            text-decoration: none; 
            font-weight: 500; 
        }
        h1 { 
            font-size: 24px; 
            font-weight: 600; 
            margin: 30px 0; 
            color: #333; 
        }
        .products-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); 
            gap: 20px; 
        }
        .product-card { 
            background: #fff; 
            border-radius: 6px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            overflow: hidden; 
            padding: 15px; 
        }
        .product-image img { 
            max-width: 100%; 
            height: 150px; 
            object-fit: cover; 
            border-radius: 4px; 
        }
        .product-details { 
            margin-top: 10px; 
        }
        .product-name { 
            font-weight: bold; 
            font-size: 16px; 
        }
        .product-price { 
            font-weight: 600; 
            color: #ff6b00; 
            margin: 5px 0; 
        }
        .product-stock {
            margin: 5px 0;
            font-size: 14px;
        }
        .in-stock {
            color: #28a745;
            font-weight: 500;
        }
        .out-of-stock {
            color: #dc3545;
            font-weight: 500;
        }
        .button-container { 
            display: flex; 
            gap: 10px; 
            margin-top: 10px; 
        }
        .edit-btn, .delete-btn { 
            flex: 1; 
            padding: 8px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-align: center; 
            font-weight: 500; 
        }
        .edit-btn { 
            background: #3b5998; 
            color: white; 
        }
        .delete-btn { 
            background: #ff0000; 
            color: white; 
        }
        .stock-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            font-weight: 500;
            background: #28a745;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .stock-btn:hover {
            background: #218838;
        }
        .edit-btn:hover { 
            background: #2a4373; 
        }
        .delete-btn:hover { 
            background: #cc0000; 
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-inner">
            <div class="logo"><a href="#">Art<span style="color: #333;">Sell</span></a></div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="add_product.php">Add Product</a></li>
                    <li><a href="vendor_products.php">My Products</a></li>
                    <li><a href="update_all_stocks.php">Bulk Update Stocks</a></li>
                    <li><a href="manage_orders.php">Manage Orders</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>My Products</h1>
        
        <?php if(!empty($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="product-details">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                            <div class="product-stock <?php echo isset($product['stock']) && $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php 
                                if (isset($product['stock'])) {
                                    echo $product['stock'] > 0 ? "In Stock: " . $product['stock'] : "Out of Stock";
                                } else {
                                    echo "Stock status unknown";
                                }
                                ?>
                            </div>
                            <div class="button-container">
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="edit-btn">Edit</a>
                                <a href="update_stock.php?id=<?php echo $product['id']; ?>" class="stock-btn">Update Stock</a>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" name="delete_product" class="delete-btn">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You haven't added any products yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>