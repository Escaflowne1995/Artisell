<?php
/**
 * PayPal Token Check Utility
 * This script checks the status of the PayPal access token
 */

// Include required files
require_once 'db_connection.php';
require_once 'config.php';
require_once 'components/paypal_api.php';

// Check if user is logged in as admin (you should implement proper admin check)
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

// Get current token
$current_token = getPayPalAccessToken();

// Check token validity by making a simple API call
$api_base_url = (PAYPAL_MODE === 'live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
$api_url = $api_base_url . "/v1/identity/oauth2/userinfo?schema=paypalv1.1";

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "Authorization: Bearer " . $current_token
));

// Execute cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
$token_valid = false;
$error_message = '';

if(curl_errno($ch)) {
    $error_message = 'cURL Error: ' . curl_error($ch);
} else {
    if($http_code == 200) {
        $token_valid = true;
    } else {
        $error_message = 'HTTP Error: ' . $http_code . ' - Response: ' . $response;
    }
}

curl_close($ch);

// Calculate token expiration
$token_generation_time = defined('PAYPAL_TOKEN_GENERATION_TIME') ? PAYPAL_TOKEN_GENERATION_TIME : 'Unknown';
$token_expires_in = defined('PAYPAL_TOKEN_EXPIRES_IN') ? PAYPAL_TOKEN_EXPIRES_IN : 'Unknown';

// Convert to timestamp if possible
$generation_timestamp = strtotime($token_generation_time);
$current_timestamp = time();
$expiry_timestamp = $generation_timestamp + $token_expires_in;
$time_remaining = $expiry_timestamp - $current_timestamp;

$expiry_status = 'Unknown';
if (is_numeric($time_remaining)) {
    if ($time_remaining > 0) {
        $hours = floor($time_remaining / 3600);
        $minutes = floor(($time_remaining % 3600) / 60);
        $seconds = $time_remaining % 60;
        $expiry_status = "Valid for approximately {$hours}h {$minutes}m {$seconds}s";
    } else {
        $expiry_status = "Token has expired";
    }
}

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal Token Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 4px;
        }
        .valid {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .invalid {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .token-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .token-details pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PayPal Token Status</h1>
        
        <div class="status <?php echo $token_valid ? 'valid' : 'invalid'; ?>">
            Token Status: <strong><?php echo $token_valid ? 'Valid' : 'Invalid'; ?></strong>
            <?php if (!$token_valid && !empty($error_message)): ?>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
        </div>
        
        <h2>Token Details</h2>
        <table>
            <tr>
                <th>Property</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Token Type</td>
                <td><?php echo defined('PAYPAL_TOKEN_TYPE') ? htmlspecialchars(PAYPAL_TOKEN_TYPE) : 'Not defined'; ?></td>
            </tr>
            <tr>
                <td>App ID</td>
                <td><?php echo defined('PAYPAL_APP_ID') ? htmlspecialchars(PAYPAL_APP_ID) : 'Not defined'; ?></td>
            </tr>
            <tr>
                <td>Generation Time</td>
                <td><?php echo htmlspecialchars($token_generation_time); ?></td>
            </tr>
            <tr>
                <td>Expiry Status</td>
                <td><?php echo htmlspecialchars($expiry_status); ?></td>
            </tr>
        </table>
        
        <h2>Token Value</h2>
        <div class="token-details">
            <pre><?php echo htmlspecialchars($current_token); ?></pre>
        </div>
        
        <h2>Token Scope</h2>
        <div class="token-details">
            <pre><?php echo defined('PAYPAL_TOKEN_SCOPE') ? htmlspecialchars(PAYPAL_TOKEN_SCOPE) : 'Not defined'; ?></pre>
        </div>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html> 