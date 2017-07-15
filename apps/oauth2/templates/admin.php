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

$urlGenerator = \OC::$server->getURLGenerator();
$themingDefaults = \OC::$server->getThemingDefaults();

script('oauth2', 'setting-admin');
style('oauth2', 'setting-admin');

/** @var array $_ */
/** @var \OCA\OAuth2\Db\Client[] $clients */
$clients = $_['clients'];
?>

<div id="oauth2" class="section">
	<h2><?php p($l->t('OAuth 2.0 clients')); ?></h2>
	<p class="settings-hint"><?php p($l->t('OAuth 2.0 allows external services to request access to %s.', [$themingDefaults->getName()])); ?></p>

	<table class="grid">
		<thead>
		<tr>
			<th id="headerName" scope="col"><?php p($l->t('Name')); ?></th>
			<th id="headerRedirectUri" scope="col"><?php p($l->t('Redirection URI')); ?></th>
			<th id="headerClientIdentifier" scope="col"><?php p($l->t('Client Identifier')); ?></th>
			<th id="headerSecret" scope="col"><?php p($l->t('Secret')); ?></th>
			<th id="headerRemove">&nbsp;</th>
		</tr>
		</thead>
		<tbody>
		<?php
		$imageUrl = $urlGenerator->imagePath('core', 'actions/toggle.svg');
		foreach ($clients as $client) {
		?>
			<tr>
				<td><?php p($client->getName()); ?></td>
				<td><?php p($client->getRedirectUri()); ?></td>
				<td><code><?php p($client->getClientIdentifier()); ?></code></td>
				<td data-value="<?php p($client->getSecret()); ?>"><code>****</code><img class='show-oauth-credentials' src="<?php p($imageUrl); ?>"/></td>
				<td>
					<form id="form-inline" class="delete" action="<?php p($urlGenerator->linkToRoute('oauth2.Settings.deleteClient', ['id' => $client->getId()])); ?>" method="POST">
						<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
						<input type="submit" class="button icon-delete" value="">
					</form>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>

	<br/>
	<h3><?php p($l->t('Add client')); ?></h3>
	<form action="<?php p($urlGenerator->linkToRoute('oauth2.Settings.addClient')); ?>" method="POST">
		<input type="text" id="name" name="name" placeholder="<?php p($l->t('Name')); ?>">
		<input type="url" id="redirectUri" name="redirectUri" placeholder="<?php p($l->t('Redirection URI')); ?>">
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
		<input type="submit" class="button" value="<?php p($l->t('Add')); ?>">
	</form>
</div>
