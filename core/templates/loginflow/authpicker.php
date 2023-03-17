<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

script('core', 'login/authpicker');
style('core', 'login/authpicker');

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
		<form id="login-form" action="<?php p($urlGenerator->linkToRoute('core.ClientFlowLogin.grantPage', ['stateToken' => $_['stateToken'], 'clientIdentifier' => $_['clientIdentifier'], 'oauthState' => $_['oauthState'], 'user' => $_['user'], 'direct' => $_['direct']])) ?>" method="get">
			<input type="submit" class="login primary icon-confirm-white" value="<?php p($l->t('Log in')) ?>" disabled>
		</form>
	</p>

	<form action="<?php p($urlGenerator->linkToRouteAbsolute('core.ClientFlowLogin.apptokenRedirect')); ?>" method="post" id="app-token-login-field" class="hidden">
		<p class="grouptop">
			<input type="text" name="user" id="user" placeholder="<?php p($l->t('Username')) ?>">
			<label for="user" class="infield"><?php p($l->t('Username')) ?></label>
		</p>
		<p class="groupbottom">
			<input type="password" name="password" id="password" placeholder="<?php p($l->t('App token')) ?>">
			<label for="password" class="infield"><?php p($l->t('Password')) ?></label>
		</p>
		<input type="hidden" name="stateToken" value="<?php p($_['stateToken']) ?>" />
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
		<?php if ($_['direct'] !== 0) { ?>
			<input type="hidden" name="direct" value="<?php p($_['direct']) ?>">
		<?php } ?>
		<input id="submit-app-token-login" type="submit" class="login primary icon-confirm-white" value="<?php p($l->t('Grant access')) ?>">
	</form>

	<?php if (empty($_['oauthState'])): ?>
		<a id="app-token-login" class="apptoken-link" href="#"><?php p($l->t('Alternative log in using app token')) ?></a>
	<?php endif; ?>
</div>
