<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

use OCP\IUser;

/**
 * @since 9.1
 */
interface IManager {
	/**
	 * @since 9.1.0
	 */
	public const KEY_ADMIN_SETTINGS = 'admin';

	/**
	 * @since 9.1.0
	 */
	public const KEY_ADMIN_SECTION = 'admin-section';

	/**
	 * @since 13.0.0
	 */
	public const KEY_PERSONAL_SETTINGS = 'personal';

	/**
	 * @since 13.0.0
	 */
	public const KEY_PERSONAL_SECTION = 'personal-section';

	/**
	 * @param string $type 'admin-section' or 'personal-section'
	 * @param string $section Class must implement OCP\Settings\ISection
	 * @since 14.0.0
	 */
	public function registerSection(string $type, string $section);

	/**
	 * @param string $type 'admin' or 'personal'
	 * @param string $setting Class must implement OCP\Settings\ISettings
	 * @since 14.0.0
	 */
	public function registerSetting(string $type, string $setting);

	/**
	 * returns a list of the admin sections
	 *
	 * @return array<int, array<int, IIconSection>> array from IConSection[] where key is the priority
	 * @since 9.1.0
	 */
	public function getAdminSections(): array;

	/**
	 * returns a list of the personal sections
	 *
	 * @return array array of ISection[] where key is the priority
	 * @since 13.0.0
	 */
	public function getPersonalSections(): array;

	/**
	 * returns a list of the admin settings
	 *
	 * @param string $section the section id for which to load the settings
	 * @param bool $subAdminOnly only return settings sub admins are supposed to see (since 17.0.0)
	 * @return array<int, array<int, ISettings>> array of ISettings[] where key is the priority
	 * @since 9.1.0
	 */
	public function getAdminSettings($section, bool $subAdminOnly = false): array;

	/**
	 * Returns a list of admin settings that the given user can use for the give section
	 *
	 * @return array<int, list<ISettings>> The array of admin settings there admin delegation is allowed.
	 * @since 23.0.0
	 */
	public function getAllowedAdminSettings(string $section, IUser $user): array;

	/**
	 * Returns a list of admin settings that the given user can use.
	 *
	 * @return array<int, list<ISettings>> The array of admin settings there admin delegation is allowed.
	 * @since 23.0.0
	 */
	public function getAllAllowedAdminSettings(IUser $user): array;

	/**
	 * returns a list of the personal  settings
	 *
	 * @param string $section the section id for which to load the settings
	 * @return array array of ISettings[] where key is the priority
	 * @since 13.0.0
	 */
	public function getPersonalSettings($section): array;

	/**
	 * Get a specific section by type and id
	 * @since 25.0.0
	 */
	public function getSection(string $type, string $sectionId): ?IIconSection;
}
