<?php
if (!OCP\App::isEnabled("user_webfinger")) {
	return;
}

$hostMetaHeader = array(
	'Access-Control-Allow-Origin' => '*',
	'Content-Type' => 'application/xrd+json'
);
$serverName = $_SERVER['SERVER_NAME'];
$hostMetaContents = '{"links":[{"rel":"lrdd","template":"http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$serverName.'/public.php?service=webfinger&q={uri}"}]}';
foreach($hostMetaHeader as $header => $value) {
	header($header . ": " . $value);
}
echo $hostMetaContents;
