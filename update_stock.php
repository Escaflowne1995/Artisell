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

$message = "";
$error = "";
$product = null;

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: vendor_products.php");
    exit;
}

$product_id = (int)$_GET['id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security verification failed. Please try again.";
    } 
    // Validate stock amount
    else if (!isset($_POST['stock']) || !is_numeric($_POST['stock']) || (int)$_POST['stock'] < 0) {
        $error = "Please enter a valid stock amount (0 or greater).";
    } else {
        $new_stock = (int)$_POST['stock'];
        
        // Update stock in database
        $sql = "UPDATE products SET stock = ? WHERE id = ? AND vendor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $new_stock, $product_id, $_SESSION['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Stock updated successfully!";
            // Generate new CSRF token after successful action
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $error = "Error updating stock: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch product details
$sql = "SELECT * FROM products WHERE id = ? AND vendor_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $product_id, $_SESSION['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $product = $row;
} else {
    // Product not found or doesn't belong to this vendor
    header("location: vendor_products.php");
    exit;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Stock - ArtSell</title>
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
            margin: 0;
            padding: 0;
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
        .form-container { 
            padding: 30px 0; 
            display: flex; 
            justify-content: center; 
        }
        .stock-form { 
            flex: 2; 
            background: #fff; 
            padding: 20px; 
            border-radius: 6px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            max-width: 600px; 
        }
        .product-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .product-image {
            width: 100px;
            height: 100px;
            margin-right: 20px;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        .product-details h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        .product-price {
            font-weight: 600;
            color: #ff6b00;
            margin-bottom: 5px;
        }
        .current-stock {
            font-size: 14px;
        }
        .in-stock {
            color: #28a745;
        }
        .out-of-stock {
            color: #dc3545;
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 500; 
        }
        .form-group input { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        .button-row {
            display: flex;
            gap: 10px;
        }
        .update-btn { 
            flex: 1;
            padding: 12px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: background 0.3s ease; 
            font-weight: 500; 
        }
        .cancel-btn {
            flex: 1;
            padding: 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
        }
        .update-btn:hover { 
            background: #218838; 
        }
        .cancel-btn:hover {
            background: #5a6268;
        }
        .message { 
            margin-bottom: 20px; 
            padding: 10px; 
            border-radius: 4px; 
            text-align: center;
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
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>Update Stock</h1>
        
        <div class="form-container">
            <div class="stock-form">
                <?php if(!empty($message)): ?>
                    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if(!empty($error)): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if($product): ?>
                    <div class="product-info">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="product-details">
                            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                            <div class="current-stock <?php echo isset($product['stock']) && $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                Current Stock: <?php echo isset($product['stock']) ? $product['stock'] : '0'; ?> units
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="stock">New Stock Amount:</label>
                            <input type="number" id="stock" name="stock" min="0" value="<?php echo isset($product['stock']) ? $product['stock'] : '0'; ?>" required>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="button-row">
                            <button type="submit" name="update_stock" class="update-btn">Update Stock</button>
                            <a href="vendor_products.php" class="cancel-btn">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 