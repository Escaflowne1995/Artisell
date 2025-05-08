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
$sql = "SELECT o.*, u.username 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - ArtSell</title>
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
        .orders-container {
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            font-weight: 600;
            color: #555;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
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
            gap: 10px;
        }
        .action-btn {
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 500;
            color: white;
        }
        .view-btn { background: #3b5998; }
        .view-btn:hover { background: #2a4373; }
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
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
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
            padding: 20px;
            border-radius: 5px;
            width: 400px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .close-btn {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }
        .status-select {
            width: 100%;
            padding: 8px;
            margin: 15px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .update-btn {
            width: 100%;
            padding: 10px;
            background: #ff6b00;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-btn:hover {
            background: #e65c00;
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
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>Manage Orders</h1>
        
        <?php if(!empty($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="orders-container">
            <?php if (!empty($orders)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
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
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($order['created_at']))); ?></td>
                                <td>₱<?php echo number_format($order['total'], 2); ?></td>
                                <td><span class="status status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                <td class="action-buttons">
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="action-btn view-btn">View Details</a>
                                    <button class="action-btn view-btn" onclick="openStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">Update Status</button>
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