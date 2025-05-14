<?php
// Initialize variables
$emailError = $passwordError = "";
$email = $password = "";
$redirectAfterLogin = false;
$redirectUrl = '';

// Process the form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // Check for CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $emailError = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        // Additional email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailError = "Invalid email format.";
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $passwordError = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Check if there are no errors
    if (empty($emailError) && empty($passwordError)) {
        // Prepare a select statement (added profile_picture)
        $sql = "SELECT id, username, password, profile_picture, role FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind results
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashedPassword, $profile_picture, $role);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashedPassword)) {
                            // Password is correct, update session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["profile_picture"] = $profile_picture;
                            $_SESSION["role"] = $role; // Store the role in the session
                            
                            // Track if we need to sync the cart
                            $syncCart = false;
                            
                            // Save guest cart items to database if items exist
                            if (!empty($_SESSION['cart'])) {
                                // Check if this is a fresh login or a login after logout
                                // We'll fetch the user's cart from database first
                                $check_existing_cart = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
                                $check_cart_stmt = mysqli_prepare($conn, $check_existing_cart);
                                mysqli_stmt_bind_param($check_cart_stmt, "i", $id);
                                mysqli_stmt_execute($check_cart_stmt);
                                $cart_count_result = mysqli_stmt_get_result($check_cart_stmt);
                                $cart_count = mysqli_fetch_assoc($cart_count_result)['count'];
                                mysqli_stmt_close($check_cart_stmt);
                                
                                // Only merge carts if the user's database cart is empty
                                // This prevents duplication when logging back in after logout
                                if ($cart_count == 0) {
                                    // User has no items in database cart, add session items
                                    foreach ($_SESSION['cart'] as $product_id => $item) {
                                        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                                        $insert_stmt = mysqli_prepare($conn, $insert_sql);
                                        mysqli_stmt_bind_param($insert_stmt, "iii", $id, $product_id, $item['quantity']);
                                        mysqli_stmt_execute($insert_stmt);
                                        mysqli_stmt_close($insert_stmt);
                                    }
                                    $syncCart = true;
                                } else {
                                    // User already has items in database, just sync
                                    $syncCart = true;
                                }
                            } else {
                                // User has no session cart items, just fetch from database
                                $syncCart = true;
                            }
                            
                            // Sync cart from database to session
                            if ($syncCart) {
                                $cart_sql = "SELECT c.product_id, c.quantity, p.name, p.description, p.price, p.image 
                                             FROM cart c 
                                             JOIN products p ON c.product_id = p.id 
                                             WHERE c.user_id = ?";
                                $cart_stmt = mysqli_prepare($conn, $cart_sql);
                                mysqli_stmt_bind_param($cart_stmt, "i", $id);
                                mysqli_stmt_execute($cart_stmt);
                                $cart_result = mysqli_stmt_get_result($cart_stmt);
                                
                                // Reset session cart
                                $_SESSION['cart'] = [];
                                
                                // Populate session cart with database values
                                while ($row = mysqli_fetch_assoc($cart_result)) {
                                    $_SESSION['cart'][$row['product_id']] = [
                                        'name' => $row['name'],
                                        'description' => $row['description'],
                                        'price' => $row['price'],
                                        'image' => $row['image'],
                                        'quantity' => $row['quantity']
                                    ];
                                }
                                
                                mysqli_stmt_close($cart_stmt);
                            }
                            
                            // Prepare redirect URL - instead of redirecting here, we'll set a flag
                            $redirectAfterLogin = true;
                            
                            // Check if user is admin, redirect to admin dashboard
                            if ($role === 'admin') {
                                $redirectUrl = "admin/index.php";
                            } else {
                                // Check if there's a redirect after login
                                $redirectUrl = 'index.php';
                                
                                // Handle the redirect_after_login if set in session
                                if (isset($_SESSION['redirect_after_login'])) {
                                    $redirectUrl = $_SESSION['redirect_after_login'];
                                    unset($_SESSION['redirect_after_login']);
                                }
                                
                                // Handle pending cart addition
                                if (isset($_SESSION['pending_cart_add'])) {
                                    $pendingAdd = $_SESSION['pending_cart_add'];
                                    unset($_SESSION['pending_cart_add']);
                                    
                                    // Redirect to add_to_cart.php with the pending product
                                    $redirectUrl = "add_to_cart.php?product_id=" . $pendingAdd['product_id'] . "&redirect=" . urlencode($pendingAdd['redirect']);
                                }
                                
                                // Handle direct redirect from query parameter
                                if (isset($_GET['redirect'])) {
                                    $redirectUrl = $_GET['redirect'];
                                }
                            }
                        } else {
                            $passwordError = "The password you entered was not valid.";
                        }
                    }
                } else {
                    $emailError = "No account found with that email.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="form-column">
    <h2 class="form-title">Welcome to ARTISELL</h2>
    <p>Sign in to your ArtiSell account</p>

    <?php if ($redirectAfterLogin): ?>
    <script>
        // Use JavaScript to redirect after the page has loaded
        window.location.href = '<?php echo $redirectUrl; ?>';
    </script>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="login" value="1">
        
        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-input" placeholder="you@email.com" required value="<?php echo htmlspecialchars($email); ?>">
            <span class="error"><?php echo $emailError; ?></span>
        </div>

        <div class="form-group">
            <div class="password-group">
                <label for="password" class="form-label">Password</label>
                <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
            </div>
            <div class="password-input">
                <input id="password" name="password" type="password" class="form-input" placeholder="••••••••" required>
                <button type="button" class="show-password">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#666" class="bi bi-eye" viewBox="0 0 16 16">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"></path>
                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"></path>
                    </svg>
                </button>
            </div>
            <span class="error"><?php echo $passwordError; ?></span>
        </div>

        <button type="submit" class="submit-button">Sign In</button>
    </form>

    <div class="separator">
        <div class="separator-line"></div>
        <span class="separator-text">Or continue with</span>
        <div class="separator-line"></div>
    </div>

    <div class="social-login">
        <button class="social-button facebook"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
            <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/>
        </svg></button>
        <button class="social-button google"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-google" viewBox="0 0 16 16">
            <path d="M15.545 6.558a9.4 9.4 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.7 7.7 0 0 1 5.352 2.082l-2.284 2.284A4.35 4.35 0 0 0 8 3.5c-2.087 0-3.86 1.408-4.492 3.304a4.8 4.8 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.7 3.7 0 0 0 1.599-2.431H8v-3.08z"/>
        </svg></button>
        <button class="social-button twitter"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter" viewBox="0 0 16 16">
            <path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334q.002-.211-.006-.422A6.7 6.7 0 0 0 16 3.542a6.7 6.7 0 0 1-1.889.518 3.3 3.3 0 0 0 1.447-1.817 6.5 6.5 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.32 9.32 0 0 1-6.767-3.429 3.29 3.29 0 0 0 1.018 4.382A3.3 3.3 0 0 1 .64 6.575v.045a3.29 3.29 0 0 0 2.632 3.218 3.2 3.2 0 0 1-.865.115 3 3 0 0 1-.614-.057 3.28 3.28 0 0 0 3.067 2.277A6.6 6.6 0 0 1 .78 13.58a6 6 0 0 1-.78-.045A9.34 9.34 0 0 0 5.026 15"/>
        </svg></button>
    </div>
    
    <p class="signup-link">Don't have an account? <a href="signup.php">Sign up</a></p>
</div>

<script>
    // Apply consistent fill color to eye icon
    function ensureGrayIcon() {
        const eyeIcons = document.querySelectorAll('.show-password svg, .show-password svg path');
        eyeIcons.forEach(icon => {
            icon.setAttribute('fill', '#666');
        });
    }
    
    // Run on page load
    document.addEventListener('DOMContentLoaded', ensureGrayIcon);
    
    document.querySelector('.show-password').addEventListener('click', function() {
        const passwordInput = document.querySelector('#password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle the eye icon
        const eyeIcon = this.querySelector('svg');
        
        if (type === 'password') {
            eyeIcon.innerHTML = '<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"></path><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"></path>';
        } else {
            eyeIcon.innerHTML = '<path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486z"></path><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"></path><path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"></path>';
        }
        
        // Ensure the color is applied after content change
        setTimeout(ensureGrayIcon, 0);
    });
</script> 