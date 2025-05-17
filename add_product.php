<?php
session_start();
require 'db_connection.php';

// Check if user is logged in and is an artisan or admin
if (!isset($_SESSION['user_id']) || (!isset($_SESSION['is_admin']) && !isset($_SESSION['is_artisan']))) {
    header("Location: login.php");
    exit();
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

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category = $_POST['category'];
    $city = $_POST['city'];
    $stock = (int)$_POST['stock'];
    
    // Validate required fields
    if (empty($name) || empty($description) || $price <= 0 || empty($category) || empty($city) || $stock < 0) {
        $message = '<div class="alert alert-danger">Please fill all required fields correctly.</div>';
    } else {
        // Process image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'images/products/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('product_') . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            } else {
                $message = '<div class="alert alert-danger">Failed to upload image.</div>';
            }
        }
        
        // If no errors, insert into database
        if (empty($message)) {
            $artisan_id = isset($_SESSION['is_admin']) ? 1 : $_SESSION['user_id']; // Default to admin if admin is adding
            
            $sql = "INSERT INTO products (name, description, price, category, city, stock, image, artisan_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssdssisd", $name, $description, $price, $category, $city, $stock, $image_path, $artisan_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = '<div class="alert alert-success">Product added successfully!</div>';
                // Clear form data
                $name = $description = $category = $city = $image_path = '';
                $price = $stock = 0;
            } else {
                $message = '<div class="alert alert-danger">Failed to add product: ' . mysqli_error($conn) . '</div>';
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - ArtiSell</title>
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
            <h1 class="text-center mb-4">Add New Product</h1>
            
            <?php echo $message; ?>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name" class="form-label">Product Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description *</label>
                    <textarea id="description" name="description" class="form-control" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price" class="form-label">Price (₱) *</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required value="<?php echo isset($price) ? $price : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="category" class="form-label">Category *</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo (isset($category) && $category === $cat) ? 'selected' : ''; ?>>
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
                            <option value="<?php echo $city_option; ?>" <?php echo (isset($city) && $city === $city_option) ? 'selected' : ''; ?>>
                                <?php echo $city_option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="stock" class="form-label">Stock Quantity *</label>
                    <input type="number" id="stock" name="stock" class="form-control" min="0" required value="<?php echo isset($stock) ? $stock : '10'; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product Image</label>
                    <div class="image-preview" id="imagePreview">
                        <span class="image-preview-text">Image Preview</span>
                    </div>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </button>
                    <a href="dashboard.php" class="btn btn-outline ml-3">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo">ArtiSell</a>
                    <p>Add your artisan products to our marketplace.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ArtiSell. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                const text = document.createElement('span');
                text.className = 'image-preview-text';
                text.textContent = 'Image Preview';
                preview.appendChild(text);
            }
        }
    </script>
</body>
</html>