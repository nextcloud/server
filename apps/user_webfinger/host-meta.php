<?php
$hostMetaHeader = array(
	'Access-Control-Allow-Origin' => '*',
	'Content-Type' => 'application/xrd+json'
);
$ownCloudDir = dirname(dirname(dirname(__FILE__)));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
try {
		$webRoot = substr(realpath($ownCloudDir), strlen(realpath($docRoot)));
} catch(Exception $e) {
		// some servers fail on realpath(), let's try it the unsecure way:
		$webRoot = substr($ownCloudDir, strlen($docRoot));
}
$serverName = $_SERVER['SERVER_NAME'];
$lrddTmpl = 'http';
if(isset($_SERVER['HTTPS'])) {
		$lrddTmpl .= 's';
}
$lrddTmpl .= '://' . $serverName . $webRoot . '/public.php?service=webfinger&q={uri}';
$hostMetaPath = $docRoot . '/.well-known/host-meta';
$hostMetaDir = $docRoot . '/.well-known';
$hostMetaContents = "{\"links\":[{\"rel\":\"lrdd\",\"template\":\"http://mich.oc/public.php?service=webfinger&q={uri}\"}]}";
foreach($hostMetaHeader as $header => $value) {
	header($header . ": " . $value);
}
echo $hostMetaContents;
