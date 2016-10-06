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
		<meta name="theme-color" content="<?php p($theme->getMailHeaderColor()); ?>">
		<link rel="icon" href="<?php print_unescaped(image_path($_['appid'], 'favicon.ico')); /* IE11+ supports png */ ?>">
		<link rel="apple-touch-icon-precomposed" href="<?php print_unescaped(image_path($_['appid'], 'favicon-touch.png')); ?>">
		<link rel="mask-icon" sizes="any" href="<?php print_unescaped(image_path($_['appid'], 'favicon-mask.svg')); ?>" color="#0082c9">
		<?php foreach($_['cssfiles'] as $cssfile): ?>
			<link rel="stylesheet" href="<?php print_unescaped($cssfile); ?>">
		<?php endforeach; ?>
		<?php foreach($_['printcssfiles'] as $cssfile): ?>
			<link rel="stylesheet" href="<?php print_unescaped($cssfile); ?>" media="print">
		<?php endforeach; ?>
		<?php foreach($_['jsfiles'] as $jsfile): ?>
			<script src="<?php print_unescaped($jsfile); ?>"></script>
		<?php endforeach; ?>
		<?php print_unescaped($_['headers']); ?>
	</head>
	<body id="<?php p($_['bodyid']);?>">
	<?php include('layout.noscript.warning.php'); ?>
	<div id="notification-container">
		<div id="notification"></div>
	</div>
	<header role="banner"><div id="header">
			<a href="<?php print_unescaped(link_to('', 'index.php')); ?>"
				id="owncloud" tabindex="1">
				<div class="logo-icon">
					<h1 class="hidden-visually">
						<?php p($theme->getName()); ?>
					</h1>
				</div>
			</a>

			<a href="#" class="header-appname-container menutoggle" tabindex="2">
				<h1 class="header-appname">
					<?php
						if(OC_Util::getEditionString() === '') {
							p(!empty($_['application'])?$_['application']: $l->t('Apps'));
						} else {
							print_unescaped($theme->getHTMLName());
						}
					?>
				</h1>
				<div class="icon-caret"></div>
			</a>

			<div id="logo-claim" style="display:none;"><?php p($theme->getLogoClaim()); ?></div>
			<div id="settings">
				<div id="expand" tabindex="6" role="link" class="menutoggle">
					<?php if ($_['enableAvatars']): ?>
					<div class="avatardiv<?php if ($_['userAvatarSet']) { print_unescaped(' avatardiv-shown'); } else { print_unescaped('" style="display: none'); } ?>">
						<?php if ($_['userAvatarSet']): ?>
							<img alt="" width="32" height="32"
							src="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.avatar.getAvatar', ['userId' => $_['user_uid'], 'size' => 32]));?>"
							srcset="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.avatar.getAvatar', ['userId' => $_['user_uid'], 'size' => 64]));?> 2x, <?php p(\OC::$server->getURLGenerator()->linkToRoute('core.avatar.getAvatar', ['userId' => $_['user_uid'], 'size' => 128]));?> 4x"
							>
						<?php endif; ?>
					</div>
					<?php endif; ?>
					<span id="expandDisplayName"><?php  p(trim($_['user_displayname']) != '' ? $_['user_displayname'] : $_['user_uid']) ?></span>
					<div class="icon-caret"></div>
				</div>
				<div id="expanddiv">
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
					<li>
						<a id="logout" <?php print_unescaped(OC_User::getLogoutAttribute()); ?>>
							<img alt="" src="<?php print_unescaped(image_path('', 'actions/logout.svg') . '?v=' . $_['versionHash']); ?>">
							<?php p($l->t('Log out'));?>
						</a>
					</li>
				</ul>
				</div>
			</div>

			<form class="searchbox" action="#" method="post" role="search" novalidate>
				<label for="searchbox" class="hidden-visually">
					<?php p($l->t('Search'));?>
				</label>
				<input id="searchbox" type="search" name="query"
					value="" required
					autocomplete="off" tabindex="5">
			</form>
		</div></header>

		<nav role="navigation"><div id="navigation">
			<div id="apps">
				<ul>
				<?php foreach($_['navigation'] as $entry): ?>
					<li data-id="<?php p($entry['id']); ?>">
						<a href="<?php print_unescaped($entry['href']); ?>" tabindex="3"
							<?php if( $entry['active'] ): ?> class="active"<?php endif; ?>>
							<svg width="32" height="32" viewBox="0 0 32 32">
								<defs><filter id="invert"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"></feColorMatrix></filter></defs>
								<image x="0" y="0" width="32" height="32" preserveAspectRatio="xMinYMin meet" filter="url(#invert)" xlink:href="<?php print_unescaped($entry['icon'] . '?v=' . $_['versionHash']); ?>"  class="app-icon"></image>
							</svg>
							<div class="icon-loading-dark" style="display:none;"></div>
							<span>
								<?php p($entry['name']); ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
				<?php
					/* show "More apps" link to app administration directly in app navigation, as last entry */
					if(OC_User::isAdminUser(OC_User::getUser())):
				?>
					<li id="apps-management">
						<a href="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('settings.AppSettings.viewApps')); ?>" tabindex="4"
							<?php if( $_['appsmanagement_active'] ): ?> class="active"<?php endif; ?>>
							<svg width="32" height="32" viewBox="0 0 32 32" class="app-icon">
								<defs><filter id="invert"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"></feColorMatrix></filter></defs>
								<image x="0" y="0" width="32" height="32" preserveAspectRatio="xMinYMin meet" filter="url(#invert)" xlink:href="<?php print_unescaped(image_path('settings', 'apps.svg') . '?v=' . $_['versionHash']); ?>"></image>
							</svg>
							<div class="icon-loading-dark" style="display:none;"></div>
							<span>
								<?php p($l->t('Apps')); ?>
							</span>
						</a>
					</li>
				<?php endif; ?>

				</ul>
			</div>
		</div></nav>

		<div id="content-wrapper">
			<div id="content" class="app-<?php p($_['appid']) ?>" role="main">
				<?php print_unescaped($_['content']); ?>
			</div>
		</div>
	</body>
</html>
