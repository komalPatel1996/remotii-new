<?php
$server_id = trim(file_get_contents('/var/www/html/php72/remotii/public/server-id.txt'));
$servers = array('prod' => 'remotii.com','wts' => 'wts.remotii.com');
if (!$server_id || !isset($servers[$server_id])) die('Unknown server ID.');
$response = file_get_contents('http://'.$servers[$server_id].'/admin/index/cron-offline-notification');

//error_log ( time().":\n".json_decode($response)."\n", 3, '/var/www/remotii/public/cron-mail.log' );
