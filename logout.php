<?php
session_start();
$_SESSION = array(); // Clear the session variables
session_destroy(); // Destroy the session
header("location: index.php"); // Redirect to home page
exit;


?>