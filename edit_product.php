<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is a vendor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'vendor') {
    header("location: login.php");
    exit;
}

// Get the correct vendor_id from database
$vendorId = null;
$sql = "SELECT id FROM vendors WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_bind_result($stmt, $vendorId);
        mysqli_stmt_fetch($stmt);
    } else {
        // Create a vendor record if it doesn't exist
        $insertVendorSql = "INSERT INTO vendors (user_id, vendor_name) VALUES (?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertVendorSql);
        if ($insertStmt) {
            // Use username as vendor_name initially
            $vendorName = $_SESSION['username'];
            mysqli_stmt_bind_param($insertStmt, "is", $_SESSION['id'], $vendorName);
            
            if (mysqli_stmt_execute($insertStmt)) {
                $vendorId = mysqli_insert_id($conn);
            }
            mysqli_stmt_close($insertStmt);
        }
    }
    mysqli_stmt_close($stmt);
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$sql = "SELECT * FROM products WHERE id = ? AND vendor_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $product_id, $vendorId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("location: vendor_products.php");
    exit;
}

// Define available cities in Cebu province
$valid_cities = [
    'Cebu City',
    'Mandaue',
    'Lapu-Lapu',
    'Carcar',
    'Talisay',
    'Danao',
    'Toledo',
    'Bogo',
    'Naga',
    'Minglanilla',
    'Moalboal',
    'Santander',
    'Aloguinsan',
    'Alcoy',
    'Dumanjug',
    'Catmon',
    'Borbon',
    'Alcantara'
];

// Define product categories
$categories = [
    'jewelry',
    'home-decor',
    'textiles',
    'food',
    'crafts'
];

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
        $message = '<div class="alert alert-danger">Please fill all required fields correctly.</div>';
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
                $message = '<div class="alert alert-danger">Only JPG, JPEG, PNG, and GIF files are allowed.</div>';
            } elseif ($_FILES["image"]["size"] > 5000000) {
                $message = '<div class="alert alert-danger">Image file is too large. Maximum size is 5MB.</div>';
            } elseif (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $message = '<div class="alert alert-danger">Error uploading image: ' . $_FILES["image"]["error"] . '</div>';
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
            mysqli_stmt_bind_param($stmt, "ssdsssiis", $name, $description, $price, $category, $target_file, $city, $stock, $product_id, $vendorId);
            if (mysqli_stmt_execute($stmt)) {
                $message = '<div class="alert alert-success">Product updated successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error updating product: ' . mysqli_stmt_error($stmt) . '</div>';
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
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-md);
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }
        
        textarea.form-control {
            min-height: 150px;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius-md);
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
        
        .image-preview {
            width: 100%;
            height: 200px;
            border: 2px dashed var(--neutral-300);
            border-radius: var(--radius-md);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .image-preview-text {
            color: var(--neutral-500);
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    
    <div class="container">
        <div class="form-container">
            <h1 class="text-center mb-4">Edit Product</h1>
            
            <?php echo $message; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name" class="form-label">Product Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description *</label>
                    <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price" class="form-label">Price (₱) *</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required value="<?php echo htmlspecialchars($product['price']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="category" class="form-label">Category *</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo ($product['category'] === $cat) ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('-', ' ', $cat)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="city" class="form-label">City *</label>
                    <select id="city" name="city" class="form-control" required>
                        <option value="">Select City</option>
                        <?php foreach ($valid_cities as $city_option): ?>
                            <option value="<?php echo $city_option; ?>" <?php echo ($product['city'] === $city_option) ? 'selected' : ''; ?>>
                                <?php echo $city_option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="stock" class="form-label">Stock Quantity *</label>
                    <input type="number" id="stock" name="stock" class="form-control" min="0" required value="<?php echo isset($product['stock']) ? htmlspecialchars($product['stock']) : '0'; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Current Image</label>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image" class="form-label">Upload New Image (optional)</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                    <a href="vendor_products.php" class="btn btn-outline ml-3">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo">ArtiSell</a>
                    <p>Edit your artisan products in our marketplace.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ArtiSell. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        function previewImage(input) {
            const preview = document.querySelector('.image-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>