<?php
$title = 'Hello';
$message = 'Hello world message';
$channel = 'notification';

require 'ACSPushNotification.php';
$apn = new ACSPushNotification(array(
	'appKey' => '<APP KEY>',
	'adminName' => '<ADMIN USER NAME>',
	'adminPass' => '<ADMIN USER PASSWORD>'
));
$apn->send($title, $message, $channel);
var_dump($apn->getLog());
$apn->close();
?>