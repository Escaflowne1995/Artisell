<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is a vendor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'vendor') {
    header("location: login.php");
    exit;
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$sql = "SELECT * FROM products WHERE id = ? AND vendor_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $product_id, $_SESSION['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("location: vendor_products.php");
    exit;
}

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = floatval($_POST["price"] ?? 0);
    $category = trim($_POST["category"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $stock = intval($_POST["stock"] ?? 0);

    if (empty($name) || empty($description) || $price <= 0 || empty($category) || empty($city) || $stock < 0) {
        $message = "All fields are required, price must be greater than 0, and stock cannot be negative.";
    } else {
        $target_file = $product['image']; // Default to existing image
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] != UPLOAD_ERR_NO_FILE) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image = basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($imageFileType, $allowed_types)) {
                $message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } elseif ($_FILES["image"]["size"] > 5000000) {
                $message = "Image file is too large. Maximum size is 5MB.";
            } elseif (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $message = "Error uploading image: " . $_FILES["image"]["error"];
            } else {
                // Delete old image if new one is uploaded
                if (file_exists($product['image']) && $product['image'] !== $target_file) {
                    unlink($product['image']);
                }
            }
        }

        if (empty($message)) {
            // Update product in database
            $sql = "UPDATE products SET name = ?, description = ?, price = ?, category = ?, image = ?, city = ?, stock = ? WHERE id = ? AND vendor_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssdsssiis", $name, $description, $price, $category, $target_file, $city, $stock, $product_id, $_SESSION['id']);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: vendor_products.php");
                exit;
            } else {
                $message = "Error updating product: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - ArtiSell</title>
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
        .form-group textarea { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-sizing: border-box; 
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
                    <li><a href="add_product.php">Add Product</a></li>
                    <li><a href="vendor_products.php">My Products</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="form-container">
            <div class="shipping-form">
                <h1>Edit Product</h1>
                <?php if (!empty($message)): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Price:</label>
                        <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Category:</label>
                        <input type="text" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>City:</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($product['city']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Stock:</label>
                        <input type="number" min="0" name="stock" value="<?php echo isset($product['stock']) ? htmlspecialchars($product['stock']) : '0'; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Current Image:</label>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Current Image" style="max-width: 100px; border-radius: 4px;">
                    </div>
                    <div class="form-group">
                        <label>Upload New Image (optional):</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <button type="submit" class="place-order-btn">Update Product</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>