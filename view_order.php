<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is admin or vendor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
    ($_SESSION["role"] !== 'admin' && $_SESSION["role"] !== 'vendor')) {
    header("location: login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: manage_orders.php");
    exit;
}

$order_id = (int)$_GET['id'];

// Get order details
$order_sql = "SELECT o.*, u.username, u.email
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = ?";

$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, "i", $order_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);

// Check if order exists
if (mysqli_num_rows($order_result) === 0) {
    header("location: manage_orders.php");
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// If vendor, check if they have products in this order
if ($_SESSION["role"] === 'vendor') {
    $check_sql = "SELECT 1 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = ? AND p.vendor_id = ?
                 LIMIT 1";
    
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $_SESSION['id']);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        // This vendor doesn't have products in this order
        header("location: manage_orders.php");
        exit;
    }
}

// Get order items
$items_sql = "SELECT oi.*, p.name, p.image, p.vendor_id, u.username as vendor_name
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id
              LEFT JOIN users u ON p.vendor_id = u.id
              WHERE oi.order_id = ?";

$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$items = [];
$vendor_items = []; // Items for current vendor if user is vendor

while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
    
    // If user is vendor, separate their items
    if ($_SESSION["role"] === 'vendor' && $item['vendor_id'] == $_SESSION['id']) {
        $vendor_items[] = $item;
    }
}

// If user is vendor, replace items with only their items
if ($_SESSION["role"] === 'vendor') {
    $items = $vendor_items;
}

// Use shipping data from orders table
$shipping = (!empty($order['shipping_address']) || !empty($order['shipping_city'])) ? true : false;
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
        .order-details {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: var(--space-5);
            margin-bottom: var(--space-6);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-4);
            flex-wrap: wrap;
            gap: var(--space-4);
        }
        
        .order-id {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--neutral-700);
        }
        
        .order-date {
            color: var(--neutral-500);
            font-size: var(--font-size-sm);
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
        
        .order-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-6);
            margin-bottom: var(--space-6);
        }
        
        .order-section {
            margin-bottom: var(--space-4);
        }
        
        .section-title {
            font-size: var(--font-size-md);
            font-weight: 600;
            margin-bottom: var(--space-3);
            color: var(--neutral-700);
            border-bottom: 1px solid var(--neutral-200);
            padding-bottom: var(--space-2);
        }
        
        .customer-info p,
        .shipping-info p {
            margin: var(--space-1) 0;
            color: var(--neutral-700);
        }
        
        .label {
            color: var(--neutral-500);
            font-size: var(--font-size-sm);
            margin-right: var(--space-2);
        }
        
        .order-items table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-items th, 
        .order-items td {
            padding: var(--space-3);
            text-align: left;
            border-bottom: 1px solid var(--neutral-200);
        }
        
        .order-items th {
            font-weight: 600;
            color: var(--neutral-700);
        }
        
        .product-cell {
            display: flex;
            align-items: center;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-md);
            margin-right: var(--space-3);
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-name {
            font-weight: 500;
            margin-bottom: var(--space-1);
        }
        
        .vendor-name {
            font-size: var(--font-size-sm);
            color: var(--neutral-500);
        }
        
        .order-summary {
            background-color: var(--neutral-100);
            padding: var(--space-4);
            border-radius: var(--radius-md);
            margin-top: var(--space-4);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--space-2);
        }
        
        .summary-total {
            font-weight: 600;
            font-size: var(--font-size-lg);
            margin-top: var(--space-2);
            padding-top: var(--space-2);
            border-top: 1px solid var(--neutral-300);
        }
        
        .back-btn {
            margin-top: var(--space-4);
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--primary-600);
            font-weight: 500;
            text-decoration: none;
        }
        
        .back-btn:hover {
            color: var(--primary-700);
        }
        
        .page-title {
            font-size: var(--font-size-2xl);
            margin: var(--space-6) 0 var(--space-4) 0;
            color: var(--neutral-800);
        }
        
        @media (max-width: 768px) {
            .order-grid {
                grid-template-columns: 1fr;
            }
            
            .product-cell {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image {
                margin-bottom: var(--space-2);
            }
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="container">
        <h1 class="page-title">Order Details</h1>
        
        <div class="order-details">
            <div class="order-header">
                <div>
                    <div class="order-id">Order #<?php echo htmlspecialchars($order['id']); ?></div>
                    <div class="order-date"><?php echo htmlspecialchars(date('F d, Y - h:i A', strtotime($order['created_at']))); ?></div>
                </div>
                <div>
                    <span class="status status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                </div>
            </div>
            
            <div class="order-grid">
                <div>
                    <div class="order-section">
                        <h3 class="section-title">Customer Information</h3>
                        <div class="customer-info">
                            <p><span class="label">Name:</span> <?php echo htmlspecialchars($order['username']); ?></p>
                            <p><span class="label">Email:</span> <?php echo htmlspecialchars($order['email']); ?></p>
                            <?php if (!empty($order['shipping_name'])): ?>
                                <p><span class="label">Shipping Name:</span> <?php echo htmlspecialchars($order['shipping_name']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($shipping): ?>
                    <div class="order-section">
                        <h3 class="section-title">Shipping Address</h3>
                        <div class="shipping-info">
                            <p><span class="label">Recipient:</span> <?php echo htmlspecialchars($order['shipping_name']); ?></p>
                            <p><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                            <p>
                                <?php echo htmlspecialchars($order['shipping_city']); ?>
                                <?php if (!empty($order['shipping_zip'])): ?>
                                    , <?php echo htmlspecialchars($order['shipping_zip']); ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($order['payment_method'])): ?>
                                <p><span class="label">Payment Method:</span> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="order-section">
                    <h3 class="section-title">Order Summary</h3>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₱<?php echo number_format($order['subtotal'] ?? $order['total'], 2); ?></span>
                        </div>
                        
                        <?php if (isset($order['shipping_fee'])): ?>
                        <div class="summary-row">
                            <span>Shipping Fee:</span>
                            <span>₱<?php echo number_format($order['shipping_fee'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($order['tax'])): ?>
                        <div class="summary-row">
                            <span>Tax:</span>
                            <span>₱<?php echo number_format($order['tax'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($order['discount'])): ?>
                        <div class="summary-row">
                            <span>Discount:</span>
                            <span>-₱<?php echo number_format($order['discount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-row summary-total">
                            <span>Total:</span>
                            <span>₱<?php echo number_format($order['total'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="order-section">
                <h3 class="section-title">Order Items</h3>
                <div class="order-items">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'image/product-placeholder.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="product-image">
                                            <div class="product-details">
                                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <?php if (!empty($item['vendor_name']) && $_SESSION['role'] === 'admin'): ?>
                                                    <div class="vendor-name">Sold by: <?php echo htmlspecialchars($item['vendor_name']); ?></div>
                                                <?php endif; ?>
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
                </div>
            </div>
            
            <a href="manage_orders.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
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
</body>
</html> 