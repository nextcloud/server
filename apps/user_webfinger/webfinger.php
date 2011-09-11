<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/xml+xrd");

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
xml version="1.0" encoding="UTF-8"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0" xmlns:hm="http://host-meta.net/xrd/1.0">
	<hm:Host xmlns="http://host-meta.net/xrd/1.0"><?php echo $_SERVER['SERVER_NAME'] ?></hm:Host>
	<Link rel="http://unhosted.org/spec/dav/0.1" href="http<?php echo ($_SERVER['HTTPS']?'s':''); ?>://<?php echo $_SERVER['SERVER_NAME'] ?>/apps/unhosted/compat.php/<?php echo $userName ?>/unhosted/"></Link>
</XRD>
