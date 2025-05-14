<?php
// Database connection
require_once 'db_connection.php';

// Update the profile picture for the customer user
$sql = "UPDATE users SET profile_picture = 'images/default-profile.jpg' WHERE id = 1";

if ($conn->query($sql) === TRUE) {
    echo "Profile picture updated successfully";
} else {
    echo "Error updating profile picture: " . $conn->error;
}

$conn->close();

// Redirect back to about.php
header("Location: about.php");
exit;
?> 