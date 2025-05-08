<?php
// Update products table to add stock column
require 'db_connection.php';

// Check if the stock column already exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'stock'");
if (mysqli_num_rows($result) == 0) {
    // Add stock column if it doesn't exist
    $sql = "ALTER TABLE products ADD COLUMN stock INT DEFAULT 0 NOT NULL";
    if (mysqli_query($conn, $sql)) {
        echo "Stock column added successfully to products table";
    } else {
        echo "Error adding stock column: " . mysqli_error($conn);
    }
} else {
    echo "Stock column already exists in products table";
}

mysqli_close($conn);
?> 