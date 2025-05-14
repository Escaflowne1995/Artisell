<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../db_connection.php";

// Initialize filter variables
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'order_date';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Prepare base SQL query with more details
$sql = "SELECT o.id, o.order_date, o.status, o.total_amount, o.shipping_address,
               o.payment_method, 
               u.username, u.email,
               GROUP_CONCAT(p.name SEPARATOR ', ') as products,
               COUNT(oi.product_id) as item_count
        FROM orders o 
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id";

// Add filters
$where_conditions = array();

if ($status_filter != 'all') {
    $where_conditions[] = "o.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

if ($payment_filter != 'all') {
    $where_conditions[] = "o.payment_method = '" . mysqli_real_escape_string($conn, $payment_filter) . "'";
}

if (!empty($search_query)) {
    $search_term = mysqli_real_escape_string($conn, $search_query);
    $where_conditions[] = "(o.id LIKE '%$search_term%' 
                          OR u.username LIKE '%$search_term%'
                          OR u.email LIKE '%$search_term%'
                          OR o.shipping_address LIKE '%$search_term%'
                          OR p.name LIKE '%$search_term%')";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.order_date) >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.order_date) <= '" . mysqli_real_escape_string($conn, $date_to) . "'";
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Add grouping
$sql .= " GROUP BY o.id";

// Add sorting
$valid_sort_columns = ['order_date', 'total_amount', 'status', 'username'];
$sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'order_date';
$sort_order = $sort_order === 'ASC' ? 'ASC' : 'DESC';
$sql .= " ORDER BY $sort_by $sort_order";

// Execute query
$result = mysqli_query($conn, $sql);
$orders = [];
while($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

// Get order statistics
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as average_order_value
FROM orders";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get status counts
$status_counts = array();
$status_sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$status_result = mysqli_query($conn, $status_sql);
while($row = mysqli_fetch_assoc($status_result)) {
    $status_counts[$row['status']] = $row['count'];
}

// Get payment method counts
$payment_counts = array();
$payment_sql = "SELECT payment_method, COUNT(*) as count FROM orders GROUP BY payment_method";
$payment_result = mysqli_query($conn, $payment_sql);
while($row = mysqli_fetch_assoc($payment_result)) {
    $payment_counts[$row['payment_method']] = $row['count'];
}

// Get paid orders count
$paid_sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'Paid'";
$paid_result = mysqli_query($conn, $paid_sql);
$paid_count = mysqli_fetch_assoc($paid_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Orders View - Artisell Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <style>
        .main-content {
            padding: 20px;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }
        
        .advanced-filters {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .date-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-box h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
        }
        
        .stat-box .value {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        
        .bulk-actions {
            margin-bottom: 20px;
        }
        
        .order-details {
            font-size: 14px;
        }
        
        .order-products {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .sort-link {
            color: inherit;
            text-decoration: none;
        }
        
        .sort-link:hover {
            color: var(--primary-color);
        }
        
        .sort-link.active {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .sort-link.active::after {
            content: "↓";
            margin-left: 5px;
        }
        
        .sort-link.active.asc::after {
            content: "↑";
        }
        
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .notification .close {
            cursor: pointer;
            padding: 0 5px;
        }
        
        .export-options {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .export-options.show {
            display: block;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .pending { background: #fff3cd; color: #856404; }
        .processing { background: #cce5ff; color: #004085; }
        .preparing { background: #e2e3ff; color: #383940; }
        .ready_for_pickup { background: #d4edda; color: #155724; }
        .out_for_delivery { background: #fff3cd; color: #856404; }
        .delivered { background: #c3e6cb; color: #155724; }
        .completed { background: #d4edda; color: #155724; }
        .cancelled { background: #f8d7da; color: #721c24; }
        .refunded { background: #ffeeba; color: #856404; }
        
        .payment-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .payment-badge.paid {
            background: #d4edda;
            color: #155724;
        }
        
        .payment-badge.cod {
            background: #f8f9fa;
            color: #383940;
        }
        
        .payment-badge.gcash {
            background: #cce5ff;
            color: #004085;
        }
        
        .paid-indicator {
            color: #155724;
            font-weight: bold;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background-color: #fff;
            min-width: 160px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 4px;
            z-index: 1000;
            margin-top: 5px;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 8px 16px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f5f5f5;
            color: var(--primary-color);
        }

        .action-buttons {
            display: inline-flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 4px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <main class="main-content">
            <div class="page-header">
                <h1>Advanced Orders Management</h1>
                <div class="header-actions">
                    <button onclick="exportOrders()" class="btn btn-secondary">Export to CSV</button>
                </div>
            </div>

            <?php
            // Display success message if set
            if (isset($_SESSION['success_message'])) {
                echo '<div class="notification success">
                        <span>' . htmlspecialchars($_SESSION['success_message']) . '</span>
                        <span class="close" onclick="this.parentElement.style.display=\'none\'">×</span>
                      </div>';
                unset($_SESSION['success_message']);
            }
            
            // Display error message if set
            if (isset($_SESSION['error_message'])) {
                echo '<div class="notification error">
                        <span>' . htmlspecialchars($_SESSION['error_message']) . '</span>
                        <span class="close" onclick="this.parentElement.style.display=\'none\'">×</span>
                      </div>';
                unset($_SESSION['error_message']);
            }
            ?>

            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Total Orders</h3>
                    <div class="value"><?php echo number_format($stats['total_orders']); ?></div>
                </div>
                <div class="stat-box">
                    <h3>Total Revenue</h3>
                    <div class="value">₱<?php echo number_format($stats['total_revenue'], 2); ?></div>
                </div>
                <div class="stat-box">
                    <h3>Average Order Value</h3>
                    <div class="value">₱<?php echo number_format($stats['average_order_value'], 2); ?></div>
                </div>
                <div class="stat-box">
                    <h3>Pending Orders</h3>
                    <div class="value"><?php echo number_format($status_counts['pending'] ?? 0); ?></div>
                </div>
                <div class="stat-box">
                    <h3>Paid Orders</h3>
                    <div class="value"><?php echo number_format($paid_count ?? 0); ?></div>
                </div>
                <div class="stat-box">
                    <h3>PayPal Payments</h3>
                    <div class="value"><?php echo number_format($payment_counts['paypal'] ?? 0); ?></div>
                </div>
                <div class="stat-box">
                    <h3>Cash on Delivery</h3>
                    <div class="value"><?php echo number_format($payment_counts['cod'] ?? 0); ?></div>
                </div>
            </div>

            <div class="advanced-filters">
                <form method="get" action="order_new.php">
                    <div class="filter-row">
                        <input type="text" name="search" placeholder="Search orders..." 
                               value="<?php echo htmlspecialchars($search_query); ?>" class="form-control">
                        
                        <select name="status" class="form-control">
                            <option value="all">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="preparing" <?php echo $status_filter == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="ready_for_pickup" <?php echo $status_filter == 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                            <option value="out_for_delivery" <?php echo $status_filter == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                        
                        <select name="payment" class="form-control">
                            <option value="all">All Payment Methods</option>
                            <option value="paypal" <?php echo $payment_filter == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                            <option value="cod" <?php echo $payment_filter == 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                            <option value="gcash" <?php echo $payment_filter == 'gcash' ? 'selected' : ''; ?>>GCash</option>
                        </select>
                        
                        <div class="date-filter">
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="form-control">
                            <span>to</span>
                            <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="form-control">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="order_new.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>
                                <a href="?sort=id&order=<?php echo $sort_by == 'id' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>" 
                                   class="sort-link <?php echo $sort_by == 'id' ? 'active' . ($sort_order == 'ASC' ? ' asc' : '') : ''; ?>">
                                    Order ID
                                </a>
                            </th>
                            <th>
                                <a href="?sort=username&order=<?php echo $sort_by == 'username' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>"
                                   class="sort-link <?php echo $sort_by == 'username' ? 'active' . ($sort_order == 'ASC' ? ' asc' : '') : ''; ?>">
                                    Customer
                                </a>
                            </th>
                            <th>
                                <a href="?sort=order_date&order=<?php echo $sort_by == 'order_date' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>"
                                   class="sort-link <?php echo $sort_by == 'order_date' ? 'active' . ($sort_order == 'ASC' ? ' asc' : '') : ''; ?>">
                                    Date
                                </a>
                            </th>
                            <th>
                                <a href="?sort=total_amount&order=<?php echo $sort_by == 'total_amount' && $sort_order == 'ASC' ? 'DESC' : 'ASC'; ?>"
                                   class="sort-link <?php echo $sort_by == 'total_amount' ? 'active' . ($sort_order == 'ASC' ? ' asc' : '') : ''; ?>">
                                    Amount
                                </a>
                            </th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Items</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="no-orders">No orders found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div class="order-details">
                                            <?php echo htmlspecialchars($order['username']); ?>
                                            <div class="text-muted"><?php echo htmlspecialchars($order['email']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            $payment_method = ucfirst($order['payment_method']);
                                            $payment_badge_class = '';
                                            
                                            if ($order['payment_method'] == 'paypal' && $order['status'] == 'Paid') {
                                                $payment_badge_class = 'paid';
                                            } elseif ($order['payment_method'] == 'cod') {
                                                $payment_badge_class = 'cod';
                                            } elseif ($order['payment_method'] == 'gcash') {
                                                $payment_badge_class = 'gcash';
                                            }
                                        ?>
                                        <span class="payment-badge <?php echo $payment_badge_class; ?>">
                                            <?php echo $payment_method; ?>
                                            <?php if ($order['status'] == 'Paid' && $order['payment_method'] != 'cod'): ?>
                                                <span class="paid-indicator">✓</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="order-products">
                                            <?php echo $order['item_count']; ?> items<br>
                                            <small><?php echo htmlspecialchars($order['products']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="viewOrder(<?php echo $order['id']; ?>)" class="btn btn-sm btn-primary">View</button>
                                            <button onclick="restoreOrder(<?php echo $order['id']; ?>)" class="btn btn-sm btn-primary">Restore</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function exportOrders() {
            // Implementation for exporting all filtered orders
            alert('Export functionality would go here');
        }
        
        function viewOrder(orderId) {
            window.location.href = `view_order.php?id=${orderId}`;
        }
        
        function restoreOrder(orderId) {
            if (confirm('Are you sure you want to restore this order to pending status?')) {
                window.location.href = `update_order_status.php?id=${orderId}&status=pending`;
            }
        }
    </script>
</body>
</html> 