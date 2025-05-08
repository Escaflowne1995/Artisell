<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is a vendor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'vendor') {
    header("location: login.php");
    exit;
}

// Handle stock update if form submitted
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    
    if ($stock < 0) {
        $error = "Stock cannot be negative";
    } else {
        $sql = "UPDATE products SET stock = ? WHERE id = ? AND vendor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $stock, $product_id, $_SESSION['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Stock updated successfully";
        } else {
            $error = "Error updating stock: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch vendor's products with stock information
$sql = "SELECT id, name, price, category, stock, 
        (SELECT SUM(oi.quantity) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = products.id AND o.status = 'Completed') as sold 
        FROM products WHERE vendor_id = ? ORDER BY stock ASC";
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
    <title>Inventory Management - ArtSell</title>
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
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .inventory-table th, 
        .inventory-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .inventory-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .inventory-table tr:hover {
            background: #f5f5f5;
        }
        .stock-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .update-btn {
            padding: 6px 12px;
            background: #3b5998;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-btn:hover {
            background: #2a4373;
        }
        .out-of-stock {
            color: #dc3545;
            font-weight: bold;
        }
        .low-stock {
            color: #ffc107;
            font-weight: bold;
        }
        .in-stock {
            color: #28a745;
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
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
            color: #3b5998;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
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
                    <li><a href="vendor_inventory.php">Inventory</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>Inventory Management</h1>
        
        <?php if(!empty($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="dashboard">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($products); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo array_sum(array_column($products, 'stock')); ?></div>
                <div class="stat-label">Total Items in Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count(array_filter($products, function($p) { return isset($p['stock']) && $p['stock'] <= 0; })); ?></div>
                <div class="stat-label">Out of Stock Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo array_sum(array_column(array_filter($products, function($p) { return isset($p['sold']); }), 'sold')); ?></div>
                <div class="stat-label">Total Items Sold</div>
            </div>
        </div>
        
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Current Stock</th>
                    <th>Items Sold</th>
                    <th>Update Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                            <td class="<?php 
                                if (!isset($product['stock']) || $product['stock'] <= 0) echo 'out-of-stock';
                                else if ($product['stock'] <= 5) echo 'low-stock';
                                else echo 'in-stock';
                            ?>">
                                <?php echo isset($product['stock']) ? $product['stock'] : 0; ?>
                            </td>
                            <td><?php echo isset($product['sold']) ? $product['sold'] : 0; ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="number" name="stock" min="0" value="<?php echo isset($product['stock']) ? $product['stock'] : 0; ?>" class="stock-input">
                                    <button type="submit" name="update_stock" class="update-btn">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">You haven't added any products yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 