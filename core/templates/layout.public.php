<!DOCTYPE html>
<html class="ng-csp" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" data-locale="<?php p($_['locale']); ?>" translate="no" >
<head data-requesttoken="<?php p($_['requesttoken']); ?>">
	<meta charset="utf-8">
	<title>
		<?php
		p(!empty($_['application'])?$_['application'].' - ':'');
p($theme->getTitle());
?>
	</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
	<?php if ($theme->getiTunesAppId() !== '') { ?>
	<meta name="apple-itunes-app" content="app-id=<?php p($theme->getiTunesAppId()); ?>">
	<?php } ?>
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="apple-mobile-web-app-title" content="<?php p((!empty($_['application']) && $_['appid'] !== 'files')? $_['application']:$theme->getTitle()); ?>">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="theme-color" content="<?php p($theme->getColorPrimary()); ?>">
	<link rel="icon" href="<?php print_unescaped(image_path($_['appid'], 'favicon.ico')); /* IE11+ supports png */ ?>">
	<link rel="apple-touch-icon" href="<?php print_unescaped(image_path($_['appid'], 'favicon-touch.png')); ?>">
	<link rel="apple-touch-icon-precomposed" href="<?php print_unescaped(image_path($_['appid'], 'favicon-touch.png')); ?>">
	<link rel="mask-icon" sizes="any" href="<?php print_unescaped(image_path($_['appid'], 'favicon-mask.svg')); ?>" color="<?php p($theme->getColorPrimary()); ?>">
	<link rel="manifest" href="<?php print_unescaped(image_path($_['appid'], 'manifest.json')); ?>">
	<?php emit_css_loading_tags($_); ?>
	<?php emit_script_loading_tags($_); ?>
	<?php print_unescaped($_['headers']); ?>
</head>
<body id="<?php p($_['bodyid']);?>">
<?php include('layout.noscript.warning.php'); ?>
<?php foreach ($_['initialStates'] as $app => $initialState) { ?>
	<input type="hidden" id="initial-state-<?php p($app); ?>" value="<?php p(base64_encode($initialState)); ?>">
<?php }?>
	<div id="skip-actions">
		<?php if ($_['id-app-content'] !== null) { ?><a href="<?php p($_['id-app-content']); ?>" class="button primary skip-navigation skip-content"><?php p($l->t('Skip to main content')); ?></a><?php } ?>
		<?php if ($_['id-app-navigation'] !== null) { ?><a href="<?php p($_['id-app-navigation']); ?>" class="button primary skip-navigation"><?php p($l->t('Skip to navigation of app')); ?></a><?php } ?>
	</div>

	<header id="header">
		<div class="header-left">
			<div class="logo logo-icon svg"></div>
			<span id="nextcloud" class="header-appname">
				<?php if (isset($template) && $template->getHeaderTitle() !== '') { ?>
					<?php p($template->getHeaderTitle()); ?>
				<?php } else { ?>
					<?php	p($theme->getName()); ?>
				<?php } ?>
			</span>
			<?php if (isset($template) && $template->getHeaderDetails() !== '') { ?>
				<div class="header-shared-by">
					<?php p($template->getHeaderDetails()); ?>
				</div>
			<?php } ?>
		</div>

		<div class="header-right">
		<?php
/** @var \OCP\AppFramework\Http\Template\PublicTemplateResponse $template */
if (isset($template) && $template->getActionCount() !== 0) {
	$primary = $template->getPrimaryAction();
	$others = $template->getOtherActions(); ?>
			<span id="header-primary-action" class="<?php if ($template->getActionCount() === 1) {
				p($primary->getIcon());
			} ?>">
				<a href="<?php p($primary->getLink()); ?>" class="primary button">
					<span><?php p($primary->getLabel()) ?></span>
				</a>
			</span>
			<?php if ($template->getActionCount() > 1) { ?>
			<div id="header-secondary-action">
				<button id="header-actions-toggle" class="menutoggle icon-more-white"></button>
				<div id="header-actions-menu" class="popovermenu menu">
					<ul>
						<?php
							/** @var \OCP\AppFramework\Http\Template\IMenuAction $action */
							foreach ($others as $action) {
								print_unescaped($action->render());
							}
				?>
					</ul>
				</div>
			</div>
			<?php } ?>
		<?php
} ?>
		</div>
	</header>
	<main id="content" class="app-<?php p($_['appid']) ?>">
		<h1 class="hidden-visually">
			<?php if (isset($template) && $template->getHeaderTitle() !== '') { ?>
				<?php p($template->getHeaderTitle()); ?>
			<?php } else { ?>
				<?php	p($theme->getName()); ?>
			<?php } ?>
		</h1>
		<?php print_unescaped($_['content']); ?>
	</main>
	<?php if (isset($template) && $template->getFooterVisible()) { ?>
	<footer>
		<p><?php print_unescaped($theme->getLongFooter()); ?></p>
		<?php
if ($_['showSimpleSignUpLink']) {
	?>
			<p>
				<a href="https://nextcloud.com/signup/" target="_blank" rel="noreferrer noopener">
					<?php p($l->t('Get your own free account')); ?>
				</a>
			</p>
			<?php
}
		?>
	</footer>
	<?php } ?>

</body>
</html>
