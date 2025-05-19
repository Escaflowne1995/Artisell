<?php
/**
 * Footer Component
 * 
 * A reusable footer that can be included in all pages
 */
?>
<!-- Footer Styles -->
<link rel="stylesheet" href="css/footer.css">

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-column">
                <a href="index.php" class="footer-logo">
                    <span class="logo-arti">Arti</span><span class="logo-sell">Sell</span>
                </a>
                <p>Connecting artisans with customers who appreciate authentic local crafts and delicacies.</p>
                
                <div class="social-links">
                    <a href="https://www.facebook.com/steve.pable.3/" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="categories.php">Products</a></li>
                    <li><a href="cities.php">Cities</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
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
            <p>&copy; <?php echo date('Y'); ?> ArtiSell. All rights reserved.</p>
        </div>
    </div>
</footer> 