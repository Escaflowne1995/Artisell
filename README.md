# Artisell - Online Art Marketplace

## PayPal Integration Setup

To enable PayPal payments in the Artisell platform, follow these steps:

1. **Create a PayPal Developer Account**:
   - Go to [PayPal Developer Dashboard](https://developer.paypal.com/dashboard/)
   - Sign up or log in to your PayPal account

2. **Create a PayPal App**:
   - In the Developer Dashboard, navigate to "My Apps & Credentials"
   - Click "Create App" under REST API apps
   - Give your app a name (e.g., "Artisell Marketplace")
   - Select "Merchant" as the app type
   - Click "Create App"

3. **Get API Credentials**:
   - Once your app is created, you'll see your Client ID and Secret
   - Make sure you're in the correct environment (Sandbox for testing, Live for production)

4. **Update Configuration**:
   - Open `config.php` in your Artisell installation
   - Replace the placeholder values with your actual credentials:
     ```php
     define('PAYPAL_CLIENT_ID', 'YOUR_ACTUAL_CLIENT_ID');
     define('PAYPAL_CLIENT_SECRET', 'YOUR_ACTUAL_CLIENT_SECRET');
     define('PAYPAL_MODE', 'sandbox'); // Change to 'live' for production
     ```

5. **Test the Integration**:
   - In sandbox mode, you can use PayPal's sandbox accounts for testing
   - Create a test purchase and complete the checkout process
   - Verify that the payment is processed correctly

## Troubleshooting PayPal Integration

If you encounter issues with PayPal integration:

1. **Check Credentials**: Ensure your Client ID and Secret are correctly entered in `config.php`

2. **Verify Mode**: Make sure you're using the correct mode (sandbox/live) and corresponding credentials

3. **Check Logs**: Review your server error logs for specific PayPal API errors

4. **Network Connectivity**: Ensure your server can connect to PayPal's API endpoints

5. **PayPal Developer Dashboard**: Check the PayPal Developer Dashboard for any issues with your app or account

For additional help, refer to the [PayPal Developer Documentation](https://developer.paypal.com/docs/) 