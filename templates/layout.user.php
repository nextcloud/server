<?php
/*
 * Template for user pages
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

	<body>
		<div id="header">
			<a href="<? echo link_to( "", "index.php" )?>" title="" id="owncloud"><img src="<? echo image_path( "", "owncloud-logo-small-white.png" ) ?>" alt="ownCloud" /></a>

			<div id="user">
				<a id="user_menu_link" href="" title="">Username</a>
				<ul id="user_menu">
					<? foreach( $_["personalmenu"] as $entry ){ ?>
						<li><a href="<? echo link_to( $entry["app"], $entry["file"] )?>" title=""><? echo $entry["name"] ?></a></li>
					<? } ?>
				</ul>
			</div>
		</div>

		<div id="main">
			<div id="plugins">
				<ul>
					<? foreach( $_["navigation"] as $entry ){ ?>
						<li><a href="<? echo link_to( $entry["app"], $entry["file"] )?>" title=""><? echo $entry["name"] ?></a></li>
					<? } ?>
				</ul>
			</div>

			<div id="content">
				<? echo $_["content"] ?>
			</div>
		</div>
	</body>
</html>
