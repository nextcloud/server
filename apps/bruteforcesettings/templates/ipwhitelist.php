<?php
/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

script('core', [
	'oc-backbone-webdav',
]);
script('bruteforcesettings', [
	'IPWhitelist',
	'IPWhitelistModel',
	'IPWhitelistCollection',
	'IPWhitelistView',
]);
style('bruteforcesettings', [
	'settings'
])

/** @var \OCP\IL10N $l */
?>
<form id="IPWhiteList" class="section">
	<h2><?php p($l->t('Brute force ip whitelist')); ?></h2>

	<table id="whitelist-list">

	</table>

	<input type="text" name="whitelist_ip" id="whitelist_ip" placeholder="1.2.3.4" style="width: 200px;" />/
	<input type="number" id="whitelist_mask" name="whitelist_mask" placeholder="24" style="width: 50px;">
	<input type="button" id="whitelist_submit" value="<?php p($l->t('Add')); ?>">
</form>
