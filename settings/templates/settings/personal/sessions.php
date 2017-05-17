<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

script('settings', [
	'authtoken',
	'authtoken_collection',
	'authtoken_view',
	'settings/sessions'
]);

?>


<div id="sessions" class="section">
	<h2><?php p($l->t('Sessions'));?></h2>
<p class="settings-hint hidden-when-empty"><?php p($l->t('Web, desktop and mobile clients currently logged in to your account.'));?></p>
<table class="icon-loading">
	<thead class="token-list-header">
	<tr>
		<th><?php p($l->t('Device'));?></th>
		<th><?php p($l->t('Last activity'));?></th>
		<th></th>
	</tr>
	</thead>
	<tbody class="token-list">
	</tbody>
</table>
</div>
