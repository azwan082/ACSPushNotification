<?php
$title = 'Hello';
$message = 'Hello world message';

require 'ACSPushNotification.php';
$apn = new ACSPushNotification(array(
	'appKey' => '<APP KEY>',
	'adminName' => '<ADMIN USER NAME>',
	'adminPass' => '<ADMIN USER PASSWORD>',
	'channel' => 'notification'
));
$apn->send($title, $message);
var_dump($apn->getLog());
$apn->close();
?>