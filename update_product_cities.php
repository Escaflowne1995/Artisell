<?php
// Start session and connect to database
session_start();
require 'db_connection.php';

// Check if user is admin 
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

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

// Get products with missing or invalid cities
$sql_missing = "SELECT id, name, city FROM products WHERE city IS NULL OR city = '' OR city NOT IN ('" . implode("','", $valid_cities) . "')";
$result_missing = mysqli_query($conn, $sql_missing);
$products_to_update = [];

while ($row = mysqli_fetch_assoc($result_missing)) {
    $products_to_update[] = $row;
}

// Process form submission to update cities
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cities'])) {
    $updated_count = 0;
    
    foreach ($_POST['city'] as $product_id => $city) {
        if (in_array($city, $valid_cities)) {
            $update_sql = "UPDATE products SET city = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "si", $city, $product_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $updated_count++;
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    if ($updated_count > 0) {
        $message = "<div class='alert alert-success'>Successfully updated $updated_count product(s).</div>";
    } else {
        $message = "<div class='alert alert-warning'>No products were updated.</div>";
    }
    
    // Refresh the list after updates
    $result_missing = mysqli_query($conn, $sql_missing);
    $products_to_update = [];
    
    while ($row = mysqli_fetch_assoc($result_missing)) {
        $products_to_update[] = $row;
    }
}

// Auto-assign random cities option
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_assign'])) {
    $auto_updated = 0;
    
    foreach ($products_to_update as $product) {
        // Randomly select a city from the valid cities
        $random_city = $valid_cities[array_rand($valid_cities)];
        
        $update_sql = "UPDATE products SET city = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $random_city, $product['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $auto_updated++;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if ($auto_updated > 0) {
        $message = "<div class='alert alert-success'>Auto-assigned cities to $auto_updated product(s).</div>";
    } else {
        $message = "<div class='alert alert-warning'>No products were updated.</div>";
    }
    
    // Refresh the list after updates
    $result_missing = mysqli_query($conn, $sql_missing);
    $products_to_update = [];
    
    while ($row = mysqli_fetch_assoc($result_missing)) {
        $products_to_update[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product Cities - ArtiSell Admin</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .admin-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        
        .product-table th, 
        .product-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--neutral-300);
        }
        
        .product-table th {
            background-color: var(--neutral-100);
            font-weight: 600;
        }
        
        .product-table tr:hover {
            background-color: var(--neutral-50);
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius-md);
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .btn-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Update Product Cities</h1>
            <a href="admin_dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>
        
        <?php echo $message; ?>
        
        <?php if (empty($products_to_update)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> All products have valid cities assigned!
            </div>
        <?php else: ?>
            <div class="btn-container">
                <form method="POST" action="">
                    <button type="submit" name="auto_assign" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Auto-Assign Random Cities
                    </button>
                </form>
            </div>
            
            <form method="POST" action="">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Current City</th>
                            <th>New City</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products_to_update as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>
                                    <?php 
                                    if (empty($product['city'])) {
                                        echo '<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> Missing</span>';
                                    } else {
                                        echo htmlspecialchars($product['city']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <select name="city[<?php echo $product['id']; ?>]" class="filter-control">
                                        <option value="">Select City</option>
                                        <?php foreach ($valid_cities as $city): ?>
                                            <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" name="update_cities" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Cities
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo">ArtiSell</a>
                    <p>Admin Area - Product City Management</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ArtiSell. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html> 