<?php
$server_id = trim(file_get_contents('/var/www/remotii/public/server-id.txt'));
$servers = array('prod' => 'remotii.com','dev' => 'dev.remotii.com');
if (!$server_id || !isset($servers[$server_id])) die('Unknown server ID.');

file_get_contents('https://'.$servers[$server_id].'/admin/index/cron-remotii-event');