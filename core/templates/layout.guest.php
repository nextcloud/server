<!DOCTYPE html>
<!--[if lt IE 7]><html class="ng-csp ie ie6 lte9 lte8 lte7" data-placeholder-focus="false" lang="<?php p($_['language']); ?>"><![endif]-->
<!--[if IE 7]><html class="ng-csp ie ie7 lte9 lte8 lte7" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" ><![endif]-->
<!--[if IE 8]><html class="ng-csp ie ie8 lte9 lte8" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" ><![endif]-->
<!--[if IE 9]><html class="ng-csp ie ie9 lte9" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" ><![endif]-->
<!--[if gt IE 9]><html class="ng-csp ie" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" ><![endif]-->
<!--[if !IE]><!--><html class="ng-csp" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" ><!--<![endif]-->

	<head data-requesttoken="<?php p($_['requesttoken']); ?>">
		<title>
		<?php p($theme->getTitle()); ?>
		</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">
		<meta name="apple-itunes-app" content="app-id=<?php p($theme->getiTunesAppId()); ?>">
		<link rel="shortcut icon" type="image/png" href="<?php print_unescaped(image_path('', 'favicon.png')); ?>" />
		<link rel="apple-touch-icon-precomposed" href="<?php print_unescaped(image_path('', 'favicon-touch.png')); ?>" />
		<?php foreach($_['cssfiles'] as $cssfile): ?>
			<link rel="stylesheet" href="<?php print_unescaped($cssfile); ?>" type="text/css" media="screen" />
		<?php endforeach; ?>
		<?php foreach($_['jsfiles'] as $jsfile): ?>
			<script type="text/javascript" src="<?php print_unescaped($jsfile); ?>"></script>
		<?php endforeach; ?>
		<?php print_unescaped($_['headers']); ?>
	</head>
	<body id="<?php p($_['bodyid']);?>">
		<noscript><div id="nojavascript"><div><?php print_unescaped($l->t('This application requires JavaScript for correct operation. Please <a href="http://enable-javascript.com/" target="_blank">enable JavaScript</a> and reload the page.')); ?></div></div></noscript>
		<div class="wrapper"><!-- for sticky footer -->
			<div class="v-align"><!-- vertically centred box -->
				<?php if ($_['bodyid'] === 'body-login' ): ?>
					<header>
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

		<footer>
			<p class="info">
				<?php print_unescaped($theme->getLongFooter()); ?>
			</p>
		</footer>
	</body>
</html>
