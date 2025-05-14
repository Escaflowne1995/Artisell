<?php
/**
 * Configuration file for Artisell
 * Contains API keys and other sensitive configuration
 */

// PayPal API Configuration
// IMPORTANT: Replace these placeholder values with your actual PayPal API credentials
// You can get these from your PayPal Developer Dashboard at https://developer.paypal.com/dashboard/
define('PAYPAL_CLIENT_ID', 'AYSq3RDGsmBLJE-otTkBtM-jBRd1TCQwFf9RGfwddNXWz0uFU9ztymylOhRS');
define('PAYPAL_CLIENT_SECRET', 'EGnHDxD_qRPdaLdZz8iCr8N7_MzF-YHPTkjs6NKYQvQSBngp4PTTVWkPZRbL');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' or 'live'

// PayPal Access Token (pre-generated)
// Note: This token is expired. The system will attempt to generate a new one using your credentials above
define('PAYPAL_ACCESS_TOKEN', 'A21AAFEpH4PsADK7qSS7pSRsgzfENtu-Q1ysgEDVDESseMHBYXVJYE8ovjj68elIDy8nF26AwPhfXTIeWAZHSLIsQkSYz9ifg');
define('PAYPAL_TOKEN_TYPE', 'Bearer');
define('PAYPAL_APP_ID', 'APP-80W284485P519543T');
define('PAYPAL_TOKEN_EXPIRES_IN', 31668);
define('PAYPAL_TOKEN_SCOPE', 'https://uri.paypal.com/services/invoicing https://uri.paypal.com/services/disputes/read-buyer https://uri.paypal.com/services/payments/realtimepayment https://uri.paypal.com/services/disputes/update-seller https://uri.paypal.com/services/payments/payment/authcapture openid https://uri.paypal.com/services/disputes/read-seller https://uri.paypal.com/services/payments/refund https://api-m.paypal.com/v1/vault/credit-card https://api-m.paypal.com/v1/payments/.* https://uri.paypal.com/payments/payouts https://api-m.paypal.com/v1/vault/credit-card/.* https://uri.paypal.com/services/subscriptions https://uri.paypal.com/services/applications/webhooks');
define('PAYPAL_TOKEN_GENERATION_TIME', '2020-04-03T15:35:36Z'); // When the token was generated

// Base URL for the application
define('BASE_URL', 'http://localhost/Artisell');

// Other configuration settings can be added here
?> 