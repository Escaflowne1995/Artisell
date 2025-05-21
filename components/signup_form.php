<?php
// Initialize error variables
$usernameError = $emailError = $passwordError = $confirmPasswordError = "";
$username = $email = $password = $confirmPassword = "";

// Process the form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    // Check for CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $usernameError = "Please enter a username.";
    } else {
        $username = trim($_POST["username"]);
        // Check if username already exists
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $usernameError = "This username is already taken.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $emailError = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        // Additional email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailError = "Invalid email format.";
        } else {
            // Check if email already exists
            $sql = "SELECT id FROM users WHERE email = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        $emailError = "This email is already registered.";
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Validate password only on form submission
    if (empty(trim($_POST["password"]))) {
        $passwordError = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
        $errors = array();
        
        // Enhanced debug logging for password validation
        error_log("Password validation started");
        error_log("Raw password length: " . strlen($_POST["password"]));
        error_log("Trimmed password length: " . strlen($password));
        
        // Check password length (8-12 characters)
        $passwordLength = strlen($password);
        error_log("Final password length for validation: " . $passwordLength);
        
        if ($passwordLength < 8 || $passwordLength > 12) {
            error_log("Password length validation failed. Length: " . $passwordLength);
            $errors[] = "Password must be between 8 and 12 characters";
        } else {
            error_log("Password length validation passed");
        }
        
        // Check for at least one capital letter
        if (!preg_match('/[A-Z]/', $password)) {
            error_log("Capital letter validation failed");
            $errors[] = "Password must contain at least one capital letter";
        }
        
        // Check for at least one number
        if (!preg_match('/[0-9]/', $password)) {
            error_log("Number validation failed");
            $errors[] = "Password must contain at least one number";
        }
        
        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            error_log("Special character validation failed");
            $errors[] = "Password must contain at least one special character (!@#$%^&*(),.?\":{}|<>)";
        }
        
        if (!empty($errors)) {
            error_log("Password validation failed with errors: " . implode(", ", $errors));
            $passwordError = implode("<br>", $errors);
        } else {
            error_log("Password validation passed all checks");
        }
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirmPasswordError = "Please confirm password.";
    } else {
        $confirmPassword = trim($_POST["confirm_password"]);
        if ($password != $confirmPassword) {
            $confirmPasswordError = "Password did not match.";
        }
    }
    
    // Check if there are no errors
    if (empty($usernameError) && empty($emailError) && empty($passwordError) && empty($confirmPasswordError)) {
        // Get the selected role (default to customer if not set)
        $role = isset($_POST['role']) && $_POST['role'] === 'vendor' ? 'vendor' : 'customer';
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Bind parameters
            mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashedPassword, $role);
            
            // Attempt to execute
            if (mysqli_stmt_execute($stmt)) {
                // Redirect to login page
                $_SESSION['signup_success'] = true;
                header("location: login.php");
                exit();
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
    <p>Create an account to discover authentic Cebuano artisan products</p>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="signup" value="1">
        
        <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <input id="username" name="username" type="text" class="form-input" placeholder="Your username" required value="<?php echo htmlspecialchars($username); ?>">
            <span class="error"><?php echo $usernameError; ?></span>
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-input" placeholder="you@email.com" required value="<?php echo htmlspecialchars($email); ?>">
            <span class="error"><?php echo $emailError; ?></span>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div class="password-input">
                <input id="password" name="password" type="password" class="form-input" placeholder="••••••••" required>
                <button type="button" class="show-password" onclick="showPassword('password')">Show</button>
            </div>
            <?php if (!empty($passwordError)): ?>
                <span class="error"><?php echo $passwordError; ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <div class="password-input">
                <input id="confirm_password" name="confirm_password" type="password" class="form-input" placeholder="••••••••" required>
                <button type="button" class="show-password" onclick="showPassword('confirm_password')">Show</button>
            </div>
            <?php if (!empty($confirmPasswordError)): ?>
                <span class="error"><?php echo $confirmPasswordError; ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Account Type</label>
            <div class="role-selection">
                <label>
                    <input type="radio" name="role" value="customer" checked> Customer
                </label>
                <label>
                    <input type="radio" name="role" value="vendor"> Vendor
                </label>
            </div>
        </div>

        <div class="form-group terms">
            <input type="checkbox" id="terms" name="terms" required>
            <label for="terms">I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a></label>
        </div>

        <button type="submit" class="submit-button">Create Account</button>
    </form>

    <div class="separator">
        <div class="separator-line"></div>
        <span class="separator-text">Or sign up with</span>
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

    <p class="login-link">Already have an account? <a href="login.php">Sign in</a></p>
</div>

<style>
    .error {
        color: #dc3545;
        font-size: 13px;
        margin-top: 5px;
        display: block;
    }

    .password-input {
        position: relative;
        display: flex;
        align-items: center;
    }

    .show-password {
        position: absolute;
        right: 10px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px 10px;
        color: #666;
        z-index: 1;
    }

    .form-input {
        width: 100%;
        padding: 8px 35px 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-input:focus {
        outline: none;
        border-color: #FF6B17;
        box-shadow: 0 0 0 2px rgba(255, 107, 23, 0.2);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers after DOM is loaded
    document.querySelectorAll('.show-password').forEach(function(button) {
        button.addEventListener('click', function() {
            var input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = 'Hide';
            } else {
                input.type = 'password';
                this.textContent = 'Show';
            }
        });
    });
});

// Backup onclick handler
function showPassword(inputId) {
    var input = document.getElementById(inputId);
    var button = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Hide';
    } else {
        input.type = 'password';
        button.textContent = 'Show';
    }
}
</script> 