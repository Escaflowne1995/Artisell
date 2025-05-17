<?php
// Simple script to update all products with missing or invalid cities
require 'db_connection.php';

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

// Get all products without valid city
$sql = "SELECT id, name, city FROM products WHERE city IS NULL OR city = '' OR city NOT IN ('" . implode("','", $valid_cities) . "')";
$result = mysqli_query($conn, $sql);

$updated_count = 0;
$error_count = 0;

// Loop through each product and assign a random city
while ($product = mysqli_fetch_assoc($result)) {
    // Select a random city
    $random_city = $valid_cities[array_rand($valid_cities)];
    
    // Update the product with the random city
    $update_sql = "UPDATE products SET city = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $random_city, $product['id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $updated_count++;
        echo "Updated product ID: " . $product['id'] . " - " . htmlspecialchars($product['name']) . " with city: " . $random_city . "<br>";
    } else {
        $error_count++;
        echo "Error updating product ID: " . $product['id'] . " - " . mysqli_error($conn) . "<br>";
    }
    
    mysqli_stmt_close($stmt);
}

echo "<h2>Update Complete</h2>";
echo "<p>Successfully updated $updated_count products with random cities.</p>";
if ($error_count > 0) {
    echo "<p>Failed to update $error_count products.</p>";
}
echo "<p><a href='shop.php'>Go to Shop</a></p>";
?> 