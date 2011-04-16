<!DOCTYPE html>
<html>
	<head>
		<title>ownCloud</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="<?php echo image_path('', 'favicon.png'); ?>" /><link rel="apple-touch-icon-precomposed" href="<?php echo image_path('', 'favicon-touch.png'); ?>" />
		<?php foreach($_['cssfiles'] as $cssfile): ?>
			<link rel="stylesheet" href="<?php echo $cssfile; ?>" type="text/css" media="screen" />
		<?php endforeach; ?>
		<?php foreach($_['jsfiles'] as $jsfile): ?>
			<script type="text/javascript" src="<?php echo $jsfile; ?>"></script>
		<?php endforeach; ?>
	</head>

	<body>
		<div id="header">
			<a href="<?php echo link_to('', 'index.php'); ?>" title="" id="owncloud"><img src="<?php echo image_path('', 'owncloud-logo-small-white.png'); ?>" alt="ownCloud" /></a>

			<ul id="metanav">
				<li><a href="<?php echo link_to('', 'index.php'); ?>" title=""><img src="<?php echo image_path('', 'layout/back.png'); ?>"></a></li>
				<li><a href="<?php echo link_to('settings', 'index.php'); ?>" title=""><img src="<?php echo image_path('', 'layout/settings.png'); ?>"></a></li>
				<li><a href="<?php echo link_to('help', 'index.php'); ?>" title=""><img src="<?php echo image_path('', 'layout/help.png'); ?>"></a></li>
				<li><a href="<?php echo link_to('', 'index.php?logout=true'); ?>" title=""><img src="<?php echo image_path('', 'layout/logout.png'); ?>"></a></li>
			</ul>
		</div>

		<div id="main">
			<div id="plugins">
				<ul>
					<li><a style="background-image:url(<?php echo image_path('settings', 'information.png'); ?>)" href="<?php echo link_to('settings', 'index.php'); ?>" title="">Information</a></li>
					<?php foreach($_['navigation'] as $entry):?>
						<li><a style="background-image:url(<?php echo $entry['icon']; ?>)" href="<?php echo $entry['href']; ?>" title=""><?php echo $entry['name'] ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div id="content">
				<?php echo $_['content']; ?>
			</div>
		</div>
	</body>
</html>
