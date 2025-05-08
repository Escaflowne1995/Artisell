<?php
require 'db_connection.php';

header('Content-Type: application/json');

$selected_city = isset($_GET['city']) ? $_GET['city'] : '';

if (empty($selected_city)) {
    echo json_encode([]);
    exit;
}

$products = [];
$selected_city_lower = strtolower($selected_city);

// Fetch products from selected city
$sql = "SELECT id, name, price, image FROM products WHERE LOWER(city) = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $selected_city_lower);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        // Ensure image path is correct, prepend 'image/' if it's not a full URL and doesn't start with it.
        if (!empty($row['image']) && !preg_match('/^(http|https):\/\//i', $row['image']) && strpos($row['image'], 'image/') !== 0 && strpos($row['image'], 'images/') !== 0) {
            // Assuming product images are in an 'uploads/' directory or similar if not in 'image/' or 'images/'
            // Adjust this logic if your product images are stored differently or have absolute paths already
            if (file_exists('uploads/' . $row['image'])) {
                 $row['image'] = 'uploads/' . $row['image'];
            } elseif (file_exists('image/' . $row['image'])) {
                 $row['image'] = 'image/' . $row['image'];
            } else {
                // Fallback or default image if not found, or keep as is if it might be an external URL without http/https
                // For now, we'll assume it might be missing, so we just use it as is. Consider adding a default image path.
            }
        } else if (empty($row['image'])){
            $row['image'] = 'images/default-product.png'; // Path to a default product image
        }
        $products[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    // Log error or handle it appropriately
    // For now, returning empty array
    error_log("Failed to prepare statement for fetching city products: " . mysqli_error($conn));
}

mysqli_close($conn);
echo json_encode($products);
?> 