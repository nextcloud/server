<?php
/*
 * Template for admin pages
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>ownCloud</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="favicon.ico" />
		<?php foreach($_["cssfiles"] as $cssfile): ?>
			<link rel="stylesheet" href="<?php echo $cssfile; ?>" type="text/css" media="screen" />
		<?php endforeach; ?>
		<?php foreach($_["jsfiles"] as $jsfile): ?>
			<script type="text/javascript" src="<?php echo $jsfile; ?>"></script>
		<?php endforeach; ?>
	</head>

	<body>
		<div id="header">
			<a href="<?php echo link_to("", "index.php"); ?>" title="" id="owncloud"><img src="<?php echo image_path("", "owncloud-logo-small-white.png"); ?>" alt="ownCloud" /></a>

			<div id="user">
				<a id="user_menu_link" href="" title="">Username</a>
				<ul id="user_menu">
					<?php foreach($_["personalmenu"] as $entry ): ?>
						<li><a href="<?php echo link_to($entry["app"], $entry["file"]); ?>" title=""><?php echo $entry["name"]; ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<div id="main">
			<div id="plugins">
				<ul>
					<?php foreach($_["navigation"] as $entry): ?>
						<li><a href="<?php echo link_to($entry["app"], $entry["file"]); ?>" title=""><?php echo $entry["name"]; ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div id="content">
				<?php echo $_["content"]; ?>
			</div>
		</div>
	</body>
</html>
