<?php
/**
 * @copyright 2019, Thomas Citharel <tcit@tcit.fr>
 *
 * @author Thomas Citharel <tcit@tcit.fr>
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

script('dav', [
	'settings-admin-carddav'
]);

/** @var \OCP\IL10N $l */
/** @var array $_ */
?>
<form id="CardDAV" class="section">
	<h2><?php p($l->t('Contacts server')); ?></h2>
	<p class="settings-hint">
		<?php print_unescaped(str_replace(
			[
				'{contactsappstoreopen}',
				'{contactsdocopen}',
				'{linkclose}',
			],
			[
				'<a target="_blank" href="../apps/office/contacts">',
				'<a target="_blank" href="' . link_to_docs('user-sync-contacts') . '" rel="noreferrer noopener">',
				'</a>',
			],
			$l->t('Also install the {contactsappstoreopen}Contacts app{linkclose}, or {contactsdocopen}connect your desktop & mobile for syncing ↗{linkclose}.')
		)); ?>
	</p>
	<p>
		<input type="checkbox" name="carddavSyncSystemAddressbook" id="carddavSyncSystemAddressbook" class="checkbox"
			<?php ($_['sync_system_addressbook'] === 'yes') ? print_unescaped('checked="checked"') : null ?>/>
		<label for="carddavSyncSystemAddressbook"><?php p($l->t('Sync the system addressbook')); ?></label>
		<br>
		<em><?php p($l->t('Syncs the instance users as contacts in a global system addressbook')); ?></em>
	</p>
</form>
