<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

script('core', 'login/grant');
style('core', 'login/authpicker');

/** @var array $_ */
/** @var \OCP\IURLGenerator $urlGenerator */
$urlGenerator = $_['urlGenerator'];
?>

<div class="picker-window small">
	<h2><?php p($l->t('Account access')) ?></h2>
	<p class="info">
		<?php p($l->t('Currently logged in as %1$s (%2$s).', [
			$_['userDisplayName'],
			$_['userId'],
		])) ?>
	</p>
	<p class="info">
		<?php print_unescaped($l->t('You are about to grant %1$s access to your %2$s account.', [
			'<strong>' . \OCP\Util::sanitizeHTML($_['client']) . '</strong>',
			\OCP\Util::sanitizeHTML($_['instanceName'])
		])) ?>
	</p>

	<br/>

	<p id="redirect-link">
		<form method="POST" action="<?php p($urlGenerator->linkToRouteAbsolute('core.ClientFlowLogin.generateAppPassword')) ?>">
			<input type="hidden" name="clientIdentifier" value="<?php p($_['clientIdentifier']) ?>" />
			<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
			<input type="hidden" name="stateToken" value="<?php p($_['stateToken']) ?>" />
			<input type="hidden" name="oauthState" value="<?php p($_['oauthState']) ?>" />
			<?php if ($_['direct']) { ?>
			<input type="hidden" name="direct" value="1" />
			<?php } ?>
			<div id="submit-wrapper">
				<input type="submit" class="login primary icon-confirm-white" title="" value="<?php p($l->t('Grant access')); ?>" />
			</div>
		</form>
	</p>
</div>
