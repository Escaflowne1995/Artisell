<?php
require 'db_connection.php';

// This script ensures all vendors are properly registered and 
// all products are properly associated with vendors in the database

echo "<h1>Fixing vendor products in database</h1>";

// 1. Ensure all users with 'vendor' role have a record in vendors table
$sql = "
    INSERT INTO vendors (user_id, vendor_name)
    SELECT u.id, u.username
    FROM users u
    LEFT JOIN vendors v ON u.id = v.user_id
    WHERE u.role = 'vendor' AND v.id IS NULL
";

if (mysqli_query($conn, $sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "<p>Added $affected missing vendor records</p>";
} else {
    echo "<p>Error creating vendor records: " . mysqli_error($conn) . "</p>";
}

// 2. Update any products that might not have a vendor_id
$sql = "
    UPDATE products 
    SET vendor_id = 1
    WHERE vendor_id = 0 OR vendor_id IS NULL
";

if (mysqli_query($conn, $sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "<p>Updated $affected products with missing vendor ID</p>";
} else {
    echo "<p>Error updating products: " . mysqli_error($conn) . "</p>";
}

// 3. Show current vendor stats
$sql = "SELECT v.id, u.username, COUNT(p.id) as product_count 
        FROM vendors v 
        JOIN users u ON v.user_id = u.id
        LEFT JOIN products p ON v.id = p.vendor_id
        GROUP BY v.id";

$result = mysqli_query($conn, $sql);

if ($result) {
    echo "<h2>Current Vendor Statistics:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Vendor ID</th><th>Username</th><th>Product Count</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_count']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Error fetching vendor statistics: " . mysqli_error($conn) . "</p>";
}

mysqli_close($conn);
echo "<p>Database update complete.</p>";
echo "<p><a href='vendor_products.php'>Return to Vendor Products</a></p>";
?> 