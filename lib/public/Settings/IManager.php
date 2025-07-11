<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Settings;

use OCP\IUser;

/**
 * @since 9.1
 */
interface IManager {
	/**
	 * @since 9.1.0
	 * @deprecated 29.0.0 Use {@see self::SETTINGS_ADMIN} instead
	 */
	public const KEY_ADMIN_SETTINGS = 'admin';

	/**
	 * @since 9.1.0
	 * @deprecated 29.0.0 Use {@see self::SETTINGS_ADMIN} instead
	 */
	public const KEY_ADMIN_SECTION = 'admin-section';

	/**
	 * @since 13.0.0
	 * @deprecated 29.0.0 Use {@see self::SETTINGS_PERSONAL} instead
	 */
	public const KEY_PERSONAL_SETTINGS = 'personal';

	/**
	 * @since 13.0.0
	 * @deprecated 29.0.0 Use {@see self::SETTINGS_PERSONAL} instead
	 */
	public const KEY_PERSONAL_SECTION = 'personal-section';

	/**
	 * @since 29.0.0
	 */
	public const SETTINGS_ADMIN = 'admin';

	/**
	 * @since 29.0.0
	 */
	public const SETTINGS_PERSONAL = 'personal';

	/**
	 * @psalm-param self::SETTINGS_* $type
	 * @param class-string<IIconSection> $section
	 * @since 14.0.0
	 */
	public function registerSection(string $type, string $section);

	/**
	 * @psalm-param self::SETTINGS_* $type
	 * @param class-string<ISettings> $setting
	 * @since 14.0.0
	 */
	public function registerSetting(string $type, string $setting);

	/**
	 * returns a list of the admin sections
	 *
	 * @return array<int, list<IIconSection>> list of sections with priority as key
	 * @since 9.1.0
	 */
	public function getAdminSections(): array;

	/**
	 * returns a list of the personal sections
	 *
	 * @return array<int, list<IIconSection>> list of sections with priority as key
	 * @since 13.0.0
	 */
	public function getPersonalSections(): array;

	/**
	 * returns a list of the admin settings
	 *
	 * @param string $section the section id for which to load the settings
	 * @param bool $subAdminOnly only return settings sub admins are supposed to see (since 17.0.0)
	 * @return array<int, list<ISettings>> list of settings with priority as key
	 * @since 9.1.0
	 */
	public function getAdminSettings(string $section, bool $subAdminOnly = false): array;

	/**
	 * Returns a list of admin settings that the given user can use for the give section
	 *
	 * @return array<int, list<ISettings>> List of admin-settings the user has access to, with priority as key.
	 * @since 23.0.0
	 */
	public function getAllowedAdminSettings(string $section, IUser $user): array;

	/**
	 * Returns a list of admin settings that the given user can use.
	 *
	 * @return list<ISettings> The array of admin settings there admin delegation is allowed.
	 * @since 23.0.0
	 */
	public function getAllAllowedAdminSettings(IUser $user): array;

	/**
	 * returns a list of the personal  settings
	 *
	 * @param string $section the section id for which to load the settings
	 * @return array<int, list<ISettings>> list of settings with priority as key
	 * @since 13.0.0
	 */
	public function getPersonalSettings(string $section): array;

	/**
	 * Get a specific section by type and id
	 * @psalm-param self::SETTINGS_* $type
	 * @since 25.0.0
	 */
	public function getSection(string $type, string $sectionId): ?IIconSection;
}
