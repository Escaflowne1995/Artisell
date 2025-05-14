<?php
/**
 * PayPal API Integration
 * This file handles PayPal API authentication and token management
 */

// Include configuration file
require_once dirname(__FILE__) . '/../config.php';

// Function to get PayPal OAuth token
function getPayPalAccessToken() {
    // Check if cURL is available
    if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
        error_log('PayPal API Error: cURL is not installed or not properly configured');
        return false;
    }
    
    // PayPal API credentials from config
    $client_id = PAYPAL_CLIENT_ID;
    $client_secret = PAYPAL_CLIENT_SECRET;
    
    // Check if credentials are properly configured
    if ($client_id === 'YOUR_PAYPAL_CLIENT_ID' || $client_secret === 'YOUR_PAYPAL_CLIENT_SECRET') {
        error_log('PayPal API Error: Client ID or Client Secret not configured properly');
        return false;
    }
    
    // API endpoint based on mode
    $api_base_url = (PAYPAL_MODE === 'live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    $api_url = $api_base_url . "/v1/oauth2/token";
    
    error_log('PayPal API: Attempting to get access token from ' . $api_url);
    
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_USERPWD, $client_id . ":" . $client_secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/x-www-form-urlencoded"
    ));
    
    // SSL settings - Disable SSL verification for local development
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    // Set timeout values
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if(curl_errno($ch)) {
        $curl_error = curl_error($ch);
        error_log('PayPal API Error: ' . $curl_error);
        error_log('PayPal API Error Code: ' . curl_errno($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Parse response
    $response_data = json_decode($response, true);
    
    // Check if token was received
    if($http_code == 200 && isset($response_data['access_token'])) {
        error_log('PayPal API: Successfully obtained access token');
        return $response_data['access_token'];
    } else {
        $error_msg = isset($response_data['error_description']) ? $response_data['error_description'] : 'Unknown error';
        error_log('PayPal API Error: Failed to get access token. HTTP Code: ' . $http_code);
        error_log('PayPal API Error: Error message: ' . $error_msg);
        error_log('PayPal API Error: Full response: ' . $response);
        return false;
    }
}

// Function to create a PayPal order
function createPayPalOrder($amount, $currency = 'USD', $description = 'ArtSell Purchase') {
    $access_token = getPayPalAccessToken();
    
    if(!$access_token) {
        error_log('PayPal API Error: Failed to get access token for order creation');
        return false;
    }
    
    // API endpoint based on mode
    $api_base_url = (PAYPAL_MODE === 'live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    $api_url = $api_base_url . "/v2/checkout/orders";
    
    error_log('PayPal API: Creating order for amount ' . $amount . ' ' . $currency);
    
    // Set return URLs
    $return_url = BASE_URL . '/paypal_success.php';
    $cancel_url = BASE_URL . '/paypal_cancel.php';
    
    // Order data
    $order_data = array(
        'intent' => 'CAPTURE',
        'purchase_units' => array(
            array(
                'amount' => array(
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', '')
                ),
                'description' => $description
            )
        ),
        'application_context' => array(
            'return_url' => $return_url,
            'cancel_url' => $cancel_url
        )
    );
    
    error_log('PayPal API: Return URL: ' . $return_url);
    error_log('PayPal API: Cancel URL: ' . $cancel_url);
    
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $access_token
    ));
    
    // SSL settings - Disable SSL verification for local development
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    // Set timeout values
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if(curl_errno($ch)) {
        $curl_error = curl_error($ch);
        error_log('PayPal API Error: ' . $curl_error);
        error_log('PayPal API Error Code: ' . curl_errno($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Parse response
    $response_data = json_decode($response, true);
    
    // Check if order was created
    if($http_code == 201 && isset($response_data['id'])) {
        error_log('PayPal API: Successfully created order with ID: ' . $response_data['id']);
        return $response_data;
    } else {
        error_log('PayPal API Error: Failed to create order. HTTP Code: ' . $http_code);
        error_log('PayPal API Error: Full response: ' . $response);
        return false;
    }
}

// Function to capture a PayPal payment
function capturePayPalPayment($order_id) {
    $access_token = getPayPalAccessToken();
    
    if(!$access_token) {
        return false;
    }
    
    // API endpoint based on mode
    $api_base_url = (PAYPAL_MODE === 'live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    $api_url = $api_base_url . "/v2/checkout/orders/{$order_id}/capture";
    
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $access_token
    ));
    
    // SSL settings - Disable SSL verification for local development
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    // Set timeout values
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if(curl_errno($ch)) {
        error_log('PayPal API Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Parse response
    $response_data = json_decode($response, true);
    
    // Check if payment was captured
    if($http_code == 201 && isset($response_data['status']) && $response_data['status'] == 'COMPLETED') {
        return $response_data;
    } else {
        error_log('PayPal API Error: Failed to capture payment. Response: ' . $response);
        return false;
    }
}
?> 