<?php
///////
//header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
//header('Pragma: no-cache'); // HTTP 1.0.
//header('Expires: 0'); // Proxies.
///////

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */

//  Include the Custom Configuration file
require_once 'config.php';

//ini_set('session.cookie_lifetime',0);

define('REQUEST_MICROTIME', microtime(true));
chdir(dirname(__DIR__));

/**
 * Custom function defined to print the array structure
 * @param type $data
 */
function _pr($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

/**
 * Custom function defined to print the array structure
 * @param type $data
 */
function _pre($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    die();
}

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Setup autoloading
require 'init_autoloader.php';

//  Stripe custom library added
require __DIR__ . '/../vendor/stripe/lib/Stripe.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
