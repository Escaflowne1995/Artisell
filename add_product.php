<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is a vendor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'vendor') {
    header("location: login.php");
    exit;
}

// Initialize feedback message
$message = "";

// Define upload directory (relative path)
$target_dir = "uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and validate form inputs
    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = floatval($_POST["price"] ?? 0);
    $category = trim($_POST["category"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $stock = intval($_POST["stock"] ?? 0);

    if (empty($name) || empty($description) || $price <= 0 || empty($category) || empty($city) || $stock < 0) {
        $message = "All fields are required, price must be greater than 0, and stock cannot be negative.";
    } elseif (!isset($_FILES["image"]) || $_FILES["image"]["error"] == UPLOAD_ERR_NO_FILE) {
        $message = "Please upload an image.";
    } else {
        // Handle file upload
        $image = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($imageFileType, $allowed_types)) {
            $message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } elseif ($_FILES["image"]["size"] > 5000000) { // 5MB limit
            $message = "Image file is too large. Maximum size is 5MB.";
        } elseif (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $message = "Error uploading image: " . $_FILES["image"]["error"];
        } else {
            // Save to database directly
            $sql = "INSERT INTO products (name, description, price, category, image, city, vendor_id, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssdsssii", $name, $description, $price, $category, $target_file, $city, $_SESSION['id'], $stock);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);
                    header("Location: shop.php"); // Redirect immediately to shop.php
                    exit;
                } else {
                    $message = "Error saving product to database: " . mysqli_stmt_error($stmt);
                    // Clean up uploaded file if database fails
                    if (file_exists($target_file)) {
                        unlink($target_file);
                    }
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = "Database error: Could not prepare statement - " . mysqli_error($conn);
            }
        }
    }
}

// Define categories and cities for dropdowns
$categories = ["Crafts", "Delicacies"];
$cities = ["Cebu City", "Mandaue City", "Lapu-Lapu City", "Talisay City", "Minglanilla", "Consolacion", "Liloan", "Cordova", "Compostela", "Danao City", "Carcar City", "Naga City", "Toledo City", "Bogo City"];

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - ArtSell</title>
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
            height: 60px;
        }
        .logo a { 
            color: #ff6b00; 
            text-decoration: none; 
            font-size: 24px; 
            font-weight: bold; 
            display: flex;
            align-items: center;
            height: 100%;
        }
        .logo span {
            color: #333;
        }
        nav ul { 
            display: flex; 
            list-style: none; 
            align-items: center;
            margin: 0;
            padding: 0;
            height: 100%;
        }
        nav ul li { 
            margin-left: 30px;
            height: 100%;
            display: flex;
            align-items: center;
        }
        nav ul li a { 
            color: #333; 
            text-decoration: none; 
            font-weight: 500;
            padding: 8px 0;
            display: flex;
            align-items: center;
            position: relative;
            transition: color 0.3s ease;
        }
        nav ul li a:hover {
            color: #ff6b00;
        }
        nav ul li a.active {
            color: #ff6b00;
        }
        nav ul li a.active:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #ff6b00;
        }
        h1 { 
            font-size: 24px; 
            font-weight: 600; 
            margin-bottom: 20px; 
            color: #333; 
        }
        .form-container { 
            padding: 30px 0; 
            display: flex; 
            justify-content: center; 
        }
        .shipping-form { 
            flex: 2; 
            background: #fff; 
            padding: 20px; 
            border-radius: 6px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            max-width: 600px; 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 500; 
        }
        .form-group input, 
        .form-group textarea,
        .form-group select { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        .form-group select {
            background-color: white;
            height: 38px;
        }
        .form-group textarea { 
            height: 100px; 
            resize: vertical; 
        }
        .place-order-btn { 
            width: 100%; 
            padding: 12px; 
            background: #ff6b00; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: background 0.3s ease; 
            font-weight: 500; 
        }
        .place-order-btn:hover { 
            background: #e65c00; 
        }
        .message { 
            margin-bottom: 20px; 
            padding: 10px; 
            border-radius: 4px; 
            text-align: center; 
            background: #f8d7da; 
            color: #721c24; 
        }
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            .header-inner {
                flex-direction: column;
                height: auto;
                padding: 15px 0;
            }
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 15px;
            }
            nav ul li {
                margin: 0 15px;
                padding: 5px 0;
            }
            .shipping-form {
                padding: 15px;
            }
        }
        /* Profile dropdown styling */
        .profile-dropdown {
            position: relative;
            cursor: pointer;
            height: 100%;
            display: flex;
            align-items: center;
        }
        .profile-dropdown > a {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dropdown-content {
            position: absolute;
            right: 0;
            top: 100%;
            background: #fff;
            border: 1px solid #eaeaea;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 4px;
            min-width: 150px;
            z-index: 1000;
            margin-top: 8px;
            display: none;
        }
        .dropdown-content a {
            display: block;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #f1f1f1;
        }
        .dropdown-content a:last-child {
            border-bottom: none;
        }
        .dropdown-content a:hover {
            background-color: #f9f9f9;
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
            <div class="logo"><a href="index.php">Art<span>Sell</span></a></div>
            <nav>
                <ul>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="add_product.php">Add Product</a></li>
                    <li class="profile-dropdown">
                        <a href="profile.php">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
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

    <div class="container">
        <div class="form-container">
            <div class="shipping-form">
                <h1>Add New Product</h1>
                <?php if (!empty($message)): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Price:</label>
                        <input type="number" step="0.01" name="price" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Category:</label>
                        <select name="category" required>
                            <option value="" disabled <?php echo !isset($_POST['category']) ? 'selected' : ''; ?>>Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>City:</label>
                        <select name="city" required>
                            <option value="" disabled <?php echo !isset($_POST['city']) ? 'selected' : ''; ?>>Select City</option>
                            <?php foreach ($cities as $cty): ?>
                                <option value="<?php echo htmlspecialchars($cty); ?>" <?php echo (isset($_POST['city']) && $_POST['city'] === $cty) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cty); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Stock:</label>
                        <input type="number" min="0" name="stock" value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '0'; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Image:</label>
                        <input type="file" name="image" accept="image/*" required>
                    </div>
                    <button type="submit" class="place-order-btn">Add Product</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Add this at the end of your body tag
        document.addEventListener('DOMContentLoaded', function() {
            const profileDropdown = document.querySelector('.profile-dropdown');
            const dropdownContent = document.querySelector('.dropdown-content');
            
            profileDropdown.addEventListener('click', function(e) {
                if (e.target.closest('a').getAttribute('href') === 'profile.php') {
                    e.preventDefault();
                    dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.profile-dropdown')) {
                    dropdownContent.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>