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
    $sql = "SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ArtSell</title>
    <link rel="stylesheet" href="css/styles.css">
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
        .profile-dropdown { 
            position: relative; 
        }
        .profile-dropdown:hover .dropdown-content { 
            display: block; 
        }
        .dropdown-content { 
            position: absolute; 
            right: 0; 
            background: #fff; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            border-radius: 4px; 
            min-width: 120px; 
        }
        .dropdown-content a { 
            display: block; 
            padding: 10px 15px; 
            color: #333; 
            text-decoration: none; 
        }
        .dropdown-content a:hover { 
            background: #f5f5f5; 
        }
        .profile-container {
            padding: 30px 0;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-pic-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 25px;
        }
        .profile-info h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        .profile-info p {
            color: #666;
            margin-bottom: 15px;
        }
        .profile-role {
            display: inline-block;
            padding: 4px 10px;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #555;
            margin-bottom: 15px;
        }
        .profile-role.vendor {
            background: #e6f7ff;
            color: #0080ff;
        }
        .profile-role.admin {
            background: #f6e6ff;
            color: #9933cc;
        }
        .edit-profile-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #ff6b00;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .edit-profile-btn:hover {
            background: #e65c00;
            text-decoration: none;
        }
        .profile-tabs {
            display: flex;
            margin-bottom: 20px;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            border-bottom: 2px solid #ff6b00;
            font-weight: 600;
            color: #ff6b00;
        }
        .profile-tab:hover {
            background: #f9f9f9;
        }
        .tab-content {
            display: none;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            border-bottom: 1px solid #eee;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        .orders-table th, .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .orders-table th {
            font-weight: 600;
            color: #555;
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
            background: #e6f7ff;
            color: #0080ff;
        }
        .status-delivered {
            background: #e6ffe6;
            color: #00cc00;
        }
        .no-items {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            height: 150px;
            overflow: hidden;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-details {
            padding: 15px;
        }
        .product-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .product-price {
            color: #ff6b00;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .product-actions {
            display: flex;
            gap: 10px;
        }
        .product-actions a {
            flex: 1;
            text-align: center;
            padding: 6px 0;
            font-size: 12px;
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
        .edit-btn {
            background: #3b5998;
        }
        .view-btn {
            background: #33cc33;
        }
        .profile-link {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .profile-pic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        .account-info {
            margin-bottom: 20px;
        }
        .account-info p {
            display: flex;
            margin-bottom: 10px;
            color: #666;
        }
        .account-info strong {
            width: 150px;
            color: #333;
        }
        .account-actions {
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin-right: 10px;
            transition: background 0.3s ease;
        }
        .btn-primary {
            background: #ff6b00;
            color: white;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn:hover {
            opacity: 0.9;
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
                    <?php if ($role === 'vendor'): ?>
                        <li><a href="add_product.php">Add Product</a></li>
                        <li><a href="vendor_products.php">My Products</a></li>
                    <?php else: ?>
                        <li><a href="cart.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                        </svg>(<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a></li>
                    <?php endif; ?>
                    <li class="profile-dropdown">
                        <a href="profile.php" class="profile-link">
                            <?php echo htmlspecialchars($username); ?>
                            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-pic">
                        </a>
                        <div class="dropdown-content">
                            <a href="settings.php">Settings</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>
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
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-items">
                        <p>You haven't placed any orders yet.</p>
                        <p><a href="shop.php">Start shopping</a></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Products Tab (for vendors) -->
        <?php if ($role === 'vendor'): ?>
            <div id="products-tab" class="tab-content">
                <h2 class="section-title">My Products</h2>
                <?php if (!empty($products)): ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <div class="product-details">
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                                    <div class="product-actions">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="edit-btn">Edit</a>
                                        <a href="shop.php?id=<?php echo $product['id']; ?>" class="view-btn">View</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-items">
                        <p>You haven't added any products yet.</p>
                        <p><a href="add_product.php">Add your first product</a></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

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
    </script>
</body>
</html>