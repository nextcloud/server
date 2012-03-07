<?php
if($_SERVER['SCRIPT_NAME'] == '/.well-known/host-meta.php') {
	header("Access-Control-Allow-Origin: *");
} else {
	header('Please-first: activate');
}
header("Content-Type: application/xrd+xml");
echo "<";
?>
?xml version="1.0" encoding="UTF-8"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0" xmlns:hm="http://host-meta.net/xrd/1.0">
	<hm:Host xmlns="http://host-meta.net/xrd/1.0"><?php echo $_SERVER['SERVER_NAME'] ?></hm:Host>
	<Link rel="lrdd" template="http<?php echo (isset($_SERVER['HTTPS'])?'s':''); ?>://<?php echo $_SERVER['SERVER_NAME'] ?>/.well-known/webfinger.php?q={uri}">
	</Link>
</XRD>

