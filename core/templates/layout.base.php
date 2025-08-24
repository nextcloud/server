<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
?>
<!DOCTYPE html>
<html class="ng-csp" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" data-locale="<?php p($_['locale']); ?>" translate="no" >
	<head data-requesttoken="<?php p($_['requesttoken']); ?>">
		<meta charset="utf-8">
		<title>
		<?php p($theme->getTitle()); ?>
		</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0<?php if (isset($_['viewport_maximum_scale'])) {
			p(', maximum-scale=' . $_['viewport_maximum_scale']);
		} ?>">
		<meta name="theme-color" content="<?php p($theme->getColorPrimary()); ?>">
		<meta name="csp-nonce" nonce="<?php p($_['cspNonce']); /* Do not pass into "content" to prevent exfiltration */ ?>">
		<link rel="icon" href="<?php print_unescaped(image_path('core', 'favicon.ico')); /* IE11+ supports png */ ?>">
		<link rel="apple-touch-icon" href="<?php print_unescaped(image_path('core', 'favicon-touch.png')); ?>">
		<link rel="mask-icon" sizes="any" href="<?php print_unescaped(image_path('core', 'favicon-mask.svg')); ?>" color="<?php p($theme->getColorPrimary()); ?>">
		<?php emit_css_loading_tags($_); ?>
		<?php emit_script_loading_tags($_); ?>
		<?php print_unescaped($_['headers']); ?>
	</head>
	<body id="body-public" class="layout-base">
		<?php include 'layout.noscript.warning.php'; ?>
		<?php include 'layout.initial-state.php'; ?>
		<div id="content" class="app-public" role="main">
			<?php print_unescaped($_['content']); ?>
		</div>
	</body>
</html>
