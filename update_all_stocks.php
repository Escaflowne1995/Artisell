<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is an admin or vendor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
    ($_SESSION["role"] !== 'admin' && $_SESSION["role"] !== 'vendor')) {
    header("location: login.php");
    exit;
}

$message = "";
$error = "";
$default_stock = 20; // Default stock amount to add to each product

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_all_stocks'])) {
    $stock_amount = isset($_POST['stock_amount']) ? (int)$_POST['stock_amount'] : $default_stock;
    
    if ($stock_amount < 0) {
        $error = "Stock amount cannot be negative.";
    } else {
        // If admin, update all products
        if ($_SESSION["role"] === 'admin') {
            $sql = "UPDATE products SET stock = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $stock_amount);
        } 
        // If vendor, only update their products
        else {
            $sql = "UPDATE products SET stock = ? WHERE vendor_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $stock_amount, $_SESSION['id']);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $rows_affected = mysqli_stmt_affected_rows($stmt);
            $message = "Success! Updated stock to {$stock_amount} for {$rows_affected} products.";
        } else {
            $error = "Error updating stocks: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Get current stock information
if ($_SESSION["role"] === 'admin') {
    $sql = "SELECT COUNT(*) as total_products, 
            SUM(CASE WHEN stock <= 0 OR stock IS NULL THEN 1 ELSE 0 END) as out_of_stock
            FROM products";
    $stmt = mysqli_prepare($conn, $sql);
} else {
    $sql = "SELECT COUNT(*) as total_products, 
            SUM(CASE WHEN stock <= 0 OR stock IS NULL THEN 1 ELSE 0 END) as out_of_stock
            FROM products 
            WHERE vendor_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stock_info = mysqli_fetch_assoc($result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update All Stocks - ArtSell</title>
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
        .stats-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
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
        .update-btn { 
            width: 100%;
            padding: 12px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: background 0.3s ease; 
            font-weight: 500; 
        }
        .update-btn:hover { 
            background: #218838; 
        }
        .warning-text {
            color: #dc3545;
            font-weight: 500;
            margin-top: 10px;
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
                    <?php if ($_SESSION['role'] === 'vendor'): ?>
                        <li><a href="add_product.php">Add Product</a></li>
                        <li><a href="vendor_products.php">My Products</a></li>
                        <li><a href="manage_orders.php">Manage Orders</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>Update All Product Stocks</h1>
        
        <div class="form-container">
            <div class="stock-form">
                <?php if(!empty($message)): ?>
                    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if(!empty($error)): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="stats-box">
                    <h3>Current Stock Status</h3>
                    <p>Total Products: <?php echo $stock_info['total_products']; ?></p>
                    <p>Products Out of Stock: <?php echo $stock_info['out_of_stock']; ?></p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="stock_amount">Set Stock Amount For All Products:</label>
                        <input type="number" id="stock_amount" name="stock_amount" min="0" value="<?php echo $default_stock; ?>" required>
                    </div>
                    
                    <p class="warning-text">Warning: This will update the stock for all <?php echo $_SESSION['role'] === 'admin' ? '' : 'your '; ?>products to the same value.</p>
                    
                    <button type="submit" name="update_all_stocks" class="update-btn">Update All Stocks</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 