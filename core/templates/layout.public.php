<!DOCTYPE html>
<html class="ng-csp" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" >
<head data-user="<?php p($_['user_uid']); ?>" data-user-displayname="<?php p($_['user_displayname']); ?>" data-requesttoken="<?php p($_['requesttoken']); ?>">
	<meta charset="utf-8">
	<title>
		<?php
		p(!empty($_['application'])?$_['application'].' - ':'');
		p($theme->getTitle());
		?>
	</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="referrer" content="never">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
	<meta name="apple-itunes-app" content="app-id=<?php p($theme->getiTunesAppId()); ?>">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="apple-mobile-web-app-title" content="<?php p((!empty($_['application']) && $_['appid']!='files')? $_['application']:$theme->getTitle()); ?>">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="theme-color" content="<?php p($theme->getColorPrimary()); ?>">
	<link rel="icon" href="<?php print_unescaped(image_path($_['appid'], 'favicon.ico')); /* IE11+ supports png */ ?>">
	<link rel="apple-touch-icon-precomposed" href="<?php print_unescaped(image_path($_['appid'], 'favicon-touch.png')); ?>">
	<link rel="mask-icon" sizes="any" href="<?php print_unescaped(image_path($_['appid'], 'favicon-mask.svg')); ?>" color="<?php p($theme->getColorPrimary()); ?>">
	<link rel="manifest" href="<?php print_unescaped(image_path($_['appid'], 'manifest.json')); ?>">
	<?php emit_css_loading_tags($_); ?>
	<?php emit_script_loading_tags($_); ?>
	<?php print_unescaped($_['headers']); ?>
</head>
<body id="<?php p($_['bodyid']);?>">
<?php include('layout.noscript.warning.php'); ?>
<header>
	<div id="header" class="<?php p($_['header-classes']); ?>">
		<div class="header-left">
			<span id="nextcloud">
				<div class="logo logo-icon svg"></div>
				<h1 class="header-appname">
					<?php p($template->getHeaderTitle()); ?>
				</h1>
				<div class="header-shared-by">
					<?php p($template->getHeaderDetails()) ?>
				</div>
			</span>
		</div>

		<?php
		/** @var \OCP\AppFramework\Http\Template\PublicTemplateResponse $template */
		if($template->getActionCount() !== 0) {
			$primary = $template->getPrimaryAction();
			$others = $template->getOtherActions();
			?>
		<div class="header-right">
			<span id="header-primary-action" class="<?php if($template->getActionCount() === 1) {  p($primary->getIcon()); } ?>">
				<a href="<?php p($primary->getLink()); ?>">
					<span><?php p($primary->getLabel()) ?></span>
				</a>
			</span>
			<?php if($template->getActionCount()>1) { ?>
			<div id="header-secondary-action">
				<span id="header-actions-toggle" class="menutoggle icon-more-white"></span>
				<div id="header-actions-menu" class="popovermenu menu">
					<ul>
						<?php
							/** @var \OCP\AppFramework\Http\Template\IMenuAction $action */
							foreach($template->getOtherActions() as $action) {
								print_unescaped($action->render());
							}
						?>
					</ul>
				</div>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
	</div>
</header>
<div id="content-wrapper">
	<div id="content" class="app-<?php p($_['appid']) ?>" role="main">
		<?php print_unescaped($_['content']); ?>
	</div>
	<?php if($template->getFooterVisible()) { ?>
	<footer>
		<p class="info"><?php print_unescaped($theme->getLongFooter()); ?></p>
	</footer>
	<?php } ?>
</div>

</body>
</html>
