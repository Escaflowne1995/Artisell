<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['order_id'])) {
    header("location: cart.php");
    exit;
}

$order_id = $_GET['order_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ArtSell</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body { background-color: #f9f9f9; color: #333; font-family: 'Open Sans', sans-serif; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        header { background: #fff; padding: 15px 0; border-bottom: 1px solid #eee; }
        .header-inner { display: flex; justify-content: space-between; align-items: center; }
        .logo a { color: #ff6b00; text-decoration: none; font-size: 20px; font-weight: bold; }
        nav ul { display: flex; list-style: none; }
        nav ul li { margin-left: 25px; }
        nav ul li a { color: #333; text-decoration: none; font-weight: 500; }
        .profile-dropdown { position: relative; }
        .profile-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content { position: absolute; right: 0; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 4px; min-width: 120px; }
        .dropdown-content a { display: block; padding: 10px 15px; color: #333; text-decoration: none; }
        .dropdown-content a:hover { background: #f5f5f5; }
        .confirmation-container { padding: 30px 0; text-align: center; }
        h1 { font-size: 24px; font-weight: 600; margin-bottom: 20px; color: #333; }
        p { font-size: 16px; margin-bottom: 15px; }
        .checkout-btn { display: inline-block; padding: 12px 20px; background: #ff6b00; color: white; border: none; border-radius: 4px; text-decoration: none; cursor: pointer; transition: background 0.3s ease; }
        .checkout-btn:hover { background: #e65c00; }
        .confirmation-box { background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }

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
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-inner">
            <div class="logo"><a href="#">Art<span style="color: #333;">Sell</span></a></div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="cart.php">Cart (<?php echo count($_SESSION['cart']); ?>)</a></li>
                    <li class="profile-dropdown">
                        <a href="profile.php" class="profile-link">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php if (!empty($_SESSION['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" class="profile-pic">
                            <?php else: ?>
                                <img src="images/default-profile.jpg" alt="Profile" class="profile-pic">
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="profile.php">Settings</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Confirmation Content -->
    <div class="container confirmation-container">
        <div class="confirmation-box">
            <h1>Thank You for Your Order!</h1>
            <p>Your order #<?php echo htmlspecialchars($order_id); ?> has been successfully placed.</p>
            <p>We'll send you a confirmation email with the details soon.</p>
            <a href="shop.php" class="checkout-btn">Continue Shopping</a>
        </div>
    </div>

</body>
</html>