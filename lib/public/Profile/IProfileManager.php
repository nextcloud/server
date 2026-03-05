<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Profile;

use OC\Core\ResponseDefinitions;
use OCP\Accounts\IAccountManager;
use OCP\IUser;

/**
 * @psalm-import-type CoreProfileFields from ResponseDefinitions
 * @since 28.0.0
 */
interface IProfileManager {
	/**
	 * Visible to users, guests, and public access
	 *
	 * @since 28.0.0
	 */
	public const VISIBILITY_SHOW = 'show';

	/**
	 * Visible to users and guests
	 *
	 * @since 28.0.0
	 */
	public const VISIBILITY_SHOW_USERS_ONLY = 'show_users_only';

	/**
	 * Visible to nobody
	 *
	 * @since 28.0.0
	 */
	public const VISIBILITY_HIDE = 'hide';

	/**
	 * Default account property visibility
	 *
	 * @since 28.0.0
	 */
	public const DEFAULT_PROPERTY_VISIBILITY = [
		IAccountManager::PROPERTY_ADDRESS => self::VISIBILITY_SHOW_USERS_ONLY,
		IAccountManager::PROPERTY_AVATAR => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_BIOGRAPHY => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_DISPLAYNAME => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_HEADLINE => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_ORGANISATION => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_ROLE => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_EMAIL => self::VISIBILITY_SHOW_USERS_ONLY,
		IAccountManager::PROPERTY_PHONE => self::VISIBILITY_SHOW_USERS_ONLY,
		IAccountManager::PROPERTY_TWITTER => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_BLUESKY => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_WEBSITE => self::VISIBILITY_SHOW,
		IAccountManager::PROPERTY_PRONOUNS => self::VISIBILITY_SHOW,
	];

	/**
	 * Default visibility
	 *
	 * @since 28.0.0
	 */
	public const DEFAULT_VISIBILITY = self::VISIBILITY_SHOW_USERS_ONLY;

	/**
	 * If no user is passed as an argument return whether profile is enabled globally in `config.php`
	 *
	 * @since 28.0.0
	 */
	public function isProfileEnabled(?IUser $user = null): bool;

	/**
	 * Return whether the profile parameter of the target user
	 * is visible to the visiting user
	 *
	 * @since 28.0.0
	 */
	public function isProfileFieldVisible(string $profileField, IUser $targetUser, ?IUser $visitingUser): bool;

	/**
	 * Return the profile parameters of the target user that are visible to the visiting user
	 * in an associative array
	 *
	 * @psalm-return CoreProfileFields
	 * @since 28.0.0
	 */
	public function getProfileFields(IUser $targetUser, ?IUser $visitingUser): array;
}
