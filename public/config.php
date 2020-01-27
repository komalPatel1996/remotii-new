<?php
//  Error Reporting ON
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
//error_reporting(0);

date_default_timezone_set('UTC');

ini_set('display_errors', true);
//ini_set('display_errors', 0);

if(empty($_SERVER['HTTPS'])) {
//    $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
//    header("location:$actual_link");
//    die();
}

define('SERVER_ID',trim(file_get_contents('/var/www/html/remotii/public/server-id.txt')));

//  Function check to get server is local or live
if (SERVER_ID == 'prod') {
 define('SITE_URL','https://remotii.com');
 define('IP','23.21.112.155');
}
elseif (SERVER_ID == 'wts') {
 define('SITE_URL','http://wts.remotii.com');
 define('IP','127.0.0.1');
}
else die('Invalid server ID');

//  Include the Custom Configuration file
require_once 'stripeconfig.php';


//  Next Billing Month Defined for service provider and End User

define('ENDUSER_BILL_MONTHS', 12);

define('SP_NEXT_BILLING', '+1 month');
define('SP_NEXT_GET_PAYMENT', '+1 day');
define('ENDUSER_NEXT_BILLING', '+12 months');

define('NUMBER_OF_TRIAL', 3);

//

define('BILLING_DAY_OF_MONTH', date('d', time()));
define('DEFAULT_ROLE', 2);
define('BASE_URL', SITE_URL);
define('BASE_PATH', '/var/www/html/remotii/public');
define('FILE_PATH', BASE_PATH . "/uploads");
define('FILE_URL', BASE_URL . "/uploads");
define('DATE_FORMAT', 'Y-m-d');
define('ADMIN_EMAIL', 'info@remotii.com');
define('SUCCESS', 1);
define('FAILURE', 0);

//  Authorizenet Credientials Defined   START
define('API_LOGINNAME', '5cBjvwX9jU4f'); // Keep this secure.
define('API_TRANSACTIONKEY', '85S58c7GqYt5n94d'); // Keep this secure.

define('API_HOST', 'apitest.authorize.net'); // FOR LIVE use api in place of apitest
define('API_PATH', '/xml/v1/request.api');

define('VALIDATIONMODE', 'testMode');   //  For testing use testMode    For live use    liveMode
/**     END     */

define('ACTIVE', 1);
define('INACTIVE', 0);
define('SUSPENDED', 2);
define('SUSPENDED_BY_ADMIN', 4);
define('DELINQUENT', 3);

define('ADMIN_SESSION_TIMEOUT', 10);
define('SP_SESSION_TIMEOUT', 10);
define('EU_SESSION_TIMEOUT', 60);
define('SESSION_TIMEOUT_WARNING_LENGTH', 5);

define( 'CUSTOM', 1 );      //  Used for remotii setup configuration type
define('GOOGLE_CAPTCHA_SECRET', '6LdAJh0TAAAAALDsV2w8NGRnV-h__YGCLqq1S-jh');
define('GOOGLE_CAPTCHA_VERIFICATION_SECRET', '6LdAJh0TAAAAAJrkdtmbNhOxmqgrhmiBDqYhlM5y');
define( 'DEFAULTS', 0 );

//STRIPE payment gateway config defined
// Krishna testing secret keys
// define('SEC_KEY', '');
// define('PUB_KEY', 'pk_test_dh2bAijWpNLE8ewqC3ZjbG70');
// Shivam testing secret keys
//define('SEC_KEY', 'sk_test_WXzj1ZkwqskbZH3NYnQpZrYq');
//define('PUB_KEY', 'pk_test_mkqcg1YbVZ2ONS99KrLnUm2T');

/**
 * Client STRIPE account keys
 */


//---------------------------------------------------------
// TEST ENV	BY kri
//define('SEC_KEY', 'sk_test_cTOkqBU88EEDiHmknqueCGIB');
//define('PUB_KEY', 'pk_test_lPtEM9y1EvbyvXqtU8s6thj4');
//---------------------------------------------------------


// LIVE ENV
//define('SEC_KEY', 'sk_live_5wNnzKC5Bzva0Ne6bQm7gnuQ');
//define('PUB_KEY', 'pk_live_R0hTugOtbiRzMObuSt2D8vdd');

/**
 * Generating the 10 digit random number
 *
 * @param type $length
 * @param type $space
 * @param type $trim
 * @return type
 */
function getBigRandom($length, $space = '0123456789', $trim = true) {
    $str = '';
    $spaceLen = strlen($space);
    for ($i = 0; $i < $length; $i++) {
        $str .= $space[mt_rand(0, $spaceLen - 1)];
    }
    if ($trim) {
        $str = ltrim($str, '0');
    }
    return $str;
}
