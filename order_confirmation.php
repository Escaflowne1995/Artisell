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
    <title>Order Confirmation - ArtiSell</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .confirmation-container {
            padding: var(--space-8) 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
        }
        
        .confirmation-card {
            background-color: var(--neutral-100);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: var(--space-6);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .success-icon {
            font-size: 64px;
            color: #008a39;
            margin-bottom: var(--space-4);
            display: block;
        }
        
        .confirmation-title {
            font-size: var(--font-size-2xl);
            font-weight: 600;
            margin-bottom: var(--space-3);
            color: var(--neutral-900);
        }
        
        .order-number {
            font-size: var(--font-size-lg);
            background-color: var(--neutral-200);
            padding: var(--space-2) var(--space-3);
            border-radius: var(--radius-md);
            display: inline-block;
            margin: var(--space-3) 0;
            color: #0066cc;
            font-weight: 500;
        }
        
        .confirmation-message {
            color: var(--neutral-700);
            margin-bottom: var(--space-5);
        }
        
        .confirmation-actions {
            margin-top: var(--space-5);
        }
        
        .btn-continue {
            padding: var(--space-3) var(--space-5);
            background-color: #0066cc;
            color: white;
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-continue:hover {
            background-color: #0055aa;
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <!-- Confirmation Content -->
    <div class="container confirmation-container">
        <div class="confirmation-card">
            <i class="fas fa-check-circle success-icon"></i>
            <h1 class="confirmation-title">Thank You for Your Order!</h1>
            <div class="order-number">
                <i class="fas fa-receipt"></i> Order #<?php echo htmlspecialchars($order_id); ?>
            </div>
            <p class="confirmation-message">Your order has been successfully placed. We'll send you a confirmation email with all the details shortly.</p>
            <div class="confirmation-actions">
                <a href="shop.php" class="btn-continue">Continue Shopping</a>
            </div>
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
        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('open');
        });
    </script>
</body>
</html>