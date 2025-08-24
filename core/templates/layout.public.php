<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
?>
<!DOCTYPE html>
<html class="ng-csp" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" data-locale="<?php p($_['locale']); ?>" translate="no" >
<head data-requesttoken="<?php p($_['requesttoken']); ?>">
	<meta charset="utf-8">
	<title>
	<?php
				p(!empty($_['pageTitle']) && (empty($_['application']) || $_['pageTitle'] !== $_['application']) ? $_['pageTitle'] . ' - ' : '');
p(!empty($_['application']) ? $_['application'] . ' - ' : '');
p($theme->getTitle());
?>
	</title>
	<meta name="csp-nonce" nonce="<?php p($_['cspNonce']); /* Do not pass into "content" to prevent exfiltration */ ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0<?php if (isset($_['viewport_maximum_scale'])) {
		p(', maximum-scale=' . $_['viewport_maximum_scale']);
	} ?>">
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
	<link rel="manifest" href="<?php print_unescaped(image_path($_['appid'], 'manifest.json')); ?>" crossorigin="use-credentials">
	<?php emit_css_loading_tags($_); ?>
	<?php emit_script_loading_tags($_); ?>
	<?php print_unescaped($_['headers']); ?>
</head>
<body id="<?php p($_['bodyid']);?>" <?php foreach ($_['enabledThemes'] as $themeId) {
	p("data-theme-$themeId ");
}?> data-themes="<?php p(join(',', $_['enabledThemes'])) ?>">
	<?php include('layout.noscript.warning.php'); ?>
	<?php include('layout.initial-state.php'); ?>
	<div id="skip-actions">
		<?php if ($_['id-app-content'] !== null) { ?><a href="<?php p($_['id-app-content']); ?>" class="button primary skip-navigation skip-content"><?php p($l->t('Skip to main content')); ?></a><?php } ?>
		<?php if ($_['id-app-navigation'] !== null) { ?><a href="<?php p($_['id-app-navigation']); ?>" class="button primary skip-navigation"><?php p($l->t('Skip to navigation of app')); ?></a><?php } ?>
	</div>

	<header id="header">
		<div class="header-start">
			<div id="nextcloud" class="header-appname">
				<?php if ($_['logoUrl']): ?>
					<a href="<?php print_unescaped($_['logoUrl']); ?>"
					   aria-label="<?php p($l->t('Go to %s', [$_['logoUrl']])); ?>">
						<div class="logo logo-icon"></div>
					</a>
				<?php else: ?>
					<div class="logo logo-icon"></div>
				<?php endif; ?>

				<div class="header-info">
					<span class="header-title">
						<?php if (isset($template) && $template->getHeaderTitle() !== '') { ?>
							<?php p($template->getHeaderTitle()); ?>
						<?php } else { ?>
							<?php	p($theme->getName()); ?>
						<?php } ?>
					</span>
					<?php if (isset($template) && $template->getHeaderDetails() !== '') { ?>
						<span class="header-shared-by">
							<?php p($template->getHeaderDetails()); ?>
						</span>
					<?php } ?>
				</div>
			</div>
		</div>

		<div class="header-end">
			<div id="public-page-menu"></div>
			<div id="public-page-user-menu"></div>
		</div>
	</header>

	<div id="content" class="app-<?php p($_['appid']) ?>">
		<h1 class="hidden-visually">
			<?php
		if (isset($template) && $template->getHeaderTitle() !== '') {
			p($template->getHeaderTitle());
		} else {
			p($theme->getName());
		} ?>
		</h1>
		<?php print_unescaped($_['content']); ?>
	</div>

	<?php if (isset($template) && $template->getFooterVisible() && ($theme->getLongFooter() !== '' || $_['showSimpleSignUpLink'])) { ?>
	<footer>
		<p><?php print_unescaped($theme->getLongFooter()); ?></p>
		<?php
if ($_['showSimpleSignUpLink']) {
	?>
			<p class="footer__simple-sign-up">
				<a href="<?php p($_['signUpLink']); ?>" target="_blank" rel="noreferrer noopener">
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
