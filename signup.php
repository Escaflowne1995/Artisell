<?php
session_start();
require 'db_connection.php'; // Include your database connection

// Initialize variables
$usernameError = $emailError = $passwordError = $confirmPasswordError = "";
$username = $email = $password = $confirmPassword = "";

// Process the form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $usernameError = "Please enter your username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $emailError = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $emailError = "This email is already taken.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $passwordError = "Please enter your password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $passwordError = "Password must be at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirmPasswordError = "Please confirm your password.";
    } else {
        $confirmPassword = trim($_POST["confirm_password"]);
        if ($password !== $confirmPassword) {
            $confirmPasswordError = "Passwords do not match.";
        }
    }

    // Check if there are no errors
    if (empty($usernameError) && empty($emailError) && empty($passwordError) && empty($confirmPasswordError)) {
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPassword);
            if (mysqli_stmt_execute($stmt)) {
                header("location: login.php");
                exit;
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ArtiSell - Cebu Artisan Marketplace</title>
    <link rel="stylesheet" href="css/shared.css" />
    <link rel="stylesheet" href="css/signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <header class="header">
            <div class="logo">Art<span>iSell</span></div>
            <nav class="navigation">
                <a href="index.php" class="nav-link">Home</a>
                <a href="categories.php" class="nav-link">Categories</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="login.php" class="nav-link">Login</a>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Registration Form -->
            <div class="form-column">
                <div class="register-form">
                    <h2 class="form-title">Welcome to ARTISELL</h2>
                    <p>Sign up for an ArtSell account</p>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input id="username" name="username" type="text" class="form-input" placeholder="John Doe" required>
                            <span class="error"><?php echo $usernameError; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" name="email" type="email" class="form-input" placeholder="your@email.com" required>
                            <span class="error"><?php echo $emailError; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-input">
                                <input id="password" name="password" type="password" class="form-input" placeholder="••••••••" required>
                                <button type="button" class="show-password"></button>
                            </div>
                            <span class="error"><?php echo $passwordError; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="password-input">
                                <input id="confirm_password" name="confirm_password" type="password" class="form-input" placeholder="••••••••" required>
                                <button type="button" class="show-password"></button>
                            </div>
                            <span class="error"><?php echo $confirmPasswordError; ?></span>
                        </div>

                        <button type="submit" class="submit-button">Sign Up</button>
                    </form>

                    <div class="separator">
                        <div class="separator-line"></div>
                        <span class="separator-text">Or continue with</span>
                        <div class="separator-line"></div>
                    </div>

                    <div class="social-login">
                        <button class="social-button facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
                                <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/>
                            </svg>
                        </button>
                        <button class="social-button google">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-google" viewBox="0 0 16 16">
                                <path d="M15.545 6.558a9.4 9.4 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.7 7.7 0 0 1 5.352 2.082l-2.284 2.284A4.35 4.35 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.8 4.8 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.7 3.7 0 0 0 1.599-2.431H8v-3.08z"/>
                            </svg>
                        </button>
                        <button class="social-button twitter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter" viewBox="0 0 16 16">
                                <path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334q.002-.211-.006-.422A6.7 6.7 0 0 0 16 3.542a6.7 6.7 0 0 1-1.889.518 3.3 3.3 0 0 0 1.447-1.817 6.5 6.5 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.32 9.32 0 0 1-6.767-3.429 3.29 3.29 0 0 0 1.018 4.382A3.3 3.3 0 0 1 .64 6.575v.045a3.29 3.29 0 0 0 2.632 3.218 3.2 3.2 0 0 1-.865.115 3 3 0 0 1-.614-.057 3.28 3.28 0 0 0 3.067 2.277A6.6 6.6 0 0 1 .78 13.58a6 6 0 0 1-.78-.045A9.34 9.34 0 0 0 5.026 15"/>
                            </svg>
                        </button>
                    </div>

                    <div class="login-link">
                        <span class="login-text">Already have an account?</span>
                        <a href="login.php" class="signin-link">Sign in</a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include 'components/footer.php'; ?>
    </div>

    <!-- JavaScript for password visibility toggle -->
    <script>
        document.querySelectorAll('.show-password').forEach(button => {
            button.addEventListener('click', function () {
                const passwordInput = this.previousElementSibling;
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                // Optionally toggle the eye icon here if you have different icons for show/hide
            });
        });
    </script>
</body>
</html>