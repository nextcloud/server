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
script('core', 'login/redirect');
style('core', 'login/authpicker');

/** @var array $_ */
/** @var \OCP\IURLGenerator $urlGenerator */
$urlGenerator = $_['urlGenerator'];
?>

<div class="picker-window">
	<p><?php p($l->t('Redirecting …')) ?></p>
</div>

<form method="POST" action="<?php p($urlGenerator->linkToRouteAbsolute('core.ClientFlowLogin.generateAppPassword')) ?>">
	<input type="hidden" name="clientIdentifier" value="<?php p($_['clientIdentifier']) ?>" />
	<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
	<input type="hidden" name="stateToken" value="<?php p($_['stateToken']) ?>" />
	<input type="hidden" name="oauthState" value="<?php p($_['oauthState']) ?>" />
	<input id="submit-redirect-form" type="submit" class="hidden "/>
</form>
