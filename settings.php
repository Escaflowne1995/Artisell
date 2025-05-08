<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Initialize variables
$username = $_SESSION["username"];
$email = $_SESSION["email"] ?? '';
$profile_picture = $_SESSION["profile_picture"] ?? 'images/default-profile.jpg';
$message = '';
$error = '';

// Process profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Get and validate input
    $new_username = trim($_POST["username"]);
    $new_email = trim($_POST["email"]);
    
    // Validate username
    if (empty($new_username)) {
        $error = "Username cannot be empty.";
    } 
    // Validate email
    elseif (empty($new_email)) {
        $error = "Email cannot be empty.";
    } 
    elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }
    else {
        // Check if email is already in use (by another user)
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $new_email, $_SESSION["id"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "This email is already taken by another user.";
        } else {
            // Process profile picture upload if exists
            $new_profile_picture = $profile_picture; // Default to current
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] != UPLOAD_ERR_NO_FILE) {
                $target_dir = "uploads/profile/";
                
                // Create directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $imageFileType = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
                $target_file = $target_dir . time() . "." . $imageFileType;
                
                // Check file size (5MB max)
                if ($_FILES["profile_picture"]["size"] > 5000000) {
                    $error = "Sorry, your file is too large.";
                }
                // Allow only certain file formats
                elseif (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
                    $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                }
                // Upload file
                elseif (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    // Delete old profile picture if not default
                    if ($profile_picture != 'images/default-profile.jpg' && file_exists($profile_picture)) {
                        unlink($profile_picture);
                    }
                    $new_profile_picture = $target_file;
                } else {
                    $error = "Sorry, there was an error uploading your file.";
                }
            }
            
            // If no errors, update profile
            if (empty($error)) {
                $sql = "UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssi", $new_username, $new_email, $new_profile_picture, $_SESSION["id"]);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Update session variables
                    $_SESSION["username"] = $new_username;
                    $_SESSION["email"] = $new_email;
                    $_SESSION["profile_picture"] = $new_profile_picture;
                    
                    $message = "Profile updated successfully!";
                    
                    // Refresh variables
                    $username = $new_username;
                    $email = $new_email;
                    $profile_picture = $new_profile_picture;
                } else {
                    $error = "Error updating profile: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Process password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } 
    elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    }
    elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    }
    else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($current_password, $row["password"])) {
                // Update password
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $hashed_new_password, $_SESSION["id"]);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Error changing password: " . mysqli_error($conn);
                }
            } else {
                $error = "Current password is incorrect.";
            }
        } else {
            $error = "User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - ArtSell</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body { 
            background-color: #f9f9f9; 
            color: #333; 
            font-family: 'Open Sans', sans-serif; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
        }
        header { 
            background: #fff; 
            padding: 15px 0; 
            border-bottom: 1px solid #eee; 
        }
        .header-inner { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .logo a { 
            color: #ff6b00; 
            text-decoration: none; 
            font-size: 20px; 
            font-weight: bold; 
        }
        nav ul { 
            display: flex; 
            list-style: none; 
        }
        nav ul li { 
            margin-left: 25px; 
        }
        nav ul li a { 
            color: #333; 
            text-decoration: none; 
            font-weight: 500; 
        }
        .profile-dropdown { 
            position: relative; 
        }
        .profile-dropdown:hover .dropdown-content { 
            display: block; 
        }
        .dropdown-content { 
            position: absolute; 
            right: 0; 
            background: #fff; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            border-radius: 4px; 
            min-width: 120px; 
        }
        .dropdown-content a { 
            display: block; 
            padding: 10px 15px; 
            color: #333; 
            text-decoration: none; 
        }
        .dropdown-content a:hover { 
            background: #f5f5f5; 
        }
        .settings-container {
            padding: 30px 0;
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        .settings-wrapper {
            display: flex;
            gap: 30px;
        }
        .settings-sidebar {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 250px;
            height: fit-content;
        }
        .profile-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .profile-pic-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        .username-display {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .email-display {
            font-size: 14px;
            color: #666;
        }
        .settings-menu {
            list-style: none;
        }
        .settings-menu li {
            margin-bottom: 10px;
        }
        .settings-menu a {
            display: block;
            padding: 8px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        .settings-menu a:hover, .settings-menu a.active {
            background: #f5f5f5;
            color: #ff6b00;
        }
        .settings-content {
            flex: 3;
        }
        .settings-card {
            background: #fff;
            padding: 25px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .settings-card h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group input[type="file"] {
            padding: 8px 0;
        }
        .button-primary {
            background: #ff6b00;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .button-primary:hover {
            background: #e65c00;
        }
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
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
    <header>
        <div class="container header-inner">
            <div class="logo"><a href="#">Art<span style="color: #333;">Sell</span></a></div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="cart.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                    </svg>(<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a></li>
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
                            <a href="settings.php">Settings</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container settings-container">
        <h1>Account Settings</h1>
        
        <!-- Display messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="settings-wrapper">
            <!-- Settings Sidebar -->
            <div class="settings-sidebar">
                <div class="profile-preview">
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-pic-large">
                    <div class="username-display"><?php echo htmlspecialchars($username); ?></div>
                    <div class="email-display"><?php echo htmlspecialchars($email); ?></div>
                </div>
                
                <ul class="settings-menu">
                    <li><a href="#profile" class="active">Profile Information</a></li>
                    <li><a href="#password">Change Password</a></li>
                    <li><a href="profile.php">Back to Profile</a></li>
                </ul>
            </div>
            
            <!-- Settings Content -->
            <div class="settings-content">
                <!-- Profile Information -->
                <div id="profile" class="settings-card">
                    <h2>Profile Information</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_picture">Profile Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                            <p style="font-size: 12px; color: #666; margin-top: 5px;">Accepted formats: JPG, JPEG, PNG, GIF (Max: 5MB)</p>
                        </div>
                        
                        <button type="submit" name="update_profile" class="button-primary">Update Profile</button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div id="password" class="settings-card">
                    <h2>Change Password</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <p style="font-size: 12px; color: #666; margin-top: 5px;">Password must be at least 6 characters long</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="button-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <script>
        // Smooth scroll to sections
        document.querySelectorAll('.settings-menu a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                    
                    // Update active class
                    document.querySelectorAll('.settings-menu a').forEach(a => {
                        a.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
