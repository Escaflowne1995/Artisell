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
            color: #2E8B57;
            text-decoration: none;
        }
        
        .signup-link {
            margin-top: 30px;
            text-align: center;
            font-size: 15px;
            color: #555;
        }
        
        .signup-link a {
            color: #2E8B57;
            text-decoration: none;
            font-weight: 500;
        }
        
        /* Override any Sign In button styles to ensure it's green */
        button[type="submit"],
        input[type="submit"],
        .submit-button,
        button:not([class]),
        .btn-primary,
        a.btn-primary,
        button.sign-in,
        .sign-in-button {
            background-color: #2E8B57 !important;
            color: white !important;
            border: none !important;
            transition: background-color 0.3s !important;
        }
        
        button[type="submit"]:hover,
        input[type="submit"]:hover,
        .submit-button:hover,
        button:not([class]):hover,
        .btn-primary:hover,
        a.btn-primary:hover,
        button.sign-in:hover,
        .sign-in-button:hover {
            background-color: #1e6e45 !important;
        }
        
        /* Handle the case of the specific Sign In button shown in the image */
        a.sign-in,
        button.sign-in,
        a.btn-sign-in,
        button.btn-sign-in,
        *[class*="sign-in"],
        input[value="Sign In"] {
            background-color: #2E8B57 !important;
            color: white !important;
            border: none !important;
        }
        
        /* Directly target the orange button visible in the screenshot */
        .container input[type="button"]:not(.show-password),
        .container button:not(.show-password),
        .container a.button,
        .container .btn,
        .container [role="button"],
        .container input[type="submit"] {
            background-color: #2E8B57 !important;
        }
        
        /* Add specific style for show-password button */
        .show-password,
        button.show-password {
            background-color: transparent !important;
            color: #666 !important;
            border: none !important;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include 'components/navbar_auth.php'; ?>
        
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
    
    <!-- Script to ensure Sign In button is green -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Target all possible Sign In buttons on the page
            const signInButtons = document.querySelectorAll('button, a.button, input[type="button"], input[type="submit"], .btn, [role="button"]');
            
            signInButtons.forEach(button => {
                // Skip the show-password button
                if(button.classList.contains('show-password')) {
                    return;
                }
                
                // Check if the button contains 'Sign In' text or has sign-in related classes/attributes
                if (button.textContent && button.textContent.trim() === 'Sign In' || 
                    button.value === 'Sign In' ||
                    button.id && button.id.toLowerCase().includes('sign') ||
                    button.className && button.className.toLowerCase().includes('sign')) {
                    
                    // Force the green color
                    button.style.backgroundColor = '#2E8B57';
                    button.style.color = 'white';
                    button.style.border = 'none';
                }
            });
            
            // Special case - if there's exactly one standalone button that might be the login button
            const allButtons = document.querySelectorAll('button:not([class]), input[type="button"]:not([class]), input[type="submit"]:not([class])');
            if (allButtons.length === 1) {
                allButtons[0].style.backgroundColor = '#2E8B57';
                allButtons[0].style.color = 'white';
                allButtons[0].style.border = 'none';
            }
        });
    </script>
</body>
</html>