<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is admin or vendor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
    ($_SESSION["role"] !== 'admin' && $_SESSION["role"] !== 'vendor')) {
    header("location: login.php");
    exit;
}

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security verification failed. Please try again.";
    } 
    // Validate order_id and new_status
    else if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id']) || !isset($_POST['new_status'])) {
        $error = "Invalid order information.";
    } else {
        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['new_status'];
        $valid_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Returned'];
        
        if (!in_array($new_status, $valid_statuses)) {
            $error = "Invalid status value.";
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Get current status
                $status_sql = "SELECT status FROM orders WHERE id = ?";
                $status_stmt = mysqli_prepare($conn, $status_sql);
                mysqli_stmt_bind_param($status_stmt, "i", $order_id);
                mysqli_stmt_execute($status_stmt);
                $status_result = mysqli_stmt_get_result($status_stmt);
                $current_status = mysqli_fetch_assoc($status_result)['status'];
                
                // Update order status
                $sql = "UPDATE orders SET status = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Handle stock updates based on status change
                    if (($current_status !== 'Cancelled' && $new_status === 'Cancelled') || 
                        ($current_status !== 'Returned' && $new_status === 'Returned')) {
                        // If order is cancelled or returned, restore stock
                        // Get order items
                        $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
                        $items_stmt = mysqli_prepare($conn, $items_sql);
                        mysqli_stmt_bind_param($items_stmt, "i", $order_id);
                        mysqli_stmt_execute($items_stmt);
                        $items_result = mysqli_stmt_get_result($items_stmt);
                        
                        // Increase stock for each product
                        while ($item = mysqli_fetch_assoc($items_result)) {
                            $update_stock_sql = "UPDATE products SET stock = stock + ? WHERE id = ?";
                            $update_stmt = mysqli_prepare($conn, $update_stock_sql);
                            mysqli_stmt_bind_param($update_stmt, "ii", $item['quantity'], $item['product_id']);
                            mysqli_stmt_execute($update_stmt);
                        }
                    } else if (($current_status === 'Cancelled' || $current_status === 'Returned') && 
                               !in_array($new_status, ['Cancelled', 'Returned'])) {
                        // If reactivating a cancelled/returned order, decrease stock again
                        // Get order items
                        $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
                        $items_stmt = mysqli_prepare($conn, $items_sql);
                        mysqli_stmt_bind_param($items_stmt, "i", $order_id);
                        mysqli_stmt_execute($items_stmt);
                        $items_result = mysqli_stmt_get_result($items_stmt);
                        
                        // Check stock before decreasing
                        $stock_sufficient = true;
                        $items = [];
                        
                        while ($item = mysqli_fetch_assoc($items_result)) {
                            $items[] = $item;
                            // Check if there's enough stock
                            $check_stock_sql = "SELECT stock FROM products WHERE id = ?";
                            $check_stmt = mysqli_prepare($conn, $check_stock_sql);
                            mysqli_stmt_bind_param($check_stmt, "i", $item['product_id']);
                            mysqli_stmt_execute($check_stmt);
                            $stock_result = mysqli_stmt_get_result($check_stmt);
                            $stock_row = mysqli_fetch_assoc($stock_result);
                            
                            if (!$stock_row || $stock_row['stock'] < $item['quantity']) {
                                $stock_sufficient = false;
                                break;
                            }
                        }
                        
                        if ($stock_sufficient) {
                            // Decrease stock for each product
                            foreach ($items as $item) {
                                $update_stock_sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
                                $update_stmt = mysqli_prepare($conn, $update_stock_sql);
                                mysqli_stmt_bind_param($update_stmt, "ii", $item['quantity'], $item['product_id']);
                                mysqli_stmt_execute($update_stmt);
                            }
                        } else {
                            // Not enough stock, rollback and show error
                            mysqli_rollback($conn);
                            $error = "Cannot reactivate order: insufficient stock for one or more products.";
                            throw new Exception($error);
                        }
                    }
                    
                    $message = "Order status updated to " . htmlspecialchars($new_status);
                    // Generate new CSRF token after successful action
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    // Commit transaction
                    mysqli_commit($conn);
                } else {
                    $error = "Error updating order status: " . mysqli_error($conn);
                    mysqli_rollback($conn);
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                if (empty($error)) {
                    $error = "An error occurred: " . $e->getMessage();
                }
            }
        }
    }
}

// Get orders - if vendor, only show their orders
$sql = "SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id ";

// If vendor, add filter for products from this vendor
if ($_SESSION["role"] === 'vendor') {
    $sql .= "WHERE EXISTS (
                SELECT 1 FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = o.id AND p.vendor_id = ?
            ) ";
    $sql .= "ORDER BY o.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
} else {
    $sql .= "ORDER BY o.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}
mysqli_stmt_close($stmt);

// For vendors, get details about which of their products were purchased in each order
$order_products = [];
if ($_SESSION["role"] === 'vendor') {
    foreach ($orders as $order) {
        $products_sql = "SELECT oi.*, p.name as product_name, p.image
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ? AND p.vendor_id = ?";
        $products_stmt = mysqli_prepare($conn, $products_sql);
        mysqli_stmt_bind_param($products_stmt, "ii", $order['id'], $_SESSION['id']);
        mysqli_stmt_execute($products_stmt);
        $products_result = mysqli_stmt_get_result($products_stmt);
        
        $order_products[$order['id']] = [];
        while ($product = mysqli_fetch_assoc($products_result)) {
            $order_products[$order['id']][] = $product;
        }
        mysqli_stmt_close($products_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - ArtiSell</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .orders-container {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: var(--space-5);
            margin-bottom: var(--space-6);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: var(--space-3);
            text-align: left;
            border-bottom: 1px solid var(--neutral-200);
        }
        
        th {
            font-weight: 600;
            color: var(--neutral-700);
        }
        
        tr:hover {
            background-color: var(--neutral-100);
        }
        
        .status {
            display: inline-block;
            padding: var(--space-1) var(--space-2);
            border-radius: 20px;
            font-size: var(--font-size-sm);
            font-weight: 600;
        }
        
        .status-Pending { background: #fff4de; color: #ffa940; }
        .status-Processing { background: #e6f7ff; color: #1890ff; }
        .status-Shipped { background: #f6ffed; color: #52c41a; }
        .status-Delivered { background: #d9f7be; color: #389e0d; }
        .status-Cancelled { background: #fff1f0; color: #ff4d4f; }
        .status-Returned { background: #f9f0ff; color: #722ed1; }
        
        .action-buttons {
            display: flex;
            gap: var(--space-2);
        }
        
        .action-btn {
            border: none;
            border-radius: var(--radius-md);
            padding: var(--space-2) var(--space-3);
            cursor: pointer;
            font-weight: 500;
            color: white;
        }
        
        .view-btn { 
            background: var(--primary-600);
        }
        
        .view-btn:hover { 
            background: var(--primary-700);
        }
        
        .message {
            padding: var(--space-3);
            margin-bottom: var(--space-5);
            border-radius: var(--radius-md);
        }
        
        .success {
            background-color: var(--success-100);
            color: var(--success-700);
            border: 1px solid var(--success-200);
        }
        
        .error {
            background-color: var(--danger-100);
            color: var(--danger-700);
            border: 1px solid var(--danger-200);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 10% auto;
            padding: var(--space-5);
            border-radius: var(--radius-lg);
            width: 400px;
            box-shadow: var(--shadow-lg);
        }
        
        .close-btn {
            position: absolute;
            right: var(--space-4);
            top: var(--space-3);
            font-size: var(--font-size-xl);
            font-weight: bold;
            cursor: pointer;
        }
        
        .status-select {
            width: 100%;
            padding: var(--space-2);
            margin: var(--space-4) 0;
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-md);
        }
        
        .update-btn {
            width: 100%;
            padding: var(--space-3);
            background: var(--primary-600);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
        }
        
        .update-btn:hover {
            background: var(--primary-700);
        }
        
        .page-title {
            font-size: var(--font-size-2xl);
            margin: var(--space-6) 0 var(--space-4) 0;
            color: var(--neutral-800);
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                gap: var(--space-1);
            }
            
            .action-btn {
                padding: var(--space-1) var(--space-2);
                font-size: var(--font-size-sm);
            }
        }
        
        /* Product display in order table */
        .product-mini {
            display: flex;
            align-items: center;
            margin-bottom: var(--space-2);
        }
        
        .product-thumbnail {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            margin-right: var(--space-2);
        }
        
        .quantity {
            color: var(--neutral-600);
            font-weight: 500;
            margin-left: var(--space-1);
        }
        
        .more-items {
            font-size: var(--font-size-sm);
            color: var(--primary-600);
            font-weight: 500;
            cursor: pointer;
        }
        
        /* Vendor specific styles */
        .vendor-order-info {
            margin-top: var(--space-4);
            background: var(--neutral-100);
            border-radius: var(--radius-md);
            padding: var(--space-3);
        }
        
        .vendor-order-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            margin-bottom: var(--space-3);
            color: var(--primary-700);
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="container">
        <h1 class="page-title">Manage Orders</h1>
        
        <?php if(!empty($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if($_SESSION["role"] === 'vendor'): ?>
        <div class="vendor-order-info">
            <h2 class="vendor-order-title">Your Product Orders</h2>
            <p>Below is a list of all orders that include your products. You can see customer details and which of your products were purchased. Click "View Details" to see the complete order information.</p>
        </div>
        <?php endif; ?>
        
        <div class="orders-container">
            <?php if (!empty($orders)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <?php if($_SESSION["role"] === 'vendor'): ?>
                            <th>Contact</th>
                            <th>Products Ordered</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td>
                                    <?php if($_SESSION["role"] === 'vendor'): ?>
                                        <strong><?php echo htmlspecialchars($order['username']); ?></strong>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($order['username']); ?>
                                    <?php endif; ?>
                                </td>
                                <?php if($_SESSION["role"] === 'vendor'): ?>
                                <td>
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['email']); ?>
                                </td>
                                <td>
                                    <?php if(!empty($order_products[$order['id']])): ?>
                                        <?php foreach ($order_products[$order['id']] as $index => $product): ?>
                                            <?php if($index < 2): ?>
                                                <div class="product-mini">
                                                    <?php if(!empty($product['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-thumbnail">
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($product['product_name']); ?> 
                                                    <span class="quantity">×<?php echo $product['quantity']; ?></span>
                                                </div>
                                            <?php elseif($index == 2): ?>
                                                <div class="more-items">+<?php echo (count($order_products[$order['id']]) - 2); ?> more</div>
                                                <?php break; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <em>No products found</em>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($order['created_at']))); ?></td>
                                <td>₱<?php echo number_format($order['total'], 2); ?></td>
                                <td><span class="status status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                <td class="action-buttons">
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="action-btn view-btn">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <button class="action-btn view-btn" onclick="openStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                        <i class="fas fa-edit"></i> Update Status
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No orders found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Update Order Status</h2>
            <form method="POST" id="updateStatusForm">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <select name="new_status" id="statusSelect" class="status-select">
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Shipped">Shipped</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                    <option value="Returned">Returned</option>
                </select>
                
                <button type="submit" name="update_status" class="update-btn">Update Status</button>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo"><span class="text-green">Arti</span><span class="text-blue">Sell</span></a>
                    <p>Connecting artisans with customers who appreciate authentic local crafts and delicacies.</p>
                    <div class="social-links">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="shop.php">Products</a></li>
                        <li><a href="cities.php">Cities</a></li>
                        <li><a href="about.php">About Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <ul class="footer-links">
                        <li><a href="#">Shipping & Returns</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Main Street, Cebu City, Philippines</li>
                        <li><i class="fas fa-phone"></i> +63 (32) 123-4567</li>
                        <li><i class="fas fa-envelope"></i> info@artisell.com</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> ArtiSell. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Modal functionality
        const modal = document.getElementById('statusModal');
        const orderIdInput = document.getElementById('modalOrderId');
        const statusSelect = document.getElementById('statusSelect');
        
        function openStatusModal(orderId, currentStatus) {
            orderIdInput.value = orderId;
            statusSelect.value = currentStatus;
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
        };
    </script>
</body>
</html> 