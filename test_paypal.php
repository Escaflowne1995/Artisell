<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include necessary files
require 'db_connection.php';
require 'components/paypal_api.php';

echo "<h1>PayPal API Test</h1>";

// Test 1: Check if cURL is installed
echo "<h2>Test 1: cURL Installation</h2>";
if (function_exists('curl_init')) {
    echo "<p style='color: green;'>✓ cURL is installed</p>";
    
    // Show cURL version
    $curl_version = curl_version();
    echo "<p>cURL Version: " . $curl_version['version'] . "</p>";
    echo "<p>SSL Version: " . $curl_version['ssl_version'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ cURL is NOT installed. PayPal integration requires cURL.</p>";
    echo "<p>Please install cURL or contact your server administrator.</p>";
    exit;
}

// Test 2: Check PayPal configuration
echo "<h2>Test 2: PayPal Configuration</h2>";
if (defined('PAYPAL_CLIENT_ID') && PAYPAL_CLIENT_ID !== 'YOUR_PAYPAL_CLIENT_ID') {
    echo "<p style='color: green;'>✓ PayPal Client ID is configured</p>";
} else {
    echo "<p style='color: red;'>✗ PayPal Client ID is not properly configured</p>";
}

if (defined('PAYPAL_CLIENT_SECRET') && PAYPAL_CLIENT_SECRET !== 'YOUR_PAYPAL_CLIENT_SECRET') {
    echo "<p style='color: green;'>✓ PayPal Client Secret is configured</p>";
} else {
    echo "<p style='color: red;'>✗ PayPal Client Secret is not properly configured</p>";
}

echo "<p>Mode: " . PAYPAL_MODE . "</p>";
echo "<p>Base URL: " . BASE_URL . "</p>";

// Test 3: Get PayPal Access Token
echo "<h2>Test 3: Getting PayPal Access Token</h2>";
$access_token = getPayPalAccessToken();
if ($access_token) {
    echo "<p style='color: green;'>✓ Successfully obtained PayPal access token</p>";
    echo "<p>Token: " . substr($access_token, 0, 10) . "..." . "</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to get PayPal access token</p>";
    echo "<p>Check the server error logs for more details.</p>";
}

// Test 4: Create a test order
echo "<h2>Test 4: Creating a Test PayPal Order</h2>";
if ($access_token) {
    $test_order = createPayPalOrder(1.00, 'USD', 'Test Order');
    if ($test_order && isset($test_order['id'])) {
        echo "<p style='color: green;'>✓ Successfully created a test order</p>";
        echo "<p>Order ID: " . $test_order['id'] . "</p>";
        
        // Display approval link
        $approval_url = '';
        foreach ($test_order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approval_url = $link['href'];
                break;
            }
        }
        
        if (!empty($approval_url)) {
            echo "<p>Approval URL: <a href='" . $approval_url . "' target='_blank'>" . $approval_url . "</a></p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to create a test order</p>";
        echo "<p>Check the server error logs for more details.</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Skipping test order creation because no access token was obtained</p>";
}

// Display error log (last 20 lines)
echo "<h2>Error Log (Last 20 Lines)</h2>";
$log_file = ini_get('error_log');
if (file_exists($log_file) && is_readable($log_file)) {
    $log_content = file($log_file);
    $log_lines = array_slice($log_content, -20);
    echo "<pre>" . implode("", $log_lines) . "</pre>";
} else {
    echo "<p>Cannot access error log file.</p>";
}
?> 