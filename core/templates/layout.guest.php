<!DOCTYPE html>
<!--[if lte IE 8]><html class="ng-csp ie ie8 lte9 lte8" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" ><![endif]-->
<!--[if IE 9]><html class="ng-csp ie ie9 lte9" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" ><![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--><html class="ng-csp" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" ><!--<![endif]-->
	<head data-requesttoken="<?php p($_['requesttoken']); ?>">
		<meta charset="utf-8">
		<title>
		<?php p($theme->getTitle()); ?>
		</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
		<meta name="apple-itunes-app" content="app-id=<?php p($theme->getiTunesAppId()); ?>">
		<link rel="shortcut icon" type="image/png" href="<?php print_unescaped(image_path('', 'favicon.png')); ?>">
		<link rel="apple-touch-icon-precomposed" href="<?php print_unescaped(image_path('', 'favicon-touch.png')); ?>">
		<?php foreach($_['cssfiles'] as $cssfile): ?>
			<link rel="stylesheet" href="<?php print_unescaped($cssfile); ?>" media="screen">
		<?php endforeach; ?>
		<?php foreach($_['jsfiles'] as $jsfile): ?>
			<script src="<?php print_unescaped($jsfile); ?>"></script>
		<?php endforeach; ?>
		<?php print_unescaped($_['headers']); ?>
	</head>
	<body id="<?php p($_['bodyid']);?>">
		<?php include('layout.noscript.warning.php'); ?>
		<div class="wrapper">
			<div class="v-align">
				<?php if ($_['bodyid'] === 'body-login' ): ?>
					<header role="banner">
						<div id="header">
							<div class="logo svg">
								<h1 class="hidden-visually">
									<?php p($theme->getName()); ?>
								</h1>
							</div>
							<div id="logo-claim" style="display:none;"><?php p($theme->getLogoClaim()); ?></div>
						</div>
					</header>
				<?php endif; ?>
				<?php print_unescaped($_['content']); ?>
				<div class="push"></div><!-- for sticky footer -->
			</div>
		</div>
		<footer role="contentinfo">
			<p class="info">
				<?php print_unescaped($theme->getLongFooter()); ?>
			</p>
		</footer>
	</body>
</html>
