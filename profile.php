<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Fetch user details
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'] ?? '';
$profile_picture = $_SESSION['profile_picture'] ?? 'images/default-profile.jpg';
$role = $_SESSION['role'] ?? 'customer';

// Fetch order history for customers
$orders = [];
if ($role === 'customer') {
    $sql = "SELECT o.*, COUNT(oi.id) as item_count, SUM(oi.quantity) as total_items 
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.user_id = ? 
            GROUP BY o.id 
            ORDER BY o.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
}

// Fetch vendor products if user is a vendor
$products = [];
if ($role === 'vendor') {
    // First get the vendor_id from vendors table
    $vendorId = null;
    $vendor_sql = "SELECT id FROM vendors WHERE user_id = ?";
    $vendor_stmt = mysqli_prepare($conn, $vendor_sql);
    mysqli_stmt_bind_param($vendor_stmt, "i", $user_id);
    mysqli_stmt_execute($vendor_stmt);
    $vendor_result = mysqli_stmt_get_result($vendor_stmt);
    
    if ($row = mysqli_fetch_assoc($vendor_result)) {
        $vendorId = $row['id'];
        
        // Now fetch the products using the correct vendor_id
        $sql = "SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $vendorId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
}

// Handle delete product request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product'])) {
    // Generate CSRF token if it doesn't exist
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Validate product_id
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        $error = "Invalid product ID";
    } else {
        $product_id = (int)$_POST['product_id'];
        
        // Get the vendor_id
        $vendorId = null;
        $vendor_sql = "SELECT id FROM vendors WHERE user_id = ?";
        $vendor_stmt = mysqli_prepare($conn, $vendor_sql);
        mysqli_stmt_bind_param($vendor_stmt, "i", $user_id);
        mysqli_stmt_execute($vendor_stmt);
        $vendor_result = mysqli_stmt_get_result($vendor_stmt);
        
        if ($row = mysqli_fetch_assoc($vendor_result)) {
            $vendorId = $row['id'];
            
            // Fetch product to ensure it belongs to the vendor and get image path
            $sql = "SELECT * FROM products WHERE id = ? AND vendor_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            
            mysqli_stmt_bind_param($stmt, "ii", $product_id, $vendorId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && $product = mysqli_fetch_assoc($result)) {
                // Delete the image file if it exists
                if (!empty($product['image']) && file_exists($product['image'])) {
                    unlink($product['image']);
                }
                
                // Delete from database
                $sql = "DELETE FROM products WHERE id = ? AND vendor_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $product_id, $vendorId);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Product deleted successfully";
                    
                    // Refresh the products list after deletion
                    $products = [];
                    $sql = "SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $vendorId);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        $products[] = $row;
                    }
                } else {
                    $error_message = "Error deleting product: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Product not found or you don't have permission to delete it";
            }
        } else {
            $error_message = "Vendor record not found";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ArtiSell</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .profile-container {
            padding: 30px 0;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            background: var(--neutral-100);
            padding: 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        .profile-pic-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 25px;
        }
        .profile-info h1 {
            font-size: var(--font-size-2xl);
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--neutral-900);
        }
        .profile-info p {
            color: var(--neutral-700);
            margin-bottom: 15px;
        }
        .profile-role {
            display: inline-block;
            padding: 4px 10px;
            background: var(--neutral-200);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: var(--neutral-700);
            margin-bottom: 15px;
        }
        .profile-role.vendor {
            background: var(--blue-100);
            color: var(--blue);
        }
        .profile-role.admin {
            background: var(--green-100);
            color: var(--primary);
        }
        .edit-profile-btn {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .edit-profile-btn:hover {
            background: var(--primary-dark);
            text-decoration: none;
        }
        .profile-tabs {
            display: flex;
            margin-bottom: 20px;
            background: var(--neutral-100);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        .profile-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .profile-tab.active {
            border-bottom: 2px solid var(--primary);
            font-weight: 600;
            color: var(--primary);
        }
        .profile-tab:hover {
            background: var(--neutral-200);
        }
        .tab-content {
            display: none;
            background: var(--neutral-100);
            padding: 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }
        .tab-content.active {
            display: block;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--neutral-300);
            color: var(--neutral-800);
        }
        .account-info {
            margin-bottom: 20px;
        }
        .account-info p {
            display: flex;
            margin-bottom: 12px;
            color: var(--neutral-700);
        }
        .account-info strong {
            width: 150px;
            color: var(--neutral-800);
            font-weight: 500;
        }
        .account-actions {
            margin-top: 24px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        .orders-table th, .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--neutral-300);
        }
        .orders-table th {
            font-weight: 600;
            color: var(--neutral-700);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-pending {
            background: #fff8e6;
            color: #ffa500;
        }
        .status-shipped {
            background: var(--blue-100);
            color: var(--blue);
        }
        .status-delivered {
            background: var(--green-100);
            color: var(--primary);
        }
        .status-cancelled {
            background: #ffe6e6;
            color: #ff4d4d;
        }
        
        /* Media Queries */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .profile-pic-large {
                margin-right: 0;
                margin-bottom: 20px;
            }
            .profile-tabs {
                flex-wrap: wrap;
            }
            .profile-tab {
                flex: 1 0 100%;
                padding: 12px;
            }
            .account-info p {
                flex-direction: column;
            }
            .account-info strong {
                width: 100%;
                margin-bottom: 5px;
            }
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .product-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        /* Modal styles */
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
            max-width: 450px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }

        .modal-title {
            margin-top: 0;
            color: var(--neutral-800);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-delete {
            background-color: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
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
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="index.php" class="logo"><span class="text-green">Arti</span><span class="text-blue">Sell</span></a>
            
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="shop.php" class="nav-link">Shop</a></li>
                    <li><a href="cities.php" class="nav-link">Cities</a></li>
                    <li><a href="about.php" class="nav-link">About</a></li>
                </ul>
            </nav>
            
            <div class="header-right">
                <a href="cart.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    $cart_count = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
                        }
                    }
                    if ($cart_count > 0) {
                        echo "<span>($cart_count)</span>";
                    }
                    ?>
                </a>
                
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <div class="profile-dropdown">
                    <a href="#" class="profile-link">
                        <?php echo htmlspecialchars($_SESSION["username"]); ?> <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <?php if ($_SESSION["role"] == "vendor"): ?>
                        <a href="vendor_products.php" class="dropdown-item">
                            <i class="fas fa-box"></i> My Products
                        </a>
                        <a href="vendor_inventory.php" class="dropdown-item">
                            <i class="fas fa-clipboard-list"></i> Inventory
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="login.php" class="btn btn-primary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-pic-large">
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($username); ?></h1>
                <p><?php echo htmlspecialchars($email); ?></p>
                <div class="profile-role <?php echo strtolower($role); ?>"><?php echo ucfirst($role); ?></div>
                <a href="settings.php" class="edit-profile-btn">Edit Profile</a>
            </div>
        </div>
        
        <!-- Profile Tabs -->
        <div class="profile-tabs">
            <div class="profile-tab active" data-tab="account">Account</div>
            <?php if ($role === 'customer'): ?>
                <div class="profile-tab" data-tab="orders">Orders</div>
            <?php elseif ($role === 'vendor'): ?>
                <div class="profile-tab" data-tab="products">My Products</div>
            <?php endif; ?>
        </div>
        
        <!-- Account Tab -->
        <div id="account-tab" class="tab-content active">
            <h2 class="section-title">Account Information</h2>
            <div class="account-info">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Account Type:</strong> <?php echo ucfirst($role); ?></p>
                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($_SESSION['created_at'] ?? 'now')); ?></p>
            </div>
            <div class="account-actions">
                <a href="settings.php" class="btn btn-primary">Update Information</a>
                <a href="settings.php#password" class="btn btn-secondary">Change Password</a>
            </div>
        </div>
        
        <!-- Orders Tab (for customers) -->
        <?php if ($role === 'customer'): ?>
            <div id="orders-tab" class="tab-content">
                <h2 class="section-title">Order History</h2>
                <?php if (!empty($orders)): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo $order['total_items']; ?> item(s)</td>
                                    <td>₱<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center p-5">
                        <p class="mb-3">You haven't placed any orders yet.</p>
                        <a href="shop.php" class="btn btn-primary">Start shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Products Tab (for vendors) -->
        <?php if ($role === 'vendor'): ?>
            <div id="products-tab" class="tab-content">
                <h2 class="section-title">My Products</h2>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($products)): ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="card">
                                <div class="card-img">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="card-price">₱<?php echo number_format($product['price'], 2); ?></p>
                                    <div class="product-actions">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="shop.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center p-5">
                        <p class="mb-3">You haven't added any products yet.</p>
                        <a href="add_product.php" class="btn btn-primary">Add your first product</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Confirm Deletion</h3>
            <p id="deleteConfirmText">Are you sure you want to delete this product?</p>
            
            <form id="deleteForm" method="POST">
                <input type="hidden" name="product_id" id="deleteProductId">
                <input type="hidden" name="delete_product" value="1">
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-delete">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab Functionality
        const tabs = document.querySelectorAll('.profile-tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab
                tab.classList.add('active');
                
                // Hide all tab contents
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Show the corresponding tab content
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
        
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