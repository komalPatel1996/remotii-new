<?php
/**
 * This file is serve to remove event cron log
 */
$server_id = trim(file_get_contents('/var/www/html/php72/remotii/public/server-id.txt'));
$servers = array('prod' => 'remotii.com','wts' => 'wts.remotii.com');
if (!$server_id || !isset($servers[$server_id])) die('Unknown server ID.');
$response = file_get_contents('http://'.$servers[$server_id].'/admin/index/cron-rm-event-cron-data');

