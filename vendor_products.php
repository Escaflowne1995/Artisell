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

// Get the correct vendor_id from database
$vendorId = null;
$sql = "SELECT id FROM vendors WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_bind_result($stmt, $vendorId);
        mysqli_stmt_fetch($stmt);
    } else {
        // Create a vendor record if it doesn't exist
        $insertVendorSql = "INSERT INTO vendors (user_id, vendor_name) VALUES (?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertVendorSql);
        if ($insertStmt) {
            // Use username as vendor_name initially
            $vendorName = $_SESSION['username'];
            mysqli_stmt_bind_param($insertStmt, "is", $_SESSION['id'], $vendorName);
            
            if (mysqli_stmt_execute($insertStmt)) {
                $vendorId = mysqli_insert_id($conn);
            } else {
                $error = "Error creating vendor record: " . mysqli_stmt_error($insertStmt);
            }
            mysqli_stmt_close($insertStmt);
        }
    }
    mysqli_stmt_close($stmt);
}

// If we couldn't get a vendor ID, show an error message
if ($vendorId === null) {
    $error = "Error: Could not determine your vendor ID. Please contact support.";
}

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
        
        mysqli_stmt_bind_param($stmt, "ii", $product_id, $vendorId);
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
                mysqli_stmt_bind_param($stmt, "ii", $product_id, $vendorId);
                
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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Count total products only if we have a valid vendor ID
$total_products = 0;
$total_pages = 1;
$products = [];

if ($vendorId !== null) {
    // Count total products
    $count_sql = "SELECT COUNT(*) as total FROM products WHERE vendor_id = ?";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "i", $vendorId);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_products = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total_products / $items_per_page);

    // Fetch products with pagination
    $sql = "SELECT * FROM products WHERE vendor_id = ? ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $vendorId, $items_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
}
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ArtiSell</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .add-product-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }
        
        .product-card {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .product-image {
            height: 200px;
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
            padding: 1.25rem;
        }
        
        .product-name {
            margin: 0 0 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .product-price {
            font-weight: 600;
            color: var(--primary);
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
            border-radius: var(--radius-md);
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
            background-color: var(--primary);
            color: white;
        }
        
        .edit-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .delete-btn {
            background-color: var(--danger);
            color: white;
        }
        
        .delete-btn:hover {
            background-color: var(--danger-dark);
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
            background-color: var(--primary);
            color: white;
        }
        
        .no-products {
            text-align: center;
            padding: 3rem 1.5rem;
            background-color: var(--neutral-100);
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
        }
        
        .no-products h3 {
            margin-top: 0;
            color: var(--neutral-800);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .no-products p {
            color: var(--neutral-600);
            margin-bottom: 1.5rem;
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
            padding: 2rem;
            width: 90%;
            max-width: 450px;
            border-radius: var(--radius-lg);
            position: relative;
            box-shadow: var(--shadow-lg);
        }
        
        .modal-title {
            margin-top: 0;
            color: var(--neutral-800);
            font-size: 1.5rem;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 1.5rem;
        }
        
        .btn-cancel {
            background-color: var(--neutral-500);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-cancel:hover {
            background-color: var(--neutral-600);
        }
        
        .btn-confirm {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-confirm:hover {
            background-color: var(--danger-dark);
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
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
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    
    <div class="container" style="padding: 2rem 0;">
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
            <a href="add_product.php" class="btn btn-primary add-product-btn">
                <i class="fas fa-plus-circle"></i> Add New Product
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
                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>">
                        </div>
                        <div class="product-details">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name'] ?? 'Unnamed Product'); ?></h3>
                            
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
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'] ?? 'this product')); ?>')">
                                    <i class="fas fa-trash-alt"></i> Delete
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
                            <i class="fas fa-chevron-left"></i>
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
                            <i class="fas fa-chevron-right"></i>
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
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo">ArtiSell</a>
                    <p>Manage your artisan products in our marketplace.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ArtiSell. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
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