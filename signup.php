<?php
session_start();
require 'db_connection.php'; // Include your database connection

// Initialize variables
$usernameError = $emailError = $passwordError = $confirmPasswordError = "";
$username = $email = $password = $confirmPassword = "";

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    <title>Sign Up - ArtiSell</title>
    <meta name="description" content="Create an account on ArtiSell to discover authentic Cebuano arts, crafts, and traditional foods" />
    <link rel="stylesheet" href="css/shared.css" />
    <link rel="stylesheet" href="css/signup.css" />
    <style>
        /* Additional styles specific to signup page */
        .signup-section {
            padding: 100px 0 60px;
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
        }
        
        .form-column {
            max-width: 500px;
            margin: 0 auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .terms {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .terms input {
            margin-top: 4px;
        }
        
        .terms label {
            font-size: 14px;
            color: #555;
        }
        
        .terms a {
            color: #FF6B17;
            text-decoration: none;
        }
        
        .login-link {
            margin-top: 30px;
            text-align: center;
            font-size: 15px;
            color: #555;
        }
        
        .login-link a {
            color: #FF6B17;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include 'components/navbar.php'; ?>
        
        <main class="main-content">
            <!-- Signup Form -->
            <section class="signup-section">
                <div class="container">
                    <?php include 'components/signup_form.php'; ?>
                </div>
            </section>
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