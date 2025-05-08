<?php
// Script to update the city name from "aloquinsan" to "aloguinsan" in the products table
require 'db_connection.php';

// Start transaction for safety
mysqli_begin_transaction($conn);

try {
    // Update the city name in the products table
    $old_city = "aloquinsan";
    $new_city = "aloguinsan";
    
    $sql = "UPDATE products SET city = ? WHERE city = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $new_city, $old_city);
    mysqli_stmt_execute($stmt);
    
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    // Commit the transaction
    mysqli_commit($conn);
    
    echo "<h2>City Name Update Complete</h2>";
    echo "<p>Successfully updated {$affected_rows} products from '{$old_city}' to '{$new_city}'.</p>";
    echo "<p><a href='shop.php'>Go back to Shop</a></p>";
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    mysqli_rollback($conn);
    echo "<h2>Error</h2>";
    echo "<p>An error occurred: " . $e->getMessage() . "</p>";
}

mysqli_close($conn);
?> 