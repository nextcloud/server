<?php
if($_SERVER['SCRIPT_NAME'] == '/.well-known/webfinger.php') {
	header("Access-Control-Allow-Origin: *");
} else {
	header('Please-first: activate');
}
// header("Content-Type: application/xml+xrd");

// calculate the documentroot
// modified version of the one in lib/base.php that takes the .well-known symlink into account
$DOCUMENTROOT=realpath($_SERVER['DOCUMENT_ROOT']);
$SERVERROOT=str_replace("\\",'/',dirname(dirname(dirname(dirname(__FILE__)))));
$SUBURI=substr(realpath($_SERVER["SCRIPT_FILENAME"]),strlen($SERVERROOT));
$WEBROOT=substr($SUBURI,0,-34);

if($_GET['q']) {
	$bits = explode('@', $_GET['q']);
	$userName = $bits[0];
} else {
	$userName = '';
}
if(substr($userName, 0, 5) == 'acct:') {
	$userName = substr($userName, 5);
}
echo "<";
?>
?xml version="1.0" encoding="UTF-8"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0" xmlns:hm="http://host-meta.net/xrd/1.0">
	<hm:Host xmlns="http://host-meta.net/xrd/1.0"><?php echo $_SERVER['SERVER_NAME'] ?></hm:Host>
	<Link rel="http://unhosted.org/spec/dav/0.1" href="http<?php echo ($_SERVER['HTTPS']?'s':''); ?>://<?php echo $_SERVER['SERVER_NAME'].$WEBROOT ?>/apps/remoteStorage/compat.php/<?php echo $userName ?>/remoteStorage/" />
</XRD>
