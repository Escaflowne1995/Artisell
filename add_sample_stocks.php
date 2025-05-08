<?php
// This is a one-time script to add sample stock values to all products
require 'db_connection.php';

$message = "";
$error = "";
$updated_count = 0;

// Only process if form submitted or direct execution requested
if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['auto_run'])) {
    // Sample stock values - will be randomly assigned to products
    $stock_values = [15, 20, 25, 30, 35, 40, 45, 50];
    
    // Start transaction for safety
    mysqli_begin_transaction($conn);
    
    try {
        // Get all products
        $query = "SELECT id FROM products";
        $result = mysqli_query($conn, $query);
        
        // Update each product with a random stock value
        while ($row = mysqli_fetch_assoc($result)) {
            $product_id = $row['id'];
            $random_stock = $stock_values[array_rand($stock_values)];
            
            $update_query = "UPDATE products SET stock = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ii", $random_stock, $product_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $updated_count++;
            }
            
            mysqli_stmt_close($stmt);
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        $message = "Successfully added sample stock values to {$updated_count} products. Products now have random stock values between 15 and 50 units.";
        
    } catch (Exception $e) {
        // Rollback the transaction in case of error
        mysqli_rollback($conn);
        $error = "An error occurred: " . $e->getMessage();
    }
}

// Get current stock information
$sql = "SELECT COUNT(*) as total_products, 
        SUM(CASE WHEN stock <= 0 OR stock IS NULL THEN 1 ELSE 0 END) as out_of_stock,
        AVG(stock) as avg_stock
        FROM products";
$result = mysqli_query($conn, $sql);
$stock_info = mysqli_fetch_assoc($result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sample Stocks - ArtSell</title>
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
        .content-box { 
            background: #fff; 
            padding: 20px; 
            border-radius: 6px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            margin-bottom: 30px;
            max-width: 600px;
            margin: 20px auto;
        }
        .stats-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .button-row {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 500;
        }
        .btn-primary {
            background: #28a745;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .message { 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 20px;
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
                    <li><a href="vendor_products.php">My Products</a></li>
                    <li><a href="update_all_stocks.php">Bulk Update Stocks</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="content-box">
            <h1>Add Sample Stock Values</h1>
            
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
                <p>Average Stock: <?php echo round($stock_info['avg_stock'], 1); ?></p>
            </div>
            
            <?php if($updated_count == 0): ?>
                <p>This script will assign random stock values (between 15 and 50 units) to all products in the database.</p>
                <p>Click the button below to add sample stock values to all products.</p>
                
                <form method="POST">
                    <button type="submit" name="add_samples" class="btn btn-primary">Add Sample Stock Values</button>
                </form>
            <?php else: ?>
                <div class="button-row">
                    <a href="shop.php" class="btn btn-primary">Go to Shop</a>
                    <a href="vendor_products.php" class="btn btn-secondary">View Products</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 