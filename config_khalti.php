<?php
$isSandbox = true;   // 👈 change to false when going live

if ($isSandbox) {
    // Sandbox URLs
    define('KHALTI_INITIATE_URL', 'https://dev.khalti.com/api/v2/epayment/initiate/');
    define('KHALTI_LOOKUP_URL',   'https://dev.khalti.com/api/v2/epayment/lookup/');

    // 🔑 Sandbox Secret Key from https://test-admin.khalti.com
    define('KHALTI_SECRET_KEY', 'Key 50e23406eb98408692c0485f92e0e21b');

} else {
    // Live URLs
    define('KHALTI_INITIATE_URL', 'https://khalti.com/api/v2/epayment/initiate/');
    define('KHALTI_LOOKUP_URL',   'https://khalti.com/api/v2/epayment/lookup/');

    // 🔑 Live Secret Key from https://admin.khalti.com
    define('KHALTI_SECRET_KEY', 'Key b80f66a4794d4f18b76e90361828b40f');
}

// Your site’s base URL
define('WEBSITE_URL', 'http://localhost/room'); // change for server deployment
define('RETURN_URL', WEBSITE_URL . '/verify_payment.php');
?>