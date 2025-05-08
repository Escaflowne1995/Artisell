<?php
session_start();
require 'db_connection.php'; // Include your database connection

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Number of categories to load per request
$offset = ($page - 1) * $limit;

// Prepare a SQL statement to fetch categories
$sql = "SELECT * FROM categories LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $offset, $limit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch categories
$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

// Return categories as JSON
header('Content-Type: application/json');
echo json_encode($categories);
?>