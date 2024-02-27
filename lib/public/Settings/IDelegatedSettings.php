<?php
/**
 * @copyright Copyright (c) Nextcloud GmbH
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Settings;

/**
 * Special cases of settings that can be allowed to use by member of special
 * groups.
 * @since 23.0.0
 */
interface IDelegatedSettings extends ISettings {
	/**
	 * Get the name of the settings to differentiate settings inside a section or
	 * null if only the section name should be displayed.
	 * @since 23.0.0
	 */
	public function getName(): ?string;

	/**
	 * Get a list of authorized app config that this setting is allowed to modify.
	 * The format of the array is the following:
	 * ```php
	 * <?php
	 * [
	 * 		'app_name' => [
	 * 			'/simple_key/', # value
	 * 			'/s[a-z]*ldap/', # regex
	 * 		],
	 * 		'another_app_name => [ ... ],
	 * ]
	 * ```
	 * @since 23.0.0
	 */
	public function getAuthorizedAppConfig(): array;
}
