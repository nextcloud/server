<!DOCTYPE html>
<html class="ng-csp" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" >
	<head data-requesttoken="<?php p($_['requesttoken']); ?>">
		<meta charset="utf-8">
		<title>
		<?php p($theme->getTitle()); ?>
		</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="referrer" content="never">
		<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
		<meta name="apple-itunes-app" content="app-id=<?php p($theme->getiTunesAppId()); ?>">
		<meta name="theme-color" content="<?php p($theme->getColorPrimary()); ?>">
		<link rel="icon" href="<?php print_unescaped(image_path('', 'favicon.ico')); /* IE11+ supports png */ ?>">
		<link rel="apple-touch-icon-precomposed" href="<?php print_unescaped(image_path('', 'favicon-touch.png')); ?>">
		<link rel="mask-icon" sizes="any" href="<?php print_unescaped(image_path('', 'favicon-mask.svg')); ?>" color="<?php p($theme->getColorPrimary()); ?>">
		<link rel="manifest" href="<?php print_unescaped(image_path('', 'manifest.json')); ?>">
		<?php emit_css_loading_tags($_); ?>
		<?php emit_script_loading_tags($_); ?>
		<?php print_unescaped($_['headers']); ?>
	</head>
	<body id="<?php p($_['bodyid']);?>">
		<?php include('layout.noscript.warning.php'); ?>
		<div class="wrapper">
			<div class="v-align">
				<?php if ($_['bodyid'] === 'body-login' ): ?>
					<header role="banner">
						<div id="header">
							<div class="logo">
								<h1 class="hidden-visually">
									<?php p($theme->getName()); ?>
								</h1>
								<?php if(\OC::$server->getConfig()->getSystemValue('installed', false)
									&& \OC::$server->getConfig()->getAppValue('theming', 'logoMime', false)): ?>
									<img src="<?php p($theme->getLogo()); ?>"/>
								<?php endif; ?>
							</div>
						</div>
					</header>
				<?php endif; ?>
				<?php print_unescaped($_['content']); ?>
			</div>
		</div>
		<footer role="contentinfo">
			<p class="info">
				<?php print_unescaped($theme->getLongFooter()); ?>
			</p>
		</footer>
	</body>
</html>
