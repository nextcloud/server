<?php

if(class_exists('OC')){
	$WEBROOT=OC::$WEBROOT;
}else{//not called trough remote.php try to guess the webroot the best we can from here
	// calculate the root directories
	$SERVERROOT=str_replace("\\",'/',substr(__FILE__,0,-strlen('apps/user_webfinger/host-meta.php')));
	$WEBROOT=substr($SERVERROOT,strlen(realpath($_SERVER['DOCUMENT_ROOT'])));

	if($WEBROOT!='' and $WEBROOT[0]!=='/'){
		$WEBROOT='/'.$WEBROOT;
	}
}

if(substr($WEBROOT,-1)==='/'){
	$WEBROOT=substr($WEBROOT,0,-1);
}

$hostMetaHeader = array(
	'Access-Control-Allow-Origin' => '*',
	'Content-Type' => 'application/xrd+json'
);
$serverName = $_SERVER['SERVER_NAME'];
$hostMetaContents = '{"links":[{"rel":"lrdd","template":"http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$serverName.$WEBROOT.'/public.php?service=webfinger&q={uri}"}]}';
foreach($hostMetaHeader as $header => $value) {
	header($header . ": " . $value);
}
echo $hostMetaContents;
