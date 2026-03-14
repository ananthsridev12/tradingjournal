<?php
// Copy to config.php and fill in your database credentials.
return [
    'db_host' => 'localhost',
    'db_name' => 'trading_journal',
    'db_user' => 'root',
    'db_pass' => '',
    'db_charset' => 'utf8mb4',
    // Fyers integration
    // Set the full Tradebook endpoint URL from Fyers API docs.
    'fyers_tradebook_url' => '',
    // App/client id used in Authorization header: appId:access_token
    'fyers_app_id' => '',
    // Redirect URI configured in your Fyers app settings.
    'fyers_redirect_uri' => '',
    // Optional: store secret id here for convenience (not recommended for shared hosts).
    'fyers_secret_id' => '',
    // Optional extra headers to send on Fyers requests.
    // Example: ['X-API-Key' => 'your_app_id']
    'fyers_extra_headers' => [],
];
