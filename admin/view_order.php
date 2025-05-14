<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../db_connection.php";

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: order_new.php");
    exit;
}

$order_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get order details with customer information
$sql = "SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header("location: order_new.php");
    exit;
}

// Get order items
$sql = "SELECT oi.*, p.name, p.price 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$order_items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Artisell Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <style>
        .main-content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-preparing { background: #e2e3ff; color: #383940; }
        .status-ready_for_pickup { background: #d4edda; color: #155724; }
        .status-out_for_delivery { background: #fff3cd; color: #856404; }
        .status-delivered { background: #c3e6cb; color: #155724; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-refunded { background: #ffeeba; color: #856404; }
        
        .order-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .order-section h2 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 1.2rem;
        }
        
        .customer-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-group {
            margin-bottom: 15px;
        }
        
        .info-group label {
            display: block;
            color: #666;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .info-group span {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .items-table td {
            padding: 12px;
            border-top: 1px solid #eee;
        }
        
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        
        .total-row {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .total-label {
            color: #666;
        }
        
        .total-value {
            font-weight: 600;
            color: #2c3e50;
            min-width: 100px;
        }
        
        .actions-section {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            border: none;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .payment-verified {
            color: #155724;
            font-weight: 500;
            background-color: #d4edda;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85em;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <main class="main-content">
            <div class="order-header">
                <div>
                    <h1>Order #<?php echo htmlspecialchars($order['id']); ?></h1>
                    <p>Placed on <?php echo date('F d, Y', strtotime($order['order_date'])); ?></p>
                </div>
                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <div class="order-section">
                <h2>Customer Information</h2>
                <div class="customer-info">
                    <div class="info-group">
                        <label>Name</label>
                        <span><?php echo htmlspecialchars($order['username']); ?></span>
                    </div>
                    <div class="info-group">
                        <label>Email</label>
                        <span><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                    <div class="info-group">
                        <label>Shipping Address</label>
                        <span><?php echo htmlspecialchars($order['shipping_address']); ?></span>
                    </div>
                    <div class="info-group">
                        <label>Payment Method</label>
                        <span>
                            <?php 
                                echo ucfirst(htmlspecialchars($order['payment_method'])); 
                                if ($order['status'] == 'Paid' && $order['payment_method'] != 'cod') {
                                    echo ' <span class="payment-verified">(Payment Verified ✓)</span>';
                                }
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="order-section">
                <h2>Order Items</h2>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="total-section">
                    <div class="total-row">
                        <span class="total-label">Subtotal:</span>
                        <span class="total-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Total:</span>
                        <span class="total-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="actions-section">
                <a href="order_new.php" class="btn btn-secondary">← Back to Orders</a>
                
                <?php if ($order['status'] == 'pending'): ?>
                    <button onclick="updateStatus('processing')" class="btn btn-primary">Start Processing</button>
                    <button onclick="updateStatus('cancelled')" class="btn btn-danger">Cancel Order</button>
                <?php elseif ($order['status'] == 'processing'): ?>
                    <button onclick="updateStatus('preparing')" class="btn btn-primary">Start Preparing</button>
                    <button onclick="updateStatus('cancelled')" class="btn btn-danger">Cancel Order</button>
                <?php elseif ($order['status'] == 'preparing'): ?>
                    <button onclick="updateStatus('ready_for_pickup')" class="btn btn-primary">Mark Ready for Pickup</button>
                    <button onclick="updateStatus('out_for_delivery')" class="btn btn-primary">Send for Delivery</button>
                <?php elseif ($order['status'] == 'ready_for_pickup'): ?>
                    <button onclick="updateStatus('completed')" class="btn btn-primary">Mark as Completed</button>
                    <button onclick="updateStatus('cancelled')" class="btn btn-danger">Cancel Order</button>
                <?php elseif ($order['status'] == 'out_for_delivery'): ?>
                    <button onclick="updateStatus('delivered')" class="btn btn-primary">Mark as Delivered</button>
                    <button onclick="updateStatus('cancelled')" class="btn btn-danger">Cancel Order</button>
                <?php elseif ($order['status'] == 'delivered'): ?>
                    <button onclick="updateStatus('completed')" class="btn btn-primary">Mark as Completed</button>
                    <button onclick="updateStatus('refunded')" class="btn btn-danger">Process Refund</button>
                <?php elseif ($order['status'] == 'cancelled'): ?>
                    <button onclick="updateStatus('pending')" class="btn btn-primary">Restore Order</button>
                <?php elseif ($order['status'] == 'refunded'): ?>
                    <button onclick="updateStatus('completed')" class="btn btn-primary">Mark as Completed</button>
                <?php endif; ?>
                
                <button onclick="printInvoice()" class="btn btn-secondary">Print Invoice</button>
                <button onclick="deleteOrder()" class="btn btn-danger">Delete Order</button>
            </div>
        </main>
    </div>

    <script>
        function updateStatus(status) {
            if (confirm('Are you sure you want to update the order status?')) {
                window.location.href = `update_order_status.php?id=<?php echo $order_id; ?>&status=${status}`;
            }
        }

        function printInvoice() {
            window.location.href = `print_invoice.php?id=<?php echo $order_id; ?>`;
        }
        
        function deleteOrder() {
            if (confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
                window.location.href = `delete_order.php?id=<?php echo $order_id; ?>`;
            }
        }
    </script>
</body>
</html> 