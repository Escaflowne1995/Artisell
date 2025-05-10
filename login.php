<?php
session_start();
require 'db_connection.php';

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - ArtiSell</title>
    <meta name="description" content="Sign in to your ArtiSell account to discover authentic Cebuano arts, crafts, and traditional foods" />
    <link rel="stylesheet" href="css/shared.css" />
    <link rel="stylesheet" href="css/login.css" />
    <style>
        /* Additional styles specific to login page */
        .login-section {
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
        
        .password-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .password-input {
            position: relative;
        }
        
        .show-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
        }
        
        .forgot-password {
            font-size: 14px;
            color: #FF6B17;
            text-decoration: none;
        }
        
        .signup-link {
            margin-top: 30px;
            text-align: center;
            font-size: 15px;
            color: #555;
        }
        
        .signup-link a {
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
            <!-- Login Form -->
            <section class="login-section">
                <div class="container">
                    <?php include 'components/login_form.php'; ?>
                </div>
            </section>
        </main>
        
        <!-- Footer -->
        <?php include 'components/footer.php'; ?>
    </div>
</body>
</html>