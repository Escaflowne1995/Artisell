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
    <title>Account Settings - ArtiSell</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .settings-container {
            padding: 2rem 0;
        }
        
        .settings-wrapper {
            display: flex;
            gap: 30px;
            margin-top: 2rem;
        }
        
        .settings-sidebar {
            flex: 1;
            background-color: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
            max-width: 280px;
            height: fit-content;
        }
        
        .profile-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--neutral-200);
        }
        
        .profile-pic-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 3px solid var(--neutral-200);
        }
        
        .username-display {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--neutral-800);
        }
        
        .email-display {
            font-size: 0.875rem;
            color: var(--neutral-600);
        }
        
        .settings-menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .settings-menu li {
            margin-bottom: 0.5rem;
        }
        
        .settings-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.75rem 1rem;
            color: var(--neutral-700);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .settings-menu a:hover, 
        .settings-menu a.active {
            background-color: var(--primary-50);
            color: var(--primary);
        }
        
        .settings-menu a i {
            min-width: 20px;
            text-align: center;
        }
        
        .settings-content {
            flex: 3;
        }
        
        .settings-card {
            background-color: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .settings-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--neutral-200);
            color: var(--neutral-800);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--neutral-700);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-md);
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.2);
        }
        
        .form-hint {
            font-size: 0.875rem;
            color: var(--neutral-600);
            margin-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .settings-wrapper {
                flex-direction: column;
            }
            
            .settings-sidebar {
                max-width: 100%;
                order: 2;
            }
            
            .settings-content {
                order: 1;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="container settings-container">
        <h1 class="text-center mb-4">Account Settings</h1>
        
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
                    <li><a href="#profile" class="active"><i class="fas fa-user"></i> Profile Information</a></li>
                    <li><a href="#password"><i class="fas fa-lock"></i> Change Password</a></li>
                    <li><a href="profile.php"><i class="fas fa-arrow-left"></i> Back to Profile</a></li>
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
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_picture">Profile Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                            <p class="form-hint">Accepted formats: JPG, JPEG, PNG, GIF (Max: 5MB)</p>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div id="password" class="settings-card">
                    <h2>Change Password</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                            <p class="form-hint">Password must be at least 6 characters long</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo">ArtiSell</a>
                    <p>Manage your account settings and preferences.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ArtiSell. All rights reserved.</p>
            </div>
        </div>
    </footer>

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
