<?php
require_once("../vendor/stripe/lib/Stripe.php");

Stripe::setApiKey("sk_test_R4S88VDb7unKbo6Hx08Md0f3");
Stripe::$apiBase = "https://api-tls12.stripe.com";
try {
 Stripe_Charge::all();
 echo "TLS 1.2 supported, no action required.";
} catch (Stripe_ApiConnectionError $e) {
 echo "TLS 1.2 is not supported. You will need to upgrade your integration.";
}

?>

