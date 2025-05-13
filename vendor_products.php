<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is a vendor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'vendor') {
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
        $sql = "SELECT * FROM products WHERE id = ? AND vendor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        // Default to user_id if vendor_id doesn't exist
        $vendor_id = isset($_SESSION['vendor_id']) ? $_SESSION['vendor_id'] : $_SESSION['id'];
        
        mysqli_stmt_bind_param($stmt, "ii", $product_id, $vendor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result) {
            $product = mysqli_fetch_assoc($result);

            if ($product) {
                // Delete the image file if it exists
                if (!empty($product['image']) && file_exists($product['image'])) {
                    unlink($product['image']);
                }

                // Delete from database
                $sql = "DELETE FROM products WHERE id = ? AND vendor_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $product_id, $vendor_id);
                
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
        } else {
            $error = "Error fetching product: " . mysqli_error($conn);
        }
    }
}

// Fetch vendor's products
$vendor_id = isset($_SESSION['vendor_id']) ? $_SESSION['vendor_id'] : $_SESSION['id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Count total products
$count_sql = "SELECT COUNT(*) as total FROM products WHERE vendor_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $vendor_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $items_per_page);

// Fetch products with pagination
$sql = "SELECT * FROM products WHERE vendor_id = ? ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $vendor_id, $items_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ArtiSell</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .add-product-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .add-product-btn:hover {
            background-color: #3e8e41;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }
        
        .product-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 180px;
            position: relative;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-draft {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .product-details {
            padding: 15px;
        }
        
        .product-name {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .product-price {
            font-weight: 600;
            color: #ff6b00;
        }
        
        .product-stock {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stock-badge {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .in-stock {
            background-color: #28a745;
        }
        
        .low-stock {
            background-color: #ffc107;
        }
        
        .out-of-stock {
            background-color: #dc3545;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .edit-btn, .delete-btn {
            padding: 8px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            transition: background-color 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .edit-btn {
            background-color: #007bff;
            color: white;
        }
        
        .edit-btn:hover {
            background-color: #0069d9;
        }
        
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c82333;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a, .pagination .current {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .pagination a {
            background-color: #f8f9fa;
            color: #333;
        }
        
        .pagination a:hover {
            background-color: #e9ecef;
        }
        
        .pagination .current {
            background-color: #ff6b00;
            color: white;
        }
        
        .no-products {
            text-align: center;
            padding: 50px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .no-products h3 {
            margin-top: 0;
            color: #333;
        }
        
        .no-products p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .dashboard-header h1 {
                margin-bottom: 0;
            }
        }
        
        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Delete confirmation modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            width: 90%;
            max-width: 400px;
            border-radius: 8px;
            position: relative;
        }
        
        .modal-title {
            margin-top: 0;
            color: #333;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-confirm {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* Header styles from index.php */
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
        
        .header-right a {
            font-weight: 700;
            text-decoration: none;
            color: #333;
        }
        
        .nav-link {
            font-weight: 700;
        }
        
        .cart-icon {
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            vertical-align: middle;
            margin-right: 4px;
        }
        
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .profile-dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-item {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            transition: background-color 0.3s;
        }
        
        .dropdown-item:hover {
            background-color: #f1f1f1;
        }
        
        /* New header styles based on the image */
        .header {
            border-bottom: 1px solid #e0e0e0;
            padding: 12px 0;
            background-color: white;
        }
        
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
            color: #333;
        }
        
        .logo span {
            color: #ff6b00;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .cart-count {
            font-weight: 500;
            color: #333;
            display: inline-flex;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-role {
            font-weight: 500;
            color: #555;
        }
        
        /* Navigation links */
        .nav-link {
            color: #333;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: #ff6b00;
        }
        
        .shop-link {
            margin-right: 5px;
        }
        
        .cart-link {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="index.php" class="logo">Arti<span>Sell</span></a>
            
            <div class="header-right">
                <a href="shop.php" class="nav-link shop-link">Shop</a>
                
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <a href="cart.php" class="nav-link cart-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                        </svg>
                        <span class="cart-count">(<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : '0'; ?>)</span>
                    </a>
                    <div class="profile-dropdown">
                        <div class="user-info">
                            <span class="user-role">vendor</span>
                            <?php if (!empty($_SESSION['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" class="profile-pic">
                            <?php else: ?>
                                <img src="images/default-profile.jpg" alt="Profile" class="profile-pic">
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-content">
                            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'vendor'): ?>
                                <a href="vendor_products.php" class="dropdown-item">My Products</a>
                            <?php endif; ?>
                            <a href="settings.php" class="dropdown-item">Settings</a>
                            <a href="logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <div class="container" style="padding: 30px 20px;">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-header">
            <h1>My Products</h1>
            <a href="add_product.php" class="add-product-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 0a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2H9v6a1 1 0 1 1-2 0V9H1a1 1 0 0 1 0-2h6V1a1 1 0 0 1 1-1z"/>
                </svg>
                Add New Product
            </a>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="no-products">
                <h3>No products found</h3>
                <p>Start adding your products to showcase them to customers.</p>
                <a href="add_product.php" class="btn btn-primary">Add Your First Product</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php 
                            // Display product status badge
                            $statusClass = 'status-active';
                            $statusText = 'Active';
                            
                            if (isset($product['stock']) && $product['stock'] <= 0) {
                                $statusClass = 'status-out-of-stock';
                                $statusText = 'Out of Stock';
                            } elseif (isset($product['status']) && $product['status'] === 'draft') {
                                $statusClass = 'status-draft';
                                $statusText = 'Draft';
                            }
                            ?>
                            <div class="product-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                            
                            <?php 
                            // Display product image or default
                            $image_path = isset($product['image']) && !empty($product['image']) ? $product['image'] : "image/coconut-bowl-palm.jpg";
                            ?>
                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($product['product_name'] ?? 'Product'); ?>">
                        </div>
                        <div class="product-details">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['product_name'] ?? 'Unnamed Product'); ?></h3>
                            
                            <div class="product-meta">
                                <div class="product-price">₱ <?php echo number_format((float)($product['price'] ?? 0), 2); ?></div>
                                <div class="product-stock">
                                    <?php 
                                    // Stock indicator
                                    $stock = isset($product['stock']) ? (int)$product['stock'] : 0;
                                    $stockClass = 'in-stock';
                                    $stockText = 'In Stock';
                                    
                                    if ($stock <= 0) {
                                        $stockClass = 'out-of-stock';
                                        $stockText = 'Out of Stock';
                                    } elseif ($stock <= 5) {
                                        $stockClass = 'low-stock';
                                        $stockText = 'Low Stock';
                                    }
                                    ?>
                                    <span class="stock-badge <?php echo $stockClass; ?>"></span>
                                    <span><?php echo htmlspecialchars($stockText); ?></span>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="edit-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                    </svg>
                                    Edit
                                </a>
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['product_name'] ?? 'this product')); ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" aria-label="Previous page">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    // Always show the first page
                    if ($start_page > 1) {
                        echo '<a href="?page=1">1</a>';
                        if ($start_page > 2) {
                            echo '<span>...</span>';
                        }
                    }
                    
                    // Show page links
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i === $page) {
                            echo '<span class="current">' . $i . '</span>';
                        } else {
                            echo '<a href="?page=' . $i . '">' . $i . '</a>';
                        }
                    }
                    
                    // Always show the last page
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span>...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" aria-label="Next page">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Confirm Deletion</h3>
            <p id="deleteConfirmText">Are you sure you want to delete this product?</p>
            
            <form id="deleteForm" method="POST">
                <input type="hidden" name="product_id" id="deleteProductId">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="delete_product" value="1">
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-confirm">Delete</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'components/footer.php'; ?>
    
    <script>
        // Delete confirmation modal
        const modal = document.getElementById('deleteModal');
        const deleteForm = document.getElementById('deleteForm');
        const deleteProductId = document.getElementById('deleteProductId');
        const deleteConfirmText = document.getElementById('deleteConfirmText');
        
        function confirmDelete(productId, productName) {
            deleteProductId.value = productId;
            deleteConfirmText.textContent = `Are you sure you want to delete "${productName}"?`;
            modal.style.display = 'block';
        }
        
        function closeModal() {
            modal.style.display = 'none';
        }
        
        // Close modal when clicking outside the content
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>