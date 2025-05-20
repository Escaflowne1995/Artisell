<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: profile.php");
    exit;
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['id'];
$role = $_SESSION['role'] ?? 'customer';

// Initialize variables
$order = null;
$order_items = [];
$total_items = 0;
$shipping_address = [];
$error_message = '';

// Fetch order details
if ($role === 'customer') {
    // For customers, only show their own orders
    $sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
} elseif ($role === 'vendor') {
    // For vendors, show orders that contain their products
    $sql = "SELECT o.* FROM orders o 
            WHERE o.id = ? AND EXISTS (
                SELECT 1 FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                JOIN vendors v ON p.vendor_id = v.id 
                WHERE oi.order_id = o.id AND v.user_id = ?
            )";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
} elseif ($role === 'admin') {
    // For admins, show all orders
    $sql = "SELECT * FROM orders WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
} else {
    // Invalid role
    header("location: profile.php");
    exit;
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $order = $row;
    
    // Fetch order items
    $items_sql = "SELECT oi.*, p.name, p.image, p.price AS current_price 
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = ?";
    $items_stmt = mysqli_prepare($conn, $items_sql);
    mysqli_stmt_bind_param($items_stmt, "i", $order_id);
    mysqli_stmt_execute($items_stmt);
    $items_result = mysqli_stmt_get_result($items_stmt);
    
    while ($item = mysqli_fetch_assoc($items_result)) {
        $order_items[] = $item;
        $total_items += $item['quantity'];
    }
    
    // Get shipping information directly from the order
    $shipping_address = [
        'full_name' => $order['shipping_name'],
        'address_line1' => $order['shipping_address'],
        'city' => $order['shipping_city'],
        'postal_code' => $order['shipping_zip']
    ];
} else {
    // Order not found or not accessible
    $error_message = "Order not found or you don't have permission to view it.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - ArtiSell</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .order-container {
            padding: 30px 0;
        }
        .order-details {
            background: var(--neutral-100);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 25px;
            margin-bottom: 30px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--neutral-300);
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--neutral-800);
        }
        .order-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--neutral-900);
        }
        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
        }
        .order-meta-item {
            flex: 1;
            min-width: 200px;
        }
        .order-meta-item h4 {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--neutral-600);
            margin-bottom: 5px;
        }
        .order-meta-item p {
            font-size: 1rem;
            color: var(--neutral-800);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending {
            background: #fff8e6;
            color: #ffa500;
        }
        .status-processing {
            background: #e6f7ff;
            color: #0099ff;
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
        .status-returned {
            background: #f8e6ff;
            color: #cc66ff;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            text-align: left;
            padding: 12px;
            background: var(--neutral-200);
            color: var(--neutral-700);
            font-weight: 500;
        }
        .items-table td {
            padding: 15px 12px;
            border-bottom: 1px solid var(--neutral-300);
        }
        .product-info {
            display: flex;
            align-items: center;
        }
        .product-img {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-md);
            object-fit: cover;
            margin-right: 15px;
        }
        .product-name {
            font-weight: 500;
            color: var(--neutral-800);
        }
        .order-totals {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--neutral-300);
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .total-row.grand-total {
            font-weight: 600;
            font-size: 1.125rem;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px solid var(--neutral-300);
        }
        .address-box {
            background: var(--neutral-200);
            padding: 15px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 20px;
        }
        .back-link i {
            margin-right: 5px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .order-meta {
                flex-direction: column;
                gap: 10px;
            }
            .items-table {
                display: block;
                overflow-x: auto;
            }
            .product-info {
                flex-direction: column;
                align-items: flex-start;
            }
            .product-img {
                margin-bottom: 10px;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="container order-container">
        <a href="<?php echo $role === 'admin' || $role === 'vendor' ? 'manage_orders.php' : 'profile.php'; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to <?php echo $role === 'admin' || $role === 'vendor' ? 'Order Management' : 'My Account'; ?>
        </a>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            <div class="order-details">
                <h1 class="order-title">Order #<?php echo $order['id']; ?></h1>
                
                <div class="order-meta">
                    <div class="order-meta-item">
                        <h4>Date</h4>
                        <p><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                    </div>
                    
                    <div class="order-meta-item">
                        <h4>Status</h4>
                        <p>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="order-meta-item">
                        <h4>Total</h4>
                        <p>₱<?php echo number_format($order['total'], 2); ?></p>
                    </div>
                    
                    <div class="order-meta-item">
                        <h4>Payment Method</h4>
                        <p><?php echo $order['payment_method']; ?></p>
                    </div>
                </div>
                
                <div class="section-header">
                    <h2 class="section-title">Items</h2>
                    <span><?php echo $total_items; ?> items</span>
                </div>
                
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
                                <td>
                                    <div class="product-info">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-img">
                                        <div>
                                            <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="order-totals">
                    <?php if (isset($order['subtotal']) && $order['subtotal'] > 0): ?>
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order['shipping_fee']) && $order['shipping_fee'] > 0): ?>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span>₱<?php echo number_format($order['shipping_fee'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                    <div class="total-row">
                        <span>Discount:</span>
                        <span>-₱<?php echo number_format($order['discount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row grand-total">
                        <span>Grand Total:</span>
                        <span>₱<?php echo number_format($order['total'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($shipping_address['full_name'])): ?>
            <div class="order-details">
                <div class="section-header">
                    <h2 class="section-title">Shipping Address</h2>
                </div>
                
                <div class="address-box">
                    <p><strong><?php echo htmlspecialchars($shipping_address['full_name']); ?></strong></p>
                    <p><?php echo htmlspecialchars($shipping_address['address_line1']); ?></p>
                    <p>
                        <?php echo htmlspecialchars($shipping_address['city']); ?>
                        <?php if (!empty($shipping_address['postal_code'])): ?>
                            <?php echo htmlspecialchars($shipping_address['postal_code']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include 'components/footer.php'; ?>
</body>
</html> 