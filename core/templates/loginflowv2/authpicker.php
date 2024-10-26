<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

style('core', 'login/authpicker');
script('core', 'login/authpicker');

/** @var array $_ */
/** @var \OCP\IURLGenerator $urlGenerator */
$urlGenerator = $_['urlGenerator'];
?>

<div class="picker-window">
	<h2><?php p($l->t('Connect to your account')) ?></h2>
	<p class="info">
		<?php print_unescaped($l->t('Please log in before granting %1$s access to your %2$s account.', [
			'<strong>' . \OCP\Util::sanitizeHTML($_['client']) . '</strong>',
			\OCP\Util::sanitizeHTML($_['instanceName'])
		])) ?>
	</p>

	<div class="notecard warning">
		<h3><?php p($l->t('Security warning')) ?></h3>
		<p>
			<?php p($l->t('If you are not trying to set up a new device or app, someone is trying to trick you into granting them access to your data. In this case do not proceed and instead contact your system administrator.')) ?>
		</p>
	</div>

	<br/>

	<p id="redirect-link">
		<form id="login-form" action="<?php p($urlGenerator->linkToRouteAbsolute('core.ClientFlowLoginV2.grantPage', ['stateToken' => $_['stateToken'], 'user' => $_['user'], 'direct' => $_['direct'] ?? 0])) ?>" method="get">
			<input type="submit" class="login primary icon-confirm-white" value="<?php p($l->t('Log in')) ?>" disabled>
		</form>
	</p>

	<form action="<?php p($urlGenerator->linkToRouteAbsolute('core.ClientFlowLoginV2.apptokenRedirect')); ?>" method="post" id="app-token-login-field" class="hidden">
		<p class="grouptop">
			<input type="text" name="user" id="user" placeholder="<?php p($l->t('Login')) ?>">
			<label for="user" class="infield"><?php p($l->t('Login')) ?></label>
		</p>
		<p class="groupbottom">
			<input type="password" name="password" id="password" placeholder="<?php p($l->t('App password')) ?>">
			<label for="password" class="infield"><?php p($l->t('Password')) ?></label>
		</p>
		<input type="hidden" name="stateToken" value="<?php p($_['stateToken']) ?>" />
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
		<input id="submit-app-token-login" type="submit" class="login primary icon-confirm-white" value="<?php p($l->t('Grant access')) ?>">
	</form>

	<?php if (empty($_['oauthState'])): ?>
		<a id="app-token-login" class="apptoken-link" href="#"><?php p($l->t('Alternative log in using app password')) ?></a>
	<?php endif; ?>
</div>
