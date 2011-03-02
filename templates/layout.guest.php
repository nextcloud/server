<?php
/*
 * Template for guest pages
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>ownCloud</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="favicon.ico" />
		<? foreach( $_["cssfiles"] as $cssfile ){ ?>
			<link rel="stylesheet" href="<? echo $cssfile ?>" type="text/css" media="screen" />
		<? } ?>
		<? foreach( $_["jsfiles"] as $jsfile ){ ?>
			<script type="text/javascript" src="<? echo $jsfile ?>"></script>
		<? } ?>
	</head>

	<body class="login">
		<? echo $_["content"] ?>
		<p class="info">
			ownCloud is an open personal cloud which runs on your personal server.<br />
			To learn more, please visit <a href="http://www.owncloud.org/">owncloud.org</a>.
		</p>
	</body>
</html>
