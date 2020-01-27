<?php
function get_client_ip() {
 if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']) return $_SERVER['HTTP_CLIENT_IP'];
 if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) return $_SERVER['HTTP_X_FORWARDED_FOR'];
 if (isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED']) return $_SERVER['HTTP_X_FORWARDED'];
 if (isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR']) return $_SERVER['HTTP_FORWARDED_FOR'];
 if (isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED']) return $_SERVER['HTTP_FORWARDED'];
 if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']) return $_SERVER['REMOTE_ADDR'];

 return 'UNKNOWN';
}
$client_ip = get_client_ip();

if (0 && $client_ip != 'UNKNOWN') // allow hit from terminal only
 die('Unauthorized Access');

 $server_id = trim(file_get_contents('/var/www/html/php72/remotii/public/server-id.txt'));
 $servers = array('prod' => 'remotii.com','wts' => 'wts.remotii.com');
 if (!$server_id || !isset($servers[$server_id])) die('Unknown server ID.');
 echo "http://$servers[$server_id]/admin/index/cron-bill-service-providers"; exit;
$response = file_get_contents("http://$servers[$server_id]/admin/index/cron-bill-service-providers");

//error_log ( time().":\n".json_decode($response)."\n", 3, '/var/www/remotii/public/cron-bsp.log' );