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
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0">
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
	<div id="notification-container">
		<div id="notification"></div>
	</div>
	<header role="banner"><div id="header">
			<div id="header-left">
				<a href="<?php print_unescaped(link_to('', 'index.php')); ?>"
					id="nextcloud" tabindex="1">
					<div class="logo logo-icon">
						<h1 class="hidden-visually">
							<?php p($theme->getName()); ?>
						</h1>
					</div>
				</a>

				<a href="#" class="header-appname-container menutoggle" tabindex="2">
					<h1 class="header-appname">
						<?php p(!empty($_['application'])?$_['application']: $l->t('Apps')); ?>
					</h1>
					<div class="icon-caret"></div>
				</a>

				<ul id="appmenu">
					<?php foreach ($_['navigation'] as $entry): ?>
						<li data-id="<?php p($entry['id']); ?>" class="hidden">
							<a href="<?php print_unescaped($entry['href']); ?>"
							   tabindex="3"
								<?php if ($entry['active']): ?> class="active"<?php endif; ?>>
								<img src="<?php print_unescaped($entry['icon'] . '?v=' . $_['versionHash']); ?>"
									 class="app-icon"/>
								<div class="icon-loading-small-dark"
									 style="display:none;"></div>
								<span>
								<?php p($entry['name']); ?>
							</span>
							</a>
						</li>
					<?php endforeach; ?>
					<li id="more-apps" class="menutoggle">
						<a href="#">
							<div class="icon-more-white"></div>
							<span><?php p($l->t('More apps')); ?></span>
						</a>
					</li>
				</ul>

				<nav role="navigation">
					<div id="navigation" style="display: none;">
						<div id="apps">
							<ul>
								<?php foreach($_['navigation'] as $entry): ?>
									<li data-id="<?php p($entry['id']); ?>">
									<a href="<?php print_unescaped($entry['href']); ?>" tabindex="3"
										<?php if( $entry['active'] ): ?> class="active"<?php endif; ?>>
										<svg width="16" height="16" viewBox="0 0 16 16">
											<defs><filter id="invert-<?php p($entry['id']); ?>"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"></feColorMatrix></filter></defs>
											<image x="0" y="0" width="16" height="16" preserveAspectRatio="xMinYMin meet" filter="url(#invert-<?php p($entry['id']); ?>)" xlink:href="<?php print_unescaped($entry['icon'] . '?v=' . $_['versionHash']); ?>"  class="app-icon"></image>
										</svg>
										<div class="icon-loading-small-dark" style="display:none;"></div>
										<span><?php p($entry['name']); ?></span>
									</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</nav>

			</div>

			<div id="header-right">
				<form class="searchbox" action="#" method="post" role="search" novalidate>
					<label for="searchbox" class="hidden-visually">
						<?php p($l->t('Search'));?>
					</label>
					<input id="searchbox" type="search" name="query"
						value="" required
						autocomplete="off" tabindex="5">
					<button class="icon-close-white" type="reset"></button>
				</form>
				<div id="contactsmenu">
					<div class="icon-contacts menutoggle"></div>
					<div class="menu"></div>
				</div>
				<div id="settings">
					<div id="expand" tabindex="6" role="link" class="menutoggle">
						<div class="avatardiv<?php if ($_['userAvatarSet']) { print_unescaped(' avatardiv-shown'); } else { print_unescaped('" style="display: none'); } ?>">
							<?php if ($_['userAvatarSet']): ?>
								<img alt="" width="32" height="32"
								src="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.avatar.getAvatar', ['userId' => $_['user_uid'], 'size' => 32, 'v' => $_['userAvatarVersion']]));?>"
								srcset="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.avatar.getAvatar', ['userId' => $_['user_uid'], 'size' => 64, 'v' => $_['userAvatarVersion']]));?> 2x, <?php p(\OC::$server->getURLGenerator()->linkToRoute('core.avatar.getAvatar', ['userId' => $_['user_uid'], 'size' => 128, 'v' => $_['userAvatarVersion']]));?> 4x"
								>
							<?php endif; ?>
						</div>
						<div id="expandDisplayName" class="icon-settings-white"></div>
					</div>
					<div id="expanddiv" style="display:none;">
					<ul>
					<?php foreach($_['settingsnavigation'] as $entry):?>
						<li>
							<a href="<?php print_unescaped($entry['href']); ?>"
								<?php if( $entry["active"] ): ?> class="active"<?php endif; ?>>
								<img alt="" src="<?php print_unescaped($entry['icon'] . '?v=' . $_['versionHash']); ?>">
								<?php p($entry['name']) ?>
							</a>
						</li>
					<?php endforeach; ?>
					</ul>

					</div>
				</div>
			</div>
		</div></header>

		<div id="sudo-login-background" class="hidden"></div>
		<form id="sudo-login-form" class="hidden">
			<?php p($l->t('This action requires you to confirm your password:')); ?><br>
			<input type="password" class="question" autocomplete="new-password" name="question" value=" <?php /* Hack against browsers ignoring autocomplete="off" */ ?>"
				placeholder="<?php p($l->t('Confirm your password')); ?>" />
			<input class="confirm icon-confirm" title="<?php p($l->t('Confirm')); ?>" value="" type="submit">
		</form>

		<div id="content-wrapper">
			<div id="content" class="app-<?php p($_['appid']) ?>" role="main">
				<?php print_unescaped($_['content']); ?>
			</div>
		</div>

	</body>
</html>
